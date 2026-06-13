<?php
/**
 * @package     com_content_api_grabber
 * @subpackage  Table
 */

namespace Nickpsal\Component\ContentApiGrabber\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

/**
 * Table for a remote source (#__cgrabber_sources).
 */
class SourceTable extends Table
{
    /**
     * Constructor.
     *
     * @param   DatabaseDriver  $db  Database driver.
     */
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__cgrabber_sources', 'id', $db);
    }

    /**
     * Basic validation before storing.
     *
     * @return  boolean
     */
    public function check(): bool
    {
        $this->name = trim((string) $this->name);
        $this->url  = trim(rtrim((string) $this->url, '/'));

        if ($this->name === '') {
            $this->setError(\Joomla\CMS\Language\Text::_('COM_CONTENT_API_GRABBER_ERROR_SOURCE_NAME'));

            return false;
        }

        if (!preg_match('#^https?://#i', $this->url)) {
            $this->setError(\Joomla\CMS\Language\Text::_('COM_CONTENT_API_GRABBER_ERROR_SOURCE_URL'));

            return false;
        }

        $now = Factory::getDate()->toSql();

        if (!$this->id) {
            $this->created = $now;
        }

        $this->modified = $now;

        return parent::check();
    }
}
