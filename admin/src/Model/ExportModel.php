<?php
/**
 * @package     Com_ContentApiGrabber
 * @subpackage  Model
 * @copyright   (C) 2026 Nick Psaltakis
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\ContentApiGrabber\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Uri\Uri;
use Nickpsal\Component\ContentApiGrabber\Administrator\Helper\ImageHelper;

/**
 * Export model: lists published articles and serialises one to XML.
 */
class ExportModel extends BaseDatabaseModel
{
    /**
     * Schema version of the produced XML.
     */
    public const XML_VERSION = '1.0';

    /**
     * Return published articles for the export picker (most recent first).
     *
     * @return  array
     */
    public function getArticles(): array
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select(
                [
                    $db->quoteName('a.id'),
                    $db->quoteName('a.title'),
                    $db->quoteName('a.created'),
                    $db->quoteName('c.title', 'category'),
                ]
            )
            ->from($db->quoteName('#__content', 'a'))
            ->join('LEFT', $db->quoteName('#__categories', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'))
            ->where($db->quoteName('a.state') . ' = 1')
            ->order($db->quoteName('a.created') . ' DESC');

        $db->setQuery($query, 0, 200);

        return (array) $db->loadObjectList();
    }

    /**
     * Build the export XML for a single article. Returns null if not found.
     *
     * @param   int  $id  Article id.
     *
     * @return  string|null
     */
    public function buildXml(int $id): ?string
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__content'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($query);
        $article = $db->loadObject();

        if (!$article) {
            return null;
        }

        $base = Uri::root(); // e.g. https://source-site.gr/

        // Absolutize inline images inside the article body.
        $introtext = ImageHelper::absolutize((string) $article->introtext, $base);
        $fulltext  = ImageHelper::absolutize((string) $article->fulltext, $base);

        // The intro/full images live in the JSON `images` field.
        $images     = json_decode((string) $article->images) ?: new \stdClass();
        $introImage = ImageHelper::absolutizeUrl((string) ($images->image_intro ?? ''), $base);
        $fullImage  = ImageHelper::absolutizeUrl((string) ($images->image_fulltext ?? ''), $base);

        $writer = new \XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->startDocument('1.0', 'UTF-8');

        $writer->startElement('grabber');
        $writer->writeAttribute('version', self::XML_VERSION);

        $writer->startElement('source');
        $writer->writeElement('site', rtrim($base, '/'));
        $writer->writeElement('article_id', (string) $article->id);
        $writer->writeElement('exported', Factory::getDate()->toSql());
        $writer->endElement();

        $writer->startElement('article');
        $writer->writeElement('title', (string) $article->title);
        $writer->writeElement('alias', (string) $article->alias);
        $writer->writeElement('language', (string) $article->language);

        $this->writeCdata($writer, 'introtext', $introtext);
        $this->writeCdata($writer, 'fulltext', $fulltext);

        $writer->startElement('images');
        $writer->writeElement('image_intro', $introImage);
        $writer->writeElement('image_intro_alt', (string) ($images->image_intro_alt ?? ''));
        $writer->writeElement('image_intro_caption', (string) ($images->image_intro_caption ?? ''));
        $writer->writeElement('image_fulltext', $fullImage);
        $writer->writeElement('image_fulltext_alt', (string) ($images->image_fulltext_alt ?? ''));
        $writer->writeElement('image_fulltext_caption', (string) ($images->image_fulltext_caption ?? ''));
        $writer->endElement();

        $writer->startElement('metadata');
        $writer->writeElement('metakey', (string) $article->metakey);
        $writer->writeElement('metadesc', (string) $article->metadesc);
        $writer->endElement();

        $writer->endElement(); // article
        $writer->endElement(); // grabber
        $writer->endDocument();

        return $writer->outputMemory();
    }

    /**
     * Helper to write a CDATA-wrapped element.
     *
     * @param   \XMLWriter  $writer  The writer.
     * @param   string      $name    Element name.
     * @param   string      $value   Raw HTML value.
     *
     * @return  void
     */
    private function writeCdata(\XMLWriter $writer, string $name, string $value): void
    {
        $writer->startElement($name);
        $writer->writeCdata($value);
        $writer->endElement();
    }
}
