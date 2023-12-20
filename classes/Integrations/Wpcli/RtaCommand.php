<?php
namespace ReThumbAdvanced\Integrations\Wpcli;

use ReThumbAdvanced\Image as Image;
use \WP_CLI as WP_CLI;

//use ReThumbAdvanced\RTA as RTA;

if ( ! defined( 'ABSPATH' ) ) {
 exit; // Exit if accessed directly.
}


class RtaCommand
{

  /**
  * Regenerate Image Thumbnails
  *
  *
  * [--onlyFeatured]
  *  Regenerate only featured images
  *
  * [--startDate=<date>]
  *  Process images starting at this date. DateFormat must be something strtotime accepts.
  *
  * [--endStart=<date>]
  *  Process images until this date. DateFormat must be something strtotime accepts.
  *
  * ## EXAMPLES
  *
  * wp rta regen
  *
  *
  */
   public function regen($args, $assoc)
   {
      $process = $this->getProcess();

      if ($process->isUnemployed() === true)
      {
         if (isset($assoc['startDate']))
         {
            $startstamp = strtotime($assoc['startDate']);
            if (false === $startstamp)
            {
               \WP_CLI::error('Start Date: Wrong datetime format. Requires is a format that strtotime can use');
            }
         }
         else {
           $startstamp = -1;
         }

         if (isset($assoc['endDate']))
         {
            $endstamp = strtotime($assoc['endDate']);
            if (false === $endstamp)
            {
               \WP_CLI::error('End date: Wrong datetime format. Requires is a format that strtotime can use');
            }
         }
         else {
           $endstamp = time();
         }


         if (isset($assoc['onlyFeatured']))
         {
            $process->setOnlyFeatured(true);
         }

         $process->setTime($startstamp, $endstamp);


         $process->start();
      }
      elseif (count($assoc) > 0)
      {
         \WP_CLI::error(__('Regenerate was called with arguments, but a process is still running. Either exit current process with "wp rta reset" or resume it by calling function without arguments'));
      }

      $this->runProcess();

      //$process = $this->getProcess();


   }

   /**
    * STATUS
    *
    * *
    * ## EXAMPLES
    *
    * wp rta status
    */
   public function status()
   {

       $this->printStatus();
   }

   /**
    * Reset current process
    */
   public function reset()
   {
     $process = $this->getProcess();
     $process->end();

     $this->printStatus();
   }

   protected function runProcess()
   {
      $process = $this->getProcess();
      $didSomething = false;


      if ($process->get('preparing') == true)  // prepare loop
      {

         \WP_CLI::line('Preparing');
         $count = $process->prepare();

         \WP_CLI::line(sprintf(__('Preparing %s items', 'regenerate-thumbnails-advanced'), $count));
         $this->printStatus();
         $didSomething = true;


      }

      if ($process->get('running') == true)
      {
          $items = $process->dequeueItems();
        //  \WP_CLI::line('Running ' . count($items) . ' items ');
          if ($items)
          {
            foreach($items as $item)
            {
              $item_id = $item->item_id;
              $image = new Image($item_id);
              $bool = $image->regenerate();
            }
          }

          $results = $this->getAjaxController()->get_status();

          if (is_array($results))
          {
            foreach($results as $index => $result)
            {
                $this->displayRegenLine($result);
            }
          }
          $this->getAjaxController()->clear_status();
          $didSomething = true;

      }

      if ($process->get('finished') == true)
      {
         if ($process->get('done') == 0) // if Q is finished with 0 done, it was empty.
         {
             \WP_CLI::line(sprintf('No images found to regenerate', 'regenerate-thumbnails-advanced'));
         }

         $this->printStatus();
         $process->end();
         $didSomething = true;
         return true;
      }

      if (false === $didSomething)
      {
        $this->printStatus();
         \WP_CLI::error('RunProcess did nothing, ending');
         return false;
      }

      sleep(1);
      $this->runProcess();
   }

   protected function displayRegenLine($result)
   {
    //  $ajaxController = $this->getAjaxController();
      $filename = basename($result['image']);

      if (true === $result['error'])
      {
          \WP_CLI::Line(sprintf(__('Error %s in %s', 'regenerate-thumbnails-advanced'), $result['message'], $filename));
      }
      else {
          \WP_CLI::Success($result['message']);
      }

   }

   protected function printStatus()
   {
       $process = $this->getProcess();
       $status = $process->getProcessStatus();


       $status['running'] = (1 == $status['running']) ? 'true' : 'false';
       $status['preparing'] = (1 == $status['preparing']) ? 'true' : 'false';
       $status['finished'] = (1 == $status['finished']) ? 'true' : 'false';

			 $fields = array('running', 'preparing', 'finished', 'done', 'items', 'errors',
       'start',
       'end',
       'onlyFeatured',
      );

       $startstamp = $process->getSetting('startstamp');
       $endstamp   = $process->getSetting('endstamp');

       if ($startstamp < 0)
       {
          $startstamp = ("N/A");
       }
       if ($endstamp < 0)
       {
          $endstamp = ("N/A");
       }

       $status['start'] = $startstamp;
       $status['end'] = $endstamp;
       $status['onlyFeatured']  = $this->textBoolean($process->get('only_featured'));


       \WP_CLI::Line("--- Status ---");
       \WP_CLI\Utils\format_items('table', array($status), $fields);



   }

   private function getProcess()
   {
     $process = \ReThumbAdvanced\RTA()->process();
     return $process;
   }

   private function getAjaxController()
   {
       $ajaxController = \ReThumbAdvanced\RTA()->ajax();
       return $ajaxController;
   }

   //  Colored is buggy, so off for now -> https://github.com/wp-cli/php-cli-tools/issues/134
  private function textBoolean($bool, $colored = false)
  {
      $colored = false;
      $values = array('','');

      if ($bool)
      {
        if ($colored)
        {
          $values = array('%g', '%n');
        }
        $res =  vsprintf(__('%sYes%s', 'shortpixel-image-optimiser'), $values);
        if ($colored)
          $res = \WP_CLI::colorize($res);
      }
      else
      {
        if ($colored)
        {
          $values = array('%r', '');
        }
        $res = vsprintf(__('%sNo%s', 'shortpixel-image-optimiser'), $values);
        if ($colored)
            $res = \WP_CLI::colorize($res);
      }

      return $res;
  }


} // class
