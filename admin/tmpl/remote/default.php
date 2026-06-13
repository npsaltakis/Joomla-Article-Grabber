<?php
/**
 * @package     com_content_api_grabber
 * @subpackage  Templates
 *
 * @var  \Nickpsal\Component\ContentApiGrabber\Administrator\View\Remote\HtmlView  $this
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<p class="text-muted"><?php echo Text::_('COM_CONTENT_API_GRABBER_REMOTE_DESC'); ?></p>

<?php if (empty($this->sources)) : ?>
    <div class="alert alert-info">
        <?php echo Text::_('COM_CONTENT_API_GRABBER_NO_SOURCES'); ?>
        <a href="<?php echo Route::_('index.php?option=com_content_api_grabber&view=source&layout=edit'); ?>">
            <?php echo Text::_('COM_CONTENT_API_GRABBER_ADD_SOURCE'); ?>
        </a>
    </div>
    <?php return; ?>
<?php endif; ?>

<!-- Source picker (reloads the page) -->
<form action="<?php echo Route::_('index.php?option=com_content_api_grabber&view=remote'); ?>" method="get" class="mb-3">
    <input type="hidden" name="option" value="com_content_api_grabber">
    <input type="hidden" name="view" value="remote">
    <div class="row align-items-end">
        <div class="col-md-6">
            <label for="source_id" class="form-label"><?php echo Text::_('COM_CONTENT_API_GRABBER_SELECT_SOURCE'); ?></label>
            <select name="source_id" id="source_id" class="form-select" onchange="this.form.submit()">
                <option value="0">&mdash;</option>
                <?php foreach ($this->sources as $s) : ?>
                    <option value="<?php echo (int) $s->id; ?>" <?php echo $this->sourceId === (int) $s->id ? 'selected' : ''; ?>>
                        <?php echo $this->escape($s->name) . ' (' . $this->escape($s->url) . ')'; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</form>

<?php if ($this->sourceId && $this->error) : ?>
    <div class="alert alert-danger"><?php echo $this->escape($this->error); ?></div>
<?php endif; ?>

<?php if ($this->sourceId && !$this->error) : ?>
    <?php if (empty($this->articles)) : ?>
        <div class="alert alert-warning"><?php echo Text::_('COM_CONTENT_API_GRABBER_NO_REMOTE_ARTICLES'); ?></div>
    <?php else : ?>
        <form action="<?php echo Route::_('index.php?option=com_content_api_grabber&view=remote'); ?>"
              method="post" name="adminForm" id="adminForm" class="form-validate">

            <div class="row">
                <div class="col-md-8">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <td style="width:1%" class="text-center">
                                    <input type="checkbox" onclick="document.querySelectorAll('.cag-cb').forEach(c=>c.checked=this.checked)">
                                </td>
                                <th><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
                                <th style="width:10%" class="text-center"><?php echo Text::_('JSTATUS'); ?></th>
                                <th style="width:18%"><?php echo Text::_('JDATE'); ?></th>
                                <th style="width:6%" class="text-center"><?php echo Text::_('JGRID_HEADING_ID'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($this->articles as $a) : ?>
                            <tr>
                                <td class="text-center">
                                    <input class="cag-cb" type="checkbox" name="cid[]" value="<?php echo (int) $a->id; ?>">
                                </td>
                                <td><?php echo $this->escape($a->title); ?></td>
                                <td class="text-center">
                                    <?php echo $a->state ? '<span class="badge bg-success">' . Text::_('JPUBLISHED') . '</span>'
                                        : '<span class="badge bg-secondary">' . Text::_('JUNPUBLISHED') . '</span>'; ?>
                                </td>
                                <td><?php echo $this->escape($a->created); ?></td>
                                <td class="text-center"><?php echo (int) $a->id; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="col-md-4">
                    <fieldset class="options-form">
                        <legend><?php echo Text::_('COM_CONTENT_API_GRABBER_PULL_TARGET'); ?></legend>
                        <?php foreach (['catid', 'created_by', 'state'] as $field) : ?>
                            <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel($field); ?></div>
                                <div class="controls"><?php echo $this->form->getInput($field); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </fieldset>

                    <button type="button" class="btn btn-primary w-100"
                            onclick="Joomla.submitform('remote.pull', document.getElementById('adminForm'));">
                        <span class="icon-download" aria-hidden="true"></span>
                        <?php echo Text::_('COM_CONTENT_API_GRABBER_PULL_SELECTED'); ?>
                    </button>
                </div>
            </div>

            <input type="hidden" name="source_id" value="<?php echo (int) $this->sourceId; ?>">
            <input type="hidden" name="task" value="">
            <?php echo HTMLHelper::_('form.token'); ?>
        </form>
    <?php endif; ?>
<?php endif; ?>
