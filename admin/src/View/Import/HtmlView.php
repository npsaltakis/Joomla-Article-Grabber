<?php
/**
 * @package     Com_ContentApiGrabber
 * @subpackage  View
 * @copyright   (C) 2026 Nick Psaltakis
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\ContentApiGrabber\Administrator\View\Import;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Import view: upload an XML, pick category + author, create the article.
 */
class HtmlView extends BaseHtmlView
{
    /**
     * @var  \Joomla\CMS\Form\Form
     */
    protected $form;

    /**
     * Render the view.
     *
     * @param   string|null  $tpl  Template name.
     *
     * @return  void
     */
    public function display($tpl = null)
    {
        $this->form = $this->get('Form');

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
        ToolbarHelper::title(Text::_('COM_CONTENT_API_GRABBER_TITLE_IMPORT'), 'upload');

        $toolbar = $this->getDocument()->getToolbar();
        $toolbar->linkButton('export', Text::_('COM_CONTENT_API_GRABBER_TOOLBAR_GOTO_EXPORT'))
            ->url('index.php?option=com_content_api_grabber&view=export')
            ->icon('icon-share-alt');

        ToolbarHelper::preferences('com_content_api_grabber');
    }
}
