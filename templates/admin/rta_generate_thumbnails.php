<?php if ( $message!="" ) { echo $message; }?>
<?php //echo wp_get_attachment_image( 8, 'rta_featured_image' );?>
<div class="wrap">
    <h2><?php _e('Regenerate Thumbnails','regenerate-thumbnails-advanced'); ?></h2>
    <br>
    <table class="wp-list-table widefat fixed" cellspacing="0">
        <thead>
            <tr>
                <th scope="col" class="manage-column" style=""><?php _e('Keep the page open to regenerate the images. If you leave or close the page, you can continue later.','regenerate-thumbnails-advanced'); ?></th>
            </tr>
	</thead>
	<tbody id="the-list">
            <tr>
                <td>
                    <div id="rtaContent">
                        <table width="100%" id="rta_regenerate_thumbs_main_container">
                            <tr>
                                <td width="120"><?php _e('Regenerate period:','regenerate-thumbnails-advanced'); ?></td>
                                <td>
                                    <select class="timeDropdownSelect" id="">
                                        <option value="0"><?php _e('All','regenerate-thumbnails-advanced'); ?></option>
                                        <option value="1"><?php _e('Past Day','regenerate-thumbnails-advanced'); ?></option>
                                        <option value="2"><?php _e('Past Week','regenerate-thumbnails-advanced'); ?></option>
                                        <option value="3"><?php _e('Past Month','regenerate-thumbnails-advanced'); ?></option>
                                        <option value="4"><?php _e('Past 3 Months','regenerate-thumbnails-advanced'); ?></option>
                                        <option value="5"><?php _e('Past 6 Months','regenerate-thumbnails-advanced'); ?></option>
                                        <option value="6"><?php _e('Past Year','regenerate-thumbnails-advanced'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr style="display:none;">
                               <td><?php _e('Default thumbnail size:','regenerate-thumbnails-advanced'); ?></td>
                               <td>
                                   <input type="number" class="rta_text_field_60" name="default_img_w" id="default_img_w" value="<?php echo $default_thumb_w;?>" placeholder="<?php _e('Width','regenerate-thumbnails-advanced'); ?>" />px&nbsp;&nbsp;<b>X</b>&nbsp;&nbsp;
                                   <input type="number" class="rta_text_field_60" name="default_img_h" id="default_img_h" value="<?php echo $default_thumb_h;?>" placeholder="<?php _e('Height','regenerate-thumbnails-advanced'); ?>" />px&nbsp;&nbsp;&nbsp;&nbsp;<?php /* ?><small>Default thumbnail size is same as WordPress set thumbnail size on this page: /wp-admin/options-media.php</small><?php */?>
                               </td>
                            </tr>
                            <tr>
                               <td><?php _e('Exact thumb size for featured images:','regenerate-thumbnails-advanced'); ?></td>
                               <td>
                                   <input type="number" class="rta_text_field_60" name="featured_img_w" id="featured_img_w" value="" placeholder="<?php _e('Width','regenerate-thumbnails-advanced'); ?>" />px&nbsp;&nbsp;<b>X</b>&nbsp;&nbsp;
                                   <input type="number" class="rta_text_field_60" name="featured_img_h" id="featured_img_h" value="" placeholder="<?php _e('Height','regenerate-thumbnails-advanced'); ?>" />px
                               </td>
                            </tr>
                            <tr>
                               <td><?php _e('Exact thumb size for non featured images:','regenerate-thumbnails-advanced'); ?></td>
                               <td>
                                   <input type="number" class="rta_text_field_60" name="no_featured_img_w" id="no_featured_img_w" value="" placeholder="<?php _e('Width','regenerate-thumbnails-advanced'); ?>" />px&nbsp;&nbsp;<b>X</b>&nbsp;&nbsp;
                                   <input type="number" class="rta_text_field_60" name="no_featured_img_h" id="no_featured_img_h" value="" placeholder="<?php _e('Height','regenerate-thumbnails-advanced'); ?>" />px
                               </td>
                            </tr>
                            <!-- Image Sizes -->
                            <tr>
                                <td colspan="2">
                                    <form style="margin-left: -10px;" method="post" name="frm_rta_image_sizes" id="frm_rta_image_sizes" class="frm_rta" action="rta_save_image_sizes" enctype="multipart/form-data">
                                        <table width="100%">

                                            <tr>
                                                <td width="100"><?php _e('Image Sizes','regenerate-thumbnails-advanced'); ?></td>
                                                <td>
                                                    <input type="button" name="btn_add_image_size" id="btn_add_image_size" class="btn_add_more" value="<?php _e('Add New Size','regenerate-thumbnails-advanced'); ?>" onclick="javascript:rta_add_image_size_row();" />
                                                    <p></p>
                                                    <table width="100%" id="rta_add_image_size_container">
                                                        <?php
                                                        if(sizeof($image_sizes['name'])>0){ ?>
                                                        <tr id="rta_add_images_header">
                                                            <td width="20%"><b><?php _e('Image Size Public Name','regenerate-thumbnails-advanced'); ?></b></td>
                                                            <td width="15%"><b><?php _e('Max. Width','regenerate-thumbnails-advanced'); ?></b></td>
                                                            <td width="15%"><b><?php _e('Max. Height','regenerate-thumbnails-advanced'); ?></b></td>
                                                            <td width="15%"><b><?php _e('Cropping','regenerate-thumbnails-advanced'); ?></b></td>
                                                            <td width="20%"><b><?php _e('Image Size Name','regenerate-thumbnails-advanced'); ?></b></td>
                                                            <td width="15%"></td>
                                                        </tr>
                                                        <?php
                                                        for($i=0;$i<sizeof($image_sizes['name']);$i++){ ?>
                                                        <?php $rowid = uniqid();?>
                                                        <tr id="<?php echo $rowid;?>">
                                                            <td>
                                                                <input type="text" name="image_sizes[pname][]" class="image_sizes_pname" value="<?php echo $image_sizes['pname'][$i];?>" placeholder="<?php _e('Image Size Public Name','regenerate-thumbnails-advanced'); ?>" onblur="javascript: rta_image_name_changed(this,'<?php echo $rowid;?>');"/>
                                                            </td>
                                                            <td>
                                                                <input type="number" name="image_sizes[width][]" class="image_sizes_width" value="<?php echo $image_sizes['width'][$i];?>" placeholder="<?php _e('Image Size Width','regenerate-thumbnails-advanced'); ?>" onchange="javascript: rta_image_width_changed(this,'<?php echo $rowid;?>');"/>
                                                            </td>
                                                            <td>
                                                                <input type="number" name="image_sizes[height][]" class="image_sizes_height" value="<?php echo $image_sizes['height'][$i];?>" placeholder="<?php _e('Image Size Height','regenerate-thumbnails-advanced'); ?>" onchange="javascript: rta_image_height_changed(this,'<?php echo $rowid;?>');"/>
                                                            </td>
                                                            <td>
                                                                <select name="image_sizes[cropping][]" class="image_sizes_cropping" onchange="javascript: rta_image_crop_changed(this,'<?php echo $rowid;?>');">
                                                                    <option value="no_cropped"<?php if($image_sizes['cropping'][$i]=='no_cropped'){echo ' selected';}?>><?php _e('No','regenerate-thumbnails-advanced'); ?></option>
                                                                    <option value="cropped"<?php if($image_sizes['cropping'][$i]=='cropped'){echo ' selected';}?>><?php _e('Yes','regenerate-thumbnails-advanced'); ?></option>
                                                                    <option value="left_top"<?php if($image_sizes['cropping'][$i]=='left_top'){echo ' selected';}?>><?php _e('Left top','regenerate-thumbnails-advanced'); ?></option>
                                                                    <option value="left_center"<?php if($image_sizes['cropping'][$i]=='left_center'){echo ' selected';}?>><?php _e('Left center','regenerate-thumbnails-advanced'); ?></option>
                                                                    <option value="left_bottom"<?php if($image_sizes['cropping'][$i]=='left_bottom'){echo ' selected';}?>><?php _e('Left bottom','regenerate-thumbnails-advanced'); ?></option>
                                                                    <option value="center_top"<?php if($image_sizes['cropping'][$i]=='center_top'){echo ' selected';}?>><?php _e('Center top','regenerate-thumbnails-advanced'); ?></option>
                                                                    <option value="center_center"<?php if($image_sizes['cropping'][$i]=='center_center'){echo ' selected';}?>><?php _e('Center center','regenerate-thumbnails-advanced'); ?></option>
                                                                    <option value="center_bottom"<?php if($image_sizes['cropping'][$i]=='center_bottom'){echo ' selected';}?>><?php _e('Center bottom','regenerate-thumbnails-advanced'); ?></option>
                                                                    <option value="right_top"<?php if($image_sizes['cropping'][$i]=='right_top'){echo ' selected';}?>><?php _e('Right top','regenerate-thumbnails-advanced'); ?></option>
                                                                    <option value="right_center"<?php if($image_sizes['cropping'][$i]=='right_center'){echo ' selected';}?>><?php _e('Right center','regenerate-thumbnails-advanced'); ?></option>
                                                                    <option value="right_bottom"<?php if($image_sizes['cropping'][$i]=='right_bottom'){echo ' selected';}?>><?php _e('Right bottom','regenerate-thumbnails-advanced'); ?></option>
                                                                </select>                                        
                                                            </td>
                                                            <td>
                                                                <input type="text" readonly name="image_sizes[name][]" class="image_sizes_name" value="<?php echo $image_sizes['name'][$i];?>" placeholder="<?php _e('Image Size name','regenerate-thumbnails-advanced'); ?>" />
                                                            </td>
                                                            <td>
                                                                <input type="button" name="btn_remove_image_size_row" value="<?php _e('Remove','regenerate-thumbnails-advanced'); ?>" class="btn_remove_row" onclick="javascript:rta_remove_image_size_row('<?php echo $rowid;?>');" />
                                                            </td>
                                                        </tr>
                                                        <?php }}?>
                                                    </table>
                                                </td>
                                            </tr>  

                                            <tr>
                                                <td><?php _e('Default JPEG Quality','regenerate-thumbnails-advanced'); ?></td>
                                                <td><input type="number" name="jpeg_quality" id="jpeg_quality" value="<?php echo $jpeg_quality = (!empty($jpeg_quality))?$jpeg_quality:90;?>" onchange="javascript: rta_save_image_sizes();" /></td>
                                            </tr>

                                        </table>
                                    </form>
                                </td>
                            </tr>
                            <tr>
                                <td><?php _e('Delete Stale Thumbnails','regenerate-thumbnails-advanced'); ?></td>
                                <td><input type="checkbox" name="del_associated_thumbs" id="del_associated_thumbs" value="YES" /> <?php _e('Delete all the thumbnails associated with images but not defined in the image\'s metadata. Useful to remove stale images but keep in mind there is no undo!','regenerate-thumbnails-advanced'); ?></td>
                                </td>
                            </tr>
                            <tr>
                                <td><?php _e('Delete Leftover Image Metadata','regenerate-thumbnails-advanced'); ?></td>
                                <td><input type="checkbox" name="del_leftover_metadata" id="del_leftover_metadata" value="YES" /> <?php _e('Delete all the metadata associated with missing images. Keep in mind there is no undo!','regenerate-thumbnails-advanced'); ?></td>
                            </tr>
                            <tr>
                                <td></td>
                                <td>
                                    <div class="rta_btnRow">
                                        <span>
                                            <div class="btnStart">
                                                <button tabindex="0" type="button" style="">
                                                    <div>
                                                        <div style="height: 36px; border-radius: 2px; transition: all 450ms cubic-bezier(0.23, 1, 0.32, 1) 0ms; top: 0px;">
                                                            <span style="position: relative; opacity: 1; font-size: 14px; letter-spacing: 0px; text-transform: uppercase; font-weight: 500; margin: 0px; user-select: none; padding-left: 16px; padding-right: 16px; color: rgb(255, 255, 255);">Regenerate</span>
                                                        </div>
                                                    </div>
                                                </button>
                                            </div>
                                            <?php /* ?>
                                            <div class="btnDel">
                                                <button tabindex="0" type="button" style="">
                                                    <div>
                                                        <div style="height: 36px; border-radius: 2px; transition: all 450ms cubic-bezier(0.23, 1, 0.32, 1) 0ms; top: 0px;">
                                                            <span style="position: relative; opacity: 1; font-size: 14px; letter-spacing: 0px; text-transform: uppercase; font-weight: 500; margin: 0px; user-select: none; padding-left: 16px; padding-right: 16px; color: rgb(255, 255, 255);">Delete Associated Thumbs</span>
                                                        </div>
                                                    </div>
                                                </button>
                                            </div>
                                            <?php */?>
                                        </span>                                        
                                    </div>
                                    <div class="rta_wait_loader">Please wait...</div>
                                    
                                </td>
                            </tr>
                            <tr>
                                <td></td>
                                <td>
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
                                </td>
                            </tr>
                            <tr>
                                <td></td>
                                <td>
                                    <a href="#" onclick="javascript: return rta_show_errorbox();" class="rta_error_link rta_hidden">There were some errors, click for details</a>
                                    <div class="listContainer rta_error_box row rta_hidden">
                                        <div class="statuslist col-sm-6">
                                            <h4 class="listTitle">Error(s)</h4>
                                            <ul class="list-group">                                                
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </table>                                                
                    </div>
                </td>
            </tr>
        </tbody> 
    </table>
</div>