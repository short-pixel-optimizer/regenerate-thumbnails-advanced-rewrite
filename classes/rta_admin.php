<?php
namespace ReThumbAdvanced;
use \ReThumbAdvanced\ShortPixelLogger\ShortPixelLogger as Log;
use \ReThumbAdvanced\Notices\NoticeController as Notice;

/**
 * Class that will hold functionality for admin side
 *
 * PHP version 5
 *
 * @category   Admin Side Code
 * @package    Regenerate Thumbnails Advanced
 * @author     ShortPixel
*/

class RTA_Admin extends rtaController
{
    const PERIOD_ALL = 0;
    const PERIOD_DAY = 1;
    const PERIOD_WEEK = 2;
    const PERIOD_MONTH = 3;
    const PERIOD_3MONTH = 4;
    const PERIOD_6MONTH = 5;
    const PERIOD_YEAR = 6;

    protected $viewControl = null; // controller that handles the admin page.

    private $process_remove_thumbnails = false;
    private $process_delete_leftmetadata = false;
    private $process_cleanup_metadata = false;

    private $process;
    private $currentImage;

    //Admin side starting point. Will call appropriate admin side hooks
    public function __construct() {
        $this->customThumbSuffixes = array('_c', '_tl', '_tr', '_br', '_bl');

      //  do_action('rta_before_admin', $this );
        //All admin side code will go here
        $this->process = $this->get_process();
        if ($this->process === false) // no saved state.
        {
          $process = new \stdClass;  // format of process.
          $process->total = 0;
          $process->current = 0;
          $process->formData = null;
          $process->running = false;
          $process->status = array(); //array('error' => false, 'status' => null, 'message' => null);

          $this->process = $process;
        }
        //add_filter( 'plugin_action_links_' . plugin_basename(RTA_PLUGIN_FILE), array($this, 'generate_plugin_links'));//for plugin settings page

      //  do_action('rta_after_admin', $this );
    }


    /**
     * schedules the image's attachment post to be deleted if all the thumbnails are missing or just removes the missing thumbnails from the sizes array if some still are present.
     * @param $image_id
     * @param $image_posts_to_delete
     * TODO Remove this pass by reference.
     */
    function rta_del_leftover_metadata($image_id, $fullsizepath, &$image_posts_to_delete) {
        $original_meta = wp_get_attachment_metadata($image_id);
        $allSizesMissing = true;
        $someSizesMissing = false;
        if(isset($original_meta['sizes']) && is_array($original_meta['sizes'])) {
            foreach ($original_meta['sizes'] as $key => $size) {
                if(isset($size['file'])) {
                    $thumb = (is_array($size['file'])) ? $size['file'][0] : $size['file'];
                    if(file_exists(trailingslashit(dirname($fullsizepath)) . $thumb)) {
                        $allSizesMissing = false;
                    } else {
                        unset($original_meta['sizes'][$key]);
                        $someSizesMissing = true;
                    }
                }
            }
        }
        if($allSizesMissing) {
            $image_posts_to_delete[] = $image_id;
        } elseif($someSizesMissing) {
            wp_update_attachment_metadata($image_id, $original_meta);
        }
    }

    public function regenerate_single_image($attach_id)
    {
        $form = $this->getFormData();
        $form['posts_per_page'] = -1;
        $form['attach_id'] = $attach_id;

        if ($this->start_process($form))
        {
          $this->regenerate_thumbnails();
        }

        foreach($this->process->status as $statusName => $statusItem)
        {
            if ($statusItem['error'])
              Notice::addError($statusItem['message']);
            elseif ($statusItem['status'] == 1)
              Notice::addSuccess(__('Image thumbnails regenerated', 'regenerate-thumbnails-advanced'));
            else
              Notice::addNormal($statusItem['message']);
        }

        $this->end_process();
    }

    public function getQueryDate($period)
    {
      $date = false;
      $args = false;
      switch (intval($period)) {
          case self::PERIOD_ALL:
              break;
          case self::PERIOD_DAY:
            $date = '-1 day';
            $args = array('after' => '1 day ago', 'before' => 'tomorrow');
      break;
          case self::PERIOD_WEEK:
            $date = '-1 week';
            $args = array('after' => '1 week ago', 'before' => 'tomorrow');
            break;
          case self::PERIOD_MONTH:
            $date = '-1 month';
            $args = array('after' => '1 month ago', 'before' => 'tomorrow');
            break;
        case self::PERIOD_3MONTH:
            $date = '-3 month';
            $args = array('after' => '3 months ago', 'before' => 'tomorrow');
        break;
        case self::PERIOD_6MONTH:
            $date = '-6 month';
            $args = array('after' => '6 months ago', 'before' => 'tomorrow');
            break;
        case self::PERIOD_YEAR:
            $date = '-1 year';
            $args = array('after' => '1 year ago', 'before' => 'tomorrow');
        break;
      }
      $result = array('date' => $date, 'args' => $args);
      return $result;
    }

    public function getThumbQueryArgs($data, $posts_per_page, $offset)
    {

      $query_args = array(
          'post_type' => 'attachment',
      //    'post_mime_type' => 'image', // @todo Crashed images can have no mime_type.
          'posts_per_page' => $posts_per_page,
          'post_status' => 'any',
          'offset' => $offset,
      );

      if (isset($data['period'])) {
          $period = intval($data['period']);
          $date_query = $this->getQueryDate($period);

          if (is_array($date_query['args']))
          {
            $query_args['date_query'] = $date_query['args'];
          }
      }

      if ($data['regenonly_featured'])
      {
        global $wpdb;
        $meta_query_args = array(
            array(
             'key' => '_thumbnail_id',
             'compare' => 'IN',
            ),
          );

          $sql = ' SELECT meta_value from ' . $wpdb->postmeta . ' where meta_key = "_thumbnail_id"';
          $result = $wpdb->get_col($sql);

         if (count($result) > 0)
           $query_args['post__in'] = array_values($result);

        if (count($result) === 0)
          return false; // no thumbnails, nothing to process
      }

      if (isset($data['attach_id']))
      {
        $query_args['p'] = $data['attach_id'];
      }

      return $query_args;
    }

    /** Collect form data, make a storable process array out of it */
    public function getFormData()
    {
        $defaults = array(
            'period' => self::PERIOD_ALL,
            'regenonly_featured' => false,
            'del_associated_thumbs' => false,
            'del_leftover_metadata' => false,
            'process_clean_metadata' => false,
        );

        $data = array();
        $form = isset($_POST['genform']) ? $_POST['genform'] : '';
        parse_str($form, $data);

        return wp_parse_args($data, $defaults);
    }

    // Seperate function for ajax, to clean main function clean of json and exists.
    public function ajax_start_process()
    {
      if (isset($_POST['genform']))
      {
          $form = $this->getFormData();
      }
      else {
      //  $this->jsonResponse(array('error' => true, 'logstatus' => __("No Form Data was send", 'message' => "Site error, No Data"));
        exit();
      }

      $nonce = isset($_POST['gen_nonce']) ? $_POST['gen_nonce'] : false;
      if (! wp_verify_nonce($nonce, 'rta_regenerate_thumbnails'))
      {
            $this->add_status('no_nonce');
            $this->jsonResponse($this->get_json_process());
            exit();
      }

      $result = $this->start_process($form);
      $this->jsonResponse($this->get_json_process());

      exit();

    }

    /** Starts a new generate process. Queries the totals based on form input
    * @param $form Array with FormData
    * @return boolean true if all went ok, false if error occured
    * Status and errors can be gotten from process attribute.
    */
    public function start_process($form)
    {

        $posts_per_page = -1; // all
        $offset = 0; // all

    //    $post_count = 0;
  /*      $process = new \stdClass;
        $process->total = 0;
        $process->current = 0; */
        $this->process->formData = $form;
        $this->process->current = 0;

        Log::addDebug('Start Process FormData', $form);
        $query_args = $this->getThumbQueryArgs($form, $posts_per_page, $offset);

        if ($query_args === false) // zero result situation.
        {
           $this->add_status('no_images');
           $this->end_process();
           return false;
        }

        $the_query = new \WP_Query($query_args);


        Log::addDebug('Start Process Start with args', $query_args);
        $count = $the_query->found_posts;
        $this->process->total = $count;

        if ($count == 0)
        {
            $this->add_status('no_images');
            $this->end_process();
            return false;
        }


        wp_reset_query();
        wp_reset_postdata();

        // This is something legacy .  @todo remove at some point.
        delete_option('rta_get_all_files');

        $this->save_process($this->process);
        return true;

    }

    protected function save_process($process)
    {
        $p = clone $process; // passed by reference, want to keep it in current scope.
        unset($p->status); // don't save status.
        update_option('rta_image_process', $p, false);
    }

    protected function get_process()
    {
       $process = get_option('rta_image_process', false);
       if (! is_object($process))
        return false;

       $process->status = array(); // process is saved without it.
       if ($process->current == $process->total)
       {
         return false; // don't consider a done process a process
       }
       return $process; // if false, no process running. If object, running process there.
    }

    protected function end_process()
    {
        $this->process->running = false;
        delete_option('rta_image_process');
    }

    // retrieve JS friendly overview, if we are in process and if yes, what are we doing here.
    public function get_json_process()
    {
        //$json = array('running' => false);

        $json['running'] = $this->process->running;
        $json['current'] = $this->process->current;
        $json['total'] = $this->process->total;
        $json['status'] = $this->process->status;
        return $json;
    }

    protected function add_status($name, $args = array() )
    {
      $status = array('error' => true, 'message' => __('Unknown Error occured', 'regenerate-thumbnails-advanced'), 'status' => 0);

      switch($name)
      {
          case 'no_nonce':
              $status['message'] = __('Site error, Invalid Nonce', 'regenerate-thumbnails-advanced');
              $status['status']  = -1;
          break;

          case 'no_images':
             $status['message'] = __('No images found for this period and/or settings or none uploaded', 'regenerate-thumbnails-advanced');
             $status['status'] = 0;
             $status['error'] = false;
          break;
          case 'file_missing':
             $name = isset($args['name']) ? $args['name'] : '';
             $status['message'] =  __(sprintf('<b>%s</b> is missing or not an image file',$name), 'regenerate-thumbnails-advanced');
             $status['status'] = -2;
          break;
          case 'error_metadata':
            $name = isset($args['name']) ? $args['name'] : '';
            $status['message'] = __(sprintf('<b>%s</b> failed on metadata. Possible issue with image',$name), 'regenerate-thumbnails-advanced');
            $status['status'] = -3;
          break;
          case 'request_stop':
             $status['message'] = __('Process stopped on request', 'regenerate-thumbnails-advanced');
             $status['status'] = -4;
          break;
          case 'regenerate_success':
             $thumb = isset($args['thumb']) ? $args['thumb'] : '';
             $status['message'] = $thumb;
             $status['status'] = 1;
             $status['error'] = false;
          break;

      }


      $this->process->status[] = $status;
    }

    // generate thumbnails. @todo Update process, so it does it by 5 or so images, not the one-by-one boredom.
    public function regenerate_thumbnails() {

        $form = $this->process->formData;
        $this->process->running = true;

        $del_thumbs = isset($form['del_associated_thumbs']) ? $form['del_associated_thumbs'] : false;
        $del_leftover_metadata = isset($form['del_leftover_metadata']) ? $form['del_leftover_metadata'] : false;

        $this->process_remove_thumbnails = $del_thumbs;
        $this->process_delete_leftmetadata = $del_leftover_metadata;
        $this->process_clean_metadata = isset($form['process_clean_metadata']) ? $form['process_clean_metadata'] : false;

        $has_period = (isset($form['period']) && $form['period'] > 0) ? true : false;
        //$is_featured_only = (isset($data['regenonly_featured']) ) ? true : false;

        //$process_type = (isset($_POST['type'])) ? sanitize_text_field($_POST['type']) : false;
        $period = isset($form['period']) ? intval($form['period']) : -1;

        $posts_per_page = apply_filters('rta/process/per_page',  $form['posts_per_page']) ;
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

        $query_args = $this->getThumbQueryArgs($form, $posts_per_page, $offset);

        $imageUrl='';
        $error = array();

        $bulk = ($period == 0) ? true : false;

        $this->viewControl = new rtaAdminController($this);

        $the_query = new \WP_Query($query_args);
        $last_success_url = false;

        if ($the_query->have_posts()) {

            $image_posts_to_delete = array();

            // The process runs just 1 image per run here.
            while ($the_query->have_posts()) {
                $the_query->the_post();
                $image_id = $the_query->post->ID;

                Log::addDebug('Next Item in process ' .  $image_id);

                // simplification
                $this->currentImage = new rtaImage($image_id);
                $fullsizepath = $this->currentImage->getPath();

                if ($this->process_remove_thumbnails)
                {
                  $this->currentImage->setCleanUp(true);
                  Log::addDebug('Image thumbnails will be cleaned');
                }

              /*  if ($this->process_delete_leftmetadata)
                {
                  $this->currentImage->setMetaCheck(true);
                  Log::addDebug('Image Metadata Thumbs will be checked');
                } */

                // If Image doesn't exist at all, remove all metadata.
                if($this->process_delete_leftmetadata && ! $this->currentImage->exists() )  { // !file_exists($fullsizepath) )
                    $this->rta_del_leftover_metadata($image_id, $fullsizepath, $image_posts_to_delete);
                    Log::addDebug('Image did not exist. Removing leftover metadata');
                    continue; //the main image is missing, nothing to regenerate.
                }

                if (isset($data['mediaID'])){
                    $image_id = intval($data['mediaID']);
                }

                $filename_only = $this->currentImage->getUri(); //wp_get_attachment_thumb_url($image_id);

                if ($this->currentImage->isImage() ) {

                    @set_time_limit(900);
                    do_action('shortpixel-thumbnails-before-regenerate', $image_id);

                    //use the original main image if exists
                    $backup = apply_filters('shortpixel_get_backup', $fullsizepath);
                    if($backup && $backup !== $fullsizepath) {
                        Log::addDebug('Retrieving SPIO backups for process');
                        copy($fullsizepath, $backup . "_optimized_" . $image_id);
                        copy($backup, $fullsizepath);
                    }

                    add_filter('intermediate_image_sizes_advanced', array($this, 'capture_generate_sizes'));

                    $new_metadata = wp_generate_attachment_metadata($image_id, $fullsizepath);

                    remove_filter('intermediate_image_sizes_advanced', array($this, 'capture_generate_sizes'));

                    Log::addDebug('New Attachment metadata generated');
                    //restore the optimized main image
                    if($backup && $backup !== $fullsizepath) {
                        rename($backup . "_optimized_" . $image_id, $fullsizepath);
                    }

                    //get the attachment name
                    if (is_wp_error($new_metadata)) {
                      /*  $error[] = array('offset' => ($offset + 1), 'error' => $error, 'logstatus' => $logstatus, 'imgUrl' => $filename_only, 'period' => $period); */
                      $this->add_status('error_metadata', array('name' => basename($filename_only) ));
                    }
                    else if (empty($new_metadata)) {
                        $filename_only = $this->currentImage->getUri();
                      //  $logstatus = '<b>'.basename($filename_only).'</b> is missing';
                        Log::addDebug('File missing - New metadata returned empty', array($new_metadata, $filename_only,$fullsizepath ));
                        $this->add_status('file_missing', array('name' => basename($filename_only) ));
                        /*$error[] = array('offset' => ($offset + 1), 'error' => $error, 'logstatus' => $logstatus, 'imgUrl' => $filename_only,  'period' => $period); */
                    } else {

                        // going for the save.
                        $original_meta = $this->currentImage->getMetaData();
                        $result = $this->currentImage->saveNewMeta($new_metadata); // this here calls the regeneration.
                        Log::addDebug('Result :', $result);

                        $is_a_bulk = true; // we are sending multiple images.
                        $regenSizes = isset($new_metadata['sizes']) ? $new_metadata['sizes'] : array();

                        // Do not send if nothing was regenerated, otherwise SP thinks all needs to be redone
                        if (count($regenSizes) > 0)
                        {
                          do_action('shortpixel-thumbnails-regenerated', $image_id, $original_meta, $regenSizes, $is_a_bulk);
                        }
                        $last_success_url = $filename_only;

                    }
                    $imageUrl = $filename_only;
                    //$logstatus = 'Processed';
                    $filename_only = wp_get_attachment_thumb_url($image_id);
                } else {
                    $filename_only = $this->currentImage->getUri();
                    //$logstatus = '<b>'.basename($filename_only).'</b> is missing or not an image file';
                    Log::addDebug('File missing - Current Image reported as not an image', array($fullsizepath) );
                    $this->add_status('file_missing', array('name' => basename($filename_only)) );

                    /*$error[] = array('offset' => ($offset + 1), 'error' => $error, 'logstatus' => $logstatus, 'imgUrl' => $filename_only, 'startTime' => $data['startTime'], 'fromTo' => $data['fromTo'], 'type' => $process_type, 'period' => $period); */
                }

                $this->process->current++;
                $this->save_process($this->process);

            } // Post Loop

            foreach($image_posts_to_delete as $to_delete) {
                wp_delete_post($to_delete, true);
            }
        }

        // @todo move this to view maybe? Test maybe via WP function
        if (!extension_loaded('gd') && !function_exists('gd_info')) {
            $filename_only = 'No file';
            $logstatus = 'PHP GD library is not installed on your web server. Please install in order to have the ability to resize and crop images';
            $error[] = array('offset' => ($offset + 1), 'error' => $error, 'logstatus' => $logstatus, 'imgUrl' => $filename_only,  'period' => $period);
        }
        //increment offset
      //  $result = $offset + 1;
        if(!isset($filename_only)){
            $filename_only = 'No files';
        }
        Log::addDebug($error);
        //$finalResult = array('offset' => ($offset + 1), 'error' => $error, 'logstatus' => $logstatus, 'imgUrl' => $filename_only, 'startTime' => $data['startTime'], 'fromTo' => $data['fromTo'], 'period' => $period);

        if ($last_success_url) // if anything was done good, return last regenerated thumb.
          $this->add_status('regenerate_success', array('thumb' => $last_success_url));

        return true;
    }

    public function ajax_regenerate_thumbnails()
    {
      $nonce = isset($_POST['gen_nonce'])? $_POST['gen_nonce'] : false;
      if (! wp_verify_nonce($nonce, 'rta_regenerate_thumbnails'))
      {
        $this->add_status('no_nonce');
        $this->jsonResponse($this->get_json_process());
        exit();
      }

      $result = $this->regenerate_thumbnails();
      $this->jsonResponse($this->get_json_process());
      exit();
    }

    public function ajax_rta_stop_process()
    {
        $nonce = isset($_POST['gen_nonce'])? $_POST['gen_nonce'] : false;
        if (! wp_verify_nonce($nonce, 'rta_regenerate_thumbnails'))
        {
          $this->add_status('no_nonce');
          $this->jsonResponse($this->get_json_process());
          exit();
        }

        $this->process = $this->get_process();
        $this->end_process();
        $this->add_status('request_stop');

        $this->jsonResponse($this->get_json_process());
        exit();

    }

    public function capture_generate_sizes($full_sizes)
    {
        $do_regenerate_sizes = $this->viewControl->process_image_sizes; // to images to be regenerated.
        $process_options = $this->viewControl->process_image_options; // the setting options for each size.

        // imageMetaSizes is sizeName => Data based array of WP metadata.
        $imageMetaSizes = $this->currentImage->getCurrentSizes();

        $prevent_regen = array();
        foreach($do_regenerate_sizes as $rsize)
        {
          // 1. Check if size exists, if not, needs generation anyhow.
          if (! isset($imageMetaSizes[$rsize]))
          {
            Log::addDebug("Image Meta size setting missing - $rsize ");
            continue;
          }

          // 2. Check meta info (file) from the current meta info we have.
          $metaSize = $imageMetaSizes[$rsize];
          $overwrite = isset($process_options[$rsize]['overwrite_files']) ? $process_options[$rsize]['overwrite_files'] : false; // 3. Check if we keep or overwrite.

           if (! $overwrite)
           {
            // thumbFile is RELATIVE. So find dir via main image.
             $thumbFile = $this->currentImage->getDir() . $metaSize['file'];
             //Log::addDebug('Preventing overwrite of - ' . $thumbFile);
             if (file_exists($thumbFile)) // 4. Check if file is really there
             {
                $prevent_regen[] = $rsize;
                // Add to current Image the metaSize since it will be dropped by the metadata redoing.
                Log::addDebug('File exists on ' . $rsize . ' ' . $thumbFile . '  - skipping regen - prevent overwrite');
                $this->currentImage->addPersistentMeta($rsize, $metaSize);
             }
           }
        }


        // 5. Drop the 'not to be' regen. images from the sizes so it will not process.
        $do_regenerate_sizes = array_diff($do_regenerate_sizes, $prevent_regen);
        Log::addDebug('Sizes going for regen - ', $do_regenerate_sizes);

        /* 6. If metadata should be cleansed of undefined sizes, remove them from the imageMetaSizes
        *   This is for sizes that are -undefined- in total by system sizes.
        */
        if ($this->process_clean_metadata)
        {
            $system_sizes = $this->viewControl->system_image_sizes;

            $not_in_system = array_diff( array_keys($imageMetaSizes), array_keys($system_sizes) );
            if (count($not_in_system) > 0)
              Log::addDebug('Cleaning not in system', $not_in_system);

            foreach($not_in_system as $index => $unset)
            {
              unset($imageMetaSizes[$unset]);
            }
        }

        // 7. If unused thumbnails are not set for delete, keep the metadata intact.
        if (! $this->process_remove_thumbnails)
        {
          $other_meta = array_diff( array_keys($imageMetaSizes), $do_regenerate_sizes, $prevent_regen);
          if (count($other_meta) > 0)
            Log::addDebug('Image sizes not selected, but not up for deletion', $other_meta);

          foreach($other_meta as $size)
          {
             if (isset($imageMetaSizes[$size]))
               $this->currentImage->addPersistentMeta($size, $imageMetaSizes[$size]);
          }
        }

        $returned_sizes = array();
        foreach($full_sizes as $key => $data)
        {
            if (in_array($key, $do_regenerate_sizes))
            {
              $returned_sizes[$key] = $data;
            }
        }

        $this->currentImage->setRegeneratedSizes($do_regenerate_sizes);
        return $returned_sizes;
    }

    /* Saves and generates JSON response */
    public function view_generate_thumbnails_save()
    {
      $json = true;
      $view = new rtaAdminController($this);
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

} // Class
