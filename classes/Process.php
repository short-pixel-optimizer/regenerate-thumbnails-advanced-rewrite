<?php
namespace ReThumbAdvanced;
use \ReThumbAdvanced\ShortPixelLogger\ShortPixelLogger as Log;
use \ReThumbAdvanced\ShortQ as ShortQ;



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
  //protected $running = false;
  //protected $is_queued = false;
  //protected $status; // notifications.

  // options.
  protected $startstamp = -1;
  protected $endstamp = -1;
  protected $only_featured = false;
  protected $remove_thumbnails = false;
  protected $delete_leftmetadata = false;
  protected $clean_metadata = false;

  protected $query_prepare_limit = 500; // amount of records to enqueue per go.
  protected $run_start = 0;
  protected $run_limit = 0;
  protected $memory_limit;
//  protected $query_chunk_size = 100;

  protected $q;
  protected $process_name = 'rta_image_process';

  public function __construct()
  {
      $shortQ = new \ReThumbAdvanced\ShortQ\ShortQ(self::RTA_SLUG);
      $this->q = $shortQ->getQueue(self::RTAQ_NAME);

      $process = $this->get_process();
      if ($process !== false)
        $this->set_process($process);

        // Allow this to be filtered.
      $this->query_prepare_limit = apply_filters('rta/process/prepare_limit', $this->query_prepare_limit);
      $this->q->setOption('numitems', apply_filters('rta/process/numitems', 3));

      $this->memory_limit =$this->unitToInt(ini_get('memory_limit'));
  }

  public static function getInstance()
  {
     if (is_null(self::$instance))
       self::$instance = new Process();

      return self::$instance;
  }

  public function getQueue()
  {
      return $this->q;
  }

  public function setTime($start, $end)
  {
    $this->startstamp = $start;
    $this->endstamp = $end;
  }

  public function setRemoveThumbnails($bool)
  {
    $this->remove_thumbnails = $bool;
  }

  public function doRemoveThumbnails()
  {
    return $this->remove_thumbnails;
  }


  public function setDeleteLeftMeta($bool)
  {
    $this->delete_leftmetadata = $bool;
  }

  public function doDeleteLeftMeta()
  {
    return $this->delete_leftmetadata;
  }

  public function setCleanMetadata($bool)
  {
    $this->clean_metadata = $bool;
  }

  public function doCleanMetadata()
  {
     return $this->clean_metadata;
  }

  public function setOnlyFeatured($bool)
  {
    $this->only_featured = $bool;
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

  public function getProcessStatus()
  {
    $process = array(
      'running' => $this->get('running'),
      'preparing' => $this->get('preparing'),
      'finished' => $this->get('finished'),
      'done' => $this->get('done'),
      'items' => $this->get('items'),
      'errors' => $this->get('errors'),
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

  // function to limit runtimes in seconds..
  protected function IsOverTimeLimit($limit = 6)
  {
      $limit = apply_filters('rta/process/prepare_limit', $limit);
      if (0 == $this->run_limit )
      {
          $this->run_start = time();
          $this->run_limit = time() + $limit;
      }

Log::addTemp('Run Limit ' . $this->run_limit);
Log::addTemp('Time      ' . time() );
      if ($this->run_limit <= time())
      {
          return true;
      }


      return false;
  }

  public function IsOverMemoryLimit($runCount)
  {
      $memory_limit = $this->memory_limit;
      $current_mem = memory_get_usage();

      $percentage_limit = ($runCount > 0) ?  (95 - round(100/$runCount)) : 95;

      $limit = round($memory_limit/100 * apply_filters('rta/process/max_memory', $percentage_limit));

Log::addTemp('Current Mem / Limit ' . $current_mem .  ' ' . $limit . ' ( ' . $percentage_limit . ' %)');
      if ($current_mem >= $limit)
      {
        Log::addTemp('Over Mem!');
         return true;
      }
      else {
        return false;
      }

  }

  public function prepare()
  {
      $result = 0;
      $i = 0;
      while( $this_result = $this->runEnqueue()  )
      {

        Log::addTemp('This_Result ' . var_export($this_result, true));
          if (false !== $this_result)
          {
            $result += $this_result;
            Log::addTemp('adding this result ' . $this_result . ' total : ' . $result);
          }

          if (true === $this->IsOverTimeLimit() || true === $this->IsOverMemoryLimit($i) )
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

Log::addTemp('Return Result ' . $result);
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

     if ($this->startstamp > -1)
     {
       $query .= ' AND post_date >= %s ';
       $prepare[] = date("Y-m-d H:i:s", $this->startstamp);
     }
     if ($this->endstamp > -1)
     {
       $query .= ' AND post_date <= %s ';
       $prepare[] = date("Y-m-d H:i:s", $this->endstamp);
     }

     if ($this->only_featured)
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
     $prepare[] = $this->query_prepare_limit;

     $sql = $wpdb->prepare($query, $prepare);

     Log::addTemp('SQL', $sql);
     $result = $wpdb->get_results($sql);
     $resultCount = 0;

    // $chunks =
     $items = array();

     foreach($result as $index => $row)
     {
          $image_id = $row->ID;
          $imageObj = new Image($image_id);
          if (false === $imageObj->isProcessable())
          {
          //  Log::addTemp("Not processable $image_id - " . $imageObj->getProcessableReason() );
             continue;
          }

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
       Log::addTemp('ResultCount :' . $resultCount);
      return $resultCount;
     }
     return false;

  }

  protected function get_process()
  {
     $process = get_option($this->process_name, false);
     return $process;
  }

  protected function set_process($process)
  {
     foreach($process as $name => $value)
     {
        $this->{$name} = $value;
     }
  }

  protected function save_process()
  {
      $data = array('startstamp' => $this->startstamp, 'endstamp' => $this->endstamp, 'only_featured' => $this->only_featured,
                  'remove_thumbnails' => $this->remove_thumbnails, 'delete_leftmetadata' => $this->delete_leftmetadata, 'clean_metadata' => $this->clean_metadata, 'query_prepare_limit' => $this->query_prepare_limit,

                );
      update_option($this->process_name, $data, false);
  }

  protected function end_process()
  {
      $this->q->resetQueue();
      delete_option($this->process_name);
  }

  private function unitToInt($s)
  {
    return (int)preg_replace_callback('/(\-?\d+)(.?)/', function ($m) {
        return $m[1] * pow(1024, strpos('BKMG', $m[2]));
    }, strtoupper($s));
  }



} // process class
