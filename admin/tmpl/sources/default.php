<?php
/**
 * @package     com_content_api_grabber
 * @subpackage  Templates
 *
 * @var  \Nickpsal\Component\ContentApiGrabber\Administrator\View\Sources\HtmlView  $this
 * @copyright   (C) 2026 Nick Psaltakis
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<form action="<?php echo Route::_('index.php?option=com_content_api_grabber&view=sources'); ?>"
      method="post" name="adminForm" id="adminForm">

    <div class="row">
        <div class="col-md-12">
            <p class="text-muted"><?php echo Text::_('COM_CONTENT_API_GRABBER_SOURCES_DESC'); ?></p>

            <?php if (empty($this->items)) : ?>
                <div class="alert alert-info"><?php echo Text::_('COM_CONTENT_API_GRABBER_NO_SOURCES'); ?></div>
            <?php else : ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <td style="width:1%" class="text-center">
                                <?php echo HTMLHelper::_('grid.checkall'); ?>
                            </td>
                            <th style="width:1%" class="text-center"><?php echo Text::_('JSTATUS'); ?></th>
                            <th><?php echo Text::_('COM_CONTENT_API_GRABBER_FIELD_SOURCE_NAME_LABEL'); ?></th>
                            <th><?php echo Text::_('COM_CONTENT_API_GRABBER_FIELD_SOURCE_URL_LABEL'); ?></th>
                            <th class="text-center"><?php echo Text::_('JGRID_HEADING_ID'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($this->items as $i => $item) : ?>
                        <tr>
                            <td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, $item->id); ?></td>
                            <td class="text-center">
                                <?php if ($item->published) : ?>
                                    <span class="icon-publish text-success" aria-hidden="true"></span>
                                <?php else : ?>
                                    <span class="icon-unpublish text-danger" aria-hidden="true"></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo Route::_('index.php?option=com_content_api_grabber&view=source&layout=edit&id=' . (int) $item->id); ?>">
                                    <?php echo $this->escape($item->name); ?>
                                </a>
                            </td>
                            <td><?php echo $this->escape($item->url); ?></td>
                            <td class="text-center"><?php echo (int) $item->id; ?></td>
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
