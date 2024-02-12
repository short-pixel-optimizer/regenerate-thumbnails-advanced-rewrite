<?php
namespace ReThumbAdvanced;
use \ReThumbAdvanced\ShortPixelLogger\ShortPixelLogger as Log;
use \ReThumbAdvanced\Controllers\AdminController as AdminController;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}


// For communication with the Javascripting.
class AjaxController
{
   protected static $instance;
   protected $status; // /for the status.

   // Ok status
   const STATUS_OK  = 0;
   const STATUS_SUCCESS = 1;
   const STATUS_STOPPED = 10;
   const STATUS_FINISHED = 11;

   // Errors
   const ERROR_GENERAL = -1;
   const ERROR_NOFILE = -2;
   const ERROR_METADATA = -3;


   public static function getInstance()
   {
       if (is_null(self::$instance))
       {
          self::$instance = new ajaxController();
       }

       return self::$instance;
   }

   // hooks
   public function init()
   {
     // Process JS
     add_action( 'wp_ajax_rta_do_process', array($this, 'ajax_do_process') );
     add_action( 'wp_ajax_rta_start_process', array($this, 'ajax_start_process') );
     add_action( 'wp_ajax_rta_stop_process', array($this, 'ajax_stop_process'));

     // For settings page
     add_action( 'wp_ajax_rta_save_image_sizes', array($this,'view_generate_thumbnails_save' ) );

   }

   public function add_status($event_name, $args = array() )
   {
     $status = array('error' => true, 'message' => __('Unknown Error occured', 'regenerate-thumbnails-advanced'), 'status' => 0);

     $defaults =  array(
         'name' => false,
         'image' => $this->getURL('images/placeholder.svg'),  // @todo Add a placeholder here
         'count' => false,

     );

     $args = wp_parse_args($args, $defaults);

     $process = RTA()->process();

     switch($event_name)
     {
         case 'no_nonce':
             $status['message'] = __('Site error, Invalid Nonce', 'regenerate-thumbnails-advanced');
             $status['status']  = self::ERROR_GENERAL;
         break;
         case 'preparing':
             $status['message'] = __('Preparing Images and Thumbnails', 'regenerate-thumbnails-advanced');
             $status['status'] = self::STATUS_OK;
             $status['error'] = false;
         break;
         case 'prepared':
           $status['message'] = __('Prepared %s items', 'regenerate-thumbnails-advanced');
           $status['mask']  = array('count');
           $status['status'] = self::STATUS_OK;
           $status['error'] = false;
         break;
         case 'prepare_failed':
            $status['message'] = __('Preparing failed', 'regenerate-thumbnails-advanced');
            $status['status'] = self::ERROR_GENERAL;
         break;
         case 'no_images':
            $status['message'] = __('No images found for this period and/or settings or none uploaded', 'regenerate-thumbnails-advanced');
            $status['status'] = self::STATUS_OK;
            $status['error'] = false;
         break;
         case 'file_missing':
            $status['message'] =  __('<b>%s</b> is missing or not an image file', 'regenerate-thumbnails-advanced');
            $status['mask'] = array('name');
            $status['status'] = self::ERROR_NOFILE;
         break;
         case 'is_virtual':
            $status['message'] =  __('<b>%s</b> is offloaded', 'regenerate-thumbnails-advanced');
            $status['mask'] = array('name');
            $status['status'] = self::ERROR_NOFILE;
         break;
         case 'not_image':
            $status['message'] = __('<b>%s</b> skipped. MimeType is an image, but reports non-displayable', 'regenerate-thumbnails-advanced');
            $status['mask'] = array('name');
            $status['status']  = self::ERROR_NOFILE;
         break;
         case 'not_writable':
            $status['message'] = __('%s skipped. File is not writable', 'regenerate-thumbnails-advanced');
            $status['mask'] = array('name');
            $status['status']  = self::ERROR_NOFILE;
         break;
         case 'error_metadata':
           $status['message'] = __('<b>%s</b> failed on metadata. Possible issue with image', 'regenerate-thumbnails-advanced');
           $status['mask'] = array('name');
           $status['status'] = self::ERROR_METADATA;
         break;
         case 'request_stop':
            $status['message'] = __('Process stopped on request', 'regenerate-thumbnails-advanced');
            $status['status'] = self::STATUS_STOPPED;
         break;
         case 'regenerate_success':
            $status['message'] = sprintf(__('%s Success! %s %s has %s new thumbnails', 'regenerate-thumbnails-advanced'), '<strong>', '</strong>', $args['name'], $args['count'] );
            $status['status'] = self::STATUS_SUCCESS;
            $status['image'] = $args['image'];
            $status['error'] = false;

            if ($args['count'] > 0 || $args['removed'] > 0)
            {
               $process->addCounts($args);
            }
         break;

         default:
            $status['message']  = '[' . $args['name'] . ']';

         break;
     }

     if (isset($status['mask']))
     {
       $mask = $status['mask'];
       foreach($mask as $mname)
       {
          if ( isset($args[$mname]) )
          {
              $value = $args[$mname];
              $pos = strpos($status['message'], '%s');

              if ($pos !== false) {
                $status['message'] = substr_replace($status['message'], $value, $pos, strlen('%s'));
              }
          }
       }
     }

     if (isset($status['mask']))
      unset($status['mask']); // internal use.

     $this->status[] = $status;
   }

   public function get_status()
   {
     return $this->status;
   }

   public function clear_status()
   {
      $this->status = array();
   }


   public function ajax_start_process()
   {

     $this->checkNonce('rta_generate');

     if (isset($_POST['form']))
     {
         $options = $this->getFormData();
         $process = RTA()->process();

         $process->setOption($options);

         $this->add_status(__('Searching for items to add', 'regenerate-thumbnails-advanced') );
         $process->start();

         $result = $this->runprocess(); // This would mostly be preparing.
     }
     else {
       Log::addError('Ajax Start Process - Starting without form post');
       exit(0);
     }



     //wp_send_json($this->get_json_process());
   }

   /** Collect form data, make a storable process array out of it */
   protected function getFormData()
   {
       $data = array();
       $options = array();

       // fill from FORM to data, sanitize and then move to options for process
       $form = isset($_POST['form']) ? $_POST['form'] : '';
       parse_str($form, $data);

      $options['only_featured'] = (isset($data['regenonly_featured']) && '1' == $data['regenonly_featured']) ? true : false;


      $options['startstamp']  = -1;
      $options['endstamp'] = strtotime(time() . ' 23:59:59');

      return $options;
   }

   // retrieve JS friendly overview, if we are in process and if yes, what are we doing here.
   public function get_json_process()
   {
       //$json = array('running' => false);
       $process = RTA()->process();

       $counter = $process->getSetting('counter');
       $json = $process->getProcessStatus();
       $json['status'] = $this->status;
       return $json;

   }

   public function ajax_do_process()
   {
     $this->checkNonce('rta_do_process');

     $result = $this->runProcess();
     $this->jsonResponse($this->get_json_process());
     exit();
   }

   protected function runProcess()
   {
      // check if preparing, or running
      $process = RTA()->process();
      if ($process->get('preparing') == true)  // prepare loop
      {
         $count = $process->prepare();
         $this->add_status('prepared', array('count' => $count));
         $this->jsonResponse($this->get_json_process());
      }

      if ($process->get('running') == true)
      {
          $imageClass = RTA()->getClass('Image');

          $items = $process->dequeueItems();

          if (is_array($items))
          {
            foreach($items as $item)
            {
              $item_id = $item->item_id;
              $image = new $imageClass($item_id);
              $status = $image->process();
            }
          }

          $process->saveCounter();
      }

      if ($process->get('finished') == true)
      {
         if ($process->get('done') == 0) // if Q is finished with 0 done, it was empty.
         {
             $this->add_status('no_images');
         }

         $stats = $this->get_json_process();
         $process->end();
         $this->jsonResponse($stats);
      }
   }

   public function ajax_stop_process()
   {
       $this->checkNonce('rta_generate');

      // $this->process = $this->get_json_process();
       $process = RTA()->process();
       $process->end();
       $this->add_status('request_stop');

       $this->jsonResponse($this->get_json_process());
   }

   /* Saves and generates JSON response */
   public function view_generate_thumbnails_save()
   {
     $json = true;
     $controller =  RTA()->getClass('AdminController');
     $view = new $controller();

     $this->checkNonce('rta_save_image_sizes');
     $response = $view->save_image_sizes();

     if ($json)
     {
       $this->jsonResponse($response);
     }
     else
     {
       return $response;
     }
   }


   // No Noncense function.
   protected function checkNonce($action)
   {
      $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : false;

      if (! wp_verify_nonce($nonce, $action))
      {
           $this->add_status('no_nonce');
           Log::addError('Ajax Start Process - Nonce failed ' . $nonce . 'on ' . $action);
           $this->jsonResponse($this->get_json_process());
           exit();
      }

      return true;
   }

   /** Central function for JSON responses. Can be extended whenever needed */
   protected function jsonResponse($response)
   {
     wp_send_json($response);
     exit();
   }

   // @todo  This should probably be mergen with the one in @controller and AjaxController should move to Controllers . ( And distinction made between ViewControl and Control)
   private function getURL($path)
   {
       return plugins_url($path, RTA_PLUGIN_FILE);
   }

}
