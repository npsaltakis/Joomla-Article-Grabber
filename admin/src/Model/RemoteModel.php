<?php
/**
 * @package     com_content_api_grabber
 * @subpackage  Model
 * @copyright   (C) 2026 Nick Psaltakis
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\ContentApiGrabber\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\Database\ParameterType;
use Nickpsal\Component\ContentApiGrabber\Administrator\Helper\ArticleImporter;
use Nickpsal\Component\ContentApiGrabber\Administrator\Helper\CryptHelper;
use Nickpsal\Component\ContentApiGrabber\Administrator\Helper\RestHelper;

/**
 * Model for browsing and pulling articles from configured remote sources.
 */
class RemoteModel extends FormModel
{
    /**
     * Load the pull form (target category + author + state).
     *
     * @param   array    $data      Data.
     * @param   boolean  $loadData  Load data flag.
     *
     * @return  \Joomla\CMS\Form\Form|false
     */
    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm(
            'com_content_api_grabber.pull',
            'pull',
            ['control' => 'jform', 'load_data' => $loadData]
        );

        return $form ?: false;
    }

    /**
     * Default author = current user.
     *
     * @return  array
     */
    protected function loadFormData()
    {
        return ['created_by' => (int) Factory::getApplication()->getIdentity()->id];
    }

    /**
     * Check whether a remote article has already been imported from the given source URL.
     *
     * @param   string  $sourceUrl  The remote site root URL stored in the log.
     * @param   int     $remoteId   The remote article id.
     *
     * @return  bool
     */
    public function isAlreadyImported(string $sourceUrl, int $remoteId): bool
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__cgrabber_log'))
            ->where($db->quoteName('source_url') . ' = :url')
            ->where($db->quoteName('source_id') . ' = :sid')
            ->bind(':url', $sourceUrl, ParameterType::STRING)
            ->bind(':sid', $remoteId, ParameterType::INTEGER);

        return (int) $db->setQuery($query)->loadResult() > 0;
    }

    /**
     * Get the published sources for the picker.
     *
     * @return  array
     */
    public function getSources(): array
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'name', 'url', 'default_catid']))
            ->from($db->quoteName('#__cgrabber_sources'))
            ->where($db->quoteName('published') . ' = 1')
            ->order($db->quoteName('ordering') . ' ASC');

        return (array) $db->setQuery($query)->loadObjectList();
    }

    /**
     * Load a single source row with the decrypted token.
     *
     * @param   int  $id  Source id.
     *
     * @return  object|null  Object with ->url and ->token (plaintext), or null.
     */
    public function getSource(int $id): ?object
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__cgrabber_sources'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);

        $row = $db->setQuery($query)->loadObject();

        if (!$row) {
            return null;
        }

        $row->token = CryptHelper::decrypt((string) $row->token_enc);
        unset($row->token_enc);

        return $row;
    }

    /**
     * Fetch the list of content categories from a remote source.
     * Returns an empty array on any error (category filter is optional).
     *
     * @param   int  $sourceId  Source id.
     *
     * @return  array  Objects with ->id and ->title.
     */
    public function getRemoteCategories(int $sourceId): array
    {
        $source = $this->getSource($sourceId);

        if (!$source) {
            return [];
        }

        try {
            return RestHelper::listCategories($source->url, $source->token);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Fetch a page of articles from a remote source.
     *
     * @param   int     $sourceId  Source id.
     * @param   int     $limit     Page size.
     * @param   int     $offset    Offset.
     * @param   string  $search    Optional title/alias search string.
     * @param   int     $catId     Optional remote category id filter (0 = all).
     *
     * @return  array  ['items' => object[], 'hasNext' => bool, 'total' => int|null,
     *                  'offset' => int, 'limit' => int]
     *
     * @throws  \RuntimeException
     */
    public function getRemoteArticles(int $sourceId, int $limit = 50, int $offset = 0, string $search = '', int $catId = 0): array
    {
        $source = $this->getSource($sourceId);

        if (!$source) {
            throw new \RuntimeException(\Joomla\CMS\Language\Text::_('COM_CONTENT_API_GRABBER_ERROR_SOURCE_NOT_FOUND'));
        }

        $result = RestHelper::listArticles($source->url, $source->token, $limit, $offset, $search, $catId);

        $items = [];

        foreach ($result['items'] as $res) {
            $a       = $res->attributes ?? null;
            $items[] = (object) [
                'id'      => (int) ($res->id ?? ($a->id ?? 0)),
                'title'   => (string) ($a->title ?? ''),
                'state'   => (int) ($a->state ?? 0),
                'created' => (string) ($a->created ?? ''),
            ];
        }

        return [
            'items'   => $items,
            'hasNext' => (bool) ($result['hasNext'] ?? (\count($items) >= $limit)),
            'total'   => $result['total'] ?? null,
            'offset'  => $offset,
            'limit'   => $limit,
        ];
    }

    /**
     * Test connectivity/credentials for given url + token (used by the Source form).
     * Returns ['ok' => bool, 'message' => string].
     *
     * @param   string  $url    Remote site root.
     * @param   string  $token  Plaintext token.
     *
     * @return  array
     */
    public function testConnection(string $url, string $token): array
    {
        try {
            $result = RestHelper::listArticles($url, $token, 1, 0);
            $total  = $result['total'];

            return [
                'ok'      => true,
                'message' => $total !== null
                    ? \Joomla\CMS\Language\Text::sprintf('COM_CONTENT_API_GRABBER_TEST_OK_COUNT', $total)
                    : \Joomla\CMS\Language\Text::_('COM_CONTENT_API_GRABBER_TEST_OK'),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Resolve the plaintext token for a stored source (used when the form token is blank).
     *
     * @param   int  $id  Source id.
     *
     * @return  string
     */
    public function getStoredToken(int $id): string
    {
        $source = $this->getSource($id);

        return $source ? (string) $source->token : '';
    }

    /**
     * Pull selected remote articles into the local site.
     *
     * @param   int    $sourceId        Source id.
     * @param   int[]  $articleIds      Remote article ids to pull.
     * @param   int    $catid           Target category.
     * @param   int    $userId          Author.
     * @param   int    $state           Published state.
     * @param   bool   $skipDuplicates  Skip articles already present in the import log.
     * @param   bool   $featured        Mark imported articles as front-page featured.
     *
     * @return  array  ['ok' => int, 'skipped' => int, 'failed' => int, 'messages' => string[]]
     *
     * @throws  \RuntimeException
     */
    public function pull(int $sourceId, array $articleIds, int $catid, int $userId, int $state, bool $skipDuplicates = false, bool $featured = false): array
    {
        $source = $this->getSource($sourceId);

        if (!$source) {
            throw new \RuntimeException(\Joomla\CMS\Language\Text::_('COM_CONTENT_API_GRABBER_ERROR_SOURCE_NOT_FOUND'));
        }

        $ok       = 0;
        $skipped  = 0;
        $failed   = 0;
        $messages = [];

        foreach ($articleIds as $remoteId) {
            $remoteId = (int) $remoteId;

            if ($skipDuplicates && $this->isAlreadyImported($source->url, $remoteId)) {
                $skipped++;
                $messages[] = 'ID ' . $remoteId . ': ' . \Joomla\CMS\Language\Text::_('COM_CONTENT_API_GRABBER_SKIP_DUPLICATE');
                continue;
            }

            try {
                $resource = RestHelper::getArticle($source->url, $source->token, $remoteId);
                $article  = $this->normalize($resource);

                // Images are absolutized against the remote site root.
                $result = ArticleImporter::import($article, $catid, $userId, $state, $source->url, $featured);

                $this->logPull($source, $remoteId, $result, $userId);

                $ok++;
                $messages[] = $article['title'] . ' → #' . $result['article_id']
                    . ' (' . $result['images_ok'] . ' img)';
            } catch (\Throwable $e) {
                $failed++;
                $messages[] = 'ID ' . $remoteId . ': ' . $e->getMessage();
                Log::add('Joomla Article Grabber pull failed (id ' . $remoteId . '): ' . $e->getMessage(), Log::WARNING, 'com_content_api_grabber');
            }
        }

        return ['ok' => $ok, 'skipped' => $skipped, 'failed' => $failed, 'messages' => $messages];
    }

    /**
     * Normalize a JSON:API article resource into the ArticleImporter payload.
     *
     * @param   object  $resource  The remote resource (may have ->_included attached by RestHelper).
     *
     * @return  array
     */
    private function normalize(object $resource): array
    {
        $a        = $resource->attributes ?? new \stdClass();
        $img      = (array) ($a->images ?? []);
        $included = \is_array($resource->_included ?? null) ? $resource->_included : [];

        $intro = (string) ($a->introtext ?? '');
        $full  = (string) ($a->fulltext ?? '');

        // Stock Joomla's content API never exposes introtext/fulltext separately:
        // it returns a single combined "text" (introtext . ' ' . fulltext), so the
        // separate fields usually arrive empty. Fall back to the combined body.
        if ($intro === '' && $full === '' && !empty($a->text)) {
            $intro = (string) $a->text;
        }

        // Recover the "Read more" split. Core drops the <hr id="system-readmore" />
        // separator when it builds "text", but the companion plugin on the source
        // (plg_content_apigrabber) re-inserts it. When the body still carries the
        // marker, split it back into intro/full so the imported article keeps its
        // read-more break instead of dumping everything into the intro text.
        $marker = '#<hr\b[^>]*\bid\s*=\s*(["\'])system-readmore\1[^>]*>#i';

        if ($full === '' && preg_match($marker, $intro)) {
            $parts = preg_split($marker, $intro, 2);
            $intro = (string) ($parts[0] ?? '');
            $full  = (string) ($parts[1] ?? '');
        }

        // --- Tags ---
        // The Joomla REST API can return tags in different shapes depending on version/config:
        //   (a) attributes.tags = [{id, title}, ...]          — direct array with titles
        //   (b) attributes.tags = {data: [{type:"tags",id:"X"}, ...]} — JSON:API relationship;
        //       titles come from the response's "included" section (attached as _included)
        $tags    = [];
        $rawTags = $a->tags ?? null;

        if (\is_array($rawTags)) {
            foreach ($rawTags as $tag) {
                $title = \is_object($tag) ? (string) ($tag->title ?? '') : (string) $tag;

                if ($title !== '') {
                    $tags[] = $title;
                }
            }
        } elseif (\is_object($rawTags) && \is_array($rawTags->data ?? null)) {
            // Collect the referenced tag IDs then resolve titles from included.
            $tagIds = array_map(static fn($t) => (string) ($t->id ?? ''), $rawTags->data);

            foreach ($included as $inc) {
                if (($inc->type ?? '') === 'tags' && \in_array((string) ($inc->id ?? ''), $tagIds, true)) {
                    $title = (string) ($inc->attributes->title ?? '');

                    if ($title !== '') {
                        $tags[] = $title;
                    }
                }
            }
        }

        return [
            'title'        => (string) ($a->title ?? ''),
            'alias'        => (string) ($a->alias ?? ''),
            'language'     => (string) ($a->language ?? '*') ?: '*',
            'introtext'    => $intro,
            'fulltext'     => $full,
            'metakey'      => (string) ($a->metakey ?? ''),
            'metadesc'     => (string) ($a->metadesc ?? ''),
            'tags'         => $tags,
            'created'      => (string) ($a->created ?? ''),
            'publish_up'   => (string) ($a->publish_up ?? ''),
            'publish_down' => (string) ($a->publish_down ?? ''),
            'images'       => [
                'image_intro'            => (string) ($img['image_intro'] ?? ''),
                'image_intro_alt'        => (string) ($img['image_intro_alt'] ?? ''),
                'image_intro_caption'    => (string) ($img['image_intro_caption'] ?? ''),
                'image_fulltext'         => (string) ($img['image_fulltext'] ?? ''),
                'image_fulltext_alt'     => (string) ($img['image_fulltext_alt'] ?? ''),
                'image_fulltext_caption' => (string) ($img['image_fulltext_caption'] ?? ''),
            ],
        ];
    }

    /**
     * Record a pull in #__cgrabber_log.
     *
     * @return  void
     */
    private function logPull(object $source, int $remoteId, array $result, int $userId): void
    {
        try {
            $db  = $this->getDatabase();
            $row = (object) [
                'source_url'        => $source->url,
                'source_id'         => $remoteId,
                'target_article_id' => $result['article_id'],
                'title'             => $result['title'],
                'imported_by'       => $userId,
                'images_ok'         => $result['images_ok'],
                'images_failed'     => $result['images_failed'],
                'created'           => Factory::getDate()->toSql(),
            ];

            $db->insertObject('#__cgrabber_log', $row);
        } catch (\Throwable $e) {
            Log::add('Joomla Article Grabber log insert failed: ' . $e->getMessage(), Log::WARNING, 'com_content_api_grabber');
        }
    }
}
