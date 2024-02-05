<?php
namespace ReThumbAdvanced;
use \ReThumbAdvanced\ShortPixelLogger\ShortPixelLogger as Log;

if ( ! defined( 'ABSPATH' ) ) {
 exit; // Exit if accessed directly.
}

class Environment
{

    protected static $instance;
    protected $memory_limit;

    protected $run_start = 0;
    protected $run_limit = 0;

    public function __construct()
    {
        $this->set();
    }

    public static function getInstance()
    {
       if (is_null(self::$instance))
       {
         self::$instance = new Environment();
       }

       return self::$instance;
    }

    protected function set()
    {
      $this->memory_limit = $this->unitToInt(ini_get('memory_limit'));

    }

    private function unitToInt($s)
    {
      if ((int) $s < 0)
      {
         return -1; // unlimited
      }

      return (int)preg_replace_callback('/(\-?\d+)(.?)/', function ($m) {
          return $m[1] * pow(1024, strpos('BKMG', $m[2]));
      }, strtoupper($s));
    }


    // function to limit runtimes in seconds..
    public function IsOverTimeLimit($limit = 6)
    {
        $limit = apply_filters('rta/process/prepare_limit', $limit);
        if (0 == $this->run_limit )
        {
            $this->run_start = time();
            $this->run_limit = time() + $limit;
        }

        if ($this->run_limit <= time())
        {
            return true;
        }


        return false;
    }

    public function IsOverMemoryLimit($runCount)
    {
        $memory_limit = $this->memory_limit;
        if ($memory_limit < 0) // check for unlimited memory
        {
           return false;
        }

        $current_mem = memory_get_usage();

        $percentage_limit = 95;

        $limit = round($memory_limit/100 * apply_filters('rta/process/max_memory', $percentage_limit));

        if ($current_mem >= $limit)
        {
           return true;
        }
        else {
          return false;
        }
    }

    public function plugin_active($name)
    {
      switch($name)
      {
          case 'shortpixel':
             $plugin = 'shortpixel-image-optimiser/wp-shortpixel.php';
             $class = 'ShortPixelPlugin';
          break;
      }

      if (!function_exists('is_plugin_active')) {
       include_once(ABSPATH . 'wp-admin/includes/plugin.php');
      }

      $bool = \is_plugin_active($plugin);
      if (false === $bool && isset($class))
      {
         $bool = \class_exists($class);
      }

      return $bool;

    }

} // class
