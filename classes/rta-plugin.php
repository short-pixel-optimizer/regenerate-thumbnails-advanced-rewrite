<?php
namespace ReThumbAdvanced;
use \ReThumbAdvanced\ShortPixelLogger\ShortPixelLogger as Log;


// load runtime.
class rtaPlugin
{

  protected $paths = array('classes', 'classes/controllers');

  protected $front;
  protected $admin;

  public function __construct()
  {
      $log = Log::getInstance();
      $uploaddir =wp_upload_dir();
      if (isset($uploaddir['basedir']))
        $log->setLogPath($uploaddir['basedir'] . "/rta_log");

      $this->initRuntime();

      add_action( 'init', array( $this, 'init' ) );
      add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

      add_action( 'admin_menu', array( $this, 'admin_menus' ) );

      add_filter( 'plugin_action_links_' . plugin_basename(RTA_PLUGIN_FILE), array($this, 'generate_plugin_links'));//for plugin settings page

  }

  public function initRuntime()
  {
  //  $plugin_path = plugin_dir_path(SHORTPIXEL_PLUGIN_FILE);
    foreach($this->paths as $short_path)
    {
      $directory_path = realpath(RTA_PLUGIN_PATH . $short_path);

      if ($directory_path !== false)
      {
        $it = new \DirectoryIterator($directory_path);
        foreach($it as $file)
        {
          $file_path = $file->getRealPath();
          if ($file->isFile() && pathinfo($file_path, PATHINFO_EXTENSION) == 'php')
          {
            require_once($file_path);
          }
        }
      }
    }
  }

  // load textdomain, init.
  public function init()
  {
    load_plugin_textdomain( 'regenerate-thumbnails-advanced', FALSE, RTA_LANG_DIR );

    $this->front = new RTA_Front();
    $this->admin = new RTA_Admin();

    //add_action( 'admin_menu', array( $this, 'rta_admin_menus' ) );
    add_action( 'wp_ajax_rta_regenerate_thumbnails', array( $this->admin, 'ajax_regenerate_thumbnails') );
    add_action( 'wp_ajax_rta_start_regenerate', array($this->admin, 'ajax_start_process') );
    add_action( 'wp_ajax_rta_stop_process', array($this->admin, 'ajax_rta_stop_process'));

    //add_filter( 'image_size_names_choose', array( $this, 'rta_image_custom_sizes' ), 10, 1 );
    add_action( 'wp_ajax_rta_save_image_sizes', array($this->admin,'view_generate_thumbnails_save' ) );

  }

  // Registering styles and scripts.
  public function enqueue_scripts() {

      //wp_enqueue_script( 'jquery' );
      wp_register_script( 'rta_js', RTA_PLUGIN_URL.'js/rta.js', array( 'jquery' ), RTA_PLUGIN_VERSION );
      wp_register_style( 'rta_css', RTA_PLUGIN_URL.'css/rta.css', array(), RTA_PLUGIN_VERSION );
      wp_register_style( 'rta_css_admin', RTA_PLUGIN_URL.'css/rta-admin-view.css', array(), RTA_PLUGIN_VERSION );
      wp_register_style( 'rta_css_admin_progress', RTA_PLUGIN_URL.'css/rta-admin-progress.css', array('rta_css_admin'), RTA_PLUGIN_VERSION );


      wp_localize_script( 'rta_js', 'rta_data', array(
                          'ajaxurl' => admin_url( 'admin-ajax.php' ),
                          'nonce_savesizes' => wp_create_nonce('rta_save_image_sizes'),
                          'nonce_generate' => wp_create_nonce('rta_regenerate_thumbnails'),
                          'strings' => array(
                          'confirm_delete' => __('Are you sure you want to delete this image size?', 'regenerate-thumbnails-advanced'),
                          'confirm_stop' => __("This will stop the regeneration process. You want to stop?", 'regenerate-thumbnails-advanced' ),
                          'status_resume' => __("Interrupted process resumed", 'regenerate-thumbnails-advanced'),
                          'status_start' => __('New Process started', 'regenerate-thumbnails-advanced'),
                          'status_finish' => __('Process finished','regenerate-thumbnails-advanced' ),
                          ),
                          'blog_id' => get_current_blog_id(),
                          'process' => $this->admin->get_json_process(),
                          ));

      do_action('rta_enqueue_scripts');
  }

  // add admin pages
  public function admin_menus(){
      $title = __('Regenerate Thumbnails', 'regenerate-thumbnails-advanced');
      add_management_page($title, $title, 'manage_options', 'rta_generate_thumbnails', array($this, 'view_generate_thumbnails' ));
  }

  // filter for plugin page.
  public function generate_plugin_links($links) {
      $in = '<a href="tools.php?page=rta_generate_thumbnails">'  . __('Settings', 'regenerate-thumbnails-advanced') . '</a>';
      array_unshift($links, $in);
      return $links;
  }

  public function view_generate_thumbnails() {
      wp_enqueue_style('rta_css');
      wp_enqueue_script('rta_js');
      //$rta_image_sizes = get_option( 'rta_image_sizes' );
      $view = new rtaAdminController($this);
      $view->show();
  }



}
