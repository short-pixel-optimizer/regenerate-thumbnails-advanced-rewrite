'use strict';

class RtaJS
{

  process = false;
  is_interrupted_process = false; // was the process killed by reload earlier?
  in_process = false; // processing, in general. can be set via server
  in_ajax = false;   // currently waiting for an ajax response.
  is_stopped = false; // is_stopped: paused, when stopped permanently it also removes server-queue.
  is_saved = true;
  is_debug = false;  // use sparingly
  status = [];
  data = null;
  strings = null;

   constructor()
   {

   }

   Init()
   {
      if (rta_data)
      {
         this.strings = rta_data.strings;
         rta_data.strings = null;
         this.data = rta_data;
      }

      if  (1 == rta_data.is_debug)
      {
         this.is_debug =true;
      }

      this.InitEvents();
      this.ToggleDeleteItems();

      this.InitProcess();

      this.CheckSubmitReady();

   }

   InitEvents()
   {
     var selectActions = document.querySelectorAll('.select, .deselect');
     for (var i = 0; i < selectActions.length; i++)
     {
        selectActions[i].addEventListener('click', this.SelectAll.bind(this));
     }

     var self = this;

     var inputSelects = document.querySelectorAll('input, select');
     for(var i = 0; i < inputSelects.length; i++)
     {
         inputSelects[i].addEventListener('change', self.UpdateSettingsEvent.bind(this));
     }

      var form = document.getElementById('rtaform_process');
      form.addEventListener('submit', this.StartProcess.bind(this));

      var tableInputs = document.querySelectorAll('.table.imagesizes input, .table.imagesizes select, button[name="save_settings"], input[name="jpeg_quality"]');
      for (var i = 0; i < tableInputs.length; i++)
      {
         var eventName = (tableInputs[i].tagName == 'BUTTON') ? 'click' : 'change';
         tableInputs[i].addEventListener(eventName, this.ImageSizeChangeEvent.bind(this));
      }

      var removeButtons = document.querySelectorAll('.table.imagesizes .btn_remove_row');
      for (var i = 0; i < removeButtons.length; i++)
      {
         removeButtons[i].addEventListener('click', this.RemoveRowEvent.bind(this));
      }

      var addRowButton = document.getElementById('btn_add_image_size');
      addRowButton.addEventListener('click', this.AddImageRowEvent.bind(this));

      var processButtons = document.querySelectorAll('.process-button');
      for(var i = 0; i < processButtons.length; i++)
      {
         processButtons[i].addEventListener('click', this.ProcessActionEvent.bind(this));
      }

     // Warnings, errors and such.
     var deleteItemToggles = document.querySelectorAll('input[name="del_associated_thumbs"], input[name^="regenerate_sizes"]');

     for (var i = 0; i < deleteItemToggles.length; i++)
     {
        deleteItemToggles[i].addEventListener('click', this.ToggleDeleteItems.bind(this));
     }

     var saveIndicatorInputs = document.querySelectorAll('.rta-settings-wrap input, .rta-settings-wrap select');
     for(var i = 0; i < saveIndicatorInputs.length; i++)
     {
        saveIndicatorInputs[i].addEventListener('change', this.ShowSaveIndicatorEvent.bind(this));
     }

     var dateInputs = document.querySelectorAll('.period-list input');
     for (var i = 0; i < dateInputs.length; i++)
     {
        dateInputs[i].addEventListener('click', this.UpdateDateEvent.bind(this));
     }

     var visibleOptions = document.querySelectorAll('input[name^="regenerate_sizes"]');
     for(var i = 0; i < visibleOptions.length; i++)
     {
        visibleOptions[i].addEventListener('change', this.CheckOptionsVisible.bind(this));
     }

     var toggleWindow = document.querySelector('.toggle-window');
     toggleWindow.addEventListener('click', this.ToggleWindow.bind(this));

     // Close action for success modal
     var closeLink = document.querySelector('.rta_success_box .modal-close');
     closeLink.addEventListener('click', function (event) {
          this.TogglePanel('success', false);
     }.bind(this));

   }

   CheckSubmitReady()
   {
     var processReady = true;

     if (this.in_process || ! this.is_saved)
     {
       processReady = false;
     }

     var regenerateButton = document.querySelector('button.rta_regenerate');
     var submitButton = document.querySelector('button[name="save_settings"]');
     var saveNote = document.querySelector('.save_note');

     if (processReady)
     {
       regenerateButton.classList.remove('disabled');
       regenerateButton.disabled = false;
     }
     else {
       regenerateButton.classList.add('disabled');
       regenerateButton.disabled = true;
     }

     if (this.is_saved)
     {
       submitButton.disabled = true;
       submitButton.classList.add('disabled');
       saveNote.classList.add('rta_hidden');
     }
     else {
       submitButton.disabled = false;
       submitButton.classList.remove('disabled');
       saveNote.classList.remove('rta_hidden');
     }

   }

   UpdateSettingsEvent(event)
   {
      event.preventDefault();
      this.CheckSubmitReady();
   }

   UpdateDateEvent(event)
   {
      var target = event.target;
      var startstamp = target.dataset.start;
      var endstamp = target.dataset.end;

      var startDate = new Date(startstamp * 1000);
      var endDate = new Date(endstamp * 1000);

      var startInput = document.querySelector('input[name="start_date"]');
      var endInput = document.querySelector('input[name="end_date"]');

      //var startFormat = startDate.getFullYear() + '-' + startDate.getMonth() + '-' + startDate.getDay();
      //var endFormat = endDate.getFullYear() + '-' + endDate.getMonth() + '-' + endDate.getDay();

      if (startstamp == '0')
      {
         startInput.value = '';
      }
      else {
        startInput.valueAsDate = startDate; //  '2019-09-01'; //startFormat;
      }

      if (endstamp == '0')
      {
         endInput.value = '';
      }
      else {
        endInput.valueAsDate =  endDate; //endFormat;
      }


   }

   InitProcess()
   {
     var process = this.data.process;
     if (this.is_debug)
     {
       console.log(process);
     }

     this.process = process;

     if (process.running)
       this.in_process = process.running;

      if (process.running || process.preparing)
      {
         this.UpdateProgress();
         this.ResumeProcess();
     }
   }

   SelectAll(event)
   {
      var action = event.target.dataList.action;
      var target = event.target.dataList.target;

      if (action == 'select')
         var checked = true;
      else {
         var checked = false;
      }

    //  $('input[name^="' + target + '"]').prop('checked', checked).trigger('change');
      var inputs = document.querySelector('input[name^="' + target + '"]');
      for (var i = 0; i < inputs.length; i++)
      {
         inputs[i].checked = checked;
      }
   }

   AjaxCall(data)
   {
     var url = this.data.ajaxurl;
     var action = data.action;

     if (action == 'rta_start_process' || action == 'rta_stop_process')
     {
       var nonce = this.data.nonce_generate;
     }
     else if (action == 'rta_do_process')
     {
       var nonce = this.data.nonce_doprocess;
     }
     else if (action == 'rta_save_image_sizes')
     {
       var nonce =  this.data.nonce_savesizes;
     }

     var xhr = new XMLHttpRequest();

     data.nonce = nonce;

     var self = this;

     // Handler to pass on parsed responses onwards.
     var successFunction = function (event) {

       if (xhr.status !== 200)
       {
          var message = 'Error ' +  xhr.status + ' ' + xhr.statusText;
          var status = {} ;
          status.id = -1;
          status.message = message;
          status.error = true;
          self.AddStatus([status]);
       }
       if (typeof xhr === 'object' && xhr.responseText && xhr.responseText.length > 0 )
       {
          try {
            var response = JSON.parse(xhr.responseText);
          }
          catch(e)
          {
             console.error('Error parsing result!', xhr.responseText);
             self.CheckSubmitReady();
             return false;
          }
       }
       else {
          var response = {};
       }

       data.success.apply(self, [response]);


     }

     // @TODO AJAX CALLS!
     xhr.open('POST', url);
     //xhr.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
     xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

     xhr.addEventListener('load', successFunction);
     xhr.addEventListener('error', function (event)
     {
        console.error(event);
     });

     if (data.form)
     {
        data.form = new URLSearchParams(new FormData(data.form)).toString();
     }


     var params = new URLSearchParams();

     if (typeof data !== 'undefined' && typeof data == 'object')
     {

        for(var key in data)
        {
            if (key == 'success')
            {
               continue; // don't send handlers.
            }
            params.append(key, data[key]);
        }
     }

     xhr.send(params.toString());

   }

   StartProcess(event)
   {
     event.preventDefault();

     this.ResetPanels();
     this.TogglePanel('main', true);
     this.TogglePanel('loading', true);


     var status = new Object;
     status.id = -1;
     status.message = this.strings.status_start;
     status.error = true;
     this.AddStatus([status]);

     this.in_process = true;
     this.is_stopped = false;
     this.CheckSubmitReady();

     var self = this;
     var action = 'rta_start_process';
     var form = document.getElementById('rtaform_process');

     var data = {
        action: action,
        form: form,
        success: this.StartProcessResponse,
     };
     this.AjaxCall(data);

   }

   StartProcessResponse(response)
   {
     if (response.status)
     {
        this.AddStatus(response.status);
     }
     this.process = response;
     this.UpdateProgress();
     this.DoProcess();

   }

   ResumeProcess()
   {
     this.TogglePanel('main', true);
     this.TogglePanel('loading', true);
     this.TogglePanel('progress', true);

     var status = {
       id: -1,
       message: this.strings.status_resume,
       error: true,
     };
     this.AddStatus([status]);

/*
     $([document.documentElement, document.body]).animate({
          scrollTop: $("section.regenerate").offset().top
      }, 1000);
*/
     this.ProcessStoppable();
     this.TogglePanel('loading', false);
     this.PauseProcess();
   //  this.doProcess();

   }

   DoProcess()
   {
       if (this.is_stopped)
         return; // escape if process has been stopped.

       this.in_process = true;
       this.in_ajax = true;
       this.CheckSubmitReady();

       this.TogglePanel('progress', true);
       this.ProcessStoppable();

       var self = this;

       var action = 'rta_do_process';
       var data = {
          action: action,
          success: this.DoProcessResponse,
          error: this.DoProcessError,
       };

       this.AjaxCall(data);

   }

   GetButton(name)
   {

      if ('pause' === name)
      {
          var buttonName = '.button.pause-process';
      }
      else if ('resume' === name)
      {
          var buttonName = '.button.resume-process';
      }
      else if ('stop' === name)
      {
          var buttonName = '.button.stop-process';
      }

      var button = document.querySelector(buttonName);
      return button;
   }

   DoProcessResponse(response)
   {
         this.in_ajax = false;
         this.TogglePanel('loading', false);
         var self = this;

         if (typeof response.items !== 'undefined') // return is a process var..
         {
           this.process = response;
           this.UpdateProgress();
         }

         if (response.status)
         {
           this.AddStatus(response.status);
         }
         if( response.running || response.preparing ) {

             if (! this.is_stopped)
             {
             //  self.offset = response.current;
               setTimeout(function(){
                  self.DoProcess.apply(self);
               },500);
             }
             else
             {
                 this.in_process = false;
                 this.TogglePanel('paused', true);
                 this.TogglePanel('pausing', false);
                 var pauseButton = this.GetButton('pause');
                 pauseButton.disabled = false;
             }
         }else{
             this.FinishProcess(); // done, or so.
         }
   }

   DoProcessError(response)
   {
     this.TogglePanel('loading', false);
     var status = new Object;
     status.id = -1;
     status.message = response.status + ' ' + response.statusText + ' :: ';
     status.error = true;
     this.AddStatus([status]);

     setTimeout(function(){ this.DoProcess(); },1000);
   }

   // Seemingly only an interface switch . Possibly better to arrange as event / differently.
   ProcessStoppable()
   {
      var stoppable = false;

       if (this.in_process)
       {
           stoppable = true;
       }


      var stopButton = this.GetButton('stop');
      var pauseButton = this.GetButton('pause');
      var resumeButton = this.GetButton('resume');

       if (stoppable)
       {
         stopButton.disabled = false;
         pauseButton.disabled = false;
         resumeButton.disabled = false;
       }
       else
       {
         stopButton.disabled = true;
         pauseButton.disabled = true;
         resumeButton.disabled = true;

       }

   }

   ProcessActionEvent(event )
   {
       event.preventDefault();
       var target = event.target;

       console.log('ProcessActionEvent', event);
       if (target.id.length === 0)
       {
          target = target.parentElement;
       }

       console.log('target', target, target.id.length);
        // @todo Resume / Pause from here.
       if ('pauseProcess' === target.id)
       {
          this.PauseProcess();
       }
       else if ('resumeProcess'  === target.id)
       {
         this.ResumeProcess();
       }
       else if ('stopProcess' === target.id)
       {
          this.StopProcess();
       }
   }

   FinishProcess()
   {
     this.in_process = false;
     this.is_interrupted_process = false;

     this.TogglePanel('success', true);
     this.TogglePanel('paused', false);
     this.TogglePanel('pausing', false);
     this.ProcessStoppable();

     var status = new Object;
     status.id = -1;
     status.message = this.strings.status_finish;
     status.error = true;
     this.AddStatus([status]);

     this.CheckSubmitReady();
   }

   PauseProcess(event)
   {
       // Disable button pending action.
       var pauseButton = this.GetButton('pause');
       var resumeButton = this.GetButton('resume');

       pauseButton.disabled = true;



       if (this.is_stopped == false)
       {
         this.is_stopped = true;

         pauseButton.style.display = 'none';
         resumeButton.style.display = 'inline';

         //$('.pause-process .pause').css('display', 'none');
         //$('.pause-process .resume').css('display', 'inline');

         if (this.in_ajax == false)
         {
           this.TogglePanel('paused', true);
           pauseButton.disabled = false;
         }
         else
         {
           this.TogglePanel('pausing', true);
         }

       }
       else if (this.is_stopped == true)
       {
         this.is_stopped = false;
         pauseButton.style.display = 'inline';
         resumeButton.style.display = 'none';

         //$('.pause-process .pause').css('display', 'inline');
         //('.pause-process .resume').css('display', 'none');

         var self = this;
         this.TogglePanel('pausing', false);
         this.TogglePanel('paused', false);
         this.TogglePanel('loading', true);

         setTimeout(function(){
             pauseButton.disabled = false;
             self.DoProcess();
         },500);
       }

   }

   StopProcess()
   {
     if (window.confirm(this.strings.confirm_stop))
     {
       this.is_stopped = true;

       this.TogglePanel('loading', true);
       var self = this;

       var action = 'rta_stop_process';
       var data = {
          action: action,
          type: 'submit',
          success: this.StopProcessResponse,
       };

       this.AjaxCall(data);
     }
   }

   StopProcessResponse(response)
   {
     if (response.status)
     {
       this.AddStatus(response.status);
     }
     this.process = false;
     this.FinishProcess();
     this.TogglePanel('loading', false);
   }

   UpdateProgress() {

       if (false === this.process)
       {
         return;
       }

       var items = parseInt(this.process.items);
       var done = parseInt(this.process.done);
       var total = (items + done);
       var errors = this.process.errors;

       if (done == 0 && total > 0)
       {
         var percentage_done = 0;
       }
       else if (total > 0)
       {
         var percentage_done = Math.round( (done/total) * 100);
       }
       else
       {
         var percentage_done = 100;
       }

       var total_circle = 289.027;
       if(percentage_done > 0) {
           total_circle = Math.round(total_circle-(total_circle*percentage_done/100));
       }
       var circularBar = document.querySelector('.CircularProgressbar-path');
       var circularText = document.querySelector('.CircularProgressbar-path');

       circularBar.style.strokeDashoffset = total_circle +  'px';
       circularText.textContent = percentage_done + '%';

      // $(".CircularProgressbar-path").css("stroke-dashoffset",total_circle+"px");
      // //$(".CircularProgressbar-text").html(percentage_done+"%");

       var progressCurrent = document.querySelector('.progress-count .current');
       var progressTotal = document.querySelector('.progress-count .total');

       progressCurrent.textContent = done;
       progressTotal.textContent = total;

   }

   TogglePanel(name, show)
   {
     var panel;
     var panelName;

     switch(name)
     {
       case 'main':
         panelName = 'section.regenerate';
       break;
       case 'loading':
         panelName = ".rta_wait_loader";
       break;
       case 'paused':
         panelName = ".rta_wait_paused";
       break;
       case 'pausing':
        panelName = '.rta_wait_pausing';
       break
       case 'progress':
         panelName = '.rta_progress_view';
       break;
       case 'thumbnail':
         panelName = '.rta_thumbnail_view';
       break;
       case 'success':
         panelName = '.rta_success_box';
       break;
       case 'notices':
         panelName = '.rta_notices';
       break;
     }

     var panel = document.querySelector(panelName);

     if (null === panel)
     {
        console.error('Panel ' + panelName + ' could not be loaded!');
        return false;
     }

     if (true === show)
     {
       if (panel.classList.contains('rta_hidden'))
       {
          panel.style.display = 'block';
       }
       else {
         panel.style.opacity = 1;
       }

       panel.classList.remove('rta_panel_off');
     }
     else if (false === show)
     {
       if (panel.classList.contains('rta_hidden'))
         panel.style.display = 'none';
       else
         panel.style.opacity = 0;

       panel.classList.add('rta_panel_off');
     }

   }

   ResetPanels()
   {
     this.TogglePanel('loading', false);
     this.TogglePanel('paused', false);
     this.TogglePanel('pausing', false);
     this.TogglePanel('progress', false);
     this.TogglePanel('thumbnail', false);
     this.TogglePanel('success', false);
     this.TogglePanel('notices', false);

     var statusUpdates = document.querySelectorAll('.rta_notices .statuslist li');
     for (var i = 0; i < statusUpdates.length; i++)
     {
        statusUpdates[i].remove();
     }

     // Flick back the pause / resume thing.
     var pauseButton = this.GetButton('pause');
     var resumeButton = this.GetButton('resume');

     pauseButton.style.display = 'inline';
     resumeButton.style.display = 'none';

     //$('.pause-process .pause').css('display', 'inline');
     //$('.pause-process .resume').css('display', 'none');

   }

   AddStatus(status) {
     //  var $ = jQuery;
       this.TogglePanel('notices', true);

       if(status!="") {
           var html = '';

           for(var i=0;i < status.length;i++) {
               var item = status[i];
               var item_class = '';

               if (item.error)
                 item_class = 'error';
               else
                 item_class = '';

                 // @todo Move these to named constants.
               if(item.status == 1) // status 1 is successfully regenerated  thumbnail with URL in message.
               {
                 this.ShowThumb(item.image);
                 var messageElement = document.querySelector('.thumb-message');
                 if (null !== messageElement)
                 {
                    messageElement.innerHTML = item.message;
                 }
                 continue;
               }


               //html = html+'<li class="list-group-item ' + item_class + '">'+ item.message +'</li>';
               var listItem = document.createElement('li');
               if (item_class.length > 0)
               {
                 listItem.classList.add(item_class);
               }
               listItem.textContent = item.message;
               html = listItem;
           }

           var statusList = document.querySelector('.rta_status_box ul.statuslist');
           statusList.append(html);
           //(".rta_status_box ul.statuslist").append(html);

       }
   }

   ShowThumb(imgUrl)
   {
     this.TogglePanel('thumbnail', true);
     var previewImage = document.querySelector(".rta_progress .images img");
     previewImage.src = imgUrl;
   }

   AddImageRowEvent() {

      // var $ = jQuery;
      //
       var container = document.querySelector('.table.imagesizes'); // $('.table.imagesizes'); // $("#rta_add_image_size_container");

       var uniqueId = Math.random().toString(36).substring(2) + (new Date()).getTime().toString(36);

       var proto = document.querySelector('.row.proto');

       var row = proto.cloneNode(true); //$('.row.proto').clone();
       row.id = uniqueId;
       row.classList.remove('proto');

       var tableInputs = row.querySelectorAll('input, select');
       for (var i = 0; i < tableInputs.length; i++)
       {
          var eventName = (tableInputs[i].tagName == 'BUTTON') ? 'click' : 'change';
          tableInputs[i].addEventListener(eventName, this.ImageSizeChangeEvent.bind(this));
       }

       var removeButton = row.querySelector('.btn_remove_row');
      // for (var i = 0; i < removeButtons.length; i++)
    //   {
          removeButton.addEventListener('click', this.RemoveRowEvent.bind(this));
    //   }

       //$(row).attr('id', uniqueId);
       //$(row).removeClass('proto');
       container.append(row); // row.css('display', 'flex')

       var header = container.querySelector('.header');
       if (header.classList.contains('rta_hidden'))
       {
          header.classList.remove('rta_hidden');
       }
    //   container.find('.header').removeClass('rta_hidden');
   }

   // Image size changed or save Needed.
   ImageSizeChangeEvent(event) {
       event.preventDefault();
       var target = event.target;

       if (target.tagName == 'BUTTON')
       {

       }
       else {
         var parentElement = target.closest('.row');
         if (null !== parentElement)
         {
           this.UpdateThumbName(parentElement);
         }
       }

       this.SaveImageSizes();
   }

   UpdateThumbName(row) {

           var inputs = row.querySelectorAll('input,select');

           var name = 'rta_thumb';

           for (var i = 0; i < inputs.length; i++)
           {
               var input = inputs[i];
               var inputName = input.name;
               if (inputName.indexOf('pname') !== -1)
               {
                  var pname = input.value;
               }
               else if (inputName.indexOf('name') !== -1)
               {
                  var currentName = input.value;

                  var nameInput = input;
               }
               else if (inputName.indexOf('width') !== -1)
               {
                 var width = input.value;
               }
               else if (inputName.indexOf('height') !== -1)
               {
                  var height = input.value;
               }
               else if (inputName.indexOf('cropping') !== -1)
               {
                  var cropping = input.options[input.selectedIndex].value;
               }
           }

        /*   var old_name = $("#"+rowid+" .image_sizes_name").val();
           var name = "rta_thumb";
           var width = $("#"+rowid+" .image_sizes_width").val();
           var height = $("#"+rowid+" .image_sizes_height").val();
           var cropping = $("#"+rowid+" .image_sizes_cropping").val();
           var pname = $("#"+rowid+" .image_sizes_pname").val(); */

           if (width <= 0) width = '';  // don't include zero values here.
           if (height <= 0) height = '';
           var slug = (name+" "+cropping+" "+width+"x"+height).toLowerCase().replace(/ /g, '_');

           // update the image size selection so it keeps checked indexes.
           var input = document.querySelector('input[name^="regenerate_sizes"][value="' + currentName + '"]');

           // If item is new, this won't exist, skip?
           if (input !== null)
           {
             input.value = slug;
             var inputKeep = document.querySelector('input[name="keep_' + currentName + '"]');
             inputKeep.name = 'keep_' + slug;
           }

           nameInput.value = slug;

   }

   SaveImageSizes() {
       this.settings_doingsave_indicator(true);
       var action = 'rta_save_image_sizes';
       var the_nonce = rta_data.nonce_savesizes;

       var self = this;
       var form = document.getElementById('rta_settings_form');

       var data = {
          action: action,
          form: form,
          success: this.SaveImageSizesEvent,
       };

       this.AjaxCall(data);

   }

   SaveImageSizesEvent(response)
   {

     if (! response.error)
     {
       if (response.new_image_sizes)
       {
         var list = document.querySelector('.thumbnail_select .checkbox-list');
         list.innerHTML = response.new_image_sizes;

         var items = list.querySelectorAll('input, select'); // rebind events to input / select of this list
         for (var i = 0; i < items.length; i++)
         {
             var item = items[i];
             item.addEventListener('change', this.ShowSaveIndicatorEvent.bind(this))
             item.addEventListener('change', this.UpdateSettingsEvent.bind(this));
             if (item.name.indexOf('regenerate_sizes') !== -1)
             {
                item.addEventListener('click', this.ToggleDeleteItems.bind(this));
                item.addEventListener('change', this.CheckOptionsVisible.bind(this));
             }
         }
         this.CheckOptionsVisible();
       }
     }
     this.is_saved = true;
     this.settings_doingsave_indicator(false);
     this.CheckSubmitReady();
     this.ToggleDeleteItems();
   }

   settings_doingsave_indicator(show)
   {
       var saveIndicator = document.querySelector('.form_controls .save_indicator');

       if (show)
       {
           saveIndicator.style.display =  'inline-block';
       }
       else {
          saveIndicator.style.display = 'none';
       }
   }

   ShowSaveIndicatorEvent(event)
   {
       this.is_saved = false;
       this.CheckSubmitReady();
   }

   RemoveRowEvent(event) {

       var target = event.target;

       if (target.classList.contains('dashicons')) // One up if icon is clicked.
       {
          target = target.parentElement;
       }

       var parentElement = target.closest('.row');
       var rowid = parentElement.id;

       if(confirm( this.strings.confirm_delete )) {
           var sizeEl = parentElement.querySelector('.image_sizes_name');
           var sizeName = sizeEl.value;

           var input = document.querySelector('input[name^="regenerate_sizes"][value="' + sizeName + '"]');

           if (null !== input)
           {
             var inputParent = input.closest('.item');
             inputParent.remove();
           }

           //$('input[name^="regenerate_sizes"][value="' + intName + '"]').remove(); // remove the checkbox as well, otherwise this will remain saved.

           parentElement.remove();

           this.SaveImageSizes();
       }
   }

   CheckOptionsVisible()
   {
       var thumbnails = document.querySelectorAll('.checkbox-list .item');

       for (var i = 0; i < thumbnails.length; i++)
       {
           var currentItem = thumbnails[i];
           var input = currentItem.querySelector('input');
           var optionElement = currentItem.querySelector('.options');

           if (true === input.checked)
           {
                optionElement.classList.remove('hidden');
           }
           else
           {
                optionElement.classList.add('hidden');
           }
       }

   }

   ToggleDeleteItems()
   {

     var removeSetting = document.querySelector('input[name="del_associated_thumbs"]');
     var removingUnselected = removeSetting.checked;
     var has_items = false;

     var thumbnails = document.querySelectorAll('.checkbox-list .item');

    // console.log(thumbnails);

     for(var i = 0; i < thumbnails.length; i++)
     {
        var currentItem = thumbnails[i];
        var input = currentItem.querySelector('input');
        var label = currentItem.querySelector('label');

        if (true === removingUnselected && false === input.checked)
        {
           has_items = true;

           label.classList.add('warning-removal');
           var warnNode = currentItem.querySelector('span.icon-warning');
           if (null === warnNode)
           {
             var warnNode = document.createElement('span');
             warnNode.classList.add('dashicons', 'dashicons-no','icon-warning');
             label.insertBefore(warnNode, input);
           }

        } else {
           label.classList.remove('warning-removal');
           var warnNode = currentItem.querySelector('span.icon-warning');
           if (null !== warnNode)
           {
             warnNode.remove();
           }
        }
        //console.log(input);
     }

     var warning = document.getElementById('warn-delete-items');

     if (true === has_items)
     {
         warning.classList.remove('rta_hidden');
     }
     else {
         warning.classList.add('rta_hidden');
     }

   }

   ToggleWindow(e)
   {
       var target = event.target;
       if (! target.classList.contains('toggle-window'))
       {
         target = target.parentElement;
       }

       var windowName = target.dataset.window;
       var windowElement = document.getElementById(windowName);
       var arrowEl = target.querySelector('span.dashicons');

       if (windowElement.classList.contains('window-up'))
       {
         windowElement.style.display = 'block';
         windowElement.classList.remove('window-up');
         windowElement.classList.add('window-down');

         arrowEl.classList.remove('dashicons-arrow-down');
         arrowEl.classList.add('dashicons-arrow-up');
       }
       else {
         windowElement.classList.add('window-up');
         windowElement.classList.remove('window-down');

         arrowEl.classList.add('dashicons-arrow-down');
         arrowEl.classList.remove('dashicons-arrow-up');

         windowElement.style.display = 'none';

       }
   }


} // Class


var r = new RtaJS();
r.Init();
