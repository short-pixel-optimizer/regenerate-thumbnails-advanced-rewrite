<?php
namespace ReThumbAdvanced;
use \ReThumbAdvanced\ShortPixelLogger\ShortPixelLogger as Log;
//use ReThumbAdvanced\ShortQ;

use \ShortPixel\ShortQ as ShortQ;

/** Class Process
* This class functions as glue between ShortQ and RTA. Responsible for enqueuing and process monitoring.
* Main class should be simply able to ask for process and it's status and act upon that.
*/

class Process
{
  const RTAQ_NAME = 'rtaq';
  const RTA_SLUG = 'rta';

  //private $process;

  protected $total = 0;
  protected $current = 0;
  //protected $running = false;
  //protected $is_queued = false;
  protected $status; // notifications.

  // options.
  protected $startstamp = -1;
  protected $endstamp = -1;
  protected $only_featured = false;
  protected $remove_thumbnails = false;
  protected $delete_leftmetadata = false;
  protected $clean_metadata = false;

  protected $query_prepare_limit = 1000; // amount of records to enqueue per go.
  protected $query_chunk_size = 100;

  protected $q;

  public function __construct()
  {
      //$p = new ShortQ\Queue\Queue();
      $shortQ = new ShortQ\ShortQ(self::RTA_SLUG);
      $this->q = $shortQ->getQueue(self::RTAQ_NAME);

      $this->status = $this->q->getStatus();

      // we need to do something
    /*  if ($status->get('items') > 0)
      {
          if ($status->get('running'))
            $this->doProcess();
          if($status->get('preparing'))
          {
            $this->addQueue();
          }
      } */

//$this->q = $shortQ->getQueue(self::RTAQ_NAME);
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

  public function setDeleteLeftMeta($bool)
  {
    $this->delete_leftmetadata = $bool;
  }

  public function setCleanMetadata($bool)
  {
    $this->clean_metadata = $bool;
  }

  public function setOnlyFeatured($bool)
  {
    $this->only_featured = $bool;
  }


  public function get($name)
  {
      return $this->status->get($name);
    /* if (isset($this->{$name}))
     {
       return $this->$name;
     }
     else
      return null; */
  }

  /** Starts a new generate process. Queries the totals based on form input
  * @param $form Array with FormData
  * @return boolean true if all went ok, false if error occured
  * Status and errors can be gotten from process attribute.
  */
  public function start()
  {
      delete_option('rta_get_all_files');
      $this->save_process();
      $result = $this->prepare();
      return $result;
  }

  public function continue()
  {

  }

  protected function prepare()
  {
     global $wpdb;
     $lastId = $this->q->get('last_id');

     $query = 'SELECT ID FROM ' . $wpdb->posts . ' where post_type = %s ';
     $prepare = array('attachment');

     if ($this->startstamp > -1)
     {
       $query .= ' AND post_date >= %s ';
       $prepare[] = date("Y-m-d", $this->startstamp);
     }
     if ($this->endstamp > -1)
     {
       $query .= ' AND post_date <= %s ';
       $prepare[] = date("Y-m-d", $this->endstamp);
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

      $query .= ' limit %d ';
      $prepare = $this->query_prepare_limit;


     $query .= ' order by ID DESC ';

     $sql = $wpdb->prepare($query, $prepare);

     $result = $wpdb->get_results($sql);

    // $chunks =
     $items = array();

     foreach($result as $index => $row)
     {
          $items[] = array('id' => $row->ID, 'value' => '');
     }

     $this->q->addItems($items);
     $this->q->enqueue();

  }


  protected function save_process()
  {
      /*$p = clone $this; // passed by reference, want to keep it in current scope.
      unset($p->status); // don't save status.
      unset($p->q); // don't save the Queue */
      $data = array('startstamp' => $this->startstamp, 'endstamp' => $this->endstamp, 'only_featured' => $this->only_featured,
                  'remove_thumbnails' => $this->remove_thumbnails, 'delete_leftmetadata' => $this->delete_leftmetadata, 'clean_metadata' => $this->clean_metadata, 'query_prepare_limit' => $query_prepare_limit,

                  );
      update_option('rta_image_process', $data, false);
  }

/*  protected function get_process()
  {
     $process = get_option('rta_image_process', false);
     if (! is_object($process))
      return false;

     //$process->status = array(); // process is saved without it.
     if ($process->current == $process->total)
     {
       return false; // don't consider a done process a process
     }
     return $process; // if false, no process running. If object, running process there.
  } */

  protected function end_process()
  {
      //$this->process->running = false;
      delete_option('rta_image_process');
  }

} // process class
