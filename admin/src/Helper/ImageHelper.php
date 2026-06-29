<?php
/**
 * @package     Com_ContentApiGrabber
 * @subpackage  Helper
 * @copyright   (C) 2026 Nick Psaltakis
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\ContentApiGrabber\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Http\HttpFactory;

/**
 * Image utilities for export (absolutize URLs) and import (download + rewrite).
 */
class ImageHelper
{
    /**
     * Allowed image content types / extensions when downloading.
     */
    private const ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp'];

    /**
     * Convert every relative <img src> in the HTML to an absolute URL using $base.
     * Used on EXPORT so the produced XML always carries fully-qualified image URLs.
     *
     * @param   string  $html  The article HTML.
     * @param   string  $base  Site root, e.g. https://source-site.gr/
     *
     * @return  string
     */
    public static function absolutize(string $html, string $base): string
    {
        if ($html === '') {
            return $html;
        }

        $base = rtrim($base, '/');

        return preg_replace_callback(
            '/(<img\b[^>]*?\bsrc\s*=\s*)(["\'])(.*?)\2/i',
            static function (array $m) use ($base) {
                $url = trim($m[3]);

                if ($url === '' || preg_match('#^(https?:)?//#i', $url) || str_starts_with($url, 'data:')) {
                    return $m[0];
                }

                $abs = $base . '/' . ltrim($url, '/');

                return $m[1] . $m[2] . $abs . $m[2];
            },
            $html
        ) ?? $html;
    }

    /**
     * Remove a URL fragment, e.g. Joomla 4/5 appends image metadata as
     * "...jpg#joomlaImage://local-images/...jpg?width=1024&height=907".
     *
     * @param   string  $url  The URL.
     *
     * @return  string
     */
    public static function stripFragment(string $url): string
    {
        $pos = strpos($url, '#');

        return $pos === false ? $url : substr($url, 0, $pos);
    }

    /**
     * Make a single (possibly relative) URL absolute against $base.
     *
     * @param   string  $url   The URL.
     * @param   string  $base  Site root.
     *
     * @return  string
     */
    public static function absolutizeUrl(string $url, string $base): string
    {
        $url = trim($url);

        if ($url === '' || preg_match('#^(https?:)?//#i', $url) || str_starts_with($url, 'data:')) {
            return $url;
        }

        return rtrim($base, '/') . '/' . ltrim($url, '/');
    }

    /**
     * Collect every absolute http(s) <img src> from the HTML.
     *
     * @param   string  $html  The article HTML.
     *
     * @return  string[]  Unique list of image URLs.
     */
    public static function extractImageUrls(string $html): array
    {
        $urls = [];

        if ($html !== '' && preg_match_all('/<img\b[^>]*?\bsrc\s*=\s*(["\'])(.*?)\1/i', $html, $m)) {
            foreach ($m[2] as $url) {
                $url = trim($url);

                if ($url !== '' && preg_match('#^https?://#i', $url)) {
                    $urls[$url] = true;
                }
            }
        }

        return array_keys($urls);
    }

    /**
     * Download a remote image into $destDir and return its new web-relative path.
     * Throws on hard failures (network, non-image, write error).
     *
     * @param   string  $url      Absolute image URL.
     * @param   string  $destDir  Absolute filesystem dir to save into.
     * @param   string  $webBase  Web path matching $destDir, e.g. images/grabbed/20260613
     * @param   int     $timeout  HTTP timeout in seconds.
     *
     * @return  string  New web-relative path, e.g. images/grabbed/20260613/foto1.jpg
     *
     * @throws  \RuntimeException
     */
    public static function download(string $url, string $destDir, string $webBase, int $timeout = 20): string
    {
        // Strip any fragment (e.g. Joomla's "#joomlaImage://local-images/...?width=...").
        $url = self::stripFragment($url);

        $http = HttpFactory::getHttp();

        try {
            // Timeout is passed per-request; passing raw cURL options here is transport-specific and unsafe.
            $response = $http->get($url, [], $timeout);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Download failed: ' . $url . ' (' . $e->getMessage() . ')');
        }

        if ((int) $response->code !== 200 || $response->body === null || $response->body === '') {
            throw new \RuntimeException('Bad response (' . $response->code . ') for ' . $url);
        }

        // Validate it actually looks like an image.
        $contentType = '';

        foreach ($response->headers ?? [] as $name => $value) {
            if (strtolower((string) $name) === 'content-type') {
                $contentType = strtolower(is_array($value) ? ($value[0] ?? '') : (string) $value);
                break;
            }
        }

        if ($contentType !== '' && !str_starts_with($contentType, 'image/')) {
            throw new \RuntimeException('Not an image (' . $contentType . '): ' . $url);
        }

        $name = self::safeFilename($url, $contentType);

        if (!is_dir($destDir) && !@mkdir($destDir, 0755, true) && !is_dir($destDir)) {
            throw new \RuntimeException('Cannot create folder: ' . $destDir);
        }

        // Avoid collisions inside the destination folder.
        $target  = $destDir . '/' . $name;
        $webPath = $webBase . '/' . $name;
        $i       = 1;

        while (is_file($target)) {
            $info    = pathinfo($name);
            $variant = $info['filename'] . '-' . $i . (isset($info['extension']) ? '.' . $info['extension'] : '');
            $target  = $destDir . '/' . $variant;
            $webPath = $webBase . '/' . $variant;
            $i++;
        }

        if (file_put_contents($target, $response->body) === false) {
            throw new \RuntimeException('Cannot write file: ' . $target);
        }

        return $webPath;
    }

    /**
     * Derive a safe filename from a URL, ensuring a valid image extension.
     *
     * @param   string  $url          The source URL.
     * @param   string  $contentType  Optional content-type to infer extension.
     *
     * @return  string
     */
    private static function safeFilename(string $url, string $contentType = ''): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: 'image';
        $base = basename($path);
        $base = preg_replace('/[^A-Za-z0-9._-]/', '-', rawurldecode($base)) ?: 'image';

        $ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));

        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            // Infer from content type, default to jpg.
            $map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp', 'image/avif' => 'avif'];
            $ext = $map[$contentType] ?? 'jpg';
            $base = pathinfo($base, PATHINFO_FILENAME) . '.' . $ext;
        }

        return strtolower($base);
    }
}
