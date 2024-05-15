<?php

?>
<section class='apikey'>
    <div class="container">
      <form method="POST" id='shortpixel-installer-form'>
        <?php wp_nonce_field('upgrade-pro', 'updater-nonce', true); ?>
         <h4>Upgrade to PRO</h4>

         <div class='option'>
           <?php _e('API Key', 'regenerate-thumbnails-advanced/'); ?>
            <input type="text" name="api_key" value="<?php esc_attr($api_key); ?>" />
         </div>

        <div class='process-wrapper hidden'>
            <?php _e('APIKey is being checked', 'regenerate-thumbnails-advanced/'); ?>
        </div>
        <div class='result-wrapper hidden'>
            --
        </div>


      <button type="submit">Install</button>
       </form>

    </div> <!-- // container -->

</section>
