<?php
/**
 * @package     com_content_api_grabber
 * @subpackage  Controller
 */

namespace Nickpsal\Component\ContentApiGrabber\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;

/**
 * Form controller for a single remote source.
 */
class SourceController extends FormController
{
    /**
     * @var  string
     */
    protected $view_list = 'sources';

    /**
     * @var  string
     */
    protected $view_item = 'source';

    /**
     * @var  string
     */
    protected $context = 'com_content_api_grabber.edit.source';

    /**
     * Open a blank source form.
     *
     * @return  boolean
     */
    public function add()
    {
        $this->setRedirect(Route::_('index.php?option=com_content_api_grabber&view=source&layout=edit', false));

        return true;
    }

    /**
     * Open an existing source form.
     *
     * @param   integer|null  $key     Record id.
     * @param   string|null   $urlVar  Request variable name.
     *
     * @return  boolean
     */
    public function edit($key = null, $urlVar = null)
    {
        $id = $this->input->getInt($urlVar ?: 'id', 0);

        $this->setRedirect(
            Route::_('index.php?option=com_content_api_grabber&view=source&layout=edit&id=' . $id, false)
        );

        return true;
    }

    /**
     * Save the source and force redirects to valid component URLs.
     *
     * @param   string|null  $key     Primary key name.
     * @param   string|null  $urlVar  Request variable name.
     *
     * @return  boolean
     */
    public function save($key = null, $urlVar = null)
    {
        $task   = $this->getTask();
        $result = parent::save($key, $urlVar);

        if (!$result) {
            return false;
        }

        $model = $this->getModel();
        $data  = $this->input->post->get('jform', [], 'array');
        $id    = (int) ($model ? $model->getState('source.id') : 0);
        $id    = $id ?: (int) ($data['id'] ?? 0);

        if ($task === 'apply') {
            $this->setRedirect(
                Route::_('index.php?option=com_content_api_grabber&view=source&layout=edit&id=' . $id, false)
            );

            return true;
        }

        if ($task === 'save2new') {
            $this->setRedirect(Route::_('index.php?option=com_content_api_grabber&view=source&layout=edit', false));

            return true;
        }

        $this->setRedirect(Route::_('index.php?option=com_content_api_grabber&view=sources', false));

        return true;
    }

    /**
     * Cancel editing and return to the sources list.
     *
     * @param   string|null  $key  Primary key name.
     *
     * @return  boolean
     */
    public function cancel($key = null)
    {
        parent::cancel($key);

        $this->setRedirect(Route::_('index.php?option=com_content_api_grabber&view=sources', false));

        return true;
    }

    /**
     * Get the source model explicitly for form tasks.
     *
     * @param   string  $name    Model name.
     * @param   string  $prefix  Class prefix.
     * @param   array   $config  Model config.
     *
     * @return  \Joomla\CMS\MVC\Model\BaseDatabaseModel|false
     */
    public function getModel($name = 'Source', $prefix = 'Administrator', $config = ['ignore_request' => false])
    {
        return parent::getModel($name, $prefix, $config);
    }
}
