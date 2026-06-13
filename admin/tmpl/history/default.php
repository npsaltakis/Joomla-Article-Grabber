<?php
/**
 * @package     com_content_api_grabber
 * @subpackage  Templates
 *
 * @var  \Nickpsal\Component\ContentApiGrabber\Administrator\View\History\HtmlView  $this
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<form action="<?php echo Route::_('index.php?option=com_content_api_grabber&view=history'); ?>"
      method="post" name="adminForm" id="adminForm">

    <div class="row">
        <div class="col-md-12">
            <p class="text-muted"><?php echo Text::_('COM_CONTENT_API_GRABBER_HISTORY_DESC'); ?></p>

            <?php if (empty($this->items)) : ?>
                <div class="alert alert-info"><?php echo Text::_('COM_CONTENT_API_GRABBER_HISTORY_EMPTY'); ?></div>
            <?php else : ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <td style="width:1%" class="text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></td>
                            <th><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
                            <th style="width:22%"><?php echo Text::_('COM_CONTENT_API_GRABBER_HISTORY_SOURCE'); ?></th>
                            <th style="width:12%"><?php echo Text::_('COM_CONTENT_API_GRABBER_FIELD_CREATED_BY_LABEL'); ?></th>
                            <th style="width:8%" class="text-center"><?php echo Text::_('COM_CONTENT_API_GRABBER_HISTORY_IMAGES'); ?></th>
                            <th style="width:15%"><?php echo Text::_('JDATE'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($this->items as $i => $item) : ?>
                        <tr>
                            <td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, $item->id); ?></td>
                            <td>
                                <?php if ($item->target_article_id && $item->article_state !== null) : ?>
                                    <a href="<?php echo Route::_('index.php?option=com_content&task=article.edit&id=' . (int) $item->target_article_id); ?>">
                                        <?php echo $this->escape($item->title); ?>
                                    </a>
                                    <?php echo $item->article_state == 1
                                        ? ' <span class="badge bg-success">' . Text::_('JPUBLISHED') . '</span>'
                                        : ' <span class="badge bg-secondary">' . Text::_('JUNPUBLISHED') . '</span>'; ?>
                                <?php else : ?>
                                    <?php echo $this->escape($item->title); ?>
                                    <span class="badge bg-warning text-dark"><?php echo Text::_('COM_CONTENT_API_GRABBER_HISTORY_ARTICLE_GONE'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($item->source_url) : ?>
                                    <span class="badge bg-info"><?php echo Text::_('COM_CONTENT_API_GRABBER_HISTORY_REST'); ?></span>
                                    <small><?php echo $this->escape(preg_replace('#^https?://#', '', $item->source_url)); ?>
                                        <?php echo $item->source_id ? '#' . (int) $item->source_id : ''; ?></small>
                                <?php else : ?>
                                    <span class="badge bg-secondary"><?php echo Text::_('COM_CONTENT_API_GRABBER_HISTORY_XML'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $this->escape($item->author_name ?: '-'); ?></td>
                            <td class="text-center">
                                <span class="text-success"><?php echo (int) $item->images_ok; ?></span>
                                <?php if ((int) $item->images_failed > 0) : ?>
                                    / <span class="text-danger"><?php echo (int) $item->images_failed; ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo HTMLHelper::_('date', $item->created, Text::_('DATE_FORMAT_LC4')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php echo $this->pagination->getListFooter(); ?>
            <?php endif; ?>
        </div>
    </div>

    <input type="hidden" name="task" value="">
    <input type="hidden" name="boxchecked" value="0">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
