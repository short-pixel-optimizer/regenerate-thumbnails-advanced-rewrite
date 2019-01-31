<?php if ( $message!="" ) { echo $message; }?>
<div class="wrap">
<h2><?php echo $rta_lang['settings'];?></h2>
<table class="wp-list-table widefat fixed" cellspacing="0">
	<thead>
        <tr>
            <th scope="col" class="manage-column" style=""><?php echo $rta_lang['misc_settings'];?></th>
        </tr>
	</thead>
	<tbody id="the-list">
        <tr>
            <td>
            	<form method="post" name="frm_rta" id="frm_rta" class="frm_rta" action="?page=rta_settings" enctype="multipart/form-data">
                <table width="100%">
                    <tr>
                    	<td width="180"><?php echo $rta_lang['txt_field_label'];?></td>
                        <td>
                            <input type="text" name="textfield" id="textfield" value="<?php echo $textfield;?>" />
                        </td>
                    </tr>
                    <tr>
                    	<td><?php echo $rta_lang['upload_field_label'];?></td>
                        <td>
                            <input type="text" name="upload_field_url" id="upload_field_url" value="<?php echo $upload_field_url;?>" class="textfield" />
                            <input type="hidden" name="upload_field" id="upload_field" value="<?php echo $upload_field;?>" />
                            <input class="upload_media_button button" type="button" name="btnupload" value="<?php echo $rta_lang['btn_upload'];?>" data-obj-url="upload_field_url" data-obj-id="upload_field" />
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