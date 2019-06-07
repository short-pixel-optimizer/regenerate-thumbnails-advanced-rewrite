<?php
/*
Plugin Name: Regenerate Thumbnails Advanced
Description: Regenerate thumbnails fast and easy while removing unused thumbnails of existing images; very useful when changing a theme.
Version: 2.1.0
Author: ShortPixel
Author URI: https://shortpixel.com/
License: GPLv2 or later
Text Domain: regenerate-thumbnails-advanced
Domain Path: /languages
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
    echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
    exit;
}

define( 'RTA_PLUGIN_VERSION', '2.1.0');
define( 'RTA_PLUGIN_PATH', plugin_dir_path(__FILE__) );
define( 'RTA_PLUGIN_URL', plugin_dir_url(__FILE__) );
define( 'RTA_SITE_BASE_URL',  rtrim(get_bloginfo('url'),"/")."/");
define( 'RTA_PLUGIN_FILE', __FILE__);
define( 'RTA_LANG_DIR', dirname( plugin_basename(__FILE__) ).'/languages' );

require_once(RTA_PLUGIN_PATH . 'includes/rta_class.php');
require_once(RTA_PLUGIN_PATH .'rta_admin.php');
require_once(RTA_PLUGIN_PATH .'rta_front.php');
require_once(RTA_PLUGIN_PATH . 'classes/rta_admin_controller.php');
require_once(RTA_PLUGIN_PATH . 'classes/rta_image.php');

$rta = new RTA(); // start runtime

// define ('RTA_DEBUG', true);


register_activation_hook( __FILE__, array( 'RTA', 'rta_install' ) );
register_deactivation_hook( __FILE__, array( 'RTA', 'rta_uninstall' ) );
