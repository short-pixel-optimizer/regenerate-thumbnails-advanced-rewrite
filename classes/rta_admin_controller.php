<?php

class rtaAdminController
{
  protected $controller;

  protected $custom_image_sizes;
  protected $process_image_sizes = false;
  protected $jpeg_quality = 90;

  protected $cropOptions;

  public function __construct($controller)
  {
        wp_enqueue_style( 'rta_css_admin', RTA_PLUGIN_URL.'css/rta-admin-view.css', array(), RTA_PLUGIN_VERSION );

        $this->controller = $controller;

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

        $this->setOptionData();

  }

  protected function setOptionData()
  {
    $options = get_option('rta_image_sizes');
    if (isset($options['image_sizes']))
      $this->custom_image_sizes = $options['image_sizes'];

    if (isset($options['jpeg_quality']))
      $this->jpeg_quality = $options['jpeg_quality'];

    if (isset($options['process_image_sizes']))
      $this->process_image_sizes = $options['process_image_sizes'];
  }

  public function show()
  {
    $html = $this->controller->rta_load_template( "rta_generate_thumbnails", "admin", array('view' => $this) );
    echo $html;
  }

  public function loadChildTemplate($name)
  {
    $html = $this->controller->rta_load_template($name, 'admin', array('view' => $this ));
    echo $html;
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

  public function __get($name)
  {
    if (isset($this->{$name}))
    {
      return $this->{$name};
    }
    return false;
  }

  public function save_image_sizes() {
      global $_wp_additional_image_sizes;

      $jsonReponse = array('message' => '', 'error' => '');
      $error = false;
      global $rta_lang;
      $rta_image_sizes = array();
      $option = array();
      $exclude = array();

      $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : false;
      if (! wp_verify_nonce($nonce, 'rta_save_image_sizes'))
      {
        $jsonResponse['error'] = 'Invalid Nonce';
        return $jsonResponse;
      }

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
      $option['process_image_sizes'] = $sizes;  // the once that are set to regen.

      update_option( 'rta_image_sizes', $option );
      $this->setOptionData();

      $newsizes = $this->generateImageSizeOptions($sizes);

      $message = $this->controller->rta_get_message_html( $rta_lang['image_sizes_save_message'], 'message' );
      $jsonResponse = array( 'error' => $error, 'message' => $message, 'new_image_sizes' => $newsizes );

      return $jsonResponse;

  }

  /** Returns system wide defined image sizes */
  public function getImageSizes()
  {
    global $_wp_additional_image_sizes;

    $option = get_option('rta_image_sizes');
    $our_image_sizes = isset($option['image_sizes']) ? $option['image_sizes']: array();

    $imageSizes = array();
    foreach ( get_intermediate_image_sizes() as $_size )
    {
       if ( strpos($_size, 'rta_') === false)
        $imageSizes[$_size] = $_size;
    }

    // put our defined images manually, to properly update when sizes /names change.
    if (isset($our_image_sizes['pname']))
    {
      for($i = 0; $i < count($our_image_sizes['pname']); $i++ )
      {
        $int_name = $our_image_sizes['name'][$i];
        $name = $our_image_sizes['pname'][$i];
        if (strlen($name) == 0) // can't since name is tied to what it gives back to the process
            $name = $int_name;

        $imageSizes[$int_name] = $name;
      }
    }
    return $imageSizes;

  }

  public function generateImageSizeOptions($checked_ar = false)
  {
    $output = '';
    $i = 0;
    $check_all = ($checked_ar === false) ? true : false;

    // size here is a name, value is how the name is found in the system (in interface, the technical name)
    foreach($this->getImageSizes() as $value =>  $size):

      //if ($check_all)
        //$checked = 'checked';
      $checked = ($check_all || in_array($value, $checked_ar)) ? 'checked' : '';

      $output .= "<span class='item'>
        <input type='checkbox' id='regenerate_sizes[$i]' name='regenerate_sizes[$i]' value='$value' $checked>
          <label for='regenerate_sizes[$i]'>" .  ucfirst($size) . "</label>
      </span>";

      $i++;
    endforeach;
    return $output;
  }

} // class
