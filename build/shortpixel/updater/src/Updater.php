<?php
namespace ReThumbAdvanced\Updater;

if ( ! defined( 'ABSPATH' ) ) {
 exit; // Exit if accessed directly.
}

class Updater
{

    protected static $instance;

    protected $plugin;
    protected $root_file;
    protected $api_url;

    protected $is_installer = false;
    protected $is_updater = false;

    protected $args = array();

    public function __construct($args = array())
    {
        $default = array(
            'plugin' => false,
            'root_file' => false,
            'features' => array(
                'installer' => false,
                'updater' => false,
            ),
            'install_slug' => false,
        );

        $args = wp_parse_args($args, $default);

        $this->args = $args;

        $this->api_url = 'https://localhost/package/index.php';

        $this->plugin = $args['plugin'];

        $this->is_installer = ($args['features']['installer']) ? true : false;
        $this->is_updater = ($args['features']['updater']) ? true : false;

        $this->init();
    }

    public static function getInstance()
    {
        if (is_null(self::$instance))
        {
           self::$instance = new static();
        }

        return self::$instance;
    }

    protected function init()
    {
      if (true === $this->is_installer)
      {
          $control = $this->installer();
          $control->setSlug($this->args['install_slug']);

      }
      $this->loadLibrary();
    }

    protected function loadLibrary($name = 'updater')
    {
        if ('updater' === $name)
        {
          require_once('Library/plugin-update-checker/plugin-update-checker.php');
          $updateChecker = PucFactory::buildUpdateChecker(
    	           $this->api_url,
    	           $this->root-file, //Full path to the main plugin file or functions.php.
    	           $this->plugin
           );
         }
    }

    public function license()
    {
        $controller = LicenseController::getInstance();
        $controller->setPlugingSlug($this->plugin);
    }

    public function updater()
    {
       return UpdateController::getInstance();
    }



} // class
