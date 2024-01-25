<?php
namespace ReThumbAdvanced;
use \ReThumbAdvanced\ShortPixelLogger\ShortPixelLogger as Log;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}


class Image extends \ReThumbAdvanced\FileSystem\Model\File\FileModel
{
  protected $id;

  protected $is_image = true;
  protected $does_exist = true;
  protected $do_cleanup = false;
  protected $do_metacheck = false;
  protected $remove_imagetypes = false;

  protected $metadata = array();

  protected $persistentMeta = array();
  protected $regeneratedSizes = array();
  protected $sizesToRemove = array();


  protected $customThumbSuffixes =  array('_c', '_tl', '_tr', '_br', '_bl');

  protected $processable_status;

  protected $images_created = 0; // created during process /regenerated
  protected $images_removed = 0; // removed during cleanup

  const P_PROCESSABLE = 0;
  const P_FILE_NOT_EXIST  = 1;
  const P_FILE_NOTWRITABLE = 6;
  const P_DIRECTORY_NOTWRITABLE = 10;
  const P_NOTDISPLAYABLE = 11;
  const P_ISVIRTUAL = 12;


  public function __construct($image_id)
  {
      $this->id = $image_id;
      $fs = RTA()->fs();

      if (function_exists('wp_get_original_image_path')) // WP 5.3+
      {
        $filePath = wp_get_original_image_path($image_id);

        /** When this function returns false it's possible the post_mime_type in wp_posts table got corrupted. If the file is displayable image,
        * attempt to fix this issue, then reget the item for further processing */
        if ($filePath === false)
        {
          $filePath = get_attached_file($image_id);
					if ($filePath === false)
					{
						RTA()->ajax()->add_status('file_missing', array('name' => basename($image_id), 'image_id' => $image_id) );
            $this->processable_status = self::P_FILE_NOT_EXIST;
						return false;
					}

          if (file_is_displayable_image($filePath))
          {
            $this->fixMimeType($image_id);
            $filePath = wp_get_original_image_path($image_id);
          }

        }
      }
      else
      {
        $filePath = get_attached_file($image_id);
      }

      parent::__construct($filePath);

      // If Pdf, check for PDF thumbnail main file.
      if ('pdf' == $this->getExtension())
      {
          $size = image_get_intermediate_size($image_id, 'full');
          if (false !== $size)
          {
             $pdfPath = $this->getFileDir() . $size['file'];
             $fs = RTA()->fs();

             $fileObj = $fs->getFile($pdfPath);

             if ($fileObj->exists())
             {
                $filePath = $fileObj->getFullPath();
                parent::__construct($filePath);
             }
          }
      }

      if (false === $this->exists())
      {
        $this->processable_status = self::P_FILE_NOT_EXIST;
        $this->does_exist = false;
      }

      if ( $this->is_virtual())
      {
          $this->processable_status = self::P_ISVIRTUAL;
          $this->is_image = false;
      }
      elseif (false === $this->exists() || false === file_is_displayable_image($this->getFullPath())) // this is based on getimagesize
			{
          $this->processable_status = self::P_NOTDISPLAYABLE;
          $this->is_image = false;
			}

      $this->metadata = wp_get_attachment_metadata($image_id);

  }

  public function isProcessable()
  {
       if ( ! $this->exists()  || (! $this->is_virtual() && ! $this->is_directory_writable() ) || false === $this->isImage() )
       {
          return false;
       }

       return true;
  }

  public function is_directory_writable()
  {
      $bool = parent::is_directory_writable();

      if (false === $bool)
      {
         $this->processable_status = self::P_DIRECTORY_NOTWRITABLE;
      }

      return $bool;

  }

  public function process()
  {
    if (RTA()->process()->doRemoveThumbnails())
    {
      $this->setCleanUp(true);
      // Might be it's own setting
      $this->setRemoveImageTypes(apply_filters('rta/image/clean_imagetypes', true));

      Log::addDebug('Image thumbnails will be cleaned');
    }

    if(RTA()->process()->doDeleteLeftMeta() && ! $this->exists() )  {
				$post = get_post($this->id);

				// Ugly exception for a plugin that doesn't play by the rules
				// @todo add a mechanism so we can cater for more bad plugins here.
				if ($post->post_mime_type == 'video/videopress')
				{
					return false;
				}
        Log::addDebug('Deleting post ' . $this->id);
        wp_delete_post($this->id, true);

    }

    if ($this->isProcessable() ) {

        @set_time_limit(900);
        do_action('shortpixel-thumbnails-before-regenerate', $this->id);

        //use the original main image if exists
        $backup = apply_filters('rta/get_backup', $this->getFullPath(), $this->id);
        if($backup && $backup !== $this->getFullPath()) {
            Log::addDebug('Retrieving SPIO backups for process');
            $fs = RTA()->fs();
            $backupObj = $fs->getFile($backup);

            $targetObj = $fs->getFile($backup . "_optimized_" . $this->id);
            $this->copy($targetObj);
            $backupObj->copy($this);
            //copy($this->getFullPath(), $backup . "_optimized_" . $this->id);
            //copy($backup, $this->getFullPath());
        }

        add_filter('intermediate_image_sizes_advanced', array($this, 'capture_generate_sizes'));
        // RTA should never touch source files. This happens when redoing scaling. This would also be problematic in combination with optimisers. Disable scaling when doing thumbs.
        add_filter('big_image_size_threshold', array($this, 'disable_scaling'));

        $new_metadata = wp_generate_attachment_metadata($this->id, $this->getFullPath());

        remove_filter('intermediate_image_sizes_advanced', array($this, 'capture_generate_sizes'));
        remove_filter('big_image_size_threshold', array($this, 'disable_scaling'));

        Log::addDebug('New Attachment metadata generated', $new_metadata);

        //restore the optimized main image
        if($backup && $backup !== $this->getFullPath()) {
            $targetObj->copy($this);
            $targetObj->delete();
        }

        //get the attachment name
        if (is_wp_error($new_metadata)) {

          RTA()->ajax()->add_status('error_metadata', array('name' => basename($this->getFullPath()) ));
        }
        else if (empty($new_metadata)) {
            Log::addDebug('File missing - New metadata returned empty', array($new_metadata, $this->getFileUri(),$this->getFullPath() ));
            RTA()->ajax()->add_status('file_missing', array('name' => basename($this->getFileName()) ));
        } else {

            // going for the save.
            $original_meta = $this->getMetaData();
            $result = $this->saveNewMeta($new_metadata); // this here calls the regeneration.
            Log::addDebug('Result :', $result);

            $is_a_bulk = false; // not a bulk in the SPIO sense ( directly optimize )
            $regenSizes = isset($new_metadata['sizes']) ? $new_metadata['sizes'] : array();

            // Do not send if nothing was regenerated, otherwise SP thinks all needs to be redone
            if (count($regenSizes) > 0)
            {
              $ext = $this->getExtension();
              if ($ext !== 'webp' && $ext !== 'avif')
              {
                do_action('shortpixel-thumbnails-regenerated', $this->id, $original_meta, $regenSizes, $is_a_bulk);
                do_action('rta/image/thumbnails_regenerated', $this->id, $regenSizes);
              }
            }
            $last_success_url = $this->getFileUri();

        }

        RTA()->ajax()->add_status('regenerate_success',
                array('image' => $last_success_url,
                'count' => count($regenSizes),
                'removed' => $this->images_removed,
                'name' => $this->getFileName(),
            ));

    } else {

          $debug_filename = (strlen($this->getFileUri()) > 0) ? $this->getFileUri() : $this->getFullPath();
          if (false === $this->does_exist) // Existing files, not image, can be attachments, zipfiles, pdf etc. Fail silently.
          {
            $mime = get_post_mime_type($this->id);
            if (strpos($mime, 'image') !== false)
            {
              RTA()->ajax()->add_status('not_image', array('name' => $debug_filename));
            }
          }
          else
          {
            if ($this->is_virtual())
            {
              Log::addDebug('File virtual', array($this->getFullPath(), $this->id) );
              RTA()->ajax()->add_status('is_virtual', array('name' => basename($debug_filename)));
            }
            else {
              Log::addDebug('File missing - Current Image reported as not an image', array($this->getFullPath(), $this->id) );
              RTA()->ajax()->add_status('file_missing', array('name' => basename($debug_filename)) );
            }
          }

          return false;
    }

    return true;
  }

  // Todo before doing this, function to remove thumbnails need to run somehow, without killing all.
  public function saveNewMeta($updated_meta)
  {
      if (count($this->persistentMeta) > 0)
      {
        foreach($this->persistentMeta as $rsize => $add)
        {
          $updated_meta['sizes'][$rsize] = $add;
        }
      }

      /* Retain in metadata main categories, if they are not set in the new metadata.
      *  This is for custom data that may be set by others, but will be removed upon regen.
      *  Of the main categories (sizes, width, file etc ) they are fixed format, so should always be present, regardless of content.
      */
      foreach($this->metadata as $key => $data)
      {
        if (! isset($updated_meta[$key]))
        {
          $updated_meta[$key] = $data;
        }
      }

      $result = array();

      if ($this->do_metacheck && isset($updated_meta['sizes']))
      {
        Log::addDebug('Do metaCheck now for ' . $this->id);
        foreach($updated_meta['sizes'] as $size => $sizedata)
        {
           $thumbfile = $this->getFileDir() . $sizedata['file'];
           if (! file_exists($thumbfile))
           {
             Log::addDebug('Thumbfile not existing. Unsetting this size', array($size, $thumbfile, $this->id));
             unset($updated_meta['sizes'][$size]);
           }
        }
      }

      $result['update'] = wp_update_attachment_metadata($this->id, $updated_meta);
      $this->metadata = wp_get_attachment_metadata($this->id);

      if (true === $this->do_cleanup)
      {
        $result = $this->clean($result);
      }

      return $result;
  }

  public function disable_scaling()
  {
     return false;
  }

  public function capture_generate_sizes($full_sizes)
  {

      $do_regenerate_sizes = RTA()->admin()->getOption('process_image_sizes'); // $this->viewControl->process_image_sizes; // to images to be regenerated.
      $process_options = RTA()->admin()->getOption('process_image_options'); // $this->viewControl->process_image_options; // the setting options for each size.

      // imageMetaSizes is sizeName => Data based array of WP metadata.
      $imageMetaSizes = $this->getCurrentSizes();
      $fs = RTA()->fs();

      $prevent_regen = array();
      foreach($do_regenerate_sizes as $rsize)
      {
        // 1. Check if size exists, if not, needs generation anyhow.
        if (! isset($imageMetaSizes[$rsize]))
        {
          Log::addDebug("Image Meta size setting missing - $rsize ");
          continue;
        }

        // 2. Check meta info (file) from the current meta info we have.
        $metaSize = $imageMetaSizes[$rsize];
        $overwrite = isset($process_options[$rsize]['overwrite_files']) ? $process_options[$rsize]['overwrite_files'] : false; // 3. Check if we keep or overwrite.

         if (! $overwrite)
         {
          // thumbFile is RELATIVE. So find dir via main image.
           $thumbFile = $fs->getFile($this->getFileDir() . $metaSize['file']);
           //Log::addDebug('Preventing overwrite of - ' . $thumbFile);
           if ($thumbFile->exists()) // 4. Check if file is really there
           {
              $prevent_regen[] = $rsize;
              // Add to current Image the metaSize since it will be dropped by the metadata redoing.
              Log::addDebug('File exists on ' . $rsize . ' ' . $thumbFile . '  - skipping regen - prevent overwrite');
              $this->addPersistentMeta($rsize, $metaSize);
           }
         }
      }

      // 5. Drop the 'not to be' regen. images from the sizes so it will not process.
      $do_regenerate_sizes = array_diff($do_regenerate_sizes, $prevent_regen);
      Log::addDebug('Sizes going for regen amount : ' . count($do_regenerate_sizes) );


      /* 6. If metadata should be cleansed of undefined sizes, remove them from the imageMetaSizes
      *   This is for sizes that are -undefined- in total by system sizes.
      */
      if (RTA()->process()->doCleanMetadata())
      {
          $system_sizes = RTA()->admin()->getOption('system_image_sizes'); //$this->viewControl->system_image_sizes;

          $not_in_system = array_diff( array_keys($imageMetaSizes), array_keys($system_sizes) );
          if (count($not_in_system) > 0)
            Log::addDebug('Cleaning not in system', $not_in_system);

          foreach($not_in_system as $index => $unset)
          {
            unset($imageMetaSizes[$unset]);
          }
      }

      // 7. If unused thumbnails are not set for delete, keep the metadata intact.
      $other_meta = array_diff( array_keys($imageMetaSizes), $do_regenerate_sizes, $prevent_regen);

      if (false === RTA()->process()->doRemoveThumbnails() )
      {
        if (count($other_meta) > 0)
        {
          Log::addDebug('Image sizes not selected, but not up for deletion', $other_meta);
        }

        foreach($other_meta as $size)
        {
           if (isset($imageMetaSizes[$size]))
             $this->addPersistentMeta($size, $imageMetaSizes[$size]);
        }
      }
      elseif (true === RTA()->process()->doRemoveThumbnails()) {
        // @todo Here add something to trigger thumbsizes later when deleting them for SPIO integration
        foreach($other_meta as $size)
        {
           if (isset($imageMetaSizes[$size]))
           {
              $this->sizesToRemove[$size] = $imageMetaSizes[$size];
           }
        }

      }

      $returned_sizes = array();
      foreach($full_sizes as $key => $data)
      {
          if (in_array($key, $do_regenerate_sizes))
          {
            $returned_sizes[$key] = $data;
          }
      }

      $this->setRegeneratedSizes($do_regenerate_sizes);
      return $returned_sizes;
  }

  public function getFileUri()
  {
    if (function_exists('wp_get_original_image_url')) // WP 5.3+
      $fileUri = wp_get_original_image_url($this->id);
    else
      $fileUri = wp_get_attachment_url($this->id);

    return $fileUri;
  }

  /** This function tries to find related thumbnails to the current image. If there are not in metadata after our process, assume cleanup.
  * This removes thumbnail files.
  * See ShortPixel Image Optimiser's findThumbs method
  **
  **/
  protected function clean($result)
  {
    $exclude = array();
    $fs = RTA()->fs();

    if (isset($this->metadata['sizes']))
    {
      foreach($this->metadata['sizes'] as $size => $data)
      {
         $exclude[] = $data['file'];
      }
    }
    $result['excluding'] = $exclude;

    $extension = $this->getExtension();

//    $ext = pathinfo($mainFile, PATHINFO_EXTENSION); // file extension
    $base = (string) $this->getFileDir() . $this->getFileBase();
  //  $base = substr($mainFile, 0, strlen($mainFile) - strlen($ext) - 1);
    $pattern = '/' . preg_quote($base, '/') . '-\d+x\d+\.'. $extension .'/';
    $thumbsCandidates = @glob($base . "-*." . $extension);

    $thumbs = array();
    if(is_array($thumbsCandidates)) {
        foreach($thumbsCandidates as $th) {
            if(preg_match($pattern, $th)) {
                $thumbs[]= $th;
            }
        }
        if( count($this->customThumbSuffixes)
           && !(   is_plugin_active('envira-gallery/envira-gallery.php')
                || is_plugin_active('soliloquy/soliloquy.php')
                || is_plugin_active('soliloquy-lite/soliloquy-lite.php'))){
            foreach ($this->customThumbSuffixes as $suffix){
                $pattern = '/' . preg_quote($base, '/') . '-\d+x\d+'. $suffix . '\.'. $extension .'/';
                foreach($thumbsCandidates as $th) {
                    if(preg_match($pattern, $th)) {
                        $thumbs[]= $th;
                    }
                }
            }
        }
    }

    $result['removed'] = array();
  //  $deleted_thumbs = array();

    foreach($thumbs as $thumb) {

        $thumbObj = $fs->getFile($thumb);

        if($thumbObj->getFullPath() === $this->getFullPath())
        {
          continue;
        }
        if (in_array($thumbObj->getFileName(), $exclude))
        {
          continue;
        }

        if($thumbObj->getFullPath() !== $this->getFullPath()) {

          if (true === $this->remove_imagetypes)
          {
             $webpCheck = $fs->getFile($thumbObj->getFileDir() . $thumbObj->getFileBase() . '.webp');
             if ($webpCheck->exists())
             {
                $webpCheck->delete();
                $result['removed'][] = $webpCheck->getFullPath();
             }
             $avifCheck = $fs->getFile($thumbObj->getFileDir() . $thumbObj->getFileBase() . '.avif');
             if ($avifCheck->exists())
             {
               $result['removed'][] = $avif->getFullPath();
                $avifCheck->delete();
             }
          }

          $status = $thumbObj->delete();
          $result['removed'][] = $thumbObj->getFullPath() . " ($status)";
        }
    }

    do_action('rta/image/thumbnails_removed', $this->id, $this->sizesToRemove);

    $this->images_removed = count($result['removed']);

    return $result;
  }


  public function isImage()
  {
      return $this->is_image;
  }


  public function getMetaData()
  {
    return $this->metadata;
  }

  public function getCurrentSizes()
  {
    return (isset($this->metadata['sizes'])) ? $this->metadata['sizes'] : array();
  }

  public function addPersistentMeta($size, $data)
  {
      $this->persistentMeta[$size] = $data;
  }

  public function setRegeneratedSizes($sizes)
  {
    $this->regeneratedSizes = $sizes;
  }

  public function setCleanUp($clean)
  {
    $this->do_cleanup = $clean;
  }

  public function setRemoveImageTypes($bool)
  {
     $this->remove_imagetypes = $bool;
  }

  public function setMetaCheck($bool)
  {
    $this->do_metacheck = $bool;
  }

  public function fixMimeType($image_id)
  {
      $post = get_post($image_id);

      if ($post->post_mime_type == '')
      {
        $mime = wp_get_image_mime($this->getFullPath());
        $post->post_mime_type = $mime;
        Log::addDebug('Fixing File Mime for ' . $this->getFullPath() . ' new MIME - ' . $mime);
        wp_update_post($post);
      }
  }

  // Stolen from SPIO
  public function getProcessableReason($status = null)
  {
    $message = false;
    $status = (! is_null($status)) ? $status : $this->processable_status;

    switch($status)
    {
       case self::P_PROCESSABLE:
          $message = __('Image Ok', 'regenerate-thumbnails-advanced');
       break;
       case self::P_FILE_NOT_EXIST:
          $message = __('File does not exist', 'regenerate-thumbnails-advanced');
       break;
       case self::P_FILE_NOTWRITABLE:
          $message = sprintf(__('Image %s is not writable in %s', 'regenerate-thumbnails-advanced'), $this->getFileName(), (string) $this->getFileDir());
       break;
       case self::P_DIRECTORY_NOTWRITABLE:
          $message = sprintf(__('Image directory %s is not writable', 'regenerate-thumbnails-advanced'), (string) $this->getFileDir());
       break;
       case self::P_NOTDISPLAYABLE:
          $message = sprintf(__('Image  %s is not displayable', 'regenerate-thumbnails-advanced'), (string) $this->getFileName());
       break;
       case self::P_ISVIRTUAL:
       $message = sprintf(__('Image  %s is virtual', 'regenerate-thumbnails-advanced'), (string) $this->getFileName());
       break;
       // Restorable Reasons
       default:
          $message = __(sprintf('Unknown Issue, Code %s',  $this->processable_status), 'regenerate-thumbnails-advanced');
       break;
    }

    return $message;
  }



} // Image Class
