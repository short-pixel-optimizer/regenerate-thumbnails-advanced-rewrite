<?php
namespace ReThumbAdvanced\Integrations;
use \ReThumbAdvanced\ShortPixelLogger\ShortPixelLogger as Log;
use \ReThumbAdvanced\Environment as Environment;
use function ReThumbAdvanced\RTA;

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
      $env = Environment::getInstance();

      if ($env->plugin_active('shortpixel'))
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
             if (strlen(trim($nicename)) > 0)
             {
               $sizes[$sizeName]['nice-name'] = $nicename;
             }
          }
       }
    }
     return $sizes;
  }


}
