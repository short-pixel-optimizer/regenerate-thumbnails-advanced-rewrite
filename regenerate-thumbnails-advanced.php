<?php
namespace ReThumbAdvanced;
/*
Plugin Name: Regenerate Thumbnails Advanced
Description: Regenerate thumbnails fast and easy while removing unused thumbnails of existing images; very useful when changing a theme.
Version: 2.2-DEV07
Author: ShortPixel
Author URI: https://shortpixel.com/
License: GPLv2 or later
Text Domain: regenerate-thumbnails-advanced
Domain Path: /languages
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
  exit;
}

define( 'RTA_PLUGIN_VERSION', '2.2-DEV07');
define( 'RTA_PLUGIN_PATH', plugin_dir_path(__FILE__) );
define( 'RTA_PLUGIN_URL', plugin_dir_url(__FILE__) );
define( 'RTA_SITE_BASE_URL',  rtrim(get_bloginfo('url'),"/")."/");
define( 'RTA_PLUGIN_FILE', __FILE__);
define( 'RTA_LANG_DIR', dirname( plugin_basename(__FILE__) ).'/languages' );

require_once(RTA_PLUGIN_PATH . 'build/shortpixel/autoload.php');
require_once(RTA_PLUGIN_PATH . 'classes/rta_controller.php');
require_once(RTA_PLUGIN_PATH . 'classes/rta-plugin.php');

function RTA()
{
  //$rta = new rtaPlugin(); // start runtime
  return rtaPlugin::getInstance();
}

RTA();

register_uninstall_hook(RTA_PLUGIN_FILE, array('rtaInstall', 'uninstall'));
register_activation_hook(RTA_PLUGIN_FILE, array('rtaInstall', 'activate'));
register_deactivation_hook(RTA_PLUGIN_FILE, array('rtaInstall', 'deactivate'));
