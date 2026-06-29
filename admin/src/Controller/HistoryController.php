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
use Joomla\Database\ParameterType;

/**
 * Controller for the import/pull history log (#__cgrabber_log).
 */
class HistoryController extends BaseController
{
    /**
     * Delete the selected log rows.
     *
     * @return  void
     */
    public function delete()
    {
        $this->checkToken();

        $app      = Factory::getApplication();
        $ids      = $app->getInput()->get('cid', [], 'array');
        $ids      = array_values(array_filter(array_map('intval', $ids)));
        $redirect = Route::_('index.php?option=com_content_api_grabber&view=history', false);

        if (empty($ids)) {
            $this->setRedirect($redirect, Text::_('JGLOBAL_NO_ITEM_SELECTED'), 'warning');

            return;
        }

        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__cgrabber_log'))
            ->whereIn($db->quoteName('id'), $ids);
        $db->setQuery($query)->execute();

        $this->setRedirect($redirect, Text::plural('COM_CONTENT_API_GRABBER_HISTORY_N_DELETED', \count($ids)));
    }

    /**
     * Clear the entire history log.
     *
     * @return  void
     */
    public function clear()
    {
        $this->checkToken();

        $app = Factory::getApplication();
        $db  = $this->getDatabase();
        $db->setQuery('DELETE FROM ' . $db->quoteName('#__cgrabber_log'))->execute();

        $this->setRedirect(
            Route::_('index.php?option=com_content_api_grabber&view=history', false),
            Text::_('COM_CONTENT_API_GRABBER_HISTORY_CLEARED')
        );
    }

    /**
     * Get the database driver.
     *
     * @return  \Joomla\Database\DatabaseInterface
     */
    private function getDatabase()
    {
        return Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
    }
}
