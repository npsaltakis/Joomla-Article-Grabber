<?php
/**
 * @package     Com_ContentApiGrabber
 * @subpackage  View
 * @copyright   (C) 2026 Nick Psaltakis
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\ContentApiGrabber\Administrator\View\Export;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Export view: pick an article and download its grabber XML.
 */
class HtmlView extends BaseHtmlView
{
    /**
     * @var  array
     */
    protected $articles = [];

    /**
     * Render the view.
     *
     * @param   string|null  $tpl  Template name.
     *
     * @return  void
     */
    public function display($tpl = null)
    {
        $this->articles = $this->get('Articles');

        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Configure the toolbar.
     *
     * @return  void
     */
    protected function addToolbar(): void
    {
        ToolbarHelper::title(Text::_('COM_CONTENT_API_GRABBER_TITLE_EXPORT'), 'share-alt');

        $toolbar = $this->getDocument()->getToolbar();
        $toolbar->linkButton('import', Text::_('COM_CONTENT_API_GRABBER_TOOLBAR_GOTO_IMPORT'))
            ->url('index.php?option=com_content_api_grabber&view=import')
            ->icon('icon-upload');

        ToolbarHelper::preferences('com_content_api_grabber');
    }
}
