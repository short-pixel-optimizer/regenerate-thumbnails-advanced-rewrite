<?php
namespace ReThumbAdvanced\Integrations\Wpcli;

if ( ! defined( 'ABSPATH' ) ) {
 exit; // Exit if accessed directly.
}

class Wpcli
{

  protected static $instance;

  public function __construct()
  {

      if (false == defined( 'WP_CLI' ) || false == WP_CLI ) {
          return false;
      }

      $log = \ReThumbAdvanced\ShortPixelLogger\ShortPixelLogger::getInstance();
      if (\ReThumbAdvanced\ShortPixelLogger\ShortPixelLogger::debugIsActive())
      {
        $uploaddir = wp_upload_dir(null, false, false);
        if (isset($uploaddir['basedir']))
          $log->setLogPath($uploaddir['basedir'] . "/rta_log_wpcli");

      }

      $this->initCommands();

  }

  public static function getInstance()
  {
      if (is_null(self::$instance))
        self::$instance = new Wpcli();

      return self::$instance;
  }


  protected function initCommands()
  {
      \WP_CLI::add_command('rta', 'ReThumbAdvanced\Integrations\Wpcli\RtaCommand');
  }


}
