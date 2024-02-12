<?php
namespace ReThumbAdvanced;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

?>
<?php  $active = ($this->isFeatureActive()) ? '' : 'disabled'; ?>

<form method="post" name="rtaform_process" id="rtaform_process" class="frm_rta" enctype="multipart/form-data">

<?php do_action('rta/ui/start-options'); ?>

<section class='period'>
  <div class='container'>

    <div class='option'>
      <label for='regenonly_featured'><?php printf(__('Regenerate %sonly%s Featured Images','regenerate-thumbnails-advanced'), '<strong>','</strong>');  ?></label>
      <input type='checkbox' id='regenonly_featured' name="regenonly_featured" value="1">
    </div>

    <div class='option'>
      <label><?php _e('Regenerate period:','regenerate-thumbnails-advanced'); ?></label>

      <?php echo $this->getProSnippet(); ?>
      <ul class='period-list'>

          <?php
          foreach($view->periods as $index => $period)
          {
            if ($period->isCustom())
              continue;

            $disabled = (! $period->isAvailable()) ? 'disabled="disabled"' : false;


            $queryDate = $period->getQueryDate();
            $startstamp = $queryDate['startstamp'];
            $endstamp = $queryDate['endstamp'];

            $checked = ($period->period_id === PERIODS::PERIOD_ALL) ? 'checked="checked"' : '';

             echo "<li><label>
             <input data-start='$startstamp' data-end='$endstamp' type='radio' name='period' value='" . $period->period_id . "' $checked $disabled>" . $period->period_name .
             '</label></li>';
          }
          ?>

      </ul>
    </div>


    <?php // Custom
    $periodClass = $this->getPeriodsClass();
    $period = $periodClass::getPeriod(Periods::PERIOD_CUSTOM);
    $disabled = (! $period->isAvailable()) ? 'disabled="disabled"' : false; ?>


    <div class='option custom_date'>
        <div> <label><?php _e('Start date', 'regenerate-thumbnails-advanced'); ?></label>
          <input type='date' name='start_date' value='' <?php echo $disabled ?> >
          <?php echo $this->getProSnippet(); ?>

        </div>
        <div> <label><?php _e('End date', 'regenerate-thumbnails-advanced'); ?></label>
          <input type='date' name='end_date' value='<?php echo date('Y-m-d', time()) ?>' <?php echo $disabled ?>>
          <?php echo $this->getProSnippet(); ?>

        </div>

    </div>


  </div> <!-- container -->

</section>


<section class='extra_options'>
  <div class='container'>
    <div class='toggle-window' data-window='advanced-window'>
        <h4><?php _e('Advanced options', 'regenerate-thumbnails-advanced') ?></h4>
        <span class='dashicons dashicons-arrow-up'>&nbsp;</span>
    </div>
    <div class='cleanup-wrapper window-down' id='advanced-window'>
      <div class='option'>

      </div>


      <div class='option'>
          <label>
            <input type='checkbox' name='process_clean_metadata' value='1' <?php echo $active ?> />
            <span><?php _e('Clean unknown metadata', 'regenerate-thumbnails-advanced'); ?></span>
         </label>

         <div class='note'>
           <?php echo $this->getProSnippet();

           ?>

           <p><?php _e('Clean old metadata not defined in system sizes. Use after removing plugins / themes with old definitions. Will not remove thumbnails from disk', 'regenerate-thumbnails-advanced') ?></p>
         </div>
      </div>


      <div class='option'>
          <label for="del_associated_thumbs">
            <input type="checkbox" name="del_associated_thumbs" id="del_associated_thumbs" value="1" <?php echo $active ?> />
            <span><?php _e('Delete Unselected Thumbnails','regenerate-thumbnails-advanced'); ?></span>
          </label>
          <div class='note'>
            <?php echo $this->getProSnippet(); ?>

            <p><?php _e('Delete thumbnails and metadata not selected in the settings. Will delete thumbnails from disk - be sure they are not in use.  ','regenerate-thumbnails-advanced'); ?></p></div>
      </div>

      <div class='warning inline rta-notice rta_hidden' id='warn-delete-items'>
      <div class='icon dashicons-info dashicons'></div>

      <p><?php _e('Not selected thumbnails will be removed from your site. Check your settings if this is intentional.'); ?></p>

      <p class='small'><?php _e('Regenerate Thumbnails Advanced will not prevent new media uploads from generating removed sizes', 'regenerate-thumbnails-advanced', 'regenerate-thumbnails-advanced'); ?></span></p>
      </div>

      <div class='option'>
          <label for="del_leftover_metadata">
            <input type="checkbox" name="del_leftover_metadata" id="del_leftover_metadata" value="1" <?php echo $active ?> />
            <span><?php _e('Remove non-existent images','regenerate-thumbnails-advanced'); ?></span>
          </label>
          <div class='note'>
            <?php echo $this->getProSnippet(); ?>

            <p><?php _e('If the main image does not exist, removes this image, thumbnails and metadata.','regenerate-thumbnails-advanced'); ?>
            <?php _e('For removing images that are gone on disk, but still in media library.', 'regenerate-thumbnails-advanced'); ?></p>
        </div>
      </div>

      <?php if (RTA()->env()->plugin_active('shortpixel'))
      { ?>
      <div class="option">
          <label for="optimize_shortpixel" >
              <input type="checkbox" id="optimize_shortpixel" name="optimize_shortpixel" value="1" <?php echo $active ?>>
              <span><?php _e('Optimize regenerated thumbnails with ShortPixel', 'regenerate-thumbnails-advanced'); ?></span>
          </label>
          <div class='note'>
            <?php echo $this->getProSnippet(); ?>
            <p><?php _e('After creating new thumbnails add them to the Shortpixel queue for processing', 'regenerate-thumbnails-advanced'); ?></p>
          </div>
        </div>
      <?php } ?>

      <div class='readmore'>
        <a href="https://help.shortpixel.com/article/233-quick-guide-to-using-regenerate-thumbnails-advanced-settings" target="_blank">
            <span class="dashicons dashicons-editor-help"></span>Read more</a>
      </div>
    </div>



  </div> <!-- container -->
</section>

<?php do_action('rta/ui/end-options'); ?>

<section class='form_controls regenerate-button'>
  <div class='container'>
    <button type='submit' disabled class='rta_regenerate disabled'><span class='dashicons dashicons-controls-play'>&nbsp;</span> <?php _e('Regenerate', 'regenerate-thumbnails-advanced'); ?></button>
    <p class='save_note rta_hidden'><?php _e('Save your settings first','regenerate-thumbnails-advanced'); ?></p>
  </div>
</section>
</form>
