<?php //if ( isset($message)  && $message!="" ) { echo $message; }?>
<?php //echo wp_get_attachment_image( 8, 'rta_featured_image' );

?>
<div class="wrap rta-admin">
    <h2><?php _e('Regenerate Thumbnails','regenerate-thumbnails-advanced'); ?></h2>

<form method="post" name="frm_rta_image_sizes" id="frm_rta_image_sizes" class="frm_rta" enctype="multipart/form-data">

<section class='image_sizes'>

  <div class='container'>
  <h4><?php _e('Image Sizes','regenerate-thumbnails-advanced'); ?>   <input type="button" name="btn_add_image_size" id="btn_add_image_size" class="btn_add_more" value="<?php _e('Add New Size','regenerate-thumbnails-advanced'); ?>" onclick="javascript:rta_add_image_size_row();" /></h4>

      <div class='table imagesizes'>

              <div class='header flex'>
                      <?php
                      $image_sizes = $view->custom_image_sizes;
                      if(count($image_sizes) > 0 && sizeof($image_sizes['name'])>0){ ?>
                          <span><b><?php _e('Image Size Public Name','regenerate-thumbnails-advanced'); ?></b></span>
                          <span><b><?php _e('Max. Width','regenerate-thumbnails-advanced'); ?></b></span>
                          <span><b><?php _e('Max. Height','regenerate-thumbnails-advanced'); ?></b></span>
                          <span><b><?php _e('Cropping','regenerate-thumbnails-advanced'); ?></b></span>
                          <span><b><?php _e('Image Size Name','regenerate-thumbnails-advanced'); ?></b></span>
                          <span>&nbsp;<span>
                </div>

                      <?php
                      for($i=0;$i<count($image_sizes['name']);$i++){ ?>

                      <?php $rowid = uniqid();

                      ?>
                      <div id="<?php echo $rowid;?>" class='row flex'>

                            <span><input type="text" name="image_sizes[pname][]" class="image_sizes_pname" value="<?php echo $image_sizes['pname'][$i];?>" placeholder="<?php _e('Image Size Public Name','regenerate-thumbnails-advanced'); ?>" /></span>

                            <span><input type="number" name="image_sizes[width][]" class="image_sizes_width tiny" value="<?php echo $image_sizes['width'][$i];?>" placeholder="<?php _e('Width','regenerate-thumbnails-advanced'); ?>" /> px </span>

                            <span> <input type="number" name="image_sizes[height][]" class="image_sizes_height tiny" value="<?php echo $image_sizes['height'][$i];?>" placeholder="<?php _e('Height','regenerate-thumbnails-advanced'); ?>" /> px </span>

                            <span>  <select name="image_sizes[cropping][]" class="image_sizes_cropping">
                              <?php echo $view->cropOptions($image_sizes['cropping'][$i]); ?>
                              </select>
                            </span>

                            <span>
                              <input type="text" readonly name="image_sizes[name][]" class="image_sizes_name" value="<?php echo $image_sizes['name'][$i];?>" placeholder="<?php _e('Image Size name','regenerate-thumbnails-advanced'); ?>" />
                            </span>
                            <span>
                              <input type="button" name="btn_remove_image_size_row" value="<?php _e('Remove','regenerate-thumbnails-advanced'); ?>" class="btn_remove_row" />
                            </span>
                      </div> <!-- row -->
                      <?php }}?>
                      <div class='row proto'>
                            <span><input type="text" name="image_sizes[pname][]" class="image_sizes_pname" value="" placeholder="<?php _e('Image Size Public Name','regenerate-thumbnails-advanced'); ?>" /></span>
                            <span><input type="number" name="image_sizes[width][]" class="image_sizes_width tiny" value="" placeholder="<?php _e('Width','regenerate-thumbnails-advanced'); ?>" /> px </span>
                            <span> <input type="number" name="image_sizes[height][]" class="image_sizes_height tiny" value="" placeholder="<?php _e('Height','regenerate-thumbnails-advanced'); ?>" /> px </span>
                            <span><select name="image_sizes[cropping][]" class="image_sizes_cropping">
                              <?php echo $view->cropOptions(); ?>
                              </select>
                            </span>
                            <span>
                              <input type="text" readonly name="image_sizes[name][]" class="image_sizes_name" value="" placeholder="<?php _e('Image Size name','regenerate-thumbnails-advanced'); ?>" />
                            </span>
                            <span>
                              <input type="button" name="btn_remove_image_size_row" value="<?php _e('Remove','regenerate-thumbnails-advanced'); ?>" class="btn_remove_row" />
                            </span>
                      </div> <!-- row -->
          </div> <!-- table -->

        </div>
</section>

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
  </div>
</section>

<section class='thumbnail_select'>
  <div class='container'>
      <div class='option'>
        <label for='regenonly_featured'><?php _e(sprintf('Regenerate %sonly%s Featured Images', '<strong>','</strong>'),'regenerate-thumbnails-advanced');  ?></label>
        <input type='checkbox' id='regenonly_featured' name="regenonly_featured" value="1">
      </div>
      <div class='option'>
        <label><?php _e('Regenerate selected thumbnails:') ?></label>
        <div class='checkbox-list'>
            <?php echo $view->generateImageSizeOptions(); ?>
        </div>
        <div class='select-options'><span class='select' data-action='select' data-target='regenerate_sizes' >Select All</span>
          <span class='deselect' data-action='deselect' data-target='regenerate_sizes'>Deselect All</span>
      </div> <!-- option -->
  </div>

</section>

<section class='extra_options'>
  <div class='container'>
    <div class='option'>
      <label><?php _e('Default JPEG Quality','regenerate-thumbnails-advanced'); ?></label>
      <input type="number" name="jpeg_quality" id="jpeg_quality" value="<?php echo $view->jpeg_quality ?>" onchange="javascript: rta_save_image_sizes();" />
    </div>
    <div class='option'>
        <label><?php _e('Delete Stale Thumbnails','regenerate-thumbnails-advanced'); ?></label>
        <span><input type="checkbox" name="del_associated_thumbs" id="del_associated_thumbs" value="YES" /> </span>
        <span class='note'><?php _e('Delete all the thumbnails associated with images but not defined in the image\'s metadata. Useful to remove stale images but keep in mind there is no undo!','regenerate-thumbnails-advanced'); ?></span>
    </div>
    <div class='option'>
        <label><?php _e('Delete Leftover Image Metadata','regenerate-thumbnails-advanced'); ?></label>
        <span><input type="checkbox" name="del_leftover_metadata" id="del_leftover_metadata" value="YES" /> </span>
        <span class='note'><?php _e('Delete all the metadata associated with missing images. Keep in mind there is no undo!','regenerate-thumbnails-advanced'); ?></span>
    </div>
  </div>
</section>

<section class='form_controls'>
  <div class='container'>
    <button  type='submit' disabled class='rta_regenerate disabled'>Save and Regenerate</button>
  </div>
</section>

<section class='regenerate'>
  <div class='container'>
    <div class="rta_wait_loader">Please wait...</div>

    <div class="cpbParent rta_progress rta_hidden">
        <svg class="CircularProgressbar  " viewBox="0 0 100 100">
            <path class="CircularProgressbar-trail" d="
                M 50,50
                m 0,-46
                a 46,46 0 1 1 0,92
                a 46,46 0 1 1 0,-92
                " stroke-width="8" fill-opacity="0">
            </path>
            <path class="CircularProgressbar-path" d="
                M 50,50
                m 0,-46
                a 46,46 0 1 1 0,92
                a 46,46 0 1 1 0,-92
                " stroke-width="8" fill-opacity="0" style="stroke-dasharray: 289.027px, 289.027px; stroke-dashoffset: 289.027px;">
            </path>
            <text class="CircularProgressbar-text" x="50" y="50">0%</text>
        </svg>
        <div class="images">
            <h5>Regenerated image:</h5>
            <img src="" alt="">
        </div>
    </div>

    <a href="#" onclick="javascript: return rta_show_errorbox();" class="rta_error_link rta_hidden">There were some errors, click for details</a>
    <div class="listContainer rta_error_box row rta_hidden">
        <div class="statuslist col-sm-6">
            <h4 class="listTitle">Error(s)</h4>
            <ul class="list-group">
            </ul>
        </div>
    </div>
  </div>  <!-- container -->
</section>


</form>
</div> <!-- rta admin wrap.
