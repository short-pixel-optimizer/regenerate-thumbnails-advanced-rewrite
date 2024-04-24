<?php
namespace ReThumbAdvanced\Updater\Controller;



if ( ! defined( 'ABSPATH' ) ) {
 exit; // Exit if accessed directly.
}

// Plugin Updater.
class UpdateController
{

    protected static $instance;
    private $api_url;

    public function __construct()
    {

    }

    public static function getInstance()
    {
      if (is_null(self::$instance))
      {
         self::$instance = new static();
      }

      return self::$instance;
    }


    protected function PostCall($args)
    {

      $result = wp_remote_post( $this->api_url, array( 'timeout' => 15, 'sslverify' => false, 'body' => $args ) );
      if ( is_wp_error( $result ) || 200 !== wp_remote_retrieve_response_code( $result ) )
      {
        $error = $result->get_error_message();

        $message =  ( is_wp_error( $result ) && ! empty( $error ) ) ? $error : __( 'An connection error occurred, please try again.', '');
        $result = array('status' => 'error',
                'error' => $message,
                "additional_info" => $message,
                );
        echo json_encode($result);

      }

      $data = json_decode( wp_remote_retrieve_body( $result ) );

      return $data;
    }


} // class
