<?php
namespace ReThumbAdvanced\Updater;

use ReThumbAdvanced\Updater\Controller\RequestController as RequestController;
use ReThumbAdvanced\Updater\Controller\LicenseController as LicenseController;

use ReThumbAdvanced\ShortPixelLogger\ShortPixelLogger as Log;


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
    private $module_path;

    private const MODULE_VERSION = '1.0';

    public function __construct()
    {

    }

    public function initSettings($args)
    {
        $defaults = array(
            'plugin' => false,
            'root_file' => false,
            'features' => array(
                'installer' => false,
                'updater' => false,
            ),
            'install_slug' => false,
            'version' => false,
        );

        $args = wp_parse_args($args, $defaults);

        $this->args = $args;

        $this->api_url = 'https://safedownloads.shortpixel.com/sp-updates/';

        $this->root_file = $args['root_file']; // root file of plugin we are serving
        $this->plugin = $args['plugin']; // name / slug of plugin we are serving.

        $this->is_installer = ($args['features']['installer']) ? true : false;
        $this->is_updater = ($args['features']['updater']) ? true : false;
        $this->module_path = __DIR__;

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
          wp_register_script('shortpixel-installer', plugins_url('js/install.js', __FILE__), ['jquery']  );
          $control = $this->installer();
          $control->setSlug($this->args['install_slug']);
      }
      if (true === $this->is_updater)
      {
          $this->loadLibrary('updater');
          $control = $this->updater();
          $control->setSlug($this->args['install_slug']);
          $control->buildChecker($this->api_url, $this->root_file, $plugin);
      }

      $requestController = RequestController::getInstance();
      $requestController->SetApiUrl($this->api_url);
      $requestController->addArg('plugin_version', $this->args['version']);
      $requestController->addArg('slug', $this->plugin );
      $requestController->addArg('action', 'get_metadata');

      $licenseController = LicenseController::getInstance();
      $licenseController->setPlugingSlug($this->plugin);

    }

    protected function loadLibrary($name = 'updater')
    {
        if ('updater' === $name)
        {
        /*  $updateChecker = PucFactory::buildUpdateChecker(
    	           $this->api_url,
    	           $this->root_file, //Full path to the main plugin file or functions.php.
    	           $this->plugin
           ); */
           Log::addTemp('Adding update checker');


         }
    }

    public function loadView()
    {
         if ($this->is_installer)
         {
             $this->installer()->loadView($this->module_path);
         }
         elseif ($this->is_updater)
         {
            $this->update()->loadView($this->module_path);
         }

    }

    public function license()
    {
        $controller = LicenseController::getInstance();
        return $controller;
    }

    public function updater()
    {
       return \ReThumbAdvanced\Updater\Controller\Updater\UpdateController::getInstance();
    }

    public function installer()
    {
        return \ReThumbAdvanced\Updater\Controller\Installer\InstallController::getInstance();
    }



} // class
