<?php
/**
 * @package     com_content_api_grabber
 * @subpackage  Model
 * @copyright   (C) 2026 Nick Psaltakis
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\ContentApiGrabber\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\QueryInterface;

/**
 * List model for the configured remote sources.
 */
class SourcesModel extends ListModel
{
    /**
     * Constructor.
     *
     * @param   array  $config  Configuration array.
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = ['id', 'name', 'url', 'published', 'ordering'];
        }

        parent::__construct($config);
    }

    /**
     * Build the list state.
     *
     * @param   string  $ordering   Default ordering column.
     * @param   string  $direction  Default direction.
     *
     * @return  void
     */
    protected function populateState($ordering = 'a.ordering', $direction = 'ASC')
    {
        $this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', ''));

        parent::populateState($ordering, $direction);
    }

    /**
     * Build the query for the list of sources.
     *
     * @return  QueryInterface
     */
    protected function getListQuery()
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['a.id', 'a.name', 'a.url', 'a.default_catid', 'a.published', 'a.ordering', 'a.created']))
            ->from($db->quoteName('#__cgrabber_sources', 'a'));

        $search = (string) $this->getState('filter.search');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where('(' . $db->quoteName('a.name') . ' LIKE :s1 OR ' . $db->quoteName('a.url') . ' LIKE :s2)')
                ->bind(':s1', $like)
                ->bind(':s2', $like);
        }

        $ordering  = $this->getState('list.ordering', 'a.ordering');
        $direction = $this->getState('list.direction', 'ASC');
        $query->order($db->escape($ordering) . ' ' . $db->escape($direction));

        return $query;
    }
}
