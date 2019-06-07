
<section class='regenerate'>
  <div class='container'>
    <div class="rta_wait_loader"><?php _e('Please wait...','regenerate-thumbnails-advanced'); ?></div>

    <div class="cpbParent rta_progress rta_hidden">
        <svg class="CircularProgressbar  " viewBox="0 0 100 100">
            <path class="CircularProgressbar-trail" d="
                M 50,50
                m 0,-46
                a 46,46 0 1 1 0,92
                a 46,46 0 1 1 0,-92
                " stroke-width="8" fill-opacity="0">
            </path>
            <path class="CircularProgressbar-path" d="
                M 50,50
                m 0,-46
                a 46,46 0 1 1 0,92
                a 46,46 0 1 1 0,-92
                " stroke-width="8" fill-opacity="0" style="stroke-dasharray: 289.027px, 289.027px; stroke-dashoffset: 289.027px;">
            </path>
            <text class="CircularProgressbar-text" x="50" y="50">0%</text>
        </svg>
        <div class="images">
            <h5><?php _e('Regenerated image:','regenerate-thumbnails-advanced'); ?></h5>
            <img src="" alt="">
        </div>

        <button class='button stop-process rta_hidden'><?php _e('Stop Process', 'regenerate-thumbnails-advanced') ?></button>

    </div>

    <a href="javascript:void(0);" class="rta_error_link rta_hidden"><?php _e('There were some errors, click for details', 'regenerate-thumbnails-advanced'); ?></a>
    <div class="listContainer rta_error_box row rta_hidden">
        <div class="statuslist col-sm-6">
            <h4 class="listTitle"><?php _e('Error(s)','regenerate-thumbnails-advanced'); ?></h4>
            <ul class="list-group">
            </ul>
        </div>
    </div>

    <?php
  		#wp_nonce_field('enable-media-replace');
      $plugins = get_plugins();
      $spInstalled = isset($plugins['shortpixel-image-optimiser/wp-shortpixel.php']);
      $spActive = is_plugin_active('shortpixel-image-optimiser/wp-shortpixel.php');

  	?>

  		<?php if(!$spInstalled): ?>
  		<div class='shortpixel-notice rta_hidden'>
  			<h3 class="" style="margin-top: 0;text-align: center;">
  				<a href="https://shortpixel.com/otp/af/TFXUHHC28044" target="_blank">
  					<?php echo esc_html__("Optimize your images with ShortPixel, get +50% credits!", "enable-media-replace"); ?>
  				</a>
  			</h3>
  			<div class="" style="text-align: center;">
  				<a href="https://shortpixel.com/otp/af/TFXUHHC28044" target="_blank">
  					<img src="https://optimizingmattersblog.files.wordpress.com/2016/10/shortpixel.png">
  				</a>
  			</div>
  			<div class="" style="margin-bottom: 10px;">
  				<?php echo esc_html__("Get more Google love by compressing your site's images! Check out how much ShortPixel can save your site and get +50% credits when signing up as an Enable Media Replace user! Forever!", "enable-media-replace"); ?>
  			</div>
  			<div class=""><div style="text-align: <?php echo (is_rtl()) ? 'left' : 'right' ?>;">
  					<a class="button button-primary" id="shortpixel-image-optimiser-info" href="https://shortpixel.com/otp/af/TFXUHHC28044" target="_blank">
  						<?php echo esc_html__("More info", "enable-media-replace"); ?></p>
  					</a>
  				</div>
  			</div>
  		</div>
  		<?php
      else:
       ?>
      <div class='shortpixel-bulk-notice rta_hidden'>
         <?php if ($spActive): ?>
          <p class='gotobulk'><?php printf(__('Thumbnails successfully regenerated. Go to %s ShortPixel Bulk page %s to optimize the updated thumbnails.'), '<a href="' . admin_url('upload.php?page=wp-short-pixel-bulk') . '">', '</a>'); ?></p>
        <?php else:
          $path = 'shortpixel-image-optimiser/wp-shortpixel.php';
          $activate_url = wp_nonce_url(admin_url('plugins.php?action=activate&plugin='.$path), 'activate-plugin_'.$path);
          ?>
          <p class='gotobulk'><?php printf(__('%s Activate ShortPixel %s to optimize your newly generated thumbnails.'), '<a href="' . $activate_url . '">', '</a>'); ?></p>
        <?php endif; ?>
      </div>
     <?php endif ?>
  </div>  <!-- container -->
</section>
