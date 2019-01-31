<script language="javascript">
    jQuery(document).ready(function($){
        var mediaUploader;
        $('.upload_media_button').unbind( 'click' );
        $('.upload_media_button').click(function(e) {
            var url_obj = $(this).attr("data-obj-url");
            var id_obj = $(this).attr("data-obj-id");
            e.preventDefault();
            // If the uploader object has already been created, reopen the dialog
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            // Extend the wp.media object
            mediaUploader = wp.media.frames.file_frame = wp.media({
                title: 'Choose Media',
                button: {text: 'Choose Media'}, 
                multiple: false 
            });

            // When a file is selected, grab the URL and set it as the text field's value
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#'+url_obj).val(attachment.url);
                $('#'+id_obj).val(attachment.id);
            });
            // Open the uploader dialog
            mediaUploader.open();
        });
    });
</script>