<?php
/**
 * @package     com_content_api_grabber
 * @subpackage  Helper
 */

namespace Nickpsal\Component\ContentApiGrabber\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\String\StringHelper;

/**
 * Shared pipeline that turns a normalized article payload (from an XML import or
 * a REST pull) into a real com_content article: fetch images locally, rewrite
 * paths, ensure a unique alias and save through the core article model.
 */
class ArticleImporter
{
    /**
     * Import a normalized article.
     *
     * @param   array   $article   Keys: title, alias, language, introtext, fulltext,
     *                              metakey, metadesc, images[image_intro, image_intro_alt,
     *                              image_intro_caption, image_fulltext, image_fulltext_alt,
     *                              image_fulltext_caption].
     * @param   int     $catid     Target category id.
     * @param   int     $userId    Author user id.
     * @param   int     $state     Published state.
     * @param   string  $imageBase When non-empty, RELATIVE image URLs are made absolute
     *                             against this base (the remote site root) before download.
     *
     * @return  array  ['title', 'article_id', 'images_ok', 'images_failed']
     *
     * @throws  \RuntimeException
     */
    public static function import(array $article, int $catid, int $userId, int $state, string $imageBase = ''): array
    {
        $params  = ComponentHelper::getParams('com_content_api_grabber');
        $baseDir = trim($params->get('image_folder', 'images/grabbed'), '/');
        $timeout = (int) $params->get('http_timeout', 20);

        $webBase = $baseDir . '/' . date('Ymd-His');
        $destDir = JPATH_ROOT . '/' . $webBase;

        $introtext = (string) ($article['introtext'] ?? '');
        $fulltext  = (string) ($article['fulltext'] ?? '');
        $images    = (array) ($article['images'] ?? []);

        // Normalize the intro/full image URLs: drop Joomla's "#joomlaImage://..." fragment.
        foreach (['image_intro', 'image_fulltext'] as $k) {
            if (!empty($images[$k])) {
                $images[$k] = ImageHelper::stripFragment((string) $images[$k]);
            }
        }

        // Make relative URLs absolute against the remote base (REST pull case).
        if ($imageBase !== '') {
            $introtext = ImageHelper::absolutize($introtext, $imageBase);
            $fulltext  = ImageHelper::absolutize($fulltext, $imageBase);

            foreach (['image_intro', 'image_fulltext'] as $k) {
                if (!empty($images[$k])) {
                    $images[$k] = ImageHelper::absolutizeUrl($images[$k], $imageBase);
                }
            }
        }

        // Collect every absolute image URL: inline + intro/full.
        $urls = ImageHelper::extractImageUrls($introtext . $fulltext);

        foreach (['image_intro', 'image_fulltext'] as $k) {
            if (!empty($images[$k]) && preg_match('#^https?://#i', $images[$k])) {
                $urls[] = $images[$k];
            }
        }

        $urls = array_values(array_unique($urls));

        // Download each image; never hard-stop on a single failure.
        $replacements = [];
        $okCount      = 0;
        $failCount    = 0;

        foreach ($urls as $url) {
            try {
                $replacements[$url] = ImageHelper::download($url, $destDir, $webBase, $timeout);
                $okCount++;
            } catch (\Throwable $e) {
                $failCount++;
                Log::add('Joomla Article Grabber image failed: ' . $e->getMessage(), Log::WARNING, 'com_content_api_grabber');
            }
        }

        $introtext  = strtr($introtext, $replacements);
        $fulltext   = strtr($fulltext, $replacements);
        $introImage = $replacements[$images['image_intro'] ?? ''] ?? ($images['image_intro'] ?? '');
        $fullImage  = $replacements[$images['image_fulltext'] ?? ''] ?? ($images['image_fulltext'] ?? '');

        $articleId = self::createArticle(
            $article,
            $catid,
            $userId,
            $state,
            $introtext,
            $fulltext,
            $introImage,
            $fullImage,
            (array) $images
        );

        return [
            'title'         => (string) ($article['title'] ?? ''),
            'article_id'    => $articleId,
            'images_ok'     => $okCount,
            'images_failed' => $failCount,
        ];
    }

    /**
     * Create the article through the core com_content administrator model.
     *
     * @return  int  New article id.
     *
     * @throws  \RuntimeException
     */
    private static function createArticle(
        array $article,
        int $catid,
        int $userId,
        int $state,
        string $introtext,
        string $fulltext,
        string $introImage,
        string $fullImage,
        array $images
    ): int {
        $mvc   = Factory::getApplication()->bootComponent('com_content')->getMVCFactory();
        $model = $mvc->createModel('Article', 'Administrator', ['ignore_request' => true]);

        if (!$model) {
            throw new \RuntimeException('Cannot load com_content article model.');
        }

        $title = ($article['title'] ?? '') !== '' ? $article['title'] : 'Imported article';
        $alias = ($article['alias'] ?? '') !== '' ? $article['alias'] : OutputFilter::stringUrlSafe($title);

        [$title, $alias] = self::ensureUniqueAlias($catid, $alias, $title);

        $payload = [
            'id'         => 0,
            'title'      => $title,
            'alias'      => $alias,
            'catid'      => $catid,
            'introtext'  => $introtext,
            'fulltext'   => $fulltext,
            'state'      => $state,
            'language'   => $article['language'] ?? '*',
            'created_by' => $userId,
            'access'     => (int) Factory::getApplication()->get('access', 1),
            'metakey'    => $article['metakey'] ?? '',
            'metadesc'   => $article['metadesc'] ?? '',
            'images'     => json_encode([
                'image_intro'            => $introImage,
                'image_intro_alt'        => $images['image_intro_alt'] ?? '',
                'image_intro_caption'    => $images['image_intro_caption'] ?? '',
                'image_fulltext'         => $fullImage,
                'image_fulltext_alt'     => $images['image_fulltext_alt'] ?? '',
                'image_fulltext_caption' => $images['image_fulltext_caption'] ?? '',
                'float_intro'            => '',
                'float_fulltext'         => '',
            ]),
        ];

        if (!$model->save($payload)) {
            throw new \RuntimeException('Article save failed: ' . $model->getError());
        }

        return (int) $model->getState('article.id');
    }

    /**
     * Ensure the alias is unique within the target category (Joomla "save as copy" style).
     *
     * @return  array  [title, alias]
     */
    private static function ensureUniqueAlias(int $catid, string $alias, string $title): array
    {
        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        while (true) {
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__content'))
                ->where($db->quoteName('catid') . ' = :catid')
                ->where($db->quoteName('alias') . ' = :alias')
                ->bind(':catid', $catid, ParameterType::INTEGER)
                ->bind(':alias', $alias, ParameterType::STRING);

            $db->setQuery($query);

            if ((int) $db->loadResult() === 0) {
                return [$title, $alias];
            }

            $alias = StringHelper::increment($alias, 'dash');
            $title = StringHelper::increment($title);
        }
    }
}
