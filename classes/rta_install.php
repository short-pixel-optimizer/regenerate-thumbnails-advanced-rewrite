<?php
namespace ReThumbAdvanced;
use \ReThumbAdvanced\ShortPixelLogger\ShortPixelLogger as Log;
use \ReThumbAdvanced\Notices\NoticeController as Notice;

use \ShortPixel\ShortQ as ShortQ;

class RTAInstall
{

  /** The handler when user completely uninstalls the plugin */
    public static function uninstall()
    {

      $shortQ = new ShortQ\ShortQ(Process::RTA_SLUG);
      $q = $shortQ->getQueue(Process::RTAQ_NAME);
      $q->uninstall();
    }

  /** Handler on activation */
    public static function activate()
    {
    }

  /** Handler on deactivate */
    public static function deactivate()
    {

    }



}
