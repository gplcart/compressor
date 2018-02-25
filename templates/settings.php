<?php

/**
 * @package Compressor
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2018, Iurii Makukh <gplcart.software@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0-or-later
 * @var $this \gplcart\core\controllers\backend\Controller
 * To see available variables <?php print_r(get_defined_vars()); ?>
 */
?>
<form method="post" class="form-horizontal">
  <input type="hidden" name="token" value="<?php echo $_token; ?>">
  <div class="form-group">
    <label class="col-md-2 control-label"><?php echo $this->text('Compress JS'); ?></label>
    <div class="col-md-4">
      <div class="btn-group" data-toggle="buttons">
        <label class="btn btn-default<?php echo empty($settings['status_js']) ? '' : ' active'; ?>">
          <input name="settings[status_js]" type="radio" autocomplete="off" value="1"<?php echo empty($settings['status_js']) ? '' : ' checked'; ?>><?php echo $this->text('Enabled'); ?>
        </label>
        <label class="btn btn-default<?php echo empty($settings['status_js']) ? ' active' : ''; ?>">
          <input name="settings[status_js]" type="radio" autocomplete="off" value="0"<?php echo empty($settings['status_js']) ? ' checked' : ''; ?>><?php echo $this->text('Disabled'); ?>
        </label>
      </div>
      <div class="help-block">
          <?php echo $this->text('If enabled, then JS files will be merged into several big files. It reduces number of HTTP queries and improves site loading speed'); ?>
      </div>
    </div>
  </div>
  <div class="form-group">
    <label class="col-md-2 control-label"><?php echo $this->text('Compress CSS'); ?></label>
    <div class="col-md-4">
      <div class="btn-group" data-toggle="buttons">
        <label class="btn btn-default<?php echo empty($settings['status_css']) ? '' : ' active'; ?>">
          <input name="settings[status_css]" type="radio" autocomplete="off" value="1"<?php echo empty($settings['status_css']) ? '' : ' checked'; ?>><?php echo $this->text('Enabled'); ?>
        </label>
        <label class="btn btn-default<?php echo empty($settings['status_css']) ? ' active' : ''; ?>">
          <input name="settings[status_css]" type="radio" autocomplete="off" value="0"<?php echo empty($settings['status_css']) ? ' checked' : ''; ?>><?php echo $this->text('Disabled'); ?>
        </label>
      </div>
      <div class="help-block">
          <?php echo $this->text('If enabled, then CSS files will be minified and merged into one big file. It reduces number of HTTP queries and improves site loading speed'); ?>
      </div>
    </div>
  </div>
  <div class="form-group">
    <div class="col-md-4 col-md-offset-2">
      <div class="btn-toolbar">
        <a href="<?php echo $this->url("admin/module/list"); ?>" class="btn btn-default"><?php echo $this->text("Cancel"); ?></a>
        <button class="btn btn-default" name="clear_cache" value="1" onclick="return confirm('<?php echo $this->text('Are you sure?'); ?>');">
            <?php echo $this->text('Delete cached JS and CSS files'); ?>
        </button>
        <button class="btn btn-default save" name="save" value="1"><?php echo $this->text("Save"); ?></button>
      </div>
    </div>
  </div>
</form>