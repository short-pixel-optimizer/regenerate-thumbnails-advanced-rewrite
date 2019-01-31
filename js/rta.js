jQuery(document).ready(function($){
    
    if( $("#rta_regenerate_thumbs_main_container").length ) {
        if( rta_offset!="" && rta_total!="" ) {
            var rta_offset = rta_get_cookie("rta_offset");
            var rta_total = rta_get_cookie("rta_total");
            rta_hide_buttons();
            rta_set_default_values();
            rta_regenerate_thumbnails((1*rta_offset),(1*rta_total));
        }
    }
    $(document).on("click", '.btnStart button', function(e) { 
        var period = $(".timeDropdownSelect").val();
        rta_unset_all_cookies();
        rta_hide_errorbox();
        rta_hide_progress();
        rta_hide_buttons();
        rta_show_wait();
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: rta_data.ajaxurl,
            data: {
                    action: 'rta_regenerate_thumbnails',
                    type: 'general',
                    period:period,
                    featured_img_w:$("#featured_img_w").val(),
                    featured_img_h:$("#featured_img_h").val(),
                    no_featured_img_w:$("#no_featured_img_w").val(),
                    no_featured_img_h:$("#no_featured_img_h").val(),
                    default_img_w:$("#default_img_w").val(),
                    default_img_h:$("#default_img_h").val(),
                    del_thumbs:$("#del_associated_thumbs").is(":checked")
            },
            success: function (response) {
                if( response.pCount > 0 ) {                        
                    rta_regenerate_thumbnails(0,response.pCount);
                }else{
                    rta_add_error(response.logstatus); 
                    rta_show_buttons();
                    rta_show_errorlink();                    
                }                 
            }
        });
    });  
    $(document).on("click", '.btnDel button', function(e) { 
        if(confirm("Are you sure you want to delete all the associated thumbs?")) {
            var period = $(".timeDropdownSelect").val();
            rta_hide_errorbox();
            rta_hide_progress();
            rta_hide_buttons();
            rta_show_wait();
            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: rta_data.ajaxurl,
                data: {
                        action: 'rta_del_thumbnails',
                        period:period
                },
                success: function (response) {
                    if( !response.error ) {                        
                        alert(response.message);
                    }else{
                        rta_add_error(response.message);    
                        rta_show_errorlink();
                    }                 
                    rta_show_buttons();
                    rta_hide_wait();
                }
            });
        }
    });
});

function rta_hide_errorbox() {
    var $ = jQuery;
    $(".rta_error_box").slideUp();
    $(".rta_error_box ul").html("");
    $(".rta_error_link").slideUp();
}

function rta_show_progress(percentage_done) {
    var $ = jQuery;
    if($(".rta_progress .images img").attr("src").indexOf("http") != -1 ) {
        $(".rta_progress .images").show();        
    }else{
        $(".rta_progress .images").hide();        
    }
    var total_circle = 289.027;
    if(percentage_done>0) {
        total_circle = Math.round(total_circle-(total_circle*percentage_done/100));
    }
    $(".CircularProgressbar-path").css("stroke-dashoffset",total_circle+"px");
    $(".CircularProgressbar-text").html(percentage_done+"%");
    if(!$(".rta_progress").is(":visible")) {
        rta_hide_wait();
        $(".rta_progress").slideDown();        
    }
}

function rta_hide_buttons() {
    var $ = jQuery;
    $(".rta_btnRow").hide();
}

function rta_show_buttons() {
    var $ = jQuery;
    $(".rta_btnRow").show();
    rta_hide_wait();
}

function rta_hide_wait() {
    var $ = jQuery;
    $(".rta_wait_loader").hide();
}

function rta_show_wait() {
    var $ = jQuery;
    $(".rta_wait_loader").show();
}

function rta_add_error(log) {
    var $ = jQuery;
    if(log!="") {
        var html = '';
        if($.isArray(log)){
            for(var i=0;i<=log.length;i++) {
                html = html+'<li class="list-group-item headLi">'+log[i]+'</li>';
            }
        }else{
            html = '<li class="list-group-item headLi">'+log+'</li>';
        }
        $(".rta_error_box ul").append(html);      
        rta_set_cookie("rta_error_box_ul",$(".rta_error_box ul").html());
    }
}

function rta_show_errorbox() {    
    var $ = jQuery;
    if(!$(".rta_error_box").is(":visible") && $(".rta_error_box ul li").length) {
        $(".rta_error_box").slideDown();
    }
    return false;
}

function rta_show_errorlink() {    
    var $ = jQuery;
    if(!$(".rta_error_link").is(":visible") && $(".rta_error_box ul li").length) {
        $(".rta_error_link").slideDown();
    }
}

function rta_hide_progress() {
    var $ = jQuery;
    var total_circle = 289.027;
    $(".rta_progress .images img").attr("src","");
    $(".CircularProgressbar-path").css("stroke-dashoffset",total_circle+"px");
    $(".rta_progress .images").hide();
    $(".rta_progress").slideUp();
    $(".CircularProgressbar-text").html("0%");
}

function rta_regenerate_thumbnails(offset,total) { 
    var $ = jQuery;
    var percentage_done = Math.round((offset/total)*100);
    rta_show_progress(percentage_done);
    var period = $(".timeDropdownSelect").val();
    if(offset < total) {
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: rta_data.ajaxurl,
            data: {
                    action: 'rta_regenerate_thumbnails',
                    type: 'submit',
                    period:period,
                    offset:offset,
                    featured_img_w:$("#featured_img_w").val(),
                    featured_img_h:$("#featured_img_h").val(),
                    no_featured_img_w:$("#no_featured_img_w").val(),
                    no_featured_img_h:$("#no_featured_img_h").val(),
                    default_img_w:$("#default_img_w").val(),
                    default_img_h:$("#default_img_h").val(),
                    del_thumbs:$("#del_associated_thumbs").is(":checked")
            },
            success: function (response) {
                if( response.offset <= total ) { 
                    if(response.logstatus=='Processed') {
                        $(".rta_progress .images img").attr("src",response.imgUrl); 
                    }
                    rta_set_values_in_cookie(response.offset,total);
                    setTimeout(function(){rta_regenerate_thumbnails(response.offset,total);},1000);
                }else{
                    rta_set_cookie("rta_image_processed",$(".rta_progress .images img").attr("src"));
                    rta_show_buttons();
                    rta_show_errorlink();
                }
                if($.isArray(response.error) && response.error.length > 0) {
                    rta_add_error(response.logstatus); 
                }
            }
        });
    }else{
        rta_set_cookie("rta_image_processed",$(".rta_progress .images img").attr("src"));
        rta_show_buttons();
        rta_show_errorlink();
    }
}

function rta_unset_all_cookies() {
    var $ = jQuery;
    rta_set_cookie("rta_offset","");
    rta_set_cookie("rta_total","");
    rta_set_cookie("featured_img_w","");
    rta_set_cookie("featured_img_h","");
    rta_set_cookie("no_featured_img_w","");
    rta_set_cookie("no_featured_img_h","");
    rta_set_cookie("default_img_w","");
    rta_set_cookie("default_img_h","");
    rta_set_cookie("del_associated_thumbs","");
    rta_set_cookie("rta_image_processed","");
    rta_set_cookie("rta_error_box_ul","");
}

function rta_set_default_values() {
    var $ = jQuery;
    $("#featured_img_w").val(rta_get_cookie("featured_img_w"));
    $("#featured_img_h").val(rta_get_cookie("featured_img_h"));
    $("#no_featured_img_w").val(rta_get_cookie("no_featured_img_w"));
    $("#no_featured_img_h").val(rta_get_cookie("no_featured_img_h"));
    $("#default_img_w").val(rta_get_cookie("default_img_w"));
    $("#default_img_h").val(rta_get_cookie("default_img_h"));
    $(".rta_progress .images img").attr("src",rta_get_cookie("rta_image_processed"));
    $(".rta_error_box ul").html(rta_get_cookie("rta_error_box_ul"));
    if(rta_get_cookie("del_associated_thumbs")=="true") {
        $("#del_associated_thumbs").prop("checked",true);
    }else{
        $("#del_associated_thumbs").prop("checked",false);
    }
}

function rta_set_values_in_cookie(offset,total) {
    var $ = jQuery;
    rta_set_cookie("rta_offset",offset);
    rta_set_cookie("rta_total",total);
    rta_set_cookie("featured_img_w",$("#featured_img_w").val());
    rta_set_cookie("featured_img_h",$("#featured_img_h").val());
    rta_set_cookie("no_featured_img_w",$("#no_featured_img_w").val());
    rta_set_cookie("no_featured_img_h",$("#no_featured_img_h").val());
    rta_set_cookie("default_img_w",$("#default_img_w").val());
    rta_set_cookie("default_img_h",$("#default_img_h").val());
    rta_set_cookie("del_associated_thumbs",$("#del_associated_thumbs").is(":checked"));
}

function rta_set_cookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    var expires = "expires="+ d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";    
}



function rta_get_cookie(cname) {

    var name = cname + "=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for(var i = 0; i <ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

function rta_add_image_size_row() {
    
    var $ = jQuery;
    var container = $("#rta_add_image_size_container");
    var uniqueId = Math.random().toString(36).substring(2) + (new Date()).getTime().toString(36);
    var row = '';
    row = row+'<tr id="rta_add_images_header">';
    row = row+'<td width="20%"><b>Image Size Public Name</b></td>';
    row = row+'<td width="15%"><b>Max. Width</b></td>';
    row = row+'<td width="15%"><b>Max. Height</b></td>';
    row = row+'<td width="15%"><b>Cropping</b></td>';
    row = row+'<td width="20%"><b>Image Size Name</b></td>';
    row = row+'<td width="15%"></td>';
    row = row+'</tr>';
    if(!$("#rta_add_images_header").length) {
        container.append(row);
    }
    var row = '<tr id="'+uniqueId+'">';
    row = row+'<td>';
    row = row+'<input type="text" name="image_sizes[pname][]" class="image_sizes_pname" value="" placeholder="Image Size Public Name" onblur="javascript: rta_image_name_changed(this,\''+uniqueId+'\');"/>';
    row = row+'</td>';
    row = row+'<td>';
    row = row+'<input type="number" name="image_sizes[width][]" class="image_sizes_width" value="" placeholder="Image Size Width" onchange="javascript: rta_image_width_changed(this,\''+uniqueId+'\');" />';
    row = row+'</td>';
    row = row+'<td>';
    row = row+'<input type="number" name="image_sizes[height][]" class="image_sizes_height" value="" placeholder="Image Size Height" onchange="javascript: rta_image_height_changed(this,\''+uniqueId+'\');"/>';
    row = row+'</td>';
    row = row+'<td>';
    row = row+'<select name="image_sizes[cropping][]" class="image_sizes_cropping"  onchange="javascript: rta_image_crop_changed(this,\''+uniqueId+'\');">';
    row = row+'<option value="no_cropped">No</option>';
    row = row+'<option value="cropped">Yes</option>';
    row = row+'<option value="left_top">Left top</option>';
    row = row+'<option value="left_center">Left center</option>';
    row = row+'<option value="left_bottom">Left bottom</option>';
    row = row+'<option value="center_top">Center top</option>';
    row = row+'<option value="center_center">Center center</option>';
    row = row+'<option value="center_bottom">Center bottom</option>';
    row = row+'<option value="right_top">Right top</option>';
    row = row+'<option value="right_center">Right center</option>';
    row = row+'<option value="right_bottom">Right bottom</option>';
    row = row+'</select>';
    row = row+'</td>';
    row = row+'<td>';
    row = row+'<input type="text" readonly name="image_sizes[name][]" class="image_sizes_name" value="" placeholder="Image Size name"/>';
    row = row+'</td>';
    row = row+'<td>';
    row = row+'<input type="button" name="btn_remove_image_size_row" value="Remove" class="btn_remove_row" onclick="javascript:rta_remove_image_size_row(\''+uniqueId+'\');" />';
    row = row+'</td>';
    row = row+'</tr>';
    container.append(row);
}

function rta_image_name_changed(obj,rowid) {
    
    var $ = jQuery;
    //rta_update_thumb_name(rowid);
}

function rta_image_width_changed(obj,rowid) {
    
    var $ = jQuery;
    rta_update_thumb_name(rowid);
    rta_save_image_sizes();
}

function rta_image_height_changed(obj,rowid) {
    
    var $ = jQuery;
    rta_update_thumb_name(rowid);
    rta_save_image_sizes();
}

function rta_image_crop_changed(obj,rowid) {
    
    var $ = jQuery;
    rta_update_thumb_name(rowid);
    rta_save_image_sizes();
}

function rta_update_thumb_name(rowid) {
    var $ = jQuery;
    if($("#"+rowid).length) {
        var name = "rta_thumb";//$("#"+rowid+" .image_sizes_name").val();
        var width = $("#"+rowid+" .image_sizes_width").val();
        var height = $("#"+rowid+" .image_sizes_height").val();
        var cropping = $("#"+rowid+" .image_sizes_cropping").val();
        var slug = (name+" "+cropping+" "+width+"x"+height).toLowerCase().replace(/ /g, '_');
        $("#"+rowid+" .image_sizes_name").val(slug);
    }
}

function rta_save_image_sizes() {
    var $ = jQuery;
    $.post(rta_data.ajaxurl+"?action="+$("#frm_rta_image_sizes").attr("action"), $('#frm_rta_image_sizes').serialize());
}

function rta_remove_image_size_row(rowid) {
    var $ = jQuery;
    if(confirm("Are you sure you want delete it?")) {
        $("#"+rowid).remove();
        if(!$("#rta_add_image_size_container input").length) {
            $("#rta_add_images_header").remove();
        }
        rta_save_image_sizes();
    }
}