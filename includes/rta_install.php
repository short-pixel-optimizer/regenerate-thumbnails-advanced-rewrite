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
        
        do_action('rta_after_install', $this );
    }    
}

$rta_install = new RTA_Install();