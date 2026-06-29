<?php
/**
 * @package     com_content_api_grabber
 * @subpackage  Model
 * @copyright   (C) 2026 Nick Psaltakis
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\ContentApiGrabber\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Language\Text;
use Nickpsal\Component\ContentApiGrabber\Administrator\Helper\CryptHelper;

/**
 * Admin model for a single remote source. Handles token encryption on save.
 */
class SourceModel extends AdminModel
{
    /**
     * @var  string
     */
    public $typeAlias = 'com_content_api_grabber.source';

    /**
     * Get the source table.
     *
     * @param   string  $name     Table name.
     * @param   string  $prefix   Class prefix.
     * @param   array   $options  Config.
     *
     * @return  \Joomla\CMS\Table\Table
     */
    public function getTable($name = 'Source', $prefix = 'Administrator', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
    }

    /**
     * Load the source edit form.
     *
     * @param   array    $data      Data.
     * @param   boolean  $loadData  Load data flag.
     *
     * @return  \Joomla\CMS\Form\Form|false
     */
    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm(
            'com_content_api_grabber.source',
            'source',
            ['control' => 'jform', 'load_data' => $loadData]
        );

        return $form ?: false;
    }

    /**
     * Preload data for the form from the session or the stored item.
     *
     * @return  mixed
     */
    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_content_api_grabber.edit.source.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    /**
     * Never expose the stored encrypted token to the form.
     *
     * @param   integer  $pk  Primary key.
     *
     * @return  mixed
     */
    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);

        if ($item && isset($item->token_enc)) {
            // The plaintext token is never shown; the field stays blank on edit.
            $item->token     = '';
            $item->has_token = $item->token_enc !== '';
            unset($item->token_enc);
        }

        return $item;
    }

    /**
     * Encrypt the token before saving. Leaving the token blank on edit keeps the existing one.
     *
     * @param   array  $data  The form data.
     *
     * @return  boolean
     */
    public function save($data)
    {
        $input = Factory::getApplication()->getInput();
        $id    = (int) ($data['id'] ?? $input->getInt('id', 0));

        if ($id > 0) {
            $data['id'] = $id;
        }

        $token = preg_replace('/\s+/', '', (string) ($data['token'] ?? '')) ?? '';
        unset($data['token']);

        if ($token !== '') {
            $decoded = base64_decode($token, true);

            if (
                $decoded === false
                || !preg_match('/^sha(256|512):[0-9]+:[a-f0-9]+$/i', $decoded)
            ) {
                $this->setError(Text::_('COM_CONTENT_API_GRABBER_ERROR_INVALID_TOKEN'));

                return false;
            }

            // New/changed token: encrypt at rest.
            $data['token_enc'] = CryptHelper::encrypt($token);
        } elseif ($id === 0) {
            // New record without a token.
            $data['token_enc'] = '';
        }
        // On edit with a blank token we simply omit token_enc so the stored value is preserved.

        return parent::save($data);
    }
}
