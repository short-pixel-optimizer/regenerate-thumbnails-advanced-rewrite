<?php
namespace ReThumbAdvanced;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

?>

<section class='rta-ad'>
  <span class="image">
    <a href="https://fastpixel.io/?utm_source=RTA" target="_blank">
    <img src="<?php echo plugins_url('images/fastpixel-logo.svg', RTA_PLUGIN_FILE); ?>" />
  </a>
  </span>
  <span class="line"><h3>
    <?php printf(__('FAST%sPIXEL%s - the new website accelerator plugin from ShortPixel', 'regenerate-thumbnails-advanced'), '<span class="red">','</span>'); ?>
    </h3>
  </span>

<span class="button-wrap">
    <a href="https://fastpixel.io/?utm_source=RTA" target="_blank" class='button' ><?php _e('TRY NOW!', 'regenerate-thumbnails-advanced'); ?></a>
</span>
</section>
