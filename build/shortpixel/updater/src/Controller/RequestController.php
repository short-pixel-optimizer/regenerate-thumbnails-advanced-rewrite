<?php
namespace ReThumbAdvanced\Updater\Controller;
use ReThumbAdvanced\ShortPixelLogger\ShortPixelLogger as Log;

if ( ! defined( 'ABSPATH' ) ) {
 exit; // Exit if accessed directly.
}


class RequestController
{

  private static $instance;

  private $url;

  protected $args = array();

  public function __construct()
  {

  }

  public static function getInstance()
  {
      if (is_null(static::$instance))
      {
          static::$instance = new static();
      }

      return static::$instance;
  }

  public function SetApiUrl($url)
  {
      $this->url = $url;

  }

  public function addArg($name, $value)
  {
     $this->args[$name]  = $value;
  }

  public function doRequest()
  {
     $result = wp_remote_get( $this->url, array( 'timeout' => 15, 'sslverify' => false, 'body' => $this->args ) );

      if ( is_wp_error( $result ) || 200 !== wp_remote_retrieve_response_code( $result ) )
      {
         Log::addError('Request error! ', $result);
        if (is_wp_error($result))
        {
          $error = $result->get_error_message();
          $message =  ( is_wp_error( $result ) && ! empty( $error ) ) ? $error : __( 'An connection error occurred, please try again.', 'regenerate-thumbnails-advanced/');
        }
        else {
          $message = sprintf(__('Failure. Server returned HTTP code %s'), wp_remote_retrieve_response_code($result));
        }

        $data = array('status' => 'error',
                'error' => $message,
                "additional_info" => $message,
                );
      }
      else {
          $data = [
              'status' => 'success',
              'return' => json_decode( wp_remote_retrieve_body( $result ) )
          ];
      }


      return $data;

  }



} // class
