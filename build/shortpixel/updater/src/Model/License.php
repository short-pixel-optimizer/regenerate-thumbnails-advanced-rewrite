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


}
