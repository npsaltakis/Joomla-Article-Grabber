<?php
/**
 * @package     Com_ContentApiGrabber
 * @subpackage  Controller
 */

namespace Nickpsal\Component\ContentApiGrabber\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

/**
 * Handles importing an article from an uploaded XML file.
 */
class ImportController extends BaseController
{
    /**
     * Parse the uploaded XML, fetch its images and create the article.
     *
     * @return  void
     */
    public function process()
    {
        $this->checkToken();

        $app   = Factory::getApplication();
        $input = $app->getInput();

        $redirect = Route::_('index.php?option=com_content_api_grabber&view=import', false);

        // Form data: target category, author and published state.
        $data   = $input->get('jform', [], 'array');
        $catid  = (int) ($data['catid'] ?? 0);
        $userId = (int) ($data['created_by'] ?? 0);
        $state  = (int) ($data['state'] ?? 0);

        if (!$catid) {
            $this->setRedirect($redirect, Text::_('COM_CONTENT_API_GRABBER_ERROR_NO_CATEGORY'), 'error');

            return;
        }

        // Default author to the current user when none picked.
        if (!$userId) {
            $userId = (int) $app->getIdentity()->id;
        }

        // The uploaded XML file.
        $files = $input->files->get('jform', [], 'raw');
        $file  = $files['xmlfile'] ?? null;

        if (empty($file) || empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            $this->setRedirect($redirect, Text::_('COM_CONTENT_API_GRABBER_ERROR_NO_FILE'), 'error');

            return;
        }

        /** @var \Nickpsal\Component\ContentApiGrabber\Administrator\Model\ImportModel $model */
        $model = $this->getModel('Import');

        try {
            $result = $model->importFromFile($file['tmp_name'], $catid, $userId, $state);
        } catch (\Throwable $e) {
            $this->setRedirect($redirect, $e->getMessage(), 'error');

            return;
        }

        $message = Text::sprintf(
            'COM_CONTENT_API_GRABBER_IMPORT_SUCCESS',
            $result['title'],
            $result['images_ok'],
            $result['images_failed']
        );

        $type = $result['images_failed'] > 0 ? 'warning' : 'message';

        $this->setRedirect($redirect, $message, $type);
    }
}
