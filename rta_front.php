<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Class that will hold functionality for front side
 *
 * PHP version 5
 *
 * @category   Front Side Code
 * @package    Regenerate Thumbnails ID SCOUT
 * @author     Muhammad Atiq
 * @version    1.0.0
 * @since      File available since Release 1.0.0
*/

class RTA_Front extends RTA
{
    //Front side starting point. Will call appropriate front side hooks
    public function __construct() {

        do_action('rta_before_front', $this );
        //All front side code will go here

        add_action( 'after_setup_theme', array( $this, 'rta_after_theme_setup' ) );
        add_filter( 'image_size_names_choose', array( $this, 'rta_image_size_names_choose' ), 10, 1 );
        add_filter( 'jpeg_quality', array( $this, 'rta_jpeg_quality' ),10, 1);

        do_action('rta_after_front', $this );
    }

    public function rta_jpeg_quality( $quality ) {
        $rta_image_sizes = get_option( 'rta_image_sizes' );
        if(is_array($rta_image_sizes) && sizeof($rta_image_sizes) > 0){
            $jpeg_quality = isset($rta_image_sizes['jpeg_quality']) ? $rta_image_sizes['jpeg_quality'] : null;
            if(!empty($jpeg_quality)) {
                return (int)$jpeg_quality;
            }
        }
        return $quality;
    }

    public function rta_image_size_names_choose( $sizes ) {

        $rta_image_sizes = get_option( 'rta_image_sizes', false );
        if (! $rta_image_sizes) // option not set
          return $sizes;
        if (! isset($rta_image_sizes['image_sizes']))
          return $sizes;

        $image_sizes = $rta_image_sizes['image_sizes'];

        $rta_sizes = array();
        if(is_array($image_sizes) && count($image_sizes) > 0 && sizeof($image_sizes['name']) > 0){
            for($i=0;$i<sizeof($image_sizes['name']);$i++){
                $slug = $image_sizes['name'][$i];
                $name = $image_sizes['pname'][$i];
                $rta_sizes[$slug] = $name;
            }

        }

        $new_sizes = array_merge( $sizes, $rta_sizes );
        return $new_sizes;
    }

    public function rta_after_theme_setup() {

        $rta_image_sizes = get_option( 'rta_image_sizes', false);
        if (! $rta_image_sizes)
          return $rta_image_sizes;

        $image_sizes = isset($rta_image_sizes['image_sizes']) && is_array($rta_image_sizes['image_sizes']) ? $rta_image_sizes['image_sizes'] : array();

        if(count($image_sizes) > 0 && count($image_sizes['name']) > 0){
            for($i=0;$i<sizeof($image_sizes['name']);$i++){
                $crop = false;
                if($image_sizes['cropping'][$i]=='no_cropped'){
                    $crop = false;
                }elseif($image_sizes['cropping'][$i]=='cropped') {
                    $crop = true;
                }else{
                    $crop = explode("_", $image_sizes['cropping'][$i]);
                }
                add_image_size( $image_sizes['name'][$i], $image_sizes['width'][$i], $image_sizes['height'][$i], $crop );
            }
        }
    }
}

$rta_front = new RTA_Front();
