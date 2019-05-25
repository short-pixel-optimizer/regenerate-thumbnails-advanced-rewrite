<?php
/*
Plugin Name: Regenerate Thumbnails Advanced
Description: Regenerate thumbnails fast and easy while removing unused thumbnails of existing images; very useful when changing a theme.
Version: 2.0.2-DEV02
Author: ShortPixel
Author URI: https://shortpixel.com/
License: GPLv2 or later
Text Domain: regenerate-thumbnails-advanced
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
    echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
    exit;
}

define( 'RTA_PLUGIN_VERSION', '2.0.2-DEV02');
define( 'RTA_PLUGIN_PATH', plugin_dir_path(__FILE__) );
define( 'RTA_PLUGIN_URL', plugin_dir_url(__FILE__) );
define( 'RTA_SITE_BASE_URL',  rtrim(get_bloginfo('url'),"/")."/");
define( 'RTA_PLUGIN_FILE', __FILE__);
define( 'RTA_LANG_DIR', dirname( plugin_basename(__FILE__) ).'/language/' );

// define ('RTA_DEBUG', true);

require_once (RTA_PLUGIN_PATH . 'includes/rta_class.php');

register_activation_hook( __FILE__, array( 'RTA', 'rta_install' ) );
register_deactivation_hook( __FILE__, array( 'RTA', 'rta_uninstall' ) );
