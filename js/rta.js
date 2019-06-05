jQuery(document).ready(function($){

function rtaJS() {};

rtaJS.prototype = {
  offset: 0,
  total:  0,
  is_interrupted_process: false, // was the process killed by reload earlier?
  in_process: false, // currently pushing it through.
  is_stopped: false,
  formcookie: null,
  is_saved: true,

}

rtaJS.prototype.init = function()
{
  this.checkSubmitReady();

  $('.select, .deselect').on('click', $.proxy(this.selectAll, this));
  $(document).on('change','input, select', $.proxy(this.checkSubmitReady, this));

  // the start of it all.
  $(document).on("click", '.rta_regenerate', $.proxy(this.processInit, this));
  //$('#frm_rta_image_sizes').on('submit', $.proxy(this.processInit, this))

  // save image sizes when updated
  $(document).on('change', '.table.imagesizes input, .table.imagesizes select', $.proxy(this.image_size_changed, this));
  $(document).on('click', 'button[name="save_settings"]', $.proxy(this.image_size_changed, this));
  $(document).on('click', '.table.imagesizes .btn_remove_row', $.proxy(this.remove_image_size_row, this));
  $(document).on('click', '#btn_add_image_size', $.proxy(this.add_image_size_row, this));
  $(document).on('click', '.stop-process', $.proxy(this.stopProcess,this));

  $(document).on('click', '.rta_error_link', $.proxy(function () { this.show_errorbox(true); }, this) ) ;
  this.formcookie = this.get_form_cookie();

  $(document).on('change', '.rta-settings-wrap input, .rta-settings-wrap select', $.proxy(this.show_save_indicator, this) );
  $(document).on('change', 'input[name^="regenerate_sizes"]', $.proxy(this.checkOptionsVisible, this));

  var offset = parseInt(this.get_cookie('rta_offset'));
  var total = parseInt(this.get_cookie('rta_total'));

  if (! isNaN(offset) && ! isNaN(total))
  {
    if (offset < total)
    {
      this.is_interrupted_process = true;
      this.offset = offset;
      this.total = total;
      this.process();
    }
  }
}

rtaJS.prototype.checkSubmitReady = function()
{
  processReady = true;

  inputs = $('input[name^="regenerate_sizes"]:checked');
  if (inputs.length == 0)
    processReady = false;

  if (this.in_process || ! this.is_saved)
    processReady = false;

  if (processReady)
  {
    $('button.rta_regenerate').removeClass('disabled');
    $('button.rta_regenerate').prop('disabled', false);
  }
  else {
    $('button.rta_regenerate').addClass('disabled');
    $('button.rta_regenerate').prop('disabled', true);
  }

  if (this.is_saved)
  {
    $('button[name="save_settings"]').prop('disabled', true);
    $('button[name="save_settings"]').addClass('disabled');
    $('.save_note').addClass('rta_hidden');

  }
  else {
    $('button[name="save_settings"]').prop('disabled', false);
    $('button[name="save_settings"]').removeClass('disabled');
    $('.save_note').removeClass('rta_hidden');
  }


}

rtaJS.prototype.selectAll = function(e)
{
   var action = $(e.target).data('action');
   var target = $(e.target).data('target');

   if (action == 'select')
      checked = true;
   else {
     checked = false;
   }

   $('input[name^="' + target + '"]').prop('checked', checked).trigger('change');

}

rtaJS.prototype.processInit = function (e)
{
  e.preventDefault();

  this.unset_all_cookies();
  this.show_errorbox(false);
  this.hide_progress();
  this.toggleShortPixelNotice(false);

  this.show_wait(true);

  this.in_process = true;
  this.is_stopped = false;
  this.checkSubmitReady();
  this.set_form_cookie();

  var self = this;
  var form = this.get_form_cookie();

  $.ajax({
      type: 'POST',
      dataType: 'json',
      url: rta_data.ajaxurl,
      data: {
              gen_nonce: rta_data.nonce_generate,
              action: 'rta_regenerate_thumbnails',
              type: 'general',
              genform: JSON.stringify(form),
       },
      success: function (response) {
          if( response.pCount > 0 ) {
            self.offset = 0;
            self.total = response.pCount;
            self.set_process_cookie(self.offset, self.total);
            self.process();
          }else{
              self.finishProcess();
              self.add_error(response.logstatus);
              self.show_errorlink(true);
          }
      }
  });
}

rtaJS.prototype.process = function()
{
    if (this.is_stopped)
      return; // escape if process has been stopped.
    offset = this.offset;
    total = this.total;

    this.in_process = true;
    this.checkSubmitReady();

    var percentage_done = Math.round((offset/total)*100);
    this.show_progress(percentage_done);
    var self = this;

    var form = this.get_form_cookie();

    if(offset < total) {
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: rta_data.ajaxurl,
            data: {
                    gen_nonce: rta_data.nonce_generate,
                    action: 'rta_regenerate_thumbnails',
                    type: 'submit',
                    offset:offset,
                    genform: JSON.stringify(form),
            },
            success: function (response) {
                if( response.offset <= total ) {
                    if(response.logstatus=='Processed') {
                        $(".rta_progress .images img").attr("src",response.imgUrl);
                    }

                    if (! self.is_stopped)
                    {
                      self.set_process_cookie(response.offset,total);
                      self.offset = response.offset;
                      setTimeout(function(){ self.process(); },400);
                    }
                }else{
                    self.set_cookie("rta_image_processed",$(".rta_progress .images img").attr("src"));
                    //this.show_buttons();
                    self.show_errorlink(true);
                }
                if($.isArray(response.error) && response.error.length > 0) {
                    self.add_error(response.logstatus);
                }
            }
        });
    }else{
        this.set_cookie("rta_image_processed",$(".rta_progress .images img").attr("src"));
        //this.show_buttons();
        this.finishProcess();
        this.show_errorlink(true);
    }

}

  rtaJS.prototype.finishProcess = function()
  {
    this.in_process = false;
    this.is_interrupted_process = false;

    this.show_wait(false);
    this.toggleShortPixelNotice(true);
    $('.stop-process').addClass('rta_hidden');
    this.checkSubmitReady();
  }

  rtaJS.prototype.stopProcess = function()
  {
    if (confirm(rta_data.confirm_stop))
    {
      this.is_stopped = true;
      this.unset_all_cookies();
      this.finishProcess();
      this.hide_progress();
      this.toggleShortPixelNotice(false);
//      this.checkSubmitReady();
    }
  }

    rtaJS.prototype.show_progress = function(percentage_done) {
        //var $ = jQuery;
        if($(".rta_progress .images img").attr("src").indexOf("http") != -1 ) {
            $(".rta_progress .images").css('opacity', 100);
        }else{
            $(".rta_progress .images").css('opacity', 0);
        }
        var total_circle = 289.027;
        if(percentage_done>0) {
            total_circle = Math.round(total_circle-(total_circle*percentage_done/100));
        }
        $(".CircularProgressbar-path").css("stroke-dashoffset",total_circle+"px");
        $(".CircularProgressbar-text").html(percentage_done+"%");
        if(!$(".rta_progress").is(":visible")) {

            this.show_wait(false);
            $('.rta_progress').removeClass('rta_hidden');
            $('.stop-process').removeClass('rta_hidden');
            $(".rta_progress").slideDown();
            $(".rta_progress").css('display', 'inline-block');
        }
    }

    rtaJS.prototype.show_wait = function(show) {
        if (! show)
          $(".rta_wait_loader").hide();
        else
          $(".rta_wait_loader").show().removeClass('rta_hidden');
    }

    rtaJS.prototype.add_error = function(log) {
      //  var $ = jQuery;
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
            this.set_cookie("rta_error_box_ul",$(".rta_error_box ul").html());
        }
    }

    rtaJS.prototype.show_errorbox = function(show) {
      //  var $ = jQuery;
       if (!show)
       {
         $(".rta_error_box").slideUp(10);
         $(".rta_error_box ul").html("");
         $(".rta_error_link").slideUp(10);

       }
       else {
         if(!$(".rta_error_box").is(":visible") && $(".rta_error_box ul li").length) {
             $(".rta_error_box").removeClass('rta_hidden').slideDown();
         }
       }

        return false;
    }

    rtaJS.prototype.show_errorlink = function() {
    //    var $ = jQuery;
        if(!$(".rta_error_link").is(":visible") && $(".rta_error_box ul li").length) {
            $(".rta_error_link").removeClass('rta_hidden').slideDown();
        }
    }

    rtaJS.prototype.hide_progress = function()  {
        var $ = jQuery;
        var total_circle = 289.027;
        $(".rta_progress .images img").attr("src","");
        $(".CircularProgressbar-path").css("stroke-dashoffset",total_circle+"px");
        $(".rta_progress .images").css('opacity', 0);
        $(".rta_progress").slideUp();
        $(".CircularProgressbar-text").html("0%");
    }

    rtaJS.prototype.unset_all_cookies = function() {
        this.set_cookie("rta_image_processed","");
        this.set_cookie("rta_offset","");
        this.set_cookie("rta_total","");
        this.set_cookie('rta_last_settings', '');
    }

    /* Empty function, disabling
      rtaJS.prototype.set_default_values = function() {
    } */

    rtaJS.prototype.set_process_cookie = function(offset, total)
    {
      offset = parseInt(offset);
      total = parseInt(total);

      if (! isNaN(offset))
        this.set_cookie('rta_offset', offset);
      if (! isNaN(total))
        this.set_cookie('rta_total', total);
    }

    rtaJS.prototype.get_form_cookie = function()
    {
      var cook = this.get_cookie('rta_last_settings');
      formcookie = {};
      if (cook.length > 0)
        formcookie = JSON.parse(cook);

      return formcookie;
    }

    rtaJS.prototype.set_form_cookie = function() {
        var formcookie = {};
        $('#frm_rta_image_sizes').find('input, select').each(function()
        {
            var value = $(this).val();
            var name = $(this).attr('name');

            if (name.indexOf('image_sizes[') >= 0)
            {
              return true;
            }

            if ( $(this).attr('type') == 'checkbox' && ! $(this).prop('checked') )
            {
              return true; // continue if not checked..
            }

            // Restore array of inputs with array. Needlessly complicated.
            // 1 = name of input without [] , 2 is index of input.
              matches = name.match(/(.*?)\[(.*)\]/);
              if (matches !== null)
              {
                if (!formcookie[matches[1]]) {
                    formcookie[matches[1]] = [];
                }
                  formcookie[matches[1]][matches[2]] = value;
              }
              else
                formcookie[name] = value;
         });

         this.set_cookie('rta_last_settings',  JSON.stringify(formcookie), 10);
    }

    rtaJS.prototype.set_cookie = function(cname, cvalue, exdays) {
        var d = new Date();
        d.setTime(d.getTime() + (exdays*24*60*60*1000));
        var expires = "expires="+ d.toUTCString();
        document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
    }

    rtaJS.prototype.get_cookie = function(cname) {

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

    rtaJS.prototype.add_image_size_row = function() {

        var $ = jQuery;
        var container = $('.table.imagesizes'); // $("#rta_add_image_size_container");
        var uniqueId = Math.random().toString(36).substring(2) + (new Date()).getTime().toString(36);

        var row = $('.row.proto').clone();
        $(row).attr('id', uniqueId);
        $(row).removeClass('proto');
        container.append(row.css('display', 'table-row') );

        container.find('.header').removeClass('rta_hidden');
    }

    rtaJS.prototype.image_size_changed = function(e) {
        e.preventDefault();
        var rowid = $(e.target).parents('.row').attr('id');
        this.update_thumb_name(rowid);
        this.save_image_sizes();
    }

    rtaJS.prototype.update_thumb_name = function(rowid) {
        if($("#"+rowid).length) {
            var old_name = $("#"+rowid+" .image_sizes_name").val();
            var name = "rta_thumb";//$("#"+rowid+" .image_sizes_name").val();
            var width = $("#"+rowid+" .image_sizes_width").val();
            var height = $("#"+rowid+" .image_sizes_height").val();
            var cropping = $("#"+rowid+" .image_sizes_cropping").val();
            var pname = $("#"+rowid+" .image_sizes_pname").val();

            if (width <= 0) width = '';  // don't include zero values here.
            if (height <= 0) height = '';
            var slug = (name+" "+cropping+" "+width+"x"+height).toLowerCase().replace(/ /g, '_');

            // update the image size selection so it keeps checked indexes.
            $('input[name^="regenerate_sizes"][value="' + old_name + '"]').val(slug);
            if (pname.length <= 0)
            {
              $('input[name^="regenerate_sizes"][value="' + old_name + '"]').text(slug);
            }
            $('input[name="keep_' + old_name + '"]').attr('name', 'keep_' + slug);



            $("#"+rowid+" .image_sizes_name").val(slug);
        }
    }

    rtaJS.prototype.save_image_sizes = function() {
        this.settings_doingsave_indicator(true);
        var action = 'rta_save_image_sizes';
        var the_nonce = rta_data.nonce_savesizes;

        var self = this;
        // proper request
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: rta_data.ajaxurl,
            data: {
                  action: action,
                  save_nonce: the_nonce,
                  saveform: $('#rta_settings_form').serialize(),
            },
            success: function (response) {
                if (! response.error)
                {
                  if (response.new_image_sizes)
                  {
                    $('.thumbnail_select .checkbox-list').fadeOut(80).html(response.new_image_sizes).fadeIn(80);
                    self.checkOptionsVisible();
                  }
                }
                self.is_saved = true;
                self.settings_doingsave_indicator(false);
                self.checkSubmitReady();
            }
        });
    }

    rtaJS.prototype.settings_doingsave_indicator = function (show)
    {
        if (show)
        {
            $('.form_controls .save_indicator').fadeIn(20);
        }
        else {
            $('.form_controls .save_indicator').fadeOut(100);
        }
    }

    rtaJS.prototype.show_save_indicator = function()
    {
        this.is_saved = false;
        this.checkSubmitReady();
    }

    rtaJS.prototype.toggleShortPixelNotice = function(show)
    {
      if (show)
        $('.shortpixel-bulk-notice, .shortpixel-notice').removeClass('rta_hidden');
      else
        $('.shortpixel-bulk-notice, .shortpixel-notice').addClass('rta_hidden');
    }

    rtaJS.prototype.remove_image_size_row = function(e) {
        var rowid = $(e.target).parents('.row').attr('id');

        if(confirm( rta_data.confirm_delete )) {
            var intName = $('#' + rowid).find('.image_sizes_name').val();
            $('input[name^="regenerate_sizes"][value="' + intName + '"]').remove(); // remove the checkbox as well, otherwise this will remain saved.

            $("#"+rowid).remove();

            this.save_image_sizes();
        }
    }

    rtaJS.prototype.checkOptionsVisible = function()
    {
        $('input[name^="regenerate_sizes"]').each(function ()
        {
           if ($(this).is(':checked'))
           {
             $(this).parents('.item').find('.options').removeClass('hidden');
             var input = $(this).parents('.item').find('input[type="checkbox"]');

             if (typeof $(input).data('setbyuser') == 'undefined')
             {
                $(input).prop('checked', true);
                $(input).data('setbyuser', true);
              }
           }
           else {
             $(this).parents('.item').find('.options').addClass('hidden');
           }
        });
    }

    window.rtaJS = new rtaJS();
    window.rtaJS.init();

}); // Jquery
