'use strict';

document.addEventListener("DOMContentLoaded",
(function () {


  class Installer
  {
      action = 'shortpixel-upgrade-pro';
      form = null;

        Init()
        {
           console.log('init installer');
           this.form = document.getElementById('shortpixel-installer-form');
           this.form.addEventListener('submit', this.Submit.bind(this));
        }

        async Submit(event)
        {
          event.preventDefault();
          var target = event.target;
          var params = new URLSearchParams();
          params.append('action', this.action);

          var self = this;

          for(var i = 0; i < target.elements.length; i++)
          {
             params.append(target.elements[i].name, target.elements[i].value);
          }

          this.ToggleWrapper('sent');

          var response = await fetch(ajaxurl, {
              'method': 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
              },
              body: params.toString(),

          }).catch (function (error){
                  console.error('Install.js reporting issue', error);
            });

          if (response.ok)
          {
              var json = await response.json();
              this.ProcessResponse(json);
          }
        }

        ToggleWrapper(action)
        {
            var processWrapper = this.GetWrapper('process');
            var resultWrapper = this.GetWrapper('result');

            if ('sent' == action)
            {
              processWrapper.classList.remove('hidden');
            }
            else if ('received' == action)
            {
               processWrapper.classList.add('hidden');
               resultWrapper.classList.remove('hidden');
            }
        }

        GetWrapper(wrapper)
        {
             var wrapper = this.form.querySelector('.' + wrapper + '-wrapper');
             return wrapper;
        }

        ProcessResponse(json)
        {
            var resultWrapper = this.GetWrapper('result');
            var status = json.status;

            if ('error' === status)
            {
               resultWrapper.classList.add('error');
               resultWrapper.innerText = json.error;
            }
            else if ('success' === status)
            {
               resultWrapper.innerText = json.message;
               setTimeout(function(){ window.location.reload() }, 2000);
            }

            this.ToggleWrapper('received');

        }

  } // class


  if (document.getElementById('shortpixel-installer-form') !== null)
  {
      var install = new Installer();
      install.Init();
  }


})); // function
