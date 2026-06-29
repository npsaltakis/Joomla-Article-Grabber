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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\FormModel;
use Nickpsal\Component\ContentApiGrabber\Administrator\Helper\ArticleImporter;

/**
 * Import model: reads a grabber XML and creates an article from it (images are
 * referenced by absolute URL and downloaded by the shared ArticleImporter).
 */
class ImportModel extends FormModel
{
    /**
     * Load the import form (file upload + category + author + state).
     *
     * @param   array    $data      Pre-filled data.
     * @param   boolean  $loadData  Whether to preload form data.
     *
     * @return  \Joomla\CMS\Form\Form|false
     */
    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm(
            'com_content_api_grabber.import',
            'import',
            ['control' => 'jform', 'load_data' => $loadData]
        );

        return $form ?: false;
    }

    /**
     * Default form data (author defaults to the current user).
     *
     * @return  array
     */
    protected function loadFormData()
    {
        return ['created_by' => (int) Factory::getApplication()->getIdentity()->id];
    }

    /**
     * Run the full import pipeline from an uploaded XML file.
     *
     * @param   string  $tmpFile  Path to the uploaded temp file.
     * @param   int     $catid    Target category id.
     * @param   int     $userId   Author user id.
     * @param   int     $state    Published state (1/0).
     *
     * @return  array  ['title','article_id','images_ok','images_failed']
     *
     * @throws  \RuntimeException
     */
    public function importFromFile(string $tmpFile, int $catid, int $userId, int $state): array
    {
        $data = $this->parseXml($tmpFile);

        // XML carries absolute image URLs already, so no image base is needed.
        $result = ArticleImporter::import($data, $catid, $userId, $state, '');

        $this->logImport($data['title'], '', 0, $result['article_id'], $userId, $result['images_ok'], $result['images_failed']);

        return $result;
    }

    /**
     * Parse and validate a grabber XML file into a plain array.
     *
     * @param   string  $file  Path to the XML file.
     *
     * @return  array
     *
     * @throws  \RuntimeException
     */
    private function parseXml(string $file): array
    {
        $previous = libxml_use_internal_errors(true);
        $xml      = simplexml_load_file($file, \SimpleXMLElement::class, LIBXML_NOCDATA | LIBXML_NONET);
        libxml_use_internal_errors($previous);

        if ($xml === false || !isset($xml->article)) {
            throw new \RuntimeException(Text::_('COM_CONTENT_API_GRABBER_ERROR_INVALID_XML'));
        }

        $a   = $xml->article;
        $img = $a->images ?? null;

        return [
            'title'     => trim((string) $a->title),
            'alias'     => trim((string) $a->alias),
            'language'  => (string) $a->language ?: '*',
            'introtext' => (string) $a->introtext,
            'fulltext'  => (string) $a->fulltext,
            'metakey'   => (string) ($a->metadata->metakey ?? ''),
            'metadesc'  => (string) ($a->metadata->metadesc ?? ''),
            'images'    => [
                'image_intro'            => trim((string) ($img->image_intro ?? '')),
                'image_intro_alt'        => (string) ($img->image_intro_alt ?? ''),
                'image_intro_caption'    => (string) ($img->image_intro_caption ?? ''),
                'image_fulltext'         => trim((string) ($img->image_fulltext ?? '')),
                'image_fulltext_alt'     => (string) ($img->image_fulltext_alt ?? ''),
                'image_fulltext_caption' => (string) ($img->image_fulltext_caption ?? ''),
            ],
        ];
    }

    /**
     * Record an import/pull in #__cgrabber_log.
     *
     * @return  void
     */
    private function logImport(string $title, string $sourceUrl, int $sourceId, int $articleId, int $userId, int $okCount, int $failCount): void
    {
        try {
            $db  = $this->getDatabase();
            $row = (object) [
                'source_url'        => $sourceUrl,
                'source_id'         => $sourceId,
                'target_article_id' => $articleId,
                'title'             => $title,
                'imported_by'       => $userId,
                'images_ok'         => $okCount,
                'images_failed'     => $failCount,
                'created'           => Factory::getDate()->toSql(),
            ];

            $db->insertObject('#__cgrabber_log', $row);
        } catch (\Throwable $e) {
            Log::add('Joomla Article Grabber log insert failed: ' . $e->getMessage(), Log::WARNING, 'com_content_api_grabber');
        }
    }
}
