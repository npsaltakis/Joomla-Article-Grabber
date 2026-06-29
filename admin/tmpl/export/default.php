<?php
/**
 * @package     Com_ContentApiGrabber
 * @subpackage  Templates
 *
 * @var  \Nickpsal\Component\ContentApiGrabber\Administrator\View\Export\HtmlView  $this
 * @copyright   (C) 2026 Nick Psaltakis
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('form.validate');
?>
<form action="<?php echo Route::_('index.php?option=com_content_api_grabber&view=export'); ?>"
      method="post" name="adminForm" id="adminForm">

    <div class="row">
        <div class="col-md-12">
            <p class="text-muted"><?php echo Text::_('COM_CONTENT_API_GRABBER_EXPORT_DESC'); ?></p>

            <?php if (empty($this->articles)) : ?>
                <div class="alert alert-info"><?php echo Text::_('COM_CONTENT_API_GRABBER_NO_ARTICLES'); ?></div>
            <?php else : ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th style="width:1%"></th>
                            <th><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
                            <th style="width:20%"><?php echo Text::_('JCATEGORY'); ?></th>
                            <th style="width:15%"><?php echo Text::_('JDATE'); ?></th>
                            <th style="width:10%"></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($this->articles as $i => $article) : ?>
                        <tr>
                            <td>
                                <input type="radio" name="id" value="<?php echo (int) $article->id; ?>"
                                       <?php echo $i === 0 ? 'checked' : ''; ?>>
                            </td>
                            <td><?php echo $this->escape($article->title); ?></td>
                            <td><?php echo $this->escape($article->category); ?></td>
                            <td><?php echo HTMLHelper::_('date', $article->created, Text::_('DATE_FORMAT_LC4')); ?></td>
                            <td>
                                <a class="btn btn-sm btn-primary"
                                   href="<?php echo Route::_('index.php?option=com_content_api_grabber&task=export.download&id=' . (int) $article->id . '&' . Factory::getApplication()->getFormToken() . '=1'); ?>">
                                    <span class="icon-download" aria-hidden="true"></span>
                                    <?php echo Text::_('COM_CONTENT_API_GRABBER_EXPORT_DOWNLOAD'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
