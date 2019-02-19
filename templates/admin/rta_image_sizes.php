<?php if ( $message!="" ) { echo $message; }?>
<div class="wrap">
<h2>Manage Image Sizes</h2>
<table class="wp-list-table widefat fixed" cellspacing="0">
	<thead>
        <tr>
            <th scope="col" class="manage-column" style="">Manage Image Sizes</th>
        </tr>
	</thead>
	<tbody id="the-list">
        <tr>
            <td>
            	<form method="post" name="frm_rta_image_sizes" id="frm_rta_image_sizes" class="frm_rta" action="?page=rta_image_sizes" enctype="multipart/form-data">
                <table width="100%">                    
                    <tr>
                    	<td width="100">Image Sizes</td>
                        <td>
                            <input type="button" name="btn_add_image_size" id="btn_add_image_size" class="btn_add_more" value="Add New Size" onclick="javascript:rta_add_image_size_row();" />
                            <p></p>
                            <table width="100%" id="rta_add_image_size_container">
                                <tr>
                                    <td colspan="6"></td>
                                </tr>
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
                                        <input type="button" name="btn_remove_image_size_row" value="<?php _e('','regenerate-thumbnails-advanced'); ?>Remove" class="btn_remove_row" onclick="javascript:rta_remove_image_size_row('<?php echo $rowid;?>');" />
                                    </td>
                                </tr>
                                <?php }}?>
                            </table>
                        </td>
                    </tr>                    
                    <tr>
                        <td></td>
                        <td><input type="submit" name="btnsave" id="btnsave" value="<?php echo $rta_lang['btn_update'];?>" class="button button-primary">
                        </td>
                    </tr>
                </table>
                </form>
            </td>
        </tr>
     </tbody>
</table>
</div>