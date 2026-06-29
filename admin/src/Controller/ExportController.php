<?php
/**
 * @package     Com_ContentApiGrabber
 * @subpackage  Controller
 * @copyright   (C) 2026 Nick Psaltakis
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\ContentApiGrabber\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

/**
 * Handles exporting an article to a downloadable XML file.
 */
class ExportController extends BaseController
{
    /**
     * Build the XML for the selected article and stream it as a download.
     *
     * @return  void
     */
    public function download()
    {
        // The download is triggered from a GET link, so validate the token from the query string.
        $this->checkToken('get');

        $app = Factory::getApplication();
        $id  = $this->input->getInt('id', 0);

        if (!$id) {
            $this->setRedirect(
                Route::_('index.php?option=com_content_api_grabber&view=export', false),
                Text::_('COM_CONTENT_API_GRABBER_ERROR_NO_ARTICLE_SELECTED'),
                'error'
            );

            return;
        }

        /** @var \Nickpsal\Component\ContentApiGrabber\Administrator\Model\ExportModel $model */
        $model = $this->getModel('Export');
        $xml   = $model->buildXml($id);

        if ($xml === null) {
            $this->setRedirect(
                Route::_('index.php?option=com_content_api_grabber&view=export', false),
                Text::_('COM_CONTENT_API_GRABBER_ERROR_EXPORT_FAILED'),
                'error'
            );

            return;
        }

        $filename = 'grabber-article-' . $id . '.xml';

        // Clean any buffered output so the download is not corrupted.
        while (ob_get_level()) {
            ob_end_clean();
        }

        $app->setHeader('Content-Type', 'application/xml; charset=utf-8', true);
        $app->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"', true);
        $app->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate', true);
        $app->sendHeaders();

        echo $xml;

        $app->close();
    }
}
