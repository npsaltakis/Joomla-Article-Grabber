<?php
/**
 * @package     com_content_api_grabber
 * @subpackage  Helper
 * @copyright   (C) 2026 Nick Psaltakis
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\ContentApiGrabber\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Http\HttpFactory;

/**
 * Minimal client for the Joomla Web Services (REST) API of a remote site.
 *
 * Talks to {base}/api/index.php/v1/content/articles using a Joomla API token
 * (the "API Authentication - Joomla Token" plugin must be enabled on the
 * remote site, and the token user must have the relevant permissions).
 */
class RestHelper
{
    /**
     * Normalize a Joomla API token to the base64 form expected by the API auth plugin.
     *
     * @param   string  $token  Saved token.
     *
     * @return  string
     */
    private static function normalizeToken(string $token): string
    {
        $token = preg_replace('/\s+/', '', trim($token)) ?? '';

        if (str_starts_with($token, 'sha256:') || str_starts_with($token, 'sha512:')) {
            return base64_encode($token);
        }

        return $token;
    }

    /**
     * Build the API endpoint for the content articles resource.
     *
     * @param   string  $baseUrl  The remote site root, e.g. https://source.gr
     * @param   string  $path     Optional sub-path appended to /content/articles
     *
     * @return  string
     */
    private static function endpoint(string $baseUrl, string $path = ''): string
    {
        return rtrim($baseUrl, '/') . '/api/index.php/v1/content/articles' . $path;
    }

    /**
     * Build the API endpoint for content categories.
     *
     * @param   string  $baseUrl  The remote site root.
     *
     * @return  string
     */
    private static function categoriesEndpoint(string $baseUrl): string
    {
        return rtrim($baseUrl, '/') . '/api/index.php/v1/content/categories';
    }

    /**
     * Perform an authenticated GET against the remote API and return decoded JSON.
     *
     * @param   string  $url      Full URL.
     * @param   string  $token    Joomla API token (plaintext).
     * @param   int     $timeout  Timeout in seconds.
     *
     * @return  object  Decoded JSON:API response.
     *
     * @throws  \RuntimeException
     */
    private static function get(string $url, string $token, int $timeout = 20): object
    {
        $token = self::normalizeToken($token);

        if ($token === '') {
            throw new \RuntimeException('Authentication failed: the saved API token is empty.');
        }

        $headers = [
            'Authorization'  => 'Bearer ' . $token,
            'X-Joomla-Token' => $token,
            'Accept'         => 'application/vnd.api+json',
        ];

        try {
            $response = HttpFactory::getHttp()->get($url, $headers, $timeout);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Connection failed: ' . $e->getMessage());
        }

        $code = (int) $response->code;

        if ($code === 401 || $code === 403) {
            throw new \RuntimeException('Authentication failed (HTTP ' . $code . ') — check the token and that the Web Services API is enabled.');
        }

        if ($code !== 200) {
            throw new \RuntimeException('Remote API returned HTTP ' . $code . '.');
        }

        $json = json_decode((string) $response->body);

        if (!\is_object($json)) {
            throw new \RuntimeException('Remote API returned an invalid response.');
        }

        return $json;
    }

    /**
     * Fetch a page of articles from the remote site.
     *
     * @param   string  $baseUrl  Remote site root.
     * @param   string  $token    Plaintext Joomla API token.
     * @param   int     $limit    Page size.
     * @param   int     $offset   Page offset.
     * @param   string  $search   Optional title/alias search string.
     * @param   int     $catId    Optional remote category id filter (0 = all).
     *
     * @return  array  ['items' => object[], 'total' => int|null, 'hasNext' => bool]
     *
     * @throws  \RuntimeException
     */
    public static function listArticles(string $baseUrl, string $token, int $limit = 50, int $offset = 0, string $search = '', int $catId = 0): array
    {
        $params = ['page' => ['limit' => $limit, 'offset' => $offset]];

        if ($search !== '') {
            $params['filter']['search'] = $search;
        }

        if ($catId > 0) {
            $params['filter']['catid'] = $catId;
        }

        $query = http_build_query($params);

        $json  = self::get(self::endpoint($baseUrl, '?' . $query), $token);
        $items = \is_array($json->data ?? null) ? $json->data : [];

        // A "next" link means there are more pages after this one.
        $hasNext = isset($json->links->next);

        // Best-effort total from the "last" page link offset (JSON:API style).
        $total = null;

        if (isset($json->links->last) && preg_match('/offset(?:%5D|\])=(\d+)/', (string) $json->links->last, $m)) {
            $total = (int) $m[1] + $limit;
        } elseif (isset($json->meta->{'total-pages'})) {
            $total = (int) $json->meta->{'total-pages'} * $limit;
        }

        return ['items' => $items, 'total' => $total, 'hasNext' => $hasNext];
    }

    /**
     * Fetch a single article (full attributes) from the remote site.
     *
     * The returned object is the JSON:API resource (->id, ->attributes).
     * An extra ->_included property is attached with the response's "included"
     * array so that callers can resolve tag titles from the related data.
     *
     * @param   string  $baseUrl  Remote site root.
     * @param   string  $token    Plaintext Joomla API token.
     * @param   int     $id       Remote article id.
     *
     * @return  object  The JSON:API resource object.
     *
     * @throws  \RuntimeException
     */
    public static function getArticle(string $baseUrl, string $token, int $id): object
    {
        $json = self::get(self::endpoint($baseUrl, '/' . $id), $token);

        if (!isset($json->data) || !\is_object($json->data)) {
            throw new \RuntimeException('Remote article ' . $id . ' not found.');
        }

        $resource            = $json->data;
        $resource->_included = \is_array($json->included ?? null) ? $json->included : [];

        return $resource;
    }

    /**
     * Fetch the list of published content categories from the remote site.
     *
     * @param   string  $baseUrl  Remote site root.
     * @param   string  $token    Plaintext Joomla API token.
     * @param   int     $timeout  Timeout in seconds.
     *
     * @return  array  Array of objects with ->id and ->title.
     *
     * @throws  \RuntimeException
     */
    public static function listCategories(string $baseUrl, string $token, int $timeout = 20): array
    {
        $query = http_build_query([
            'page'   => ['limit' => 200],
            'filter' => ['published' => 1],
        ]);

        $json  = self::get(self::categoriesEndpoint($baseUrl) . '?' . $query, $token, $timeout);
        $items = \is_array($json->data ?? null) ? $json->data : [];
        $cats  = [];

        foreach ($items as $item) {
            $a = $item->attributes ?? null;

            if (!$a) {
                continue;
            }

            $cats[] = (object) [
                'id'    => (int) ($item->id ?? 0),
                'title' => (string) ($a->title ?? ''),
            ];
        }

        return $cats;
    }

    /**
     * Quick connectivity/credentials check. Returns null on success or an error message.
     *
     * @param   string  $baseUrl  Remote site root.
     * @param   string  $token    Plaintext Joomla API token.
     *
     * @return  string|null
     */
    public static function test(string $baseUrl, string $token): ?string
    {
        try {
            self::listArticles($baseUrl, $token, 1, 0);

            return null;
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }
}
