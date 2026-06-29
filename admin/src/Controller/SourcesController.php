<?php
/**
 * @package     com_content_api_grabber
 * @subpackage  Controller
 * @copyright   (C) 2026 Nick Psaltakis
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\ContentApiGrabber\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;

/**
 * List controller for remote sources (publish / unpublish / delete).
 */
class SourcesController extends AdminController
{
    /**
     * Proxy to the Source model.
     *
     * @param   string  $name     Model name.
     * @param   string  $prefix   Class prefix.
     * @param   array   $config   Configuration.
     *
     * @return  \Joomla\CMS\MVC\Model\BaseDatabaseModel
     */
    public function getModel($name = 'Source', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
}
