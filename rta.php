<?php
/*
Plugin Name: Regenerate Thumbnails Advanced
Description: Plugin will Regenerate Thumbnails
Version: 1.0.0
Author: Muhammad Atiq
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
    echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
    exit;
}

define( 'RTA_PLUGIN_NAME', 'Regenerate Thumbnails' );
define( 'RTA_PLUGIN_PATH', plugin_dir_path(__FILE__) );
define( 'RTA_PLUGIN_URL', plugin_dir_url(__FILE__) );
define( 'RTA_SITE_BASE_URL',  rtrim(get_bloginfo('url'),"/")."/");
define( 'RTA_LANG_DIR', dirname( plugin_basename(__FILE__) ).'/language/' );

require_once RTA_PLUGIN_PATH.'includes/rta_class.php';

register_activation_hook( __FILE__, array( 'RTA', 'rta_install' ) );
register_deactivation_hook( __FILE__, array( 'RTA', 'rta_uninstall' ) );