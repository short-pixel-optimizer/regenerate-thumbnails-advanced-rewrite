<?php
namespace ReThumbAdvanced;
use \ReThumbAdvanced\ShortPixelLogger\ShortPixelLogger as Log;
use \ReThumbAdvanced\ShortQ as ShortQ;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/** Class Process
* This class functions as glue between ShortQ and RTA. Responsible for enqueuing and process monitoring.
* Main class should be simply able to ask for process and it's status and act upon that.
*/

class Process
{
  const RTAQ_NAME = 'rtaq';
  const RTA_SLUG = 'rta';

  protected static $instance;

  protected $total = 0;
  protected $current = 0;

  // options.
  protected $options = array(
    'startstamp' => -1,
    'endstamp' => -1,
    'only_featured' => false,
    'query_prepare_limit' => 500,
  );


  protected $q;
  protected $counter = false;

  protected $process_name = 'rta_image_process';
  protected $counter_name = 'rta_image_counter';

  public function __construct()
  {
      $shortQ = new \ReThumbAdvanced\ShortQ\ShortQ(self::RTA_SLUG);
      $this->q = $shortQ->getQueue(self::RTAQ_NAME);

      $process = $this->get_process();
      if ($process !== false)
        $this->set_process($process);

        // Allow this to be filtered.
      $this->options['query_prepare_limit'] = apply_filters('rta/process/prepare_limit', $this->options['query_prepare_limit']);
      $this->q->setOption('numitems', apply_filters('rta/process/numitems', 3));


  }

  public static function getInstance()
  {
     if (is_null(self::$instance))
     {
       self::$instance = new Process();
     }

      return self::$instance;
  }

  public function getQueue()
  {
      return $this->q;
  }


  public function doRemoveThumbnails()
  {
    return $this->options['remove_thumbnails'];
  }

  // Options are RTA-specific options, saved
  public function setOption($options, $value = false)
  {

      // Multiple Options.
      if (is_array($options))
      {
         foreach($options as $name => $val)
         {
            if (isset($this->options[$name]))
            {
               $this->options[$name] = $val;
            }
         }
      }
      else { // Single Option
         if (isset($this->options[$options]))
         {
            $this->options[$options] = $value;
         }
      }
  }

  public function getOption($name)
  {
     if (isset($this->options[$name]))
     {
        return $this->options[$name];
     }

     return null;
  }

  public function get($name = false)
  {
      return $this->q->getStatus($name);
  }

  public function getSetting($name)
  {
      if (property_exists($this, $name))
      {
         return $this->{$name};
      }
      return null;
  }

  // This comes from queue module
  public function getProcessStatus()
  {
    $process = array(
      'running' => $this->get('running'),
      'preparing' => $this->get('preparing'),
      'finished' => $this->get('finished'),
      'done' => $this->get('done'),
      'items' => $this->get('items'),
      'errors' => $this->get('errors'),
      'regenerated' => (isset($this->counter['count'])) ? $this->counter['count'] : 0,
      'removed' => (isset($this->counter['removed'])) ? $this->counter['removed'] : 0,
    );

     return $process;

  }

  public function isRunning()
  {
      return (true == $this->get('running')) ? true : false;
  }

  public function isPreparing()
  {
      return (true == $this->get('preparing')) ? true : false;
  }

  public function isFinished()
  {
      return (true == $this->get('finished')) ? true : false;
  }


  public function isUnemployed()
  {
     if (false === $this->isRunning() && false === $this->isPreparing())
     {
        return true;
     }
     return false;
  }


  /** Starts a new generate process. Queries the totals based on form input
  * @param $form Array with FormData
  * @return boolean true if all went ok, false if error occured
  * Status and errors can be gotten from process attribute.
  */
  public function start()
  {
      $this->end_process(); // reset all before starting.
      $this->save_process();
      $this->q->setStatus('preparing', true);
  }

  public function end()
  {
     $this->end_process();
  }



  public function prepare()
  {
      $result = 0;
      $i = 0;
      $env = RTA()->env();
      while( $this_result = $this->runEnqueue()  )
      {

          if (false !== $this_result)
          {
            $result += $this_result;
          }

          if (true === $env->IsOverTimeLimit() || true === $env->IsOverMemoryLimit($i) )
          {
            Log::addDebug('Prepare went over time or Memory, breaking');
            break;
          }

          $i++;
      }

      if ($this_result === false)
      {
         $this->q->setStatus('preparing', false, false);
         $this->q->setStatus('running', true);
         Log::addDebug('Preparing done, Starting run status');
      }

      return $result;
  }

  public function dequeueItems()
  {
     return $this->q->dequeue();
  }

  protected function runEnqueue()
  {
     global $wpdb;
     $lastId = $this->q->getStatus('last_item_id');

     $query = 'SELECT ID FROM ' . $wpdb->posts . ' where post_type = %s ';
     $prepare = array('attachment');

     if ($this->getOption('startstamp') > -1)
     {
       $query .= ' AND post_date >= %s ';
       $prepare[] = date("Y-m-d H:i:s", $this->getOption('startstamp'));
     }
     if ($this->getOption('endstamp') > -1)
     {
       $query .= ' AND post_date <= %s ';
       $prepare[] = date("Y-m-d H:i:s", $this->getOption('endstamp'));
     }

     if (true === $this->getOption('only_featured'))
     {
        $query .= ' and ID in (select meta_value from ' . $wpdb->postmeta . ' where meta_key = "_thumbnail_id")';
     }

     if ($lastId > 0)
     {
       $query .= ' and ID < %d'; // note the reverse here, due to order!
       $prepare[] = $lastId;

     }

     $query .= ' order by ID DESC ';

     $query .= ' limit %d ';
     $prepare[] = $this->getOption('query_prepare_limit');

     $sql = $wpdb->prepare($query, $prepare);

     $result = $wpdb->get_results($sql);
     $resultCount = 0;

    // $chunks =
     $items = array();

     foreach($result as $index => $row)
     {

          $resultCount++;
          $items[] = array('id' => $row->ID, 'value' => '');

     }

     $this->q->addItems($items);
     $this->q->enqueue();

     if (0 === count($items) && isset($image_id))
     {
        $this->q->setStatus('last_item_id', $image_id);
     }

     /** Keep looping preparing ( possible query limit reached ) until no new items are forthcoming. */
     if ($resultCount > 0)
     {
      return $resultCount;
     }
     return false;

  }

  protected function get_process()
  {
     $process = get_option($this->process_name, false);
     $counter = get_option($this->counter_name, false);
     $this->counter = $counter;
     return $process;
  }

  protected function set_process($process)
  {
     foreach($process as $name => $value)
     {
        if (property_exists($this, $name))
        {
            $this->{$name} = $value;
        }
        elseif (isset($this->options[$name]))
        {
           $this->options[$name] = $value;
        }

     }
  }

  protected function save_process()
  {
      $data = $this->options;

      /* array('startstamp' => $this->startstamp, 'endstamp' => $this->endstamp, 'only_featured' => $this->only_featured,
                  'remove_thumbnails' => $this->remove_thumbnails, 'delete_leftmetadata' => $this->delete_leftmetadata, 'clean_metadata' => $this->clean_metadata, 'query_prepare_limit' => $this->query_prepare_limit,

                );
        */
      update_option($this->process_name, $data, false);
  }

  /* Add numbers to the counter.
  * @param $counts Array  should have index count or index removed to count
  */
  public function addCounts($counts)
  {
     if (false === $this->counter)
     {
        $this->counter = array(
          'count' => 0,
          'removed' => 0,
        );
     }

     if (isset($counts['count']) && $counts['count'] > 0)
     {
        $this->counter['count'] += $counts['count'];
     }

     if (isset($counts['removed']) && $counts['removed'] > 0)
     {
        $this->counter['removed'] += $counts['removed'];
     }
  }

  public function saveCounter()
  {
      update_option($this->counter_name, $this->counter, false);
  }

  protected function end_process()
  {
      $this->q->resetQueue();
      delete_option($this->counter_name);
      delete_option($this->process_name);
  }





} // process class
