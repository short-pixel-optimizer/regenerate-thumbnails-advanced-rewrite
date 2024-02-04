<?php
namespace ReThumbAdvanced;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

?>

<section class='regenerate rta_hidden'>
  <div class='container two-panel-wrap process-wrap'>

    <div class="rta_progress no-panel-wrap">

      <div class="images rta_thumbnail_view rta_panel_off">
          <h4 class='thumb-label'><?php _e('Last Regenerated','regenerate-thumbnails-advanced'); ?></h4>
          <p class='thumb-message'>&nbsp;</p>
          <div class='thumbnail'> <img src="<?php echo $this->getURL('images/placeholder.svg') ?>" alt=""> </div>
      </div>

      <!--
        <div class='rta_progress_view rta_panel_off'>
          <svg class="CircularProgressbar" viewBox="0 0 100 100">
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
              <text class="progress-count" x="50" y="70"><tspan class='current'>0</tspan> / <tspan class='total'>0</tspan></text>
          </svg>
       </div>
-->
       <div class='rta_progressbar_view rta_panel_off' >
         <div class='rta_progressbar'><span class='right'>0%</span><span class='centre'>
            <span class='text'>0/100</span>

            (<span class="images_regenerated add-title">0 <?php _e('regenerated', 'regenerate-thumbnails-advanced') ?></span>
              <span class="images_removed rta_hidden add-title">0 <?php _e('removed', 'regenerate-thumbnails-advanced') ?></span>)
         </span>
         </div>
       </div>
    </div>

    <div class="rta_status_box">
      <button class='button pause-process process-button' id="pauseProcess" type="button" disabled>
      <span class="dashicons dashicons-controls-pause">&nbsp;</span> <?php _e('Pause Process', 'regenerate-thumbnails-advanced') ?>
      </button>

      <button class='button resume-process process-button' id="resumeProcess" type="button" disabled>
        <span class="dashicons dashicons-controls-play">&nbsp;</span> <?php _e('Resume Process', 'regenerate-thumbnails-advanced') ?>
      </button>


      <button class='button stop-process process-button' id="stopProcess" type="button" disabled>
          <span class="dashicons dashicons-no">&nbsp;</span>
          <?php _e('Stop Process', 'regenerate-thumbnails-advanced') ?>
      </button>

        <div class="rta_notices rta_panel_off">
            <ul class="statuslist">
            </ul>
        </div>
        <div class="rta_wait_loader rta_panel_off" >
          <span class='dashicons dashicons-update'>&nbsp;</span>
          <div class='start'>
          <h4><?php _e('Starting Process', 'regenerate-thumbnails-advanced', 'regenerate-thumbnails-advanced'); ?></h4>
          <p><?php _e('Please wait...','regenerate-thumbnails-advanced', 'regenerate-thumbnails-advanced'); ?></p>
          </div>
        </div>

        <div class="rta_wait_paused rta_panel_off" >
          <span class='dashicons dashicons-controls-pause'>&nbsp;</span>
          <div class='resume'>
            <h4 ><?php _e('The process is paused', 'regenerate-thumbnails-advanced', 'regenerate-thumbnails-advanced'); ?></h4>
            <p><?php _e('Click on Resume Process to continue','regenerate-thumbnails-advanced', 'regenerate-thumbnails-advanced'); ?></p>
          </div>
        </div>

        <div class="rta_wait_pausing rta_panel_off" >
          <span class='dashicons dashicons-update'>&nbsp;</span>
          <div class='pausing'>
                <h4><?php _e('The process is pausing, please wait...', 'regenerate-thumbnails-advanced'); ?></h4>
                <p><?php _e('This may take a few seconds...', 'regenerate-thumbnails-advanced') ?></p>
          </div>
        </div>

    </div>

    <?php
      $plugins = get_plugins();
      $spInstalled = isset($plugins['shortpixel-image-optimiser/wp-shortpixel.php']);
      $spActive = is_plugin_active('shortpixel-image-optimiser/wp-shortpixel.php');
  	?>

    <div class='rta_success_box rta_hidden'>
        <div class='modal-close'><span class='dashicons dashicons-no ' >&nbsp;</span></div>
        <h3 class='header'>
          <?php printf(__('%s images regenerated!', 'regenerate-thumbnails-advanced'), '<span class="images_regenerated">0</span>');
          ?>
                  </h3>
        <p><?php printf(__('Regenerate Thumbnails Advanced is finished with your task. %s images regenerated and %s removed', 'regenerate-thumbnails-advanced'), '<span class="images_regenerated">0</span>', '<span class="images_removed">0</span>'); ?></p>


        <div class='shortpixel'>
          <?php if (! $spInstalled): ?>
          <h3 class="">
    				<a href="https://shortpixel.com/otp/af/TFXUHHC28044" target="_blank">
    					<?php echo esc_html__("Optimize your images with ShortPixel, get +50% credits!", 'regenerate-thumbnails-advanced'); ?>
    				</a>
    			</h3>
    			<div>
    				<a href="https://shortpixel.com/otp/af/TFXUHHC28044" target="_blank">
    					<img src="https://optimizingmattersblog.files.wordpress.com/2016/10/shortpixel.png">
    				</a>
    			</div>
    			<p>
    				<?php echo esc_html__("Get more Google love by compressing your website's images! Test how much ShortPixel can save your website and get +50% credits when you sign up as a Regenerate Thumbnails Advanced user! Forever!", 'regenerate-thumbnails-advanced'); ?>
    			</p>
    			<div><div>
    					<a class="button button-primary" id="shortpixel-image-optimiser-info" href="https://shortpixel.com/otp/af/TFXUHHC28044" target="_blank">
    						<?php echo esc_html__("More info", 'regenerate-thumbnails-advanced'); ?>
    					</a>
    				</div>
    			</div>
          <?php endif; ?>
          <?php if ($spInstalled && $spActive): ?>
           <p class='gotobulk'><?php printf(__('The thumbnails have been successfully regenerated. <strong>Go to the%s ShortPixel Bulk Processing page %s to optimize the updated thumbnails.</strong>', 'regenerate-thumbnails-advanced'), '<a href="' . admin_url('upload.php?page=wp-short-pixel-bulk') . '">', '</a>'); ?></p>
         <?php elseif($spInstalled):
           $path = 'shortpixel-image-optimiser/wp-shortpixel.php';
           $activate_url = wp_nonce_url(admin_url('plugins.php?action=activate&plugin='.$path), 'activate-plugin_'.$path);
           ?>
           <p class='gotobulk'><strong><?php printf(__('%s Activate ShortPixel %s to optimize your newly generated thumbnails.', 'regenerate-thumbnails-advanced'), '<a href="' . $activate_url . '">', '</a>'); ?></strong></p>
         <?php endif; ?>
        </div>
    </div>

  </div>  <!-- container -->

</section>
