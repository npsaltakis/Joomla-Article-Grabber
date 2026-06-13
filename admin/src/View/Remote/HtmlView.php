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
    protected $sources           = [];
    protected $articles          = [];
    protected $sourceId          = 0;
    protected $error             = '';
    protected $limit             = 20;
    protected $offset            = 0;
    protected $hasNext           = false;
    protected $total             = null;
    protected $search            = '';
    protected $catId             = 0;
    protected $remoteCategories  = [];

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

        $input          = Factory::getApplication()->getInput();
        $this->form     = $this->get('Form');
        $this->sources  = $model->getSources();
        $this->sourceId = (int) $input->getInt('source_id', 0);
        $this->limit    = max(5, min(100, (int) $input->getInt('limit', 20)));
        $this->offset   = max(0, (int) $input->getInt('offset', 0));
        $this->search   = trim($input->getString('search', ''));
        $this->catId    = (int) $input->getInt('cat_id', 0);

        // Pre-select the source's saved default category in the pull form.
        if ($this->sourceId && $this->form) {
            foreach ($this->sources as $s) {
                if ((int) $s->id === $this->sourceId && (int) $s->default_catid > 0) {
                    $this->form->setValue('catid', null, (int) $s->default_catid);
                    break;
                }
            }
        }

        if ($this->sourceId) {
            $this->remoteCategories = $model->getRemoteCategories($this->sourceId);

            try {
                $page           = $model->getRemoteArticles($this->sourceId, $this->limit, $this->offset, $this->search, $this->catId);
                $this->articles = $page['items'];
                $this->hasNext  = $page['hasNext'];
                $this->total    = $page['total'];
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
