<?php
/**
 * @package     com_content_api_grabber
 * @subpackage  Templates
 *
 * @var  \Nickpsal\Component\ContentApiGrabber\Administrator\View\Source\HtmlView  $this
 * @copyright   (C) 2026 Nick Psaltakis
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
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

                <div class="control-group">
                    <div class="control-label"></div>
                    <div class="controls">
                        <button type="button" class="btn btn-secondary" id="cag-test-btn" onclick="cagTestConnection()">
                            <span class="icon-power-cord" aria-hidden="true"></span>
                            <?php echo Text::_('COM_CONTENT_API_GRABBER_TEST_CONNECTION'); ?>
                        </button>
                        <span id="cag-test-result" class="ms-2"></span>
                    </div>
                </div>
            </fieldset>
        </div>
    </div>

    <input type="hidden" name="task" value="">
    <?php echo $this->form->getInput('id'); ?>
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

<script>
function cagTestConnection() {
    var form = document.getElementById('source-form');
    var btn  = document.getElementById('cag-test-btn');
    var out  = document.getElementById('cag-test-result');
    var fd   = new FormData();
    var url  = form.querySelector('[name="jform[url]"]');
    var tok  = form.querySelector('[name="jform[token]"]');
    var idf  = form.querySelector('[name="jform[id]"]');
    fd.append('url', url ? url.value : '');
    fd.append('token', tok ? tok.value : '');
    fd.append('id', idf ? idf.value : '0');
    // Forward the CSRF token (Joomla renders it as a 32-hex named hidden input with value 1).
    form.querySelectorAll('input[type="hidden"]').forEach(function (i) {
        if (/^[0-9a-f]{32}$/.test(i.name)) { fd.append(i.name, i.value); }
    });
    out.className = 'ms-2 text-muted';
    out.textContent = '…';
    btn.disabled = true;
    fetch('index.php?option=com_content_api_grabber&task=source.testConnection', {
        method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(function (r) { return r.json(); })
        .then(function (j) {
            out.className = 'ms-2 ' + (j.ok ? 'text-success' : 'text-danger');
            out.textContent = (j.ok ? '✓ ' : '✗ ') + j.message;
        })
        .catch(function (e) { out.className = 'ms-2 text-danger'; out.textContent = 'Error: ' + e; })
        .finally(function () { btn.disabled = false; });
}
</script>
