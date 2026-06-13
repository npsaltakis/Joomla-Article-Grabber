<?php
/**
 * @package     com_content_api_grabber
 * @subpackage  View
 */

namespace Nickpsal\Component\ContentApiGrabber\Administrator\View\History;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Import/pull history list view.
 */
class HtmlView extends BaseHtmlView
{
    protected $items;
    protected $pagination;
    protected $state;

    /**
     * Render the view.
     *
     * @param   string|null  $tpl  Template.
     *
     * @return  void
     */
    public function display($tpl = null)
    {
        $this->items      = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state      = $this->get('State');

        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Toolbar.
     *
     * @return  void
     */
    protected function addToolbar(): void
    {
        ToolbarHelper::title(Text::_('COM_CONTENT_API_GRABBER_TITLE_HISTORY'), 'list');

        $toolbar = $this->getDocument()->getToolbar();

        $toolbar->delete('history.delete')
            ->message('JGLOBAL_CONFIRM_DELETE')
            ->listCheck(true);

        $toolbar->confirmButton('clear', Text::_('COM_CONTENT_API_GRABBER_HISTORY_CLEAR_ALL'), 'history.clear')
            ->message(Text::_('COM_CONTENT_API_GRABBER_HISTORY_CLEAR_CONFIRM'))
            ->icon('icon-trash');

        $toolbar->linkButton('remote', Text::_('COM_CONTENT_API_GRABBER_TOOLBAR_GOTO_REMOTE'))
            ->url('index.php?option=com_content_api_grabber&view=remote')
            ->icon('icon-download');
    }
}
