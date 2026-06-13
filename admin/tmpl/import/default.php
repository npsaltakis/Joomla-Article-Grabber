<?php
/**
 * @package     Com_ContentApiGrabber
 * @subpackage  Templates
 *
 * @var  \Nickpsal\Component\ContentApiGrabber\Administrator\View\Import\HtmlView  $this
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('form.validate');
?>
<form action="<?php echo Route::_('index.php?option=com_content_api_grabber&view=import'); ?>"
      method="post" name="adminForm" id="adminForm"
      enctype="multipart/form-data" class="form-validate">

    <div class="row">
        <div class="col-md-8">
            <p class="text-muted"><?php echo Text::_('COM_CONTENT_API_GRABBER_IMPORT_DESC'); ?></p>

            <fieldset class="options-form">
                <legend><?php echo Text::_('COM_CONTENT_API_GRABBER_IMPORT_LEGEND'); ?></legend>

                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('xmlfile'); ?></div>
                    <div class="controls"><?php echo $this->form->getInput('xmlfile'); ?></div>
                </div>

                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('catid'); ?></div>
                    <div class="controls"><?php echo $this->form->getInput('catid'); ?></div>
                </div>

                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('created_by'); ?></div>
                    <div class="controls"><?php echo $this->form->getInput('created_by'); ?></div>
                </div>

                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('state'); ?></div>
                    <div class="controls"><?php echo $this->form->getInput('state'); ?></div>
                </div>
            </fieldset>

            <button type="button" class="btn btn-primary"
                    onclick="Joomla.submitform('import.process', document.getElementById('adminForm'));">
                <span class="icon-upload" aria-hidden="true"></span>
                <?php echo Text::_('COM_CONTENT_API_GRABBER_IMPORT_RUN'); ?>
            </button>
        </div>
    </div>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
