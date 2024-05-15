<?php
namespace ReThumbAdvanced\Updater\Controller\Installer; 
use ReThumbAdvanced\ShortPixelLogger\ShortPixelLogger as Log;

if ( ! defined( 'ABSPATH' ) ) {
 exit; // Exit if accessed directly.
}

class InstallController
{

  protected $slug;

  protected $installed;
  protected $active;

  private static $instance;

  public function __construct()
  {
      add_action('wp_ajax_shortpixel-upgrade-pro', array($this, 'ajaxCall'));
  }

  public static function getInstance()
  {
    if (is_null(self::$instance))
    {
       self::$instance = new static();
    }

    return self::$instance;
  }

  public function setSlug($slug)
  {
     $this->slug = $slug;
  }


  protected function checkIfExists()
  {

  }

  public function isInstalled()
  {
    $plugins = get_plugins();
    if (isset($plugins[$this->slug]))
    {
       return true;
    }
    else {
       return false;
    }

  }

  public function isActive()
  {
      return is_plugin_active($this->slug);
  }

  public function loadView($plugin_path)
  {
      if ($this->isInstalled())
      {
         return;
      }

      $licenseController = LicenseController::getInstance();

      if ($licenseController->hasLicense())
      {
         $api_key = $licenseController->getLicenseForDisplay();
      }
      else {
         $api_key = '';
      }

      wp_enqueue_script('shortpixel-installer');
      require_once($plugin_path . '/templates/upgrade-to-pro.php');
  }

  public function ajaxCall()
  {
    if ( ! isset( $_POST['updater-nonce'] ) || ! wp_verify_nonce( $_POST['updater-nonce'], 'upgrade-pro' )
    ) {
      _e('Nonce failed', 'regenerate-thumbnails-advanced/');
      exit();
    }

    $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : false;


    $request =  RequestController::getInstance();
    $request->addArg('api_key', $api_key);

    $result = $request->doRequest();
    Log::addTemp('Result', $result);

    if ('error' == $result['status'] )
    {
      // Away with it.
      wp_send_json($result);
      exit();
    }

    $returnJson = $result['return'];

    if (is_object($returnJson) && property_exists($returnJson, 'download_url'))
    {
        $download_url = $returnJson->download_url;

        $result = $this->install($download_url);
        if ('success' === $result['status'])
        {
            $bool = $this->activatePlugin($result['basename']);
            if (false === $bool)
            {
               $result['status'] = 'error';
               $result['message'] = __('Plugin successfully installed, but failed to activate', 'regenerate-thumbnails-advanced/');
            }
            else {
               $result['status'] = 'success';
               $result['message'] = __('Plugin Installed!', 'regenerate-thumbnails-advanced/');
            }

            $licenseController = LicenseController::getInstance();
            $licenseController->addLicense($api_key);

            wp_send_json($result);
            exit();
        }
        else { // Install failed.
           $result['status'] = 'error';
           $result['message'] = $result['error_message'];
           wp_send_json($result);
           exit();
        }

    }
    else {
        $result['status'] = 'error';
        $result['message'] = __('Download did not return url', 'regenerate-thumbnails-advanced/');
        wp_send_json($result);
        exit();
    }


  }

  protected function install($download_url)
  {

    $result = array(
  		'success' => false,
  		'error_message' => __('Unspecified error while installing plugin', 'regenerate-thumbnails-advanced/'),
  		'basename' => null,
  	);

    if (true === $this->isInstalled())
    {
        $result['error_message'] = __('Plugin already installed', 'regenerate-thumbnails-advanced/');
        return $result;
    }

    // Set the current screen to avoid undefined notices.
    //set_current_screen();

    require_once (ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
    require_once (ABSPATH . 'wp-admin/includes/class-automatic-upgrader-skin.php');

    // Create the plugin upgrader with our custom skin.
    $skin      = new \Automatic_Upgrader_Skin();
    $installer = new \Plugin_Upgrader( $skin );
    $installer_result = $installer->install( $download_url );

    if (is_wp_error($installer_result))
    {
        $result['error_message'] = $installer_result->get_error_message();
        return $result;
    }

    if ( $installer->plugin_info() ) {
      $plugin_basename = $installer->plugin_info();

    //ob_clean();
    $result['basename'] = $plugin_basename;
    $result['status'] = 'success';
    unset($result['error_message']);

    return $result;

    }
    else
    {
          // Check filesystem perms to see if user is allowed to install.
          // If not this annoying FTP permission screen pops up.
          $bool = ob_start();

          $url = admin_url('wp-admin');
          $method = 'POST';

          $creds = request_filesystem_credentials( $url, $method, false, false, null );
          if ( false === $creds ) {

              $result['error_message'] = sprintf(__('Installation failed. WordPress doesn\'t have permission to install plugin(s). Please check and correct %s your permissions %s', 'maxbuttons'), '<a href="https://wordpress.org/support/article/changing-file-permissions/" target="_blank">', '</a>');
              $result['success'] = false;
               $bool = ob_end_clean();
               return false;
          }

    }

    // failed, unreasonably.
    return $result;
  }

  protected function activatePlugin($basename)
  {
      $res = activate_plugin($basename);
      return $res;
  }

} // class
