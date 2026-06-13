<?php
/**
 * @package     com_content_api_grabber
 * @subpackage  Model
 */

namespace Nickpsal\Component\ContentApiGrabber\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\QueryInterface;

/**
 * List model for the import/pull history (#__cgrabber_log).
 */
class HistoryModel extends ListModel
{
    /**
     * Constructor.
     *
     * @param   array  $config  Configuration array.
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = ['id', 'a.id', 'title', 'a.title', 'source_url', 'a.source_url', 'created', 'a.created'];
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
    protected function populateState($ordering = 'a.created', $direction = 'DESC')
    {
        $this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', ''));

        parent::populateState($ordering, $direction);
    }

    /**
     * Build the query for the history list.
     *
     * @return  QueryInterface
     */
    protected function getListQuery()
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select(
                $db->quoteName(
                    [
                        'a.id', 'a.source_url', 'a.source_id', 'a.target_article_id',
                        'a.title', 'a.imported_by', 'a.images_ok', 'a.images_failed', 'a.created',
                    ]
                )
            )
            ->select($db->quoteName('u.name', 'author_name'))
            ->select($db->quoteName('c.state', 'article_state'))
            ->from($db->quoteName('#__cgrabber_log', 'a'))
            ->join('LEFT', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('a.imported_by'))
            ->join('LEFT', $db->quoteName('#__content', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.target_article_id'));

        $search = (string) $this->getState('filter.search');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where('(' . $db->quoteName('a.title') . ' LIKE :s1 OR ' . $db->quoteName('a.source_url') . ' LIKE :s2)')
                ->bind(':s1', $like)
                ->bind(':s2', $like);
        }

        $ordering  = $this->getState('list.ordering', 'a.created');
        $direction = $this->getState('list.direction', 'DESC');
        $query->order($db->escape($ordering) . ' ' . $db->escape($direction));

        return $query;
    }
}
