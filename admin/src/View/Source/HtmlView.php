<?php
/**
 * @package     com_content_api_grabber
 * @subpackage  View
 * @copyright   (C) 2026 Nick Psaltakis
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\ContentApiGrabber\Administrator\View\Source;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Edit view for a single remote source.
 */
class HtmlView extends BaseHtmlView
{
    protected $form;
    protected $item;

    /**
     * Render the view.
     *
     * @param   string|null  $tpl  Template.
     *
     * @return  void
     */
    public function display($tpl = null)
    {
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');

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
        $isNew = empty($this->item->id);

        ToolbarHelper::title(
            Text::_($isNew ? 'COM_CONTENT_API_GRABBER_TITLE_SOURCE_NEW' : 'COM_CONTENT_API_GRABBER_TITLE_SOURCE_EDIT'),
            'link'
        );

        $toolbar = $this->getDocument()->getToolbar();

        $toolbar->apply('source.apply');
        $toolbar->save('source.save');
        $toolbar->cancel('source.cancel');
    }
}
