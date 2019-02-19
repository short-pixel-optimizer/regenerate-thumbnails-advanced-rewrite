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
    //Admin side starting point. Will call appropriate admin side hooks
    public function __construct() {
        $this->customThumbSuffixes = array('_c', '_tl', '_tr', '_br', '_bl');

        do_action('rta_before_admin', $this );
        //All admin side code will go here
        
        add_action( 'admin_menu', array( $this, 'rta_admin_menus' ) );    
        add_action( 'wp_ajax_rta_regenerate_thumbnails', array( $this, 'rta_regenerate_thumbnails') );
        add_action( 'wp_ajax_rta_save_image_sizes', array( $this, 'rta_save_image_sizes' ) );
        add_filter( 'image_size_names_choose', array( $this, 'rta_image_custom_sizes' ), 10, 1 );

        add_filter( 'plugin_action_links_' . plugin_basename(RTA_PLUGIN_FILE), array(&$this, 'generate_plugin_links'));//for plugin settings page

        do_action('rta_after_admin', $this );            
    }

    public function rta_admin_menus(){
        $title = __('Regenerate Thumbnails', 'regenerate-thumbnails-advanced');
        add_management_page($title, $title, 'manage_options', 'rta_generate_thumbnails', array( $this, 'rta_generate_thumbnails' ));
    }

    public function generate_plugin_links($links) {
        $in = '<a href="tools.php?page=rta_generate_thumbnails">Settings</a>';
        array_unshift($links, $in);
        return $links;

    }
    
    public function rta_save_image_sizes() {
        $error = false;
        global $rta_lang;
        $rta_image_sizes = array();   
        $exclude = array();
        foreach( $_POST as $k => $v ) {
            if( !in_array( $k, $exclude )) {
                if(!is_array($v)) {
                    $val = $this->make_safe($v);
                }else{
                    $val = $v;
                }
                $rta_image_sizes[$k] = $val;
            }
        }            
        update_option( 'rta_image_sizes', $rta_image_sizes );
        $message = $this->rta_get_message_html( $rta_lang['image_sizes_save_message'], 'message' );
        $return = array( 'error' => $error, 'message'=> $message );
            
        header('Content-Type: application/json');
        echo json_encode($return);
        exit();
    }
    
    public function rta_image_sizes() {
        
        global $rta_lang;
        
        if( isset($_POST['btnsave']) && $_POST['btnsave'] != "" ) {            
            $exclude = array('btnsave');
            $rta_image_sizes = array();            
            foreach( $_POST as $k => $v ) {
                if( !in_array( $k, $exclude )) {
                    if(!is_array($v)) {
                        $val = $this->make_safe($v);
                    }else{
                        $val = $v;
                    }
                    $rta_image_sizes[$k] = $val;
                }
            }            
            update_option( 'rta_image_sizes', $rta_image_sizes );
            $message = $this->rta_get_message_html( $rta_lang['image_sizes_save_message'], 'message' );
        }
        $rta_image_sizes = get_option( 'rta_image_sizes' );
        
        $attr = $rta_image_sizes;
        $attr['message'] = $message;
        $html = $this->rta_load_template( "rta_image_sizes", "admin", $attr );
        
        echo $html;
    }

    function rta_del_associated_thumbs($mainFile='') {
        //See ShortPixel Image Optimiser's findThumbs method
        $ext = pathinfo($mainFile, PATHINFO_EXTENSION);
        $base = substr($mainFile, 0, strlen($mainFile) - strlen($ext) - 1);
        $pattern = '/' . preg_quote($base, '/') . '-\d+x\d+\.'. $ext .'/';
        $thumbsCandidates = @glob($base . "-*." . $ext);
        $thumbs = array();
        if(is_array($thumbsCandidates)) {
            foreach($thumbsCandidates as $th) {
                if(preg_match($pattern, $th)) {
                    $thumbs[]= $th;
                }
            }
            if(   count($this->customThumbSuffixes)
               && !(   is_plugin_active('envira-gallery/envira-gallery.php')
                    || is_plugin_active('soliloquy/soliloquy.php')
                    || is_plugin_active('soliloquy-lite/soliloquy-lite.php'))){
                foreach ($this->customThumbSuffixes as $suffix){
                    $pattern = '/' . preg_quote($base, '/') . '-\d+x\d+'. $suffix . '\.'. $ext .'/';
                    foreach($thumbsCandidates as $th) {
                        if(preg_match($pattern, $th)) {
                            $thumbs[]= $th;
                        }
                    }
                }
            }
        }
        foreach($thumbs as $thumb) {
            if($thumb !== $mainFile) {
                @unlink($thumb);
            }
        }
        return $thumbs;
    }

    /**
     * schedules the image's attachment post to be deleted if all the thumbnails are missing or just removes the missing thumbnails from the sizes array if some still are present.
     * @param $image_id
     * @param $image_posts_to_delete
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
    
    public function rta_regenerate_thumbnails() {
        
        $data = $_POST;
        
        $imageUrl='';
        if (isset($data['type'])) {
            $type = $data['type'];
        }
        $logstatus = '';
        $offset = 0;
        switch ($type) {
            case 'general':
                $args = array(
                    'post_type' => 'attachment',
                    'post_mime_type' => 'image',
                    'posts_per_page' => -1,
                    'post_status' => 'any',
                    'offset' => 0,
                );
                if (isset($data['period'])) {
                    $period = $data['period'];

                    switch ($period) {
                        case '0':
                            break;
                        case '1':
                          $date = '-1 day';
                          $startDate = date("d/m/Y",strtotime($date));  
                          $endDate = date("d/m/Y",strtotime('-'.$date));  
                          $args['date_query'] = array('after' => '1 day ago', 'before' => 'tomorrow');
                    break;
                        case '2':
                          $date = '-1 week';
                          $startDate = date("d/m/Y",strtotime($date));  
                          $endDate = date("d/m/Y",strtotime('-'.$date));  
                          $args['date_query'] = array('after' => '1 week ago', 'before' => 'tomorrow');
                          break;
                        case '3':
                          $date = '-1 month';
                          $startDate = date("d/m/Y",strtotime($date));  
                          $endDate = date("d/m/Y",strtotime('-'.$date));  
                          $args['date_query'] = array('after' => '1 month ago', 'before' => 'tomorrow');
                          break;
                      case '4':
                          $date = '-3 month';
                          $startDate = date("d/m/Y",strtotime($date));  
                          $endDate = date("d/m/Y",strtotime('-'.$date));  
                          $args['date_query'] = array('after' => '3 months ago', 'before' => 'tomorrow');
                      break;
                      case '5':
                          $date = '-6 month';
                          $startDate = date("d/m/Y",strtotime($date));  
                          $endDate = date("d/m/Y",strtotime('-'.$date));  
                          $args['date_query'] = array('after' => '6 months ago', 'before' => 'tomorrow');
                          break;
                      case '6':
                          $date = '-1 year';
                          $startDate = date("d/m/Y",strtotime($date));  
                          $endDate = date("d/m/Y",strtotime('-'.$date));  
                          $args['date_query'] = array('after' => '1 year ago', 'before' => 'tomorrow');
                      break;
                    }
                }
                $the_query = new WP_Query($args);
                $post_count = 0;

                if ($the_query->have_posts()) {
                    $post_count = $the_query->post_count;
                }else{
                    $logstatus = 'No pictures uploaded';
                    $error[] = array('offset' => 0, 'logstatus' => $logstatus, 'imgUrl' => '', 'startTime' => '', 'fromTo' => '', 'type' => $data['type'], 'period' =>'');
                    $finalResult = array('offset' => 0, 'error' => 1,'pCount'=>0, 'logstatus' => $logstatus, 'imgUrl' => '', 'startTime' => '', 'fromTo' => '', 'type' => $data['type'], 'period' =>'');
                    header('Content-Type: application/json');
                    echo json_encode($finalResult);
                    exit();
                }
                wp_reset_query();
                wp_reset_postdata();
                //$logstatus .= '<pre>'.print_r($the_query, true).'</pre>';
                if (isset($data['type'])) {
                    $typeV = $data['type'];
                }
                if (!isset($date) || empty($date)) {
                    $date = '';
                }
                delete_option('rta_get_all_files');
                $return_arr = array('pCount' => $post_count, 'fromTo' => $date, 'type' => $typeV, 'period' => $period);
                header('Content-Type: application/json');
                echo json_encode($return_arr);
                exit();
                break;
            case 'submit':
                $logstatus = '';
                $error = array();
                $del_thumbs = $data['del_thumbs'];
                $del_leftover_metadata = $data['del_leftover_metadata'];
                if (isset($data['offset'])) {
                    $offset = $data['offset'];
                }
                if (isset($data['period'])) {
                    $period = $data['period'];

                    $args = array(
                        'post_type' => 'attachment',
                        'post_mime_type' => 'image',
                        'post_status' => 'any',
                        'posts_per_page' => 1,
                        'offset' => $offset,
                        'orderby' => 'ID',
                        'order' => 'DESC',
                    );
                    $bulk = false;
                    switch ($period) {
                        case '0':
                            $bulk = true;
                            break;
                        case '1':
                            $date = '-1 day';
                            $startDate = date("d/m/Y",strtotime($date));  
                            $endDate = date("d/m/Y",strtotime('-'.$date));  
                            $args['date_query'] = array('after' => '1 day ago', 'before' => 'tomorrow');
                            break;
                        case '2':
                            $date = '-1 week';
                            $startDate = date("d/m/Y",strtotime($date));  
                            $endDate = date("d/m/Y",strtotime('-'.$date));  
                            $args['date_query'] = array('after' => '1 week ago', 'before' => 'tomorrow');
                            break;
                        case '3':
                            $date = '-1 month';
                            $startDate = date("d/m/Y",strtotime($date));  
                            $endDate = date("d/m/Y",strtotime('-'.$date));  
                            $args['date_query'] = array('after' => '1 month ago', 'before' => 'tomorrow');
                            break;
                        case '4':
                            $date = '-3 month';
                            $startDate = date("d/m/Y",strtotime($date));  
                            $endDate = date("d/m/Y",strtotime('-'.$date));  
                            $args['date_query'] = array('after' => '3 months ago', 'before' => 'tomorrow');
                            break;
                        case '5':
                            $date = '-6 month';
                            $startDate = date("d/m/Y",strtotime($date));  
                            $endDate = date("d/m/Y",strtotime('-'.$date));  
                            $args['date_query'] = array('after' => '6 months ago', 'before' => 'tomorrow');
                            break;
                        case '6':
                            $date = '-1 year';
                            $startDate = date("d/m/Y",strtotime($date));  
                            $endDate = date("d/m/Y",strtotime('-'.$date));  
                            $args['date_query'] = array('after' => '1 year ago', 'before' => 'tomorrow');
                            break;
                    }
                }

                $args = array(
                    'post_type' => 'attachment',
                    'post_mime_type' => 'image',
                    'post_status' => 'any',
                    'posts_per_page' => 1,
                    'offset' => $offset,
                );

                if ($period != 0 && isset($date)) {
                    if (!empty($date)) {
                        $fromTo = explode('-', $date);
                        $startDate = date('m/d/Y', strtotime($fromTo[0]));
                        $endDate = date('m/d/Y', strtotime($fromTo[1].' +1 day'));

                        if (!empty($startDate) && empty($endDate)) {
                            $args['date_query'] = array('after' => $startDate);
                        } elseif (!empty($endDate) && empty($startDate)) {
                            $args['date_query'] = array('before' => $endDate);
                        } elseif (!empty($startDate) && !empty($endDate)) {
                            $args['date_query'] = array('after' => $startDate, 'before' => $endDate);
                        }
                    }
                }
                $featured_img_w = $data['featured_img_w'];
                $featured_img_h = $data['featured_img_h'];
                $no_featured_img_w = $data['no_featured_img_w'];
                $no_featured_img_h = $data['no_featured_img_h'];
                $default_thumb_w = $data['default_img_w'];
                $default_thumb_h = $data['default_img_h'];
                update_option('thumbnail_size_w',$default_thumb_w);
                update_option('thumbnail_size_h',$default_thumb_h);
                $featured_images_ids = array();
                if(!empty($featured_img_w) || !empty($no_featured_img_w)) {
                    $featured_images = $this->rta_get_data("postmeta", "meta_key = '_thumbnail_id'");                    
                    foreach($featured_images as $row) {
                        $featured_images_ids[] = $row->meta_value;
                    }                    
                }
                $the_query = new WP_Query($args);
                $debug = '';
                if ($the_query->have_posts()) {
                    $image_posts_to_delete = array();
                    while ($the_query->have_posts()) {
                        $the_query->the_post();
                        $image_id = $the_query->post->ID;

                        $fullsizepath = get_attached_file($image_id);

                        $debug .= "ID $image_id FULLSIZEPATH: $fullsizepath";

                        if($del_leftover_metadata == 'true' && !file_exists($fullsizepath)) {
                            $debug .= ' missing, continue ';
                            $this->rta_del_leftover_metadata($image_id, $fullsizepath, $image_posts_to_delete);
                            continue; //the main image is missing, nothing to regenerate.
                        }
                        $debug .= ' exists ';

                        if(!empty($featured_img_w) || !empty($no_featured_img_w)) {
                            $is_featured = false;
                            if(in_array($image_id, $featured_images_ids)) {
                                $is_featured = true;
                            }
                            if($is_featured) {
                                if(!empty($featured_img_w) && !empty($featured_img_h)){
                                    update_option('thumbnail_size_w',$featured_img_w);
                                    update_option('thumbnail_size_h',$featured_img_h);
                                }else{
                                    update_option('thumbnail_size_w',$default_thumb_w);
                                    update_option('thumbnail_size_h',$default_thumb_h);
                                }
                            }else{
                                if(!empty($no_featured_img_w) && !empty($no_featured_img_h)){
                                    update_option('thumbnail_size_w',$no_featured_img_w);
                                    update_option('thumbnail_size_h',$no_featured_img_h);
                                }else{
                                    update_option('thumbnail_size_w',$default_thumb_w);
                                    update_option('thumbnail_size_h',$default_thumb_h);
                                }
                            }
                        }
                        $is_image = true;
                        if (isset($data['mediaID'])){
                            $image_id = $data['mediaID'];
                        }
                        //is image:
                        if (!is_array(getimagesize($fullsizepath))) {
                            $is_image = false;
                        }
                        $filename_only = wp_get_attachment_thumb_url($image_id);
                        
                        if($del_thumbs=='true') {
                            $result = $this->rta_del_associated_thumbs($fullsizepath);
                        }

                        if ($is_image) {
                            if (false === $fullsizepath || !file_exists($fullsizepath)) {
                                $error[] = array('offset' => ($offset + 1), 'error' => $error, 'logstatus' => $logstatus, 'imgUrl' => $fullsizepath, 'startTime' => $data['startTime'], 'fromTo' => $data['fromTo'], 'type' => $data['type'], 'period' => $period);
                            }
                            @set_time_limit(900);
                            do_action('shortpixel-thumbnails-before-regenerate', $image_id);
                            //include( ABSPATH . 'wp-admin/includes/image.php' );
                            //$metadata = wp_generate_attachment_metadata($image_id, $fullsizepath);
                            
                            //use the original main image if exists
                            $backup = apply_filters('shortpixel_get_backup', $fullsizepath);
                            if($backup && $backup !== $fullsizepath) {
                                copy($fullsizepath, $backup . "_optimized_" . $image_id);
                                copy($backup, $fullsizepath);
                            }

                            $original_meta = wp_get_attachment_metadata($image_id);
                            // TODO also make sure only the regenerated thumbnails are passed to the action

                            //This has to be changed to also generate the newly added sizes
                            $metadata = wp_generate_attachment_metadata($image_id, $fullsizepath);

                            //restore the optimized main image
                            if($backup && $backup !== $fullsizepath) {
                                rename($backup . "_optimized_" . $image_id, $fullsizepath);
                            }
                            
                            //get the attachment name
                            if (is_wp_error($metadata)) {
                                $error[] = array('offset' => ($offset + 1), 'error' => $error, 'logstatus' => $logstatus, 'imgUrl' => $filename_only, 'startTime' => $data['startTime'], 'fromTo' => $data['fromTo'], 'type' => $data['type'], 'period' => $period);
                            }
                            if (empty($metadata)) {
                                $filename_only = wp_get_attachment_url($image_id);
                                $logstatus = '<b>'.basename($filename_only).'</b> is missing';                            
                                $error[] = array('offset' => ($offset + 1), 'error' => $error, 'logstatus' => $logstatus, 'imgUrl' => $filename_only, 'startTime' => $data['startTime'], 'fromTo' => $data['fromTo'], 'type' => $data['type'], 'period' => $period);
                            } else {
                                wp_update_attachment_metadata($image_id, $metadata);
                                do_action('shortpixel-thumbnails-regenerated', $image_id, $original_meta, $metadata['sizes'], $bulk);
                            }
                            $imageUrl = $filename_only;
                            $logstatus = 'Processed';
                            $filename_only = wp_get_attachment_thumb_url($image_id); 
                        } else {
                            $filename_only = wp_get_attachment_url($image_id);
                            $logstatus = '<b>'.basename($filename_only).'</b> is missing';
                            $error[] = array('offset' => ($offset + 1), 'error' => $error, 'logstatus' => $logstatus, 'imgUrl' => $filename_only, 'startTime' => $data['startTime'], 'fromTo' => $data['fromTo'], 'type' => $data['type'], 'period' => $period);
                        }

                    }
                    update_option('thumbnail_size_w',$default_thumb_w);
                    update_option('thumbnail_size_h',$default_thumb_h);
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
                    $error[] = array('offset' => ($offset + 1), 'error' => $error, 'logstatus' => $logstatus, 'imgUrl' => $filename_only, 'startTime' => $data['startTime'], 'fromTo' => $data['fromTo'], 'type' => $data['type'], 'period' => $period);
                }
                //increment offset
                $result = $offset + 1;
                if(!isset($filename_only)){
                    $filename_only = 'No files';
                }
                $finalResult = array(/*'debug' => $debug,*/ 'offset' => ($offset + 1), 'error' => $error, 'logstatus' => $logstatus, 'imgUrl' => $filename_only, 'startTime' => $data['startTime'], 'fromTo' => $data['fromTo'], 'type' => $data['type'], 'period' => $period);
                break;
        }
        header('Content-Type: application/json');
        echo json_encode($finalResult);
        exit();
    }
    
    public function rta_image_custom_sizes( $sizes ) {
        
        global $rta_lang;
        return array_merge( $sizes, array(
            'rta_featured_image' => $rta_lang['featured_image_label'],
            'rta_non_featured_image' => $rta_lang['no_featured_image_label'],
        ) );
    }
    
    public function rta_generate_thumbnails() {
        
        $rta_image_sizes = get_option( 'rta_image_sizes' );
        $attr = $rta_image_sizes;
        $default_thumb_w = get_option('thumbnail_size_w');
        $default_thumb_h = get_option('thumbnail_size_h');
        $attr['default_thumb_w'] = $default_thumb_w;
        $attr['default_thumb_h'] = $default_thumb_h;
        
        $html = $this->rta_load_template( "rta_generate_thumbnails", "admin", $attr );
        echo $html;
    }
    
    public function rta_settings() {
        global $rta_options, $rta_lang;
        do_action('rta_before_settings', $this, $rta_options );
        
        if( isset($_POST['btnsave']) && $_POST['btnsave'] != "" ) {            
            $exclude = array('btnsave');
            $rta_options = array();            
            foreach( $_POST as $k => $v ) {
                if( !in_array( $k, $exclude )) {
                    if(!is_array($v)) {
                        $val = $this->make_safe($v);
                    }else{
                        $val = $v;
                    }
                    $rta_options[$k] = $val;
                }
            }            
            update_option( 'rta_settings', $rta_options );
            $message = $this->rta_get_message_html( $rta_lang['settings_save_message'], 'message' );
        }
        
        $attr = $rta_options;
        $attr['message'] = $message;
        $html = $this->rta_load_template( "settings", "admin", $attr );
        do_action('rta_after_settings', $this, $rta_options );
        echo $html;
    }
    
    private function load_wp_media_uploader() {
        
        wp_enqueue_script('media-upload');
    	wp_enqueue_script('thickbox');
    	wp_enqueue_style('thickbox');
        $html = $this->rta_load_template( "load_media_upload_js", "admin" );
        echo $html;
    }    
}

$rta_admin = new RTA_Admin();