<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Plugin main class that will control the whole skeleton and common functions
 *
 * PHP version 5
 *
 * @category   Main
 * @package    Regenerate Thumbnails ID SCOUT
 * @author     Muhammad Atiq
 * @version    1.0.0
 * @since      File available since Release 1.0.0
*/

class RTA
{
    protected $admin;

    //Plugin starting point. Will call appropriate actions
    public function __construct() {

        add_action( 'init', array( $this, 'rta_init' ) );
      ///  add_action( 'wp_enqueue_scripts', array( $this, 'rta_enqueue_scripts' ), 10 );
        add_action( 'admin_enqueue_scripts', array( $this, 'rta_enqueue_scripts' ), 10 );

        //add_filter('media_row_actions', array($this,'add_media_action'), 10, 2);

    }

    //Plugin initialization
    public function rta_init() {

        do_action('rta_before_init');
        $this->admin = new RTA_Admin(); // admin hooks.

        load_plugin_textdomain( 'regenerate-thumbnails-advanced', FALSE, RTA_LANG_DIR );

        do_action('rta_after_init');
    }

    //Function will add CSS and JS files
    public function rta_enqueue_scripts() {

        do_action('rta_before_enqueue_scripts');

        //wp_enqueue_script( 'jquery' );
        wp_register_script( 'rta_js', RTA_PLUGIN_URL.'js/rta.js', array( 'jquery' ), RTA_PLUGIN_VERSION );
        wp_register_style( 'rta_css', RTA_PLUGIN_URL.'css/rta.css', array(), RTA_PLUGIN_VERSION );

        wp_localize_script( 'rta_js', 'rta_data', array(
                            'ajaxurl' => admin_url( 'admin-ajax.php' ),
                            'nonce_savesizes' => wp_create_nonce('rta_save_image_sizes'),
                            'nonce_generate' => wp_create_nonce('rta_regenerate_thumbnails'),
                            'confirm_delete' => __('Are you sure you want to delete this image size?', 'regenerate-thumbnails-advanced'),
                            'confirm_stop' => __("This will stop the regeneration process. You want to stop?", 'regenerate-thumbnails-advanced' ),
                            ));

        do_action('rta_after_enqueue_scripts');
    }

    public function rta_format_size($bytes) {

        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        }elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        }else{
            $bytes = '0 bytes';
        }
        return $bytes;
    }

    public function rta_load_template( $template='', $for='front', $attr=array() ) {

        do_action( 'rta_before_load_template', $template, $for, $attr );
        $template = apply_filters( 'rta_template_to_load', $template, $for, $attr );
        $attr = apply_filters( 'rta_template_variables', $attr, $template, $for );

        if( empty($template) ) {
            return '';
        }
        if( is_array($attr) ) {
            extract($attr);
        }
        $html = '';
        $html = apply_filters( 'rta_before_template_html', $html, $template, $for, $attr );
        ob_start();
        require (RTA_PLUGIN_PATH.'templates/'.$for.'/'.$template.'.php');
        $html = ob_get_contents();
        ob_end_clean();

        do_action( 'rta_after_load_template', $template, $for, $attr, $html );
        $html = apply_filters( 'rta_after_template_html', $html, $template, $for, $attr );

        return $html;
    }

    public function rta_get_message_html( $message, $type = 'message' ) {
        do_action( 'rta_before_get_message_html', $message, $type );
        $message = apply_filters( 'rta_message_text', $message, $type );
        $type = apply_filters( 'rta_message_type', $type, $message );

        $html = '';

        $html = apply_filters( 'rta_before_message_html', $html, $message, $type );

        $attr = array( 'message' => $message, 'type' => $type );

        $html = $this->rta_load_template( $type, 'common', $attr );

        do_action( 'rta_after_get_message_html', $message, $type );
        $html = apply_filters( 'rta_after_message_html', $html, $message, $type );

        return $html;
    }

    public function debug($message)
    {
        if (defined('RTA_DEBUG'))
        {
          if (is_array($message) || is_object($message)) {
              $message = print_r($message, true);
          }
          file_put_contents(WP_CONTENT_DIR . '/rta.log' , '[' . date('Y-m-d H:i:s') . "] $message\n", FILE_APPEND);
          //file_put_contents(SHORTPIXEL_BACKUP_FOLDER . "/shortpixel_log", '[' . date('Y-m-d H:i:s') . "] $message\n", FILE_APPEND);
        }
    }

    /** Central function for JSON responses. Can be extended whenever needed */
    protected function jsonResponse($response)
    {
      wp_send_json($response);
    }

    /*   @todo To Implement

      public function add_media_action( $actions, $post) {
      $url = admin_url( "upload.php");
      $url = add_query_arg(array(
          'action' => 'media_replace',
          'attachment_id' => $post->ID,
      ), $url);
      $action = "regenerate_thumbnails";

      $editurl = wp_nonce_url( $url, $action );
      $link = "href=\"$editurl\"";

      $newaction['regenerate'] = '<a ' . $link . ' aria-label="' . esc_html__("Replace media", "regenerate-thumbnails-advanced") . '" rel="permalink">' . esc_html__("Regenerate Thumbnails", "regenerate-thumbnails-advanced") . '</a>';
      return array_merge($actions,$newaction);
    }
    */



    /* [BS] Seems not in use */
    /*
    public function rta_add_record( $table = '', $data = array() ) {

        if( empty($data) || empty($table) ) {
            return false;
        }

        global $wpdb;
        $exclude = array( 'btnsave' );
        $attr = "";
        $attr_val = "";
        foreach( $data as $k=>$val ) {
            if(is_array($val)) {
                $val = maybe_serialize($val);
            }else{
                $val = $this->make_safe($val);
            }
            if( !in_array( $k, $exclude )) {
                if( $attr == "" ) {
                    $attr.="`".$k."`";
                    $attr_val.="'".$val."'";
                }else{
                    $attr.=", `".$k."`";
                    $attr_val.=", '".$val."'";
                }
            }
        }
        $sql = "INSERT INTO `".$wpdb->prefix.$table."` (".$attr.") VALUES (".$attr_val.")";
        $wpdb->query($sql);
        $lastid = $wpdb->insert_id;
        return $lastid;
    } */

    /* [BS] Seems not in use  - All these function don't use Prepare SQL statement, which might be dangerous */
    /*
    public function rta_add_multiple_records( $table = '', $attr = array(), $data = array() ) {
        if( empty($data) || empty($table) || empty($attr) ) {
            return false;
        }
        global $wpdb;
        $exclude = array( 'btnsave' );
        $attr_str = "";
        foreach( $attr as $v ) {
            if( $attr_str == "" ) {
                $attr_str.="`".$v."`";
            }else{
                $attr_str.=", `".$v."`";
            }
        }
        $attr_val = "";
        foreach( $data as $row ) {
            if( $attr_val == '' ) {
                $attr_val.='(';
            }else{
                $attr_val.=',(';
            }
            $attr_val_row = '';
            foreach( $row as $k=>$val ) {
                if(is_array($val)) {
                    $val = maybe_serialize($val);
                }else{
                    $val = $this->make_safe($val);
                }
                if( !in_array( $k, $exclude )) {
                    if( $attr_val_row == "" ) {
                        $attr_val_row.="'".$val."'";
                    }else{
                        $attr_val_row.=", '".$val."'";
                    }
                }
            }
            $attr_val.= $attr_val_row.')';
        }
        $sql = "INSERT INTO `".$wpdb->prefix.$table."` (".$attr_str.") VALUES ".$attr_val;
        $wpdb->query($sql);
        $lastid = $wpdb->insert_id;
        return $lastid;
    } */

    /* [BS] Seems not in use */
    /*
    public function rta_update_record( $table = '', $data = array(), $where = '' ) {

        if( empty($where) || empty($data) || empty($table) ) {
            return false;
        }

        global $wpdb;
        $exclude = array( 'id','btnsave' );
        $attr = "";
        foreach( $data as $k=>$val ) {
            if(is_array($val)) {
                $val = maybe_serialize($val);
            }else{
                $val = $this->make_safe($val);
            }
            if( !in_array( $k, $exclude )) {
                if( $attr == "" ) {
                    $attr.="`".$k."` = '".$val."'";
                }else{
                    $attr.=", `".$k."` = '".$val."'";
                }
            }
        }
        $sql = "UPDATE `".$wpdb->prefix.$table."` SET ".$attr." WHERE ".$where;
        $wpdb->query($sql);

        return true;
    } */

    /* [BS] Seems not in use */
    /*
    public function rta_del_record( $table = '', $where = '' ) {

        if( empty($where) || empty($table) ) {
            return false;
        }

        global $wpdb;
        $sql = "DELETE FROM `".$wpdb->prefix.$table."` WHERE ".$where;
        $wpdb->query($sql);
        return true;
    } */

    /* [BS] Get data from postmeta table. */
    public function rta_get_data( $table = '', $where = "1", $get_row = false, $attr = "*" ) {

        if( empty($table) ) {
            return false;
        }

        global $wpdb;
        // [BS] TODO - Query not prepared by default here.
        $sql = "SELECT ".$attr." FROM `".$wpdb->prefix.$table."` WHERE ".$where;
        if( $get_row ) {
            $data = $wpdb->get_row($sql);
        }else{
            $data = $wpdb->get_results($sql);
        }

        return $data;
    }

    /* [BS] Seems not in use. Also using a hardcoded key doesn't sound like a very strong function */
    /*
    public function rta_number_encrypt($data, $key = 'geyktksYMZNQU8lRTRSAIMFWSF2csvsq2we', $base64_safe=true, $shrink=true) {
        if ($shrink) $data = base_convert($data, 10, 36);
        $data = @mcrypt_encrypt(MCRYPT_ARCFOUR, $key, $data, MCRYPT_MODE_STREAM);
        if ($base64_safe) $data = str_replace('=', '', base64_encode($data));
        return $data;
    } */

    /* [BS] Seems not in use */
    /*
    public function rta_number_decrypt($data, $key = 'geyktksYMZNQU8lRTRSAIMFWSF2csvsq2we', $base64_safe=true, $expand=true) {
        if ($base64_safe) $data = base64_decode($data.'==');
        $data = @mcrypt_encrypt(MCRYPT_ARCFOUR, $key, $data, MCRYPT_MODE_STREAM);
        if ($expand) $data = base_convert($data, 36, 10);
        return $data;
    } */


    /* [BS] Replace this usages by sanitize_text_field which is made for this */

    public function make_safe( $variable ) {

    /*    $variable = $this->strip_html_tags($variable);
        $bad = array("<", ">");
        $variable = str_replace($bad, "", $variable); */

        return sanitize_text_field($variable);
    }

    public function strip_html_tags( $text ) {
        $text = preg_replace(
                array(
                  // Remove invisible content
                        '@<head[^>]*?>.*?</head>@siu',
                        '@<style[^>]*?>.*?</style>@siu',
                        '@<script[^>]*?.*?</script>@siu',
                        '@<object[^>]*?.*?</object>@siu',
                        '@<embed[^>]*?.*?</embed>@siu',
                        '@<applet[^>]*?.*?</applet>@siu',
                        '@<noframes[^>]*?.*?</noframes>@siu',
                        '@<noscript[^>]*?.*?</noscript>@siu',
                        '@<noembed[^>]*?.*?</noembed>@siu'
                ),
                array(
                        '', '', '', '', '', '', '', '', ''), $text );

        return strip_tags( $text);
    }

    public function array_sort($array, $on, $order='ASC'){

        $new_array = array();
        $sortable_array = array();

        if (count($array) > 0) {
            foreach ($array as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $k2 => $v2) {
                        if ($k2 == $on) {
                            $sortable_array[$k] = $v2;
                        }
                    }
                } else {
                    $sortable_array[$k] = $v;
                }
            }

            switch ($order) {
                case 'ASC':
                    asort($sortable_array);
                    break;
                case 'DESC':
                    arsort($sortable_array);
                    break;
            }

            foreach ($sortable_array as $k => $v) {
                $new_array[$k] = $array[$k];
            }
        }

        return $new_array;
    }

    // Function to safe redirect the page without warnings
    /* [BS] Seems not in use. Also wp_safe_redirect should be used for something like this */
    /*
    public function redirect( $url ) {
        echo '<script language="javascript">window.location.href="'.$url.'";</script>';
        exit();
    } */

    //Function will get called on plugin activation
    public static function rta_install() {

        do_action('rta_before_install');

        require_once RTA_PLUGIN_PATH.'includes/rta_install.php';

        do_action('rta_after_install');
    }

    // Function will get called on plugin de activation
    public static function rta_uninstall() {

        do_action('rta_before_uninstall');

        require_once RTA_PLUGIN_PATH.'includes/rta_uninstall.php';

        do_action('rta_after_uninstall');
    }
} // class
