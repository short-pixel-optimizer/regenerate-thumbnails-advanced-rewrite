<?php
namespace ReThumbAdvanced;
/**
 * Plugin Name: Regenerate Thumbnails Advanced
 * Description: Regenerate thumbnails quickly and easyly, including forced regeneration; very useful when changing a theme or adding new thumbnail sizes.
 * Version: 2.5.0
 * Author: ShortPixel
 * Author URI: https://shortpixel.com/
 * License: GPLv2 or later
 * GitHub Plugin URI: https://github.com/short-pixel-optimizer/regenerate-thumbnails-advanced-rewrite
 * Text Domain: regenerate-thumbnails-advanced
 * Domain Path: /languages
 */

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
  exit;
}

define( 'RTA_PLUGIN_VERSION', '2.5.0');
define( 'RTA_PLUGIN_PATH', plugin_dir_path(__FILE__) );
define( 'RTA_PLUGIN_URL', plugin_dir_url(__FILE__) );
define( 'RTA_SITE_BASE_URL',  rtrim(get_bloginfo('url'),"/")."/");
define( 'RTA_PLUGIN_FILE', __FILE__);
define( 'RTA_LANG_DIR', dirname( plugin_basename(__FILE__) ).'/languages' );

require_once(RTA_PLUGIN_PATH . 'build/shortpixel/autoload.php');


$loader = new Build\PackageLoader();
$loader->setComposerFile(RTA_PLUGIN_PATH . 'classes/plugin.json');
$loader->load(RTA_PLUGIN_PATH);



function RTA()
{
  if (class_exists('\ReThumbAdvanced\PluginPro'))
  {
    return PluginPro::getInstance();
  }
  else {
    return Plugin::getInstance();
  }

}

add_action('plugins_loaded', function () {
  RTA();
});

// Low-init to put the correct path in the manual logger box
Plugin::checkLogger();



register_uninstall_hook(RTA_PLUGIN_FILE, array('ReThumbAdvanced\Install', 'uninstall'));
register_activation_hook(RTA_PLUGIN_FILE, array('ReThumbAdvanced\Install', 'activate'));
register_deactivation_hook(RTA_PLUGIN_FILE, array('ReThumbAdvanced\Install', 'deactivate'));
