<div class="rta-admin-wrap rta-admin">
    <div class='two-panel-wrap'>
      <div class='rta-regenerate-wrap'>
        <h2><?php _e('Regenerate Thumbnails','regenerate-thumbnails-advanced'); ?></h2>
        <?php $view->loadChildTemplate('view_rta_regenerate'); ?>
      </div>
      <div class='rta-settings-wrap'>
        <h2><?php _e('Settings', 'regenerate-thumbnails-advanced'); ?></h2>
        <?php $view->loadChildTemplate('view_rta_settings'); ?>
      </div>
    </div>

    <?php $view->loadChildTemplate('view_regenerate_process'); ?>
</div> <!-- rta admin wrap. -->
