<?php
/**
 * Class that will hold functionality for admin side
 *
 * PHP version 5
 *
 * @category   Admin Side Code
 * @package    Regenerate Thumbnails Advanced
 * @author     ShortPixel
*/

class RTA_Admin extends RTA
{
    const PERIOD_ALL = 0;
    const PERIOD_DAY = 1;
    const PERIOD_WEEK = 2;
    const PERIOD_MONTH = 3;
    const PERIOD_3MONTH = 4;
    const PERIOD_6MONTH = 5;
    const PERIOD_YEAR = 6;

    protected $viewControl = null; // controller that handles the admin page.

    //Admin side starting point. Will call appropriate admin side hooks
    public function __construct() {
        $this->customThumbSuffixes = array('_c', '_tl', '_tr', '_br', '_bl');

        do_action('rta_before_admin', $this );
        //All admin side code will go here

        add_action( 'admin_menu', array( $this, 'rta_admin_menus' ) );
        add_action( 'wp_ajax_rta_regenerate_thumbnails', array( $this, 'rta_regenerate_thumbnails') );
        //add_filter( 'image_size_names_choose', array( $this, 'rta_image_custom_sizes' ), 10, 1 );
        add_action( 'wp_ajax_rta_save_image_sizes', array($this,'view_generate_thumbnails_save' ) );

        add_filter( 'plugin_action_links_' . plugin_basename(RTA_PLUGIN_FILE), array(&$this, 'generate_plugin_links'));//for plugin settings page

        do_action('rta_after_admin', $this );
    }

    public function rta_admin_menus(){
        $title = __('Regenerate Thumbnails', 'regenerate-thumbnails-advanced');
        add_management_page($title, $title, 'manage_options', 'rta_generate_thumbnails', array( $this, 'view_generate_thumbnails' ));
    }

    public function generate_plugin_links($links) {
        $in = '<a href="tools.php?page=rta_generate_thumbnails">Settings</a>';
        array_unshift($links, $in);
        return $links;

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

    public function rta_regenerate_thumbnails() {
        if (defined('DOING_AJAX') && DOING_AJAX)
          $json = true;
        else {
          $json = false;
        }

        $nonce = isset($_POST['gen_nonce'])? $_POST['gen_nonce'] : false;
        if (! wp_verify_nonce($nonce, 'rta_regenerate_thumbnails'))
        {
              $this->jsonResponse(array('error' => true, 'logstatus' => "Invalid Nonce", 'message' => "Site error, Invalid Nonce"));
              exit();
        }

        if (isset($_POST['genform']))
        {
            $data = json_decode(html_entity_decode(stripslashes($_POST['genform'])), true);
        }
        else {
          $this->jsonResponse(array('error' => true, 'logstatus' => "No Data", 'message' => "Site error, No Data"));
        }

        $this->debug('POST VARS'); $this->debug($data);

        $has_period = (isset($data['period']) && $data['period'] > 0) ? true : false;
        $is_featured_only = (isset($data['regenonly_featured']) ) ? true : false;

        $process_type = (isset($_POST['type'])) ? sanitize_text_field($_POST['type']) : false;
        $period = isset($data['period']) ? intval($data['period']) : -1;

         if (! $process_type)
         {
           $this->jsonResponse(array('error' => true, 'logstatus' => "No Process defined", 'message' => "No Process Defined"));
         }

         if ($process_type == 'general')
         {
           $posts_per_page = -1; // all
           $offset = 0; // all
         }
         elseif ($process_type == 'submit')
         {
           $posts_per_page = 1;
           $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
         }

         $query_args = array(
             'post_type' => 'attachment',
             'post_mime_type' => 'image',
             'posts_per_page' => $posts_per_page,
             'post_status' => 'any',
             'offset' => $offset,
         );

         // 2. Check the period selection
         if (isset($data['period'])) {
             $period = intval($data['period']);
             $date_query = $this->getQueryDate($period);

             if (is_array($date_query['args']))
             {
               $query_args['date_query'] = $date_query['args'];
             }
         }

         if ($is_featured_only)
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

            if (count($result) == 0)
            {
              $return_arr = array('pCount' => 0, 'type' => $process_type, 'period' => $period);
              $this->jsonResponse($return_arr);
            }

         }

        // [BS] Both seem not to be in use anymore.
        if (! isset($data['startTime']))
          $data['startTime'] = 0;
        if (! isset($data['fromTo']))
          $data['fromTo'] = 0;

        $imageUrl='';
        $logstatus = '';

        switch ($process_type) {
            case 'general': // this function only gather amount of images to process, runs before 'submit'

                $the_query = new WP_Query($query_args);
                $this->debug('(General) Process Start with args'); $this->debug($query_args);
                $post_count = 0;

                if ($the_query->have_posts()) {
                    $post_count = $the_query->post_count;
                }else{
                    $logstatus = __('No images found for this period or none uploaded');
                    //$error[] = array('offset' => 0, 'logstatus' => $logstatus, 'imgUrl' => '', 'startTime' => '', 'fromTo' => '', 'type' => $process_type, 'period' =>'');
                    $finalResult = array('offset' => 0, 'error' => 1,'pCount'=>0, 'logstatus' => $logstatus, 'imgUrl' => '', 'type' => $process_type, 'period' =>'');
                    //header('Content-Type: application/json');
                    //echo json_encode($finalResult);
                    $this->jsonResponse($finalResult);
                    exit();
                }
                wp_reset_query();
                wp_reset_postdata();

                if (!isset($date) || empty($date)) {
                    $date = '';
                }
                delete_option('rta_get_all_files');
                $return_arr = array('pCount' => $post_count, 'type' => $process_type, 'period' => $period);
                //header('Content-Type: application/json');
                //echo json_encode($return_arr);
                $this->jsonResponse($return_arr);
                exit();
                break;
            case 'submit': // submit does the image processing
                $logstatus = '';
                $error = array();

                $del_thumbs = isset($data['del_associated_thumbs']) ? true : false;
                $del_leftover_metadata = isset($data['del_leftover_metadata']) ? true : false;
                $bulk = ($period == 0) ? true : false;

                $this->viewControl = new rtaAdminController($this);

                $the_query = new WP_Query($query_args);
                $this->debug('Regenerate Process started with'); $this->debug($query_args);

                if ($the_query->have_posts()) {

                    $image_posts_to_delete = array();

                    // The process runs just 1 image per run here.
                    while ($the_query->have_posts()) {
                        $the_query->the_post();
                        $image_id = $the_query->post->ID;

                        // simplification
                        $this->currentImage = new rtaImage($image_id);
                        $fullsizepath = $this->currentImage->getPath();

                        if ($del_thumbs)
                        {
                          $this->currentImage->setCleanUp(true);
                          $this->debug('Image thumbnails will be cleaned');
                        }

                        $this->debug( (array) $this->currentImage );

                        if($del_leftover_metadata && ! $this->currentImage->exists() )  { // !file_exists($fullsizepath) )
                            $this->rta_del_leftover_metadata($image_id, $fullsizepath, $image_posts_to_delete);
                            $this->debug('Image did not exist. Removing leftover metadata');
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
                                copy($fullsizepath, $backup . "_optimized_" . $image_id);
                                copy($backup, $fullsizepath);
                            }

                            //$original_meta = wp_get_attachment_metadata($image_id);

                            add_filter('intermediate_image_sizes_advanced', array($this, 'capture_generate_sizes'));

                            $new_metadata = wp_generate_attachment_metadata($image_id, $fullsizepath);

                            remove_filter('intermediate_image_sizes_advanced', array($this, 'capture_generate_sizes'));

                            //restore the optimized main image
                            if($backup && $backup !== $fullsizepath) {
                                rename($backup . "_optimized_" . $image_id, $fullsizepath);
                            }

                            //get the attachment name
                            if (is_wp_error($new_metadata)) {
                                $error[] = array('offset' => ($offset + 1), 'error' => $error, 'logstatus' => $logstatus, 'imgUrl' => $filename_only, 'startTime' => $data['startTime'], 'fromTo' => $data['fromTo'], 'type' => $process_type, 'period' => $period);
                            }
                            if (empty($new_metadata)) {
                                $filename_only = wp_get_attachment_url($image_id);
                                $logstatus = '<b>'.basename($filename_only).'</b> is missing';
                                $error[] = array('offset' => ($offset + 1), 'error' => $error, 'logstatus' => $logstatus, 'imgUrl' => $filename_only, 'startTime' => $data['startTime'], 'fromTo' => $data['fromTo'], 'type' => $process_type, 'period' => $period);
                            } else {

                                // going for the save.
                                $original_meta = $this->currentImage->getMetaData();
                                $result = $this->currentImage->saveNewMeta($new_metadata); // this here calls the regeneration.
                                $this->debug('Result :');
                                $this->debug($result);
                                $this->debug($this->currentImage->getMetaData());

                                $is_a_bulk = true; // we are sending multiple images.
                                $regenSizes = isset($new_metadata['sizes']) ? $new_metadata['sizes'] : array();

                                // Do not send if nothing was regenerated, otherwise SP thinks all needs to be redone

                                if (count($regenSizes) > 0)
                                {
                                  do_action('shortpixel-thumbnails-regenerated', $image_id, $original_meta, $regenSizes, $is_a_bulk);
                                }

                            }
                            $imageUrl = $filename_only;
                            $logstatus = 'Processed';
                            $filename_only = wp_get_attachment_thumb_url($image_id);
                        } else {
                            $filename_only = wp_get_attachment_url($image_id);
                            $logstatus = '<b>'.basename($filename_only).'</b> is missing or not an image file';
                            $error[] = array('offset' => ($offset + 1), 'error' => $error, 'logstatus' => $logstatus, 'imgUrl' => $filename_only, 'startTime' => $data['startTime'], 'fromTo' => $data['fromTo'], 'type' => $process_type, 'period' => $period);
                        }

                    } // Post Loop

                    foreach($image_posts_to_delete as $to_delete) {
                        wp_delete_post($to_delete, true);
                    }
                } else {
                    $logstatus = 'No pictures uploaded';
                    $error[] = array('offset' => 0, 'error' => $error, 'logstatus' => $logstatus, 'imgUrl' => '');
                }
                if (!extension_loaded('gd') && !function_exists('gd_info')) {
                    $filename_only = 'No file';
                    $logstatus = 'PHP GD library is not installed on your web server. Please install in order to have the ability to resize and crop images';
                    $error[] = array('offset' => ($offset + 1), 'error' => $error, 'logstatus' => $logstatus, 'imgUrl' => $filename_only, 'startTime' => $data['startTime'], 'fromTo' => $data['fromTo'], 'type' => $process_type, 'period' => $period);
                }
                //increment offset
                $result = $offset + 1;
                if(!isset($filename_only)){
                    $filename_only = 'No files';
                }
                $this->debug($error);
                $finalResult = array('offset' => ($offset + 1), 'error' => $error, 'logstatus' => $logstatus, 'imgUrl' => $filename_only, 'startTime' => $data['startTime'], 'fromTo' => $data['fromTo'], 'type' => $process_type, 'period' => $period);
                break;
        } // switch - end submit.


        $this->jsonResponse($finalResult);
        exit();
    }

    public function get_error($message)
    {

    }

    public function capture_generate_sizes($full_sizes)
    {
//        $this->debug('Ignoring Wordpress:'); $this->debug($ignored_sizes);

        $do_regenerate_sizes = $this->viewControl->process_image_sizes; //settings
        $process_options = $this->viewControl->process_image_options;

        $imageMetaSizes = $this->currentImage->getCurrentSizes();
        $this->debug('Image Meta Sizes');
        $this->debug($imageMetaSizes);
        $this->debug($process_options);

        $prevent_regen = array();
        foreach($do_regenerate_sizes as $rsize)
        {
          // 1. Check if size exists, if not, needs generation anyhow.
          if (! isset($imageMetaSizes[$rsize]))
          {
            $this->debug("Image Meta size setting missing - $rsize ");
            continue;
          }

          // 2. Check meta info (file) from the current meta info we have.
          $metaSize = $imageMetaSizes[$rsize];
          $overwrite = isset($process_options[$rsize]['overwrite_files']) ? $process_options[$rsize]['overwrite_files'] : false; // 3. Check if we keep or overwrite.

           if (! $overwrite)
           {
            // thumbFile is RELATIVE. So find dir via main image.
             $thumbFile = $this->currentImage->getDir() . $metaSize['file'];
             $this->debug('Preventing overwrite of - ' . $thumbFile);
             if (file_exists($thumbFile)) // 4. Check if file is really there
             {
                $prevent_regen[] = $rsize;
                // Add to current Image the metaSize since it will be dropped by the metadata redoing.
                $this->debug('File exists on ' . $rsize . ' ' . $thumbFile . '  - skipping regen');
                $this->currentImage->addPersistentMeta($rsize, $metaSize);
             }
           }

        }

        // 5. Drop the 'not to be' regen. images from the sizes so it will not process.
        $do_regenerate_sizes = array_diff($do_regenerate_sizes, $prevent_regen);
        $this->debug('Sizes going for regen - '); $this->debug($do_regenerate_sizes);

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



    public function view_generate_thumbnails() {
        wp_enqueue_style('rta_css');
        wp_enqueue_script('rta_js');
        //$rta_image_sizes = get_option( 'rta_image_sizes' );
        $view = new rtaAdminController($this);
        $view->show();

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

/* Probably outdated
    public function rta_settings() {
        do_action('rta_before_settings', $this, $rta_options );

        $attr = $rta_options;
        $attr['message'] = $message;
        $html = $this->rta_load_template( "settings", "admin", $attr );
        do_action('rta_after_settings', $this, $rta_options );
        echo $html;
    }
*/
    /*
    * Seems not in use.
    private function load_wp_media_uploader() {

        wp_enqueue_script('media-upload');
    	wp_enqueue_script('thickbox');
    	wp_enqueue_style('thickbox');
        $html = $this->rta_load_template( "load_media_upload_js", "admin" );
        echo $html;
    } */
}
