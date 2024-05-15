<?php
namespace ReThumbAdvanced\Updater\Model;


if ( ! defined( 'ABSPATH' ) ) {
 exit; // Exit if accessed directly.
}


class License
{

    protected $key;
    protected $tried;
    protected $lastKeyVerified;
    protected $updated;


    public function __construct()
    {

    }

    public function setData($args)
    {
       foreach($args as $name => $value)
       {
          if (property_exists($this, $name))
          {
             $this->$name = $value;
          }
       }
    }

    public function getSaveData()
    {
       return [
          'key' => $this->key,
          'tried' => $this->tried,
          'lastKeyVerified' => $this->lastKeyVerified,
          'updated' => time(),
       ];
    }

    public function get($name)
    {
       if (property_exists($this, $name))
       {
          return $this->$name;
       }
    }


}
