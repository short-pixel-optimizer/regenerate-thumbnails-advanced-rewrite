<?php
namespace ReThumbAdvanced\Controllers;
use function ReThumbAdvanced\RTA;


if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Main Controller
class Controller
{

  /** @todo not sur why this is such a complicated function */
  public function load_template( $template='', $for='front', $attr=array() ) {

      do_action( 'rta_before_load_template', $template, $for, $attr );
      $template = apply_filters( 'rta_template_to_load', $template, $for, $attr );
      $attr = apply_filters( 'rta_template_variables', $attr, $template, $for );
      $pluginPaths = RTA()->getTemplatePaths();

      if( empty($template) ) {
          return '';
      }
      if( is_array($attr) ) {
          extract($attr);
      }
      $html = '';
      $html = apply_filters( 'rta_before_template_html', $html, $template, $for, $attr );
      ob_start();
      try {
        foreach($pluginPaths as $pluginPath)
        {
            $template_path = $pluginPath . 'templates/'.$for.'/'.$template.'.php';

            if (file_exists($template_path))
            {
              require ($template_path);
            }
        }

      }
      catch (Error $e)
      {
         Log::addError('Load Template error! Could not load : ' .  $template .  ' error : ' . $e->getMessage());
      }
      $html = ob_get_contents();
      ob_end_clean();

      do_action( 'rta_after_load_template', $template, $for, $attr, $html );
      $html = apply_filters( 'rta_after_template_html', $html, $template, $for, $attr );

      return $html;
  }

  public function getURL($path)
  {
      return plugins_url($path, RTA_PLUGIN_FILE);
  }
} // class
