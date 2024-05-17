<?php
namespace ReThumbAdvanced;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

?>

<div class='wrap'> <!-- this wrap to keep notices and other scum out of the interface -->
  <h1 class="rta-title"><?php echo $this->pageTitle ?>

  </h1>

<div class="rta-admin-wrap rta-admin wrap">

    <div class='menu'>
      <a href="#dashboard-panel" ><?php _e('Dashboard', 'regenerate-thumbnails-advanced'); ?></a>
      <a href="#license-panel"><?php _e('License', 'regenerate-thumbnails-advanced'); ?></a>
    </div>

    <div class='two-panel-wrap settings-panels is_active is_panel' id="dashboard-panel">
      <div class='rta-regenerate-wrap'>
        <h2><?php _e('Regenerate Options', 'regenerate-thumbnails-advanced') ?></h2>
        <?php $this->loadChildTemplate('view_rta_regenerate'); ?>
      </div>
      <div class='rta-settings-wrap'>
        <h2><?php _e('Settings', 'regenerate-thumbnails-advanced'); ?></h2>
        <?php $this->loadChildTemplate('view_rta_settings'); ?>

      </div>
    </div>

    <div class="one-panel-wrap license-panels is_panel" id="license-panel">
       <div class='rta-license-wrap'>
         <h2><?php _e('License Management', 'regenerate-thumbnails-advanced'); ?></h2>
        <?php  $this->loadChildTemplate('view_rta_license'); ?>
       </div>
    </div>

    <?php $this->loadChildTemplate('view_ad');  ?>
    <?php $this->loadChildTemplate('view_regenerate_process'); ?>
</div> <!-- rta admin wrap. -->

</div>
