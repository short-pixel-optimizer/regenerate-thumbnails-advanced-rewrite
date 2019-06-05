<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Class that will hold functionality for plugin activation
 *
 * PHP version 5
 *
 * @category   Install
 * @package    Regenerate Thumbnails ID SCOUT
 * @author     Muhammad Atiq
 * @version    1.0.0
 * @since      File available since Release 1.0.0
*/

class RTA_Install extends RTA
{
    public function __construct() {

        do_action('rta_before_install', $this );

        $options = get_option('rta_image_sizes');

        if ($options === false) // if no default settings are there; add.
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
          add_option('rta_image_sizes', $options);
        }

        do_action('rta_after_install', $this );
    }
}

$rta_install = new RTA_Install();
