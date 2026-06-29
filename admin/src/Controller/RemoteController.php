<?php
/**
 * @package     com_content_api_grabber
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
 * Controller for pulling articles from a remote source.
 */
class RemoteController extends BaseController
{
    /**
     * Pull the selected remote articles into the local site.
     *
     * @return  void
     */
    public function pull()
    {
        $this->checkToken();

        $app   = Factory::getApplication();
        $input = $app->getInput();

        $sourceId = $input->getInt('source_id', 0);
        $ids      = $input->get('cid', [], 'array');
        $ids      = array_values(array_filter(array_map('intval', $ids)));

        $data           = $input->get('jform', [], 'array');
        $catid          = (int) ($data['catid'] ?? 0);
        $userId         = (int) ($data['created_by'] ?? 0) ?: (int) $app->getIdentity()->id;
        $state          = (int) ($data['state'] ?? 0);
        $featured       = (bool) (int) ($data['featured'] ?? 0);
        $skipDuplicates = (bool) (int) ($data['skip_duplicates'] ?? 0);

        $redirect = Route::_('index.php?option=com_content_api_grabber&view=remote&source_id=' . $sourceId, false);

        if (!$sourceId) {
            $this->setRedirect($redirect, Text::_('COM_CONTENT_API_GRABBER_ERROR_NO_SOURCE_SELECTED'), 'error');

            return;
        }

        if (!$catid) {
            $this->setRedirect($redirect, Text::_('COM_CONTENT_API_GRABBER_ERROR_NO_CATEGORY'), 'error');

            return;
        }

        if (empty($ids)) {
            $this->setRedirect($redirect, Text::_('COM_CONTENT_API_GRABBER_ERROR_NO_ARTICLES_SELECTED'), 'error');

            return;
        }

        /** @var \Nickpsal\Component\ContentApiGrabber\Administrator\Model\RemoteModel $model */
        $model = $this->getModel('Remote');

        try {
            $result = $model->pull($sourceId, $ids, $catid, $userId, $state, $skipDuplicates, $featured);
        } catch (\Throwable $e) {
            $this->setRedirect($redirect, $e->getMessage(), 'error');

            return;
        }

        $message = Text::sprintf('COM_CONTENT_API_GRABBER_PULL_SUMMARY', $result['ok'], $result['skipped'], $result['failed']);
        $type    = $result['failed'] > 0 ? 'warning' : 'message';

        $this->setRedirect($redirect, $message, $type);
    }
}
