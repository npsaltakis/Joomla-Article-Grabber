<?php
/**
 * @package     Com_ContentApiGrabber
 * @subpackage  Controller
 * @copyright   (C) 2026 Nick Psaltakis
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\ContentApiGrabber\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Default controller. Routes to the requested view (remote | sources | export | import).
 */
class DisplayController extends BaseController
{
    /**
     * The default view.
     *
     * @var string
     */
    protected $default_view = 'remote';
}
