<?php
namespace ReThumbAdvanced\Updater\Controller;

if ( ! defined( 'ABSPATH' ) ) {
 exit; // Exit if accessed directly.
}

// Plugin Updater.
class UpdateController
{

    protected static $instance;

    public static function getInstance()
    {
      if (is_null(self::$instance))
      {
         self::$instance = new static();
      }

      return self::$instance;
    }


} // class
