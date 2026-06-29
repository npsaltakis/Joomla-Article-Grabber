<?php
/**
 * @package     com_content_api_grabber
 * @subpackage  Templates
 *
 * @var  \Nickpsal\Component\ContentApiGrabber\Administrator\View\Remote\HtmlView  $this
 * @copyright   (C) 2026 Nick Psaltakis
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
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

<!-- Source + filter bar (all GET params in one form) -->
<form action="<?php echo Route::_('index.php?option=com_content_api_grabber&view=remote'); ?>" method="get" class="mb-3" id="cagFilterForm">
    <input type="hidden" name="option" value="com_content_api_grabber">
    <input type="hidden" name="view" value="remote">
    <input type="hidden" name="limit" value="<?php echo (int) $this->limit; ?>">
    <input type="hidden" name="offset" value="0">

    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label for="source_id" class="form-label"><?php echo Text::_('COM_CONTENT_API_GRABBER_SELECT_SOURCE'); ?></label>
            <select name="source_id" id="source_id" class="form-select"
                    onchange="['cag_cat_id','cag_search'].forEach(function(id){var el=document.getElementById(id);if(el)el.value='';});this.form.submit();">
                <option value="0">&mdash;</option>
                <?php foreach ($this->sources as $s) : ?>
                    <option value="<?php echo (int) $s->id; ?>" <?php echo $this->sourceId === (int) $s->id ? 'selected' : ''; ?>>
                        <?php echo $this->escape($s->name) . ' (' . $this->escape($s->url) . ')'; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if ($this->sourceId && !empty($this->remoteCategories)) : ?>
        <div class="col-md-3">
            <label for="cag_cat_id" class="form-label"><?php echo Text::_('COM_CONTENT_API_GRABBER_FILTER_CATEGORY'); ?></label>
            <select name="cat_id" id="cag_cat_id" class="form-select" onchange="this.form.submit()">
                <option value="0"><?php echo Text::_('COM_CONTENT_API_GRABBER_FILTER_ALL_CATEGORIES'); ?></option>
                <?php foreach ($this->remoteCategories as $cat) : ?>
                    <option value="<?php echo (int) $cat->id; ?>" <?php echo $this->catId === (int) $cat->id ? 'selected' : ''; ?>>
                        <?php echo $this->escape($cat->title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php else : ?>
            <input type="hidden" name="cat_id" id="cag_cat_id" value="<?php echo (int) $this->catId; ?>">
        <?php endif; ?>

        <?php if ($this->sourceId) : ?>
        <div class="col-md-4">
            <label for="cag_search" class="form-label visually-hidden"><?php echo Text::_('JSEARCH_FILTER'); ?></label>
            <div class="input-group">
                <input type="text" name="search" id="cag_search" class="form-control"
                       placeholder="<?php echo $this->escape(Text::_('COM_CONTENT_API_GRABBER_FILTER_SEARCH_PLACEHOLDER')); ?>"
                       value="<?php echo $this->escape($this->search); ?>">
                <button class="btn btn-outline-secondary" type="submit">
                    <span class="icon-search" aria-hidden="true"></span>
                </button>
                <?php if ($this->search !== '') : ?>
                    <a class="btn btn-outline-secondary"
                       href="<?php echo Route::_('index.php?option=com_content_api_grabber&view=remote&source_id=' . (int) $this->sourceId . '&cat_id=' . (int) $this->catId . '&limit=' . (int) $this->limit); ?>"
                       title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>">
                        <span class="icon-times" aria-hidden="true"></span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
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

                    <?php
                    $pgBase = 'index.php?option=com_content_api_grabber&view=remote'
                        . '&source_id=' . (int) $this->sourceId
                        . '&limit=' . (int) $this->limit
                        . '&cat_id=' . (int) $this->catId
                        . ($this->search !== '' ? '&search=' . urlencode($this->search) : '');
                    $from   = $this->offset + 1;
                    $to     = $this->offset + \count($this->articles);
                    ?>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <small class="text-muted">
                            <?php echo Text::sprintf('COM_CONTENT_API_GRABBER_SHOWING', $from, $to, $this->total !== null ? $this->total : '?'); ?>
                        </small>
                        <div class="btn-group">
                            <?php if ($this->offset > 0) : ?>
                                <a class="btn btn-outline-secondary btn-sm"
                                   href="<?php echo Route::_($pgBase . '&offset=' . max(0, $this->offset - $this->limit)); ?>">
                                    <span class="icon-arrow-left" aria-hidden="true"></span> <?php echo Text::_('JPREV'); ?>
                                </a>
                            <?php endif; ?>
                            <?php if ($this->hasNext) : ?>
                                <a class="btn btn-outline-secondary btn-sm"
                                   href="<?php echo Route::_($pgBase . '&offset=' . ($this->offset + $this->limit)); ?>">
                                    <?php echo Text::_('JNEXT'); ?> <span class="icon-arrow-right" aria-hidden="true"></span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
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
                        <hr class="my-2">
                        <?php foreach (['featured', 'skip_duplicates'] as $field) : ?>
                            <div class="control-group">
                                <div class="controls">
                                    <?php echo $this->form->getInput($field); ?>
                                    <?php echo $this->form->getLabel($field); ?>
                                </div>
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
