<?php
/**
 * @package     com_content_api_grabber
 * @subpackage  View
 */

namespace Nickpsal\Component\ContentApiGrabber\Administrator\View\Remote;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Remote pull view: pick a source, list its articles, pull selected ones.
 */
class HtmlView extends BaseHtmlView
{
    protected $form;
    protected $sources = [];
    protected $articles = [];
    protected $sourceId = 0;
    protected $error = '';

    /**
     * Render the view.
     *
     * @param   string|null  $tpl  Template.
     *
     * @return  void
     */
    public function display($tpl = null)
    {
        /** @var \Nickpsal\Component\ContentApiGrabber\Administrator\Model\RemoteModel $model */
        $model = $this->getModel();

        $this->form     = $this->get('Form');
        $this->sources  = $model->getSources();
        $this->sourceId = (int) Factory::getApplication()->getInput()->getInt('source_id', 0);

        if ($this->sourceId) {
            try {
                $this->articles = $model->getRemoteArticles($this->sourceId, 100, 0);
            } catch (\Throwable $e) {
                $this->error = $e->getMessage();
            }
        }

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
        ToolbarHelper::title(Text::_('COM_CONTENT_API_GRABBER_TITLE_REMOTE'), 'download');

        $toolbar = $this->getDocument()->getToolbar();
        $toolbar->linkButton('sources', Text::_('COM_CONTENT_API_GRABBER_TOOLBAR_GOTO_SOURCES'))
            ->url('index.php?option=com_content_api_grabber&view=sources')
            ->icon('icon-link');

        ToolbarHelper::preferences('com_content_api_grabber');
    }
}
