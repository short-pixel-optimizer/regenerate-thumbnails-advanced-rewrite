

<form method="post" name="frm_rta_image_sizes" id="frm_rta_image_sizes" class="frm_rta" enctype="multipart/form-data">
<section class='period'>
  <div class='container'>
    <div class='option'>
      <label><?php _e('Regenerate period:','regenerate-thumbnails-advanced'); ?></label>
      <select name='period' class="timeDropdownSelect" id="">
          <option value="0"><?php _e('All','regenerate-thumbnails-advanced'); ?></option>
          <option value="1"><?php _e('Past Day','regenerate-thumbnails-advanced'); ?></option>
          <option value="2"><?php _e('Past Week','regenerate-thumbnails-advanced'); ?></option>
          <option value="3"><?php _e('Past Month','regenerate-thumbnails-advanced'); ?></option>
          <option value="4"><?php _e('Past 3 Months','regenerate-thumbnails-advanced'); ?></option>
          <option value="5"><?php _e('Past 6 Months','regenerate-thumbnails-advanced'); ?></option>
          <option value="6"><?php _e('Past Year','regenerate-thumbnails-advanced'); ?></option>
      </select>
    </div>
    <div class='option'>
      <label for='regenonly_featured'><?php _e(sprintf('Regenerate %sonly%s Featured Images', '<strong>','</strong>'),'regenerate-thumbnails-advanced');  ?></label>
      <input type='checkbox' id='regenonly_featured' name="regenonly_featured" value="1">
    </div>
  </div>
</section>

<section class='extra_options'>
  <div class='container'>
    <div class='cleanup-wrapper'>
      <h4><?php _e('Clean-up options', 'regenerate-thumbnails-advanced') ?></h4>
      <div class='option'>
          <label for="del_associated_thumbs"><?php _e('Delete Unused Thumbnails','regenerate-thumbnails-advanced'); ?></label>
          <span><input type="checkbox" name="del_associated_thumbs" id="del_associated_thumbs" value="1" /> </span>
          <span class='note'><?php _e('This option will remove thumbnails not selected in the settings. Good for stale thumbnails, but be sure they are not in use.  ','regenerate-thumbnails-advanced'); ?></span>
      </div>
      <div class='option'>
          <label for="del_leftover_metadata"><?php _e('Delete Leftover Image Metadata','regenerate-thumbnails-advanced'); ?></label>
          <span><input type="checkbox" name="del_leftover_metadata" id="del_leftover_metadata" value="1" /> </span>
          <span class='note'><?php _e('Delete all the metadata associated with missing (non-existing) images. Keep in mind there is no undo!','regenerate-thumbnails-advanced'); ?></span>
      </div>
    </div>
  </div>
</section>

<section class='form_controls'>
  <div class='container'>
    <button  type='submit' disabled class='rta_regenerate disabled'><?php _e('Regenerate', 'regenerate-thumbnails-advanced'); ?></button>
    <p class='save_note rta_hidden'><?php _e('Save your settings first','regenerate-thumbnails-advanced'); ?></p>
  </div>
</section>
</form>
