<?php if ( $message!="" ) { echo $message; }?>
<?php //echo wp_get_attachment_image( 8, 'rta_featured_image' );?>
<div class="wrap">
    <h2>Regenerate Thumbnails</h2>
    <br>
    <table class="wp-list-table widefat fixed" cellspacing="0">
        <thead>
            <tr>
                <th scope="col" class="manage-column" style="">Regenerate Thumbnails</th>
            </tr>
	</thead>
	<tbody id="the-list">
            <tr>
                <td>
                    <div id="rtaContent">
                        <table width="100%" id="rta_regenerate_thumbs_main_container">
                            <tr>
                                <td width="120">Regenerate period:</td>
                                <td>
                                    <select class="timeDropdownSelect" id="">
                                        <option value="0">All</option>
                                        <option value="1">Past Day</option>
                                        <option value="2">Past Week</option>
                                        <option value="3">Past Month</option>
                                        <option value="4">Past 3 Months</option>
                                        <option value="5">Past 6 Months</option>
                                        <option value="6">Past Year</option>
                                    </select>
                                </td>
                            </tr>
                            <tr style="display:none;">
                               <td>Default thumb size:</td>
                               <td>
                                   <input type="number" class="rta_text_field_60" name="default_img_w" id="default_img_w" value="<?php echo $default_thumb_w;?>" placeholder="Width" />px&nbsp;&nbsp;<b>X</b>&nbsp;&nbsp; 
                                   <input type="number" class="rta_text_field_60" name="default_img_h" id="default_img_h" value="<?php echo $default_thumb_h;?>" placeholder="Height" />px&nbsp;&nbsp;&nbsp;&nbsp;<?php /* ?><small>Default thumbnail size is same as WordPress set thumbnail size on this page: /wp-admin/options-media.php</small><?php */?> 
                               </td>
                            </tr>
                            <tr>
                               <td>Exact thumb size for featured images:</td>
                               <td>
                                   <input type="number" class="rta_text_field_60" name="featured_img_w" id="featured_img_w" value="" placeholder="Width" />px&nbsp;&nbsp;<b>X</b>&nbsp;&nbsp; 
                                   <input type="number" class="rta_text_field_60" name="featured_img_h" id="featured_img_h" value="" placeholder="Height" />px
                               </td>
                            </tr>
                            <tr>
                               <td>Exact thumb size for non featured images:</td>
                               <td>
                                   <input type="number" class="rta_text_field_60" name="no_featured_img_w" id="no_featured_img_w" value="" placeholder="Width" />px&nbsp;&nbsp;<b>X</b>&nbsp;&nbsp; 
                                   <input type="number" class="rta_text_field_60" name="no_featured_img_h" id="no_featured_img_h" value="" placeholder="Height" />px
                               </td>
                            </tr>
                            <!-- Image Sizes -->
                            <tr>
                                <td colspan="2">
                                    <form style="margin-left: -10px;" method="post" name="frm_rta_image_sizes" id="frm_rta_image_sizes" class="frm_rta" action="rta_save_image_sizes" enctype="multipart/form-data">
                                        <table width="100%">

                                            <tr>
                                                <td width="100">Image Sizes</td>
                                                <td>
                                                    <input type="button" name="btn_add_image_size" id="btn_add_image_size" class="btn_add_more" value="Add New Size" onclick="javascript:rta_add_image_size_row();" />
                                                    <p></p>
                                                    <table width="100%" id="rta_add_image_size_container">
                                                        <?php
                                                        if(sizeof($image_sizes['name'])>0){ ?>
                                                        <tr id="rta_add_images_header">
                                                            <td width="20%"><b>Image Size Public Name</b></td>
                                                            <td width="15%"><b>Max. Width</b></td>
                                                            <td width="15%"><b>Max. Height</b></td>
                                                            <td width="15%"><b>Cropping</b></td>
                                                            <td width="20%"><b>Image Size Name</b></td>
                                                            <td width="15%"></td>
                                                        </tr>
                                                        <?php
                                                        for($i=0;$i<sizeof($image_sizes['name']);$i++){ ?>
                                                        <?php $rowid = uniqid();?>
                                                        <tr id="<?php echo $rowid;?>">
                                                            <td>
                                                                <input type="text" name="image_sizes[pname][]" class="image_sizes_pname" value="<?php echo $image_sizes['pname'][$i];?>" placeholder="Image Size Public Name" onblur="javascript: rta_image_name_changed(this,'<?php echo $rowid;?>');"/>
                                                            </td>
                                                            <td>
                                                                <input type="number" name="image_sizes[width][]" class="image_sizes_width" value="<?php echo $image_sizes['width'][$i];?>" placeholder="Image Size Width" onchange="javascript: rta_image_width_changed(this,'<?php echo $rowid;?>');"/>
                                                            </td>
                                                            <td>
                                                                <input type="number" name="image_sizes[height][]" class="image_sizes_height" value="<?php echo $image_sizes['height'][$i];?>" placeholder="Image Size Height" onchange="javascript: rta_image_height_changed(this,'<?php echo $rowid;?>');"/>
                                                            </td>
                                                            <td>
                                                                <select name="image_sizes[cropping][]" class="image_sizes_cropping" onchange="javascript: rta_image_crop_changed(this,'<?php echo $rowid;?>');">
                                                                    <option value="no_cropped"<?php if($image_sizes['cropping'][$i]=='no_cropped'){echo ' selected';}?>>No</option>
                                                                    <option value="cropped"<?php if($image_sizes['cropping'][$i]=='cropped'){echo ' selected';}?>>Yes</option>
                                                                    <option value="left_top"<?php if($image_sizes['cropping'][$i]=='left_top'){echo ' selected';}?>>Left top</option>
                                                                    <option value="left_center"<?php if($image_sizes['cropping'][$i]=='left_center'){echo ' selected';}?>>Left center</option>
                                                                    <option value="left_bottom"<?php if($image_sizes['cropping'][$i]=='left_bottom'){echo ' selected';}?>>Left bottom</option>
                                                                    <option value="center_top"<?php if($image_sizes['cropping'][$i]=='center_top'){echo ' selected';}?>>Center top</option>
                                                                    <option value="center_center"<?php if($image_sizes['cropping'][$i]=='center_center'){echo ' selected';}?>>Center center</option>
                                                                    <option value="center_bottom"<?php if($image_sizes['cropping'][$i]=='center_bottom'){echo ' selected';}?>>Center bottom</option>
                                                                    <option value="right_top"<?php if($image_sizes['cropping'][$i]=='right_top'){echo ' selected';}?>>Right top</option>
                                                                    <option value="right_center"<?php if($image_sizes['cropping'][$i]=='right_center'){echo ' selected';}?>>Right center</option>
                                                                    <option value="right_bottom"<?php if($image_sizes['cropping'][$i]=='right_bottom'){echo ' selected';}?>>Right bottom</option>
                                                                </select>                                        
                                                            </td>
                                                            <td>
                                                                <input type="text" readonly name="image_sizes[name][]" class="image_sizes_name" value="<?php echo $image_sizes['name'][$i];?>" placeholder="Image Size name" />
                                                            </td>
                                                            <td>
                                                                <input type="button" name="btn_remove_image_size_row" value="Remove" class="btn_remove_row" onclick="javascript:rta_remove_image_size_row('<?php echo $rowid;?>');" />
                                                            </td>
                                                        </tr>
                                                        <?php }}?>
                                                    </table>
                                                </td>
                                            </tr>  

                                            <tr>
                                                <td>Default JPEG Quality</td> 
                                                <td><input type="number" name="jpeg_quality" id="jpeg_quality" value="<?php echo $jpeg_quality = (!empty($jpeg_quality))?$jpeg_quality:90;?>" onchange="javascript: rta_save_image_sizes();" /></td>
                                            </tr>

                                        </table>
                                    </form>
                                </td>
                            </tr>
                            <tr>
                                <td>Delete Associated Thumbs</td> 
                                <td><input type="checkbox" name="del_associated_thumbs" id="del_associated_thumbs" value="YES" /> CAUTION: use this option to delete all the thumbnails associated with images even when the thumbnails aren't defined inside database. Useful to remove stale images but keep in mind there is no undo!</td>
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