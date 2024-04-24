<?php
namespace ReThumbAdvanced\Updater\Controller;



if ( ! defined( 'ABSPATH' ) ) {
 exit; // Exit if accessed directly.
}


class InstallController
{
  protected $slug;

  protected $installed;
  protected $active;

  public function __construct()
  {

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

  }

  public function isActive()
  {

  }

}
