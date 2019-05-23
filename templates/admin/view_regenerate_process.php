
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
    </div>

    <a href="javascript:void(0);" class="rta_error_link rta_hidden"><?php _e('There were some errors, click for details', 'regenerate-thumbnails-advanced'); ?></a>
    <div class="listContainer rta_error_box row rta_hidden">
        <div class="statuslist col-sm-6">
            <h4 class="listTitle"><?php _e('Error(s)','regenerate-thumbnails-advanced'); ?></h4>
            <ul class="list-group">
            </ul>
        </div>
    </div>
  </div>  <!-- container -->
</section>
