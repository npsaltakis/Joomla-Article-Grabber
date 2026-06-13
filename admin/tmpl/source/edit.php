<?php
/**
 * @package     com_content_api_grabber
 * @subpackage  Templates
 *
 * @var  \Nickpsal\Component\ContentApiGrabber\Administrator\View\Source\HtmlView  $this
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')->useScript('form.validate');

$hasToken = !empty($this->item->has_token);
?>
<form action="<?php echo Route::_('index.php?option=com_content_api_grabber&view=source&layout=edit&id=' . (int) ($this->item->id ?? 0)); ?>"
      method="post" name="adminForm" id="source-form" class="form-validate">

    <div class="row">
        <div class="col-md-8">
            <fieldset class="options-form">
                <legend><?php echo Text::_('COM_CONTENT_API_GRABBER_SOURCE_LEGEND'); ?></legend>

                <?php foreach (['name', 'url', 'token', 'default_catid', 'published'] as $field) : ?>
                    <div class="control-group">
                        <div class="control-label"><?php echo $this->form->getLabel($field); ?></div>
                        <div class="controls">
                            <?php echo $this->form->getInput($field); ?>
                            <?php if ($field === 'token' && $hasToken) : ?>
                                <small class="text-muted d-block">
                                    <?php echo Text::_('COM_CONTENT_API_GRABBER_TOKEN_KEEP_HINT'); ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </fieldset>
        </div>
    </div>

    <input type="hidden" name="task" value="">
    <?php echo $this->form->getInput('id'); ?>
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
