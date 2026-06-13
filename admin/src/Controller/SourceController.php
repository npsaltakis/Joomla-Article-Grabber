<?php
/**
 * @package     com_content_api_grabber
 * @subpackage  Controller
 */

namespace Nickpsal\Component\ContentApiGrabber\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;

/**
 * Form controller for a single remote source.
 */
class SourceController extends FormController
{
    /**
     * AJAX: test connectivity to a remote source using the form's url + token
     * (falls back to the stored token when the field is left blank on edit).
     * Returns JSON { ok: bool, message: string }.
     *
     * @return  void
     */
    public function testConnection()
    {
        $this->checkToken('request');

        $app   = Factory::getApplication();
        $input = $app->getInput();

        $url   = trim((string) $input->get('url', '', 'string'));
        $token = (string) $input->get('token', '', 'raw');
        $id    = (int) $input->getInt('id', 0);

        /** @var \Nickpsal\Component\ContentApiGrabber\Administrator\Model\RemoteModel $model */
        $model = $app->bootComponent('com_content_api_grabber')->getMVCFactory()
            ->createModel('Remote', 'Administrator', ['ignore_request' => true]);

        // Blank token on an existing source: use the stored (decrypted) one.
        if ($token === '' && $id) {
            $token = $model->getStoredToken($id);
        }

        if ($url === '') {
            $result = ['ok' => false, 'message' => Text::_('COM_CONTENT_API_GRABBER_ERROR_SOURCE_URL')];
        } elseif ($token === '') {
            $result = ['ok' => false, 'message' => Text::_('COM_CONTENT_API_GRABBER_TEST_NO_TOKEN')];
        } else {
            $result = $model->testConnection($url, $token);
        }

        $app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        $app->sendHeaders();
        echo json_encode($result);
        $app->close();
    }

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
