<?php
/**
 * @package     plg_content_apigrabber
 * @subpackage  Extension
 * @copyright   (C) 2026 Nick Psaltakis
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Plugin\Content\ApiGrabber\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;

/**
 * Companion plugin for the Joomla Article Grabber, installed on the SOURCE site.
 *
 * Stock Joomla's content Web Services (REST) API never exposes introtext and
 * fulltext separately: its article view builds a single combined field as
 * `introtext . ' ' . fulltext` and drops the read-more separator entirely.
 * As a result a consumer pulling articles over the API cannot tell where the
 * intro ended, so the imported article loses its "Read more" break and dumps
 * the whole body into the intro text.
 *
 * This plugin re-inserts the `<hr id="system-readmore" />` marker between the
 * intro and full text — but ONLY for requests served by the API application —
 * so the grabber on the target site can split the body back into introtext and
 * fulltext. The site frontend output is left completely untouched.
 */
final class ApiGrabber extends CMSPlugin
{
    /**
     * Re-insert the read-more separator into the combined API "text" field.
     *
     * @param   string  $context  The content context (e.g. com_content.article).
     * @param   object  $article  The article object being prepared (modified in place).
     * @param   mixed   $params   The content params.
     * @param   int     $page     Optional page number.
     *
     * @return  void
     */
    public function onContentPrepare($context, $article, $params, $page = 0): void
    {
        if ($context !== 'com_content.article') {
            return;
        }

        $app = $this->getApplication();

        // Only touch responses produced by the Web Services (REST) API.
        if (!$app || !$app->isClient('api')) {
            return;
        }

        // Nothing to preserve unless the article actually has a read-more split.
        if (!isset($article->text, $article->introtext, $article->fulltext) || (string) $article->fulltext === '') {
            return;
        }

        $article->text = $article->introtext . '<hr id="system-readmore" />' . $article->fulltext;
    }
}
