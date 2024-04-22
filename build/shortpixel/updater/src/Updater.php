<?php
namespace ReThumbAdvanced\Updater;

if ( ! defined( 'ABSPATH' ) ) {
 exit; // Exit if accessed directly.
}

class Updater
{

    protected $plugin;
    protected $root_file;

    public function __construct($args = array())
    {
        $default = array(
            'plugin' => false,
            'root_file' => false,
        );

        $args = wp_parse_args($args, $default);

        $this->plugin = $args['plugin'];
        $this->root_file = $args['root_file'];

        $this->init();
    }


    protected function init()
    {
        $this->loadLibrary();
    }

    protected function loadLibrary()
    {
        require_once('Library/plugin-update-checker/plugin-update-checker.php');
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
