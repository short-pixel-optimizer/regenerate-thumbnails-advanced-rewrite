<?php
namespace ReThumbAdvanced\Updater\Controller;

if ( ! defined( 'ABSPATH' ) ) {
 exit; // Exit if accessed directly.
}


class LicenseController
{

  protected static $instance;

  protected $plugin;

  protected $licenseModel;

  public function __construct()
  {
      $this->findLicense();
  }

  public static function getInstance()
  {
    if (is_null(self::$instance))
    {
       self::$instance = new static();
    }

    return self::$instance;
  }

  public function setPlugingSlug($plugin)
  {
      $this->plugin = $plugin;
  }

  protected function findLicense()
  {
      $license_option = get_option($plugin . '_license');
      if (is_array($license_option))
      {
          $license = new LicenseModel();
          $license->setData($license_option);
      }

      // @todo Add here other ways to obtain a key, i.e. other of this module, or spio.
  }

  public function hasLicense()
  {
       if (! is_null($this->licenseModel))
       {
          return true;
       }
       else {
           return false;
       }
  }


} // class
