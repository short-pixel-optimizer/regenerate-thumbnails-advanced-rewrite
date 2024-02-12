<?php
namespace ReThumbAdvanced;
use \ReThumbAdvanced\ShortPixelLogger\ShortPixelLogger as Log;
use \ReThumbAdvanced\Notices\NoticeController as Notice;

/**
 * Class that will hold functionality for admin side
 *
  *
 * @category   Admin Side Code
 * @package    Regenerate Thumbnails Advanced
 * @author     ShortPixel
*/

class Admin
{
    /*private $process_remove_thumbnails = false;
    private $process_delete_leftmetadata = false;
    private $process_cleanup_metadata = false;
*/
    private static $instance;

    protected $custom_image_sizes = array();
    protected $process_image_sizes = false;
    protected $process_image_options = array();
    protected $system_image_sizes = array();
    protected $jpeg_quality = 90;

    //Admin side starting point. Will call appropriate admin side hooks
    public function __construct() {
        $this->setOptionData();
    }

    public static function getInstance()
    {
       if (! isset(self::$instance))
       {
          self::$instance = new Admin();
       }

       return self::$instance;
    }

    protected function setOptionData()
    {
      $options = get_option('rta_image_sizes', $this->getDefaultOptions() );

      if (isset($options['image_sizes']) && is_array($options['image_sizes']))
        $this->custom_image_sizes = $options['image_sizes'];

      if (isset($options['jpeg_quality']))
        $this->jpeg_quality = $options['jpeg_quality'];

      if (isset($options['process_image_sizes']) && is_array($options['process_image_sizes']))
        $this->process_image_sizes = $options['process_image_sizes'];
      else
        $this->process_image_sizes = array();


      if (isset($options['process_image_options']) && is_array($options['process_image_options']) )
          $this->process_image_options = $options['process_image_options'];
      else
        $this->process_image_options = array();

       $this->system_image_sizes = $this->getImageSizes();
    }

    /** Returns system wide defined image sizes plus our custom sizes
    *
    *
    */
    protected function getImageSizes()
    {
      global $_wp_additional_image_sizes;

      $option = get_option('rta_image_sizes', array());
      $our_image_sizes = isset($option['image_sizes']) ? $option['image_sizes']: array();

      /* Snips stolen from SPIO */
  			$sizes_names = get_intermediate_image_sizes();
  			$sizes = array();
  			foreach ( $sizes_names as $size ) {
  					$sizes[ $size ][ 'width' ] = intval( get_option( "{$size}_size_w" ) );
  					$sizes[ $size ][ 'height' ] = intval( get_option( "{$size}_size_h" ) );
  					$sizes[ $size ][ 'crop' ] = get_option( "{$size}_crop" ) ? get_option( "{$size}_crop" ) : false;
            $sizes[ $size ][ 'nice-name'] = ucfirst($size);
  			}
  			if(function_exists('wp_get_additional_image_sizes')) {
  					$sizes = array_merge($sizes, wp_get_additional_image_sizes());
  			} elseif(is_array($_wp_additional_image_sizes)) {
  					$sizes = array_merge($sizes, $_wp_additional_image_sizes);
  			}

      // put our defined images manually, to properly update when sizes /names change.
      if (isset($our_image_sizes['pname']))
      {
        for($i = 0; $i < count($our_image_sizes['pname']); $i++ )
        {
          $int_name = $our_image_sizes['name'][$i];
          $name = $our_image_sizes['pname'][$i];
          if (strlen($name) == 0) // can't since name is tied to what it gives back to the process
              $name = $int_name;

          $sizes[$int_name] = array(
            'width' => $our_image_sizes['width'][$i],
            'height' => $our_image_sizes['height'][$i],
            'nice-name' => $name,
          );
        }
      }

      return $sizes;

    }

    public function resetOptionData()
    {
       $this->setOptionData();
    }

    private function getDefaultOptions()
    {
      $standard_sizes = array( 'thumbnail', 'medium', 'medium_large', 'large' ); // directly from media.php, hardcoded there.
      $process_image_options = array();
      foreach($standard_sizes as $name)
      {
        $process_image_options[$name] = array('overwrite_files' => false);
      }
      $options = array();
      $options['process_image_sizes'] = $standard_sizes;
      $options['process_image_options'] = $process_image_options;

      return $options;
    }

    public function getOption($name)
    {
       if (isset($this->$name))
          return $this->$name;

        return false;
    }

    public function regenerate_single_image($attach_id)
    {

        $imageClass = RTA()->getClass('Image');
        $image = new $imageClass($attach_id);
        $image->process();

        $status = RTA()->ajax()->get_status();

        foreach($status as $statusName => $statusItem)
        {
            if ($statusItem['error'])
              Notice::addError('<p>' . $statusItem['message'] . '</p>');
            elseif ($statusItem['status'] == 1)
              Notice::addSuccess('<p>' . __('Image thumbnails regenerated', 'regenerate-thumbnails-advanced') . '</p>');
            else
              Notice::addNormal('<p>' . $statusItem['message'] . '</p>');
        }

      //  $this->end_process();
    }

} // Class
