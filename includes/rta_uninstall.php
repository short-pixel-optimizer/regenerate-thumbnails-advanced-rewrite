<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Class that will hold functionality for plugin deactivation
 *
 * PHP version 5
 *
 * @category   Uninstall
 * @package    Regenerate Thumbnails ID SCOUT
 * @author     Muhammad Atiq
 * @version    1.0.0
 * @since      File available since Release 1.0.0
*/

class RTA_Uninstall extends RTA
{
    public function __construct() {
        
        do_action('rta_before_uninstall', $this );
        
        do_action('rta_after_uninstall', $this );
    }    
}

$rta_uninstall = new RTA_Uninstall();