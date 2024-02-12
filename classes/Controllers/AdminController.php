<?php
namespace ReThumbAdvanced\Controllers;

use function ReThumbAdvanced\RTA;
use \ReThumbAdvanced\ShortPixelLogger\ShortPixelLogger as Log;
use \ReThumbAdvanced\Periods as Periods;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}


class AdminController extends Controller
{
  protected $cropOptions;

  protected $pageTitle;
  protected $proLink;

  public function __construct()
  {
        wp_enqueue_style( 'rta_css_admin');
        wp_enqueue_style( 'rta_css_admin_progress');

      //  $this->controller = $controller;

        $this->cropOptions = array(
            'no_cropped' => __('No','regenerate-thumbnails-advanced'),
            'cropped' => __('Yes','regenerate-thumbnails-advanced'),
            'left_top' => __('Left top','regenerate-thumbnails-advanced'),
            'left_center' => __('Left center','regenerate-thumbnails-advanced'),
            'left_bottom' => __('Left bottom','regenerate-thumbnails-advanced'),
            'center_top' => __('Center top','regenerate-thumbnails-advanced'),
            'center_center' => __('Center center','regenerate-thumbnails-advanced'),
            'center_bottom' => __('Center bottom','regenerate-thumbnails-advanced'),
            'right_top' => __('Right top','regenerate-thumbnails-advanced'),
            'right_center' => __('Right center','regenerate-thumbnails-advanced'),
            'right_bottom' => __('Right bottom','regenerate-thumbnails-advanced'),
        );

        $this->pageTitle = __('Regenerate Thumbnails Advanced','regenerate-thumbnails-advanced');
        $this->proLink = 'https://shortpixel.com/products/regenerate-thumbnails-advanced-pro';

  }

  public function show()
  {
    $view = new \stdClass;

    $html = $this->load_template( "rta_generate_thumbnails", "admin", array('view' => $view) );
    echo $html;
  }

  public function loadChildTemplate($name)
  {
    $view = new \stdClass;
    if ($name == 'view_rta_settings')
    {
      $view->custom_image_sizes = RTA()->admin()->getOption('custom_image_sizes');
      $view->process_image_sizes = RTA()->admin()->getOption('process_image_sizes');
      $view->process_image_options = RTA()->admin()->getOption('process_image_options');
      $view->jpeg_quality = RTA()->admin()->getOption('jpeg_quality');

    }
    elseif ($name === 'view_rta_regenerate')
    {
        $periodsClass = $this->getPeriodsClass(); // compat for 5.6  :(
        $view->periods = $periodsClass::getAll();
    }

    $html = $this->load_template($name, 'admin', array('view' => $view ));
    echo $html;
  }

  public function getProSnippet()
  {
      $output =   sprintf('<div class="pro-only">%s %s %s</div>',
                  '<a href="' . esc_url($this->proLink) . '" target="_blank">',
                  __('PRO Only', 'regenerate-thumbnails-advanced'),
                  '</a>');
      return $output;
  }

  protected function getPeriodsClass()
  {
      return Periods::class;
  }

  /** Generate cropOptions
  *
  */
  public function cropOptions($current = '')
  {
    $output = '';
    foreach($this->cropOptions as $name => $label)
    {
      $selected =  ($name == $current) ? 'selected' : '';
      $output .= "<option value='$name' $selected>$label</option>";
    }

    return $output;
  }


  /** Save thumbnail settings.
  *
  * @return JSON  Returns json result data
  */
  public function save_image_sizes() {
      global $_wp_additional_image_sizes;

      $jsonReponse = array('message' => '', 'error' => '');
      $error = false;
      $rta_image_sizes = array();
      $option = array();
      $exclude = array();


      if (isset($_POST['form']))
          parse_str($_POST['form'], $formpost);
       else
          $formpost = array();

      $image_sizes = isset($formpost['image_sizes']) ? $formpost['image_sizes'] : array();
      $jpeg_quality = isset( $formpost['jpeg_quality']) ? $formpost['jpeg_quality'] : 0;

      if (isset($image_sizes['name']))
      {
        for($i =0; $i < count($image_sizes['name']); $i++)
        {
            if (strlen($image_sizes['name'][$i]) <= 0)
            {
              continue;
            }
            // sanitize!
            $rta_image_sizes['name'][] = isset($image_sizes['name'][$i]) ? sanitize_text_field($image_sizes['name'][$i]) : '';
            $rta_image_sizes['pname'][] = isset($image_sizes['pname'][$i]) ? sanitize_text_field($image_sizes['pname'][$i]) : '';
            $rta_image_sizes['width'][] = isset($image_sizes['width'][$i]) ? intval($image_sizes['width'][$i]) : '';
            $rta_image_sizes['height'][] = isset($image_sizes['height'][$i]) ? intval($image_sizes['height'][$i]) : '';
            $rta_image_sizes['cropping'][] = isset($image_sizes['cropping'][$i]) ? sanitize_text_field($image_sizes['cropping'][$i]) : '';
        }

      }

      if ($jpeg_quality > 0)
        $option['jpeg_quality'] = $jpeg_quality;

      $option['image_sizes'] = $rta_image_sizes;

      // redo the thumbnail options, apply changes
      $sizes = isset($formpost['regenerate_sizes']) ? $formpost['regenerate_sizes'] : array();
      // filter out stub
      $sizes = array_filter($sizes, 'is_numeric', ARRAY_FILTER_USE_KEY);

      $size_options = array();
      foreach($sizes as $rsize)
      {
          if (isset($formpost['overwrite_' . $rsize]))
          {
            $size_options[$rsize] = array('overwrite_files' => true);
          }
          else {
            $size_options[$rsize] = array('overwrite_files' => false);
          }
      }
      $option['process_image_sizes'] = array_values($sizes);  // the once that are set to regen. Array values resets index
      $option['process_image_options'] = $size_options;

      update_option( 'rta_image_sizes', $option );
      RTA()->admin()->resetOptionData();

      $newsizes = $this->generateImageSizeOptions($sizes);
      $jsonResponse = array( 'error' => $error, 'message' => '', 'new_image_sizes' => $newsizes );

      return $jsonResponse;
  }

  protected function isFeatureActive($name = '')
  {
     return false;
  }

  protected function generateImageSizeOptions($checked_ar = false)
  {
    $output = '';
    $i = 0;
    $check_all = ($checked_ar === false) ? true : false;

    //$process_options = $this->process_image_options;
    $process_options = RTA()->admin()->getOption('process_image_options');

    $system_image_sizes = RTA()->admin()->getOption('system_image_sizes');
    $image_sizes = array();

    // size here is a name, value is how the name is found in the system (in interface, the technical name)
    foreach($system_image_sizes as $size => $item)
    {

      $width = isset($item['width']) ? $item['width'] : '*';
      $height = isset($item['height']) ? $item['height'] : '*';
      $name = isset($item['nice-name']) ? $item['nice-name'] : ucfirst($size);
    //  $size = $item['name']

      //if ($check_all)
        //$checked = 'checked';
      $checked = ($check_all || in_array($size, $checked_ar)) ? 'checked' : '';
      $hidden = ($checked == 'checked') ? '' : 'hidden'; // hide add. option if not checked.

    //  $option_in_db = (isset($process_options[$size])) ? true : false;
      $checked_overwrite = (isset($process_options[$size]) && isset($process_options[$size]['overwrite_files']) &&  $process_options[$size]['overwrite_files'] )  ? 'checked' : '';

    //  if ($option_in_db)
      //  $checked .= ' data-setbyuser=true'; // if value was ever saved in DB, don't change it in the JS.


      $stub = $this->getHTMLStub();

      $replacer = array(
          '%%class%%' => 'item',
          '%%index%%' => $i,
          '%%size%%' => $size,
          '%%checked%%' => $checked,
          '%%name%%' => $name,
          '%%width%%' => $width,
          '%%height%%' => $height,
          '%%hidden%%' => $hidden,
          '%%checked_overwrite%%' => $checked_overwrite,

      );

      $output .= str_replace(array_keys($replacer), array_values($replacer), $stub);

      $i++;
    };

    // default and checked gives issues on checkbox
    $output .= str_replace(array('%%class%%', '%%checked_overwrite%%', '%%checked%%'), array('item stub hidden', '', 'checked'), $stub);

    return $output;
  }

  private function getHTMLStub()
  {
      $html = '<div class="%%class%%">
                <label>
                  <input type="checkbox" id="regenerate_sizes[%%index%%]" name="regenerate_sizes[%%index%%]" value="%%size%%" %%checked%%>
                  <span class="text">%%name%% (%%width%%x%%height%%)</span>
                </label>
                <span class="options %%hidden%%">
                  <label title="' . __('If this option is enabled, the thumbnails will be regenerated even if the files already exist.', 'regenerate-thumbnails-advanced') . '">
                  <input value="1" type="checkbox" %%checked_overwrite%% name="overwrite_%%size%%">' . __('Force regeneration', 'regenerate-thumbnails-advanced') . '</label>
                </span>

               </div>';
      return $html;
  }



} // class
