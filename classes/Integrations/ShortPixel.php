<?php
namespace ReThumbAdvanced\Integrations;
use \ReThumbAdvanced\ShortPixelLogger\ShortPixelLogger as Log;
use \ReThumbAdvanced\RTA as RTA;

if ( ! defined( 'ABSPATH' ) ) {
 exit; // Exit if accessed directly.
}


class ShortPixel
{

  protected static $instance;


  public function __construct()
  {
      $this->hooks();
  }

  protected function hooks()
  {
      // @todo This is asking for an Environment Controller.
      if (!function_exists('is_plugin_active')) {
       include_once(ABSPATH . 'wp-admin/includes/plugin.php');
      }

      if (\is_plugin_active('shortpixel-image-optimiser/wp-shortpixel.php'))
      {
         add_filter('rta/get_backup', array($this, 'spio_get_backup'),10,2);
      }

      add_filter('shortpixel/settings/image_sizes', array($this, 'image_sizes_name'));
  }

  public static function getInstance()
  {
      if (is_null(self::$instance))
        self::$instance = new ShortPixel();

      return self::$instance;
  }

  public function spio_get_backup($fullpath, $id)
  {
      // Fail-safe.
      if (function_exists('wpSPIO') && method_exists(\wpSPIO(), 'filesystem'))
      {
        $fs = \wpSPIO()->filesystem();

        if (! is_object($fs))
        {
           return $fullpath;
        }

        $mediaItem = $fs->getMediaImage($id);
        if (is_object($mediaItem))
        {
           if ($mediaItem->hasBackup())
           {
              $backupFile = $mediaItem->getBackupFile();
              if (false !== $backupFile)
              {
                 Log::addInfo("Integration: Backup File found: " . $backupFile->getFullPath());
                 $fullpath = $backupFile->getFullPath();
              }

           }
        }

      }

      return $fullpath;
  }

  public function image_sizes_name($sizes)
  {
    $custom_images = \ReThumbAdvanced\RTA()->admin()->getOption('custom_image_sizes');
    if (! is_array($custom_images) || count($custom_images) == 0)
    {
       return $sizes;
    }

    foreach($sizes as $sizeName => $data)
    {
       if (in_array($sizeName, $custom_images['name']))
       {
          $index = array_search($sizeName, $custom_images['name']);
          if (false !== $index)
          {
             $nicename = $custom_images['pname'][$index];
             $sizes[$sizeName]['nice-name'] = $nicename;

          }
       }
    }
     return $sizes;
  }


}
