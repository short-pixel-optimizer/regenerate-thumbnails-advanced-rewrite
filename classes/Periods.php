<?php
namespace ReThumbAdvanced;
use \ReThumbAdvanced\ShortPixelLogger\ShortPixelLogger as Log;
use \ReThumbAdvanced\Controllers\AdminController as AdminController;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}


class Periods {

    /** PERIOD OPTIONS */
    const PERIOD_ALL = 0;
    const PERIOD_DAY = 1;
    const PERIOD_WEEK = 2;
    const PERIOD_MONTH = 3;
    const PERIOD_3MONTH = 4;
    const PERIOD_6MONTH = 5;
    const PERIOD_YEAR = 6;
    const PERIOD_CUSTOM = 7;


    public static function getAll()
    {
       $periods = array(
          'all' => self::getPeriod(self::PERIOD_ALL),
          'day' => self::getPeriod(self::PERIOD_DAY),
          'week' => self::getPeriod(self::PERIOD_WEEK),
          'month' => self::getPeriod(self::PERIOD_MONTH),
          '3month' => self::getPeriod(self::PERIOD_3MONTH),
          '6month' => self::getPeriod(self::PERIOD_6MONTH),
          'year' => self::getPeriod(self::PERIOD_YEAR),
          'custom' => self::getPeriod(self::PERIOD_CUSTOM),
       );

       return $periods;
    }

    public static function getPeriod($period_int)
    {
        return new Period($period_int);
    }

}

class Period {

    public $period_id;
    public $period_name;

    public $startstamp;
    public $endstamp;


    public function __construct($period_id)
    {
        $this->period_id = intval($period_id);
        $this->set();
    }

    // period comes from form.
    protected function set()
    {
        $now = time();
        $current_stamp = current_time('timestamp');
        $endstamp = -1;


        switch ($this->period_id) {
            case PERIODS::PERIOD_ALL:
              $this->startstamp = 0;
              $this->period_name =  __('All','regenerate-thumbnails-advanced');
            break;
            case PERIODS::PERIOD_DAY:
              $this->startstamp = $now - DAY_IN_SECONDS;
              $this->period_name = __('Past Day','regenerate-thumbnails-advanced');
           break;
            case PERIODS::PERIOD_WEEK:
              $this->startstamp = $now - WEEK_IN_SECONDS;
              $this->period_name = __('Past Week','regenerate-thumbnails-advanced');
              break;
            case PERIODS::PERIOD_MONTH:
              $this->startstamp = $now - MONTH_IN_SECONDS;
              $this->period_name = __('Past Month','regenerate-thumbnails-advanced');
              break;
          case PERIODS::PERIOD_3MONTH:
              $this->startstamp = $now - (3* MONTH_IN_SECONDS);
              $this->period_name = __('Past 3 Months','regenerate-thumbnails-advanced');
          break;
          case PERIODS::PERIOD_6MONTH:
              $this->startstamp = $now - (6* MONTH_IN_SECONDS);
              $this->period_name = __('Past 6 Months','regenerate-thumbnails-advanced');
              break;
          case PERIODS::PERIOD_YEAR:
              $this->startstamp = $now - YEAR_IN_SECONDS;
              $this->period_name = __('Past Year','regenerate-thumbnails-advanced');
          break;
          case PERIODS::PERIOD_CUSTOM:
              $this->period_name = __('Custom Date','regenerate-thumbnails-advanced');

          break;

        }

        if ($endstamp < 0)
        {
           $this->endstamp = $current_stamp;
        }

    }

    public function getQueryDate()
    {
        return array('startstamp' => $this->startstamp, 'endstamp' => $this->endstamp);
    }

    public function isAvailable()
    {
       return false;
    }

    public function isCustom()
    {
       return ($this->period_id === PERIODS::PERIOD_CUSTOM);

    }



}
