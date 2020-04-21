<?php
namespace ReThumbAdvanced;
use \ReThumbAdvanced\ShortPixelLogger\ShortPixelLogger as Log;


class rtaImage
{
  protected $id;

  protected $is_image = true;
  protected $does_exist = true;
  protected $do_cleanup =false;
  protected $do_metacheck = false;

  protected $filePath;
  protected $fileUri;
  protected $fileDir;
  protected $metadata = array();

  protected $persistentMeta = array();
  protected $regeneratedSizes = array();

  protected $customThumbSuffixes =  array('_c', '_tl', '_tr', '_br', '_bl');

  public function __construct($image_id)
  {
      $this->id = $image_id;

      if (function_exists('wp_get_original_image_path')) // WP 5.3+
        $this->filePath = wp_get_original_image_path($image_id);
      else
        $this->filePath = get_attached_file($image_id);
      $this->fileDir = trailingslashit(pathinfo($this->filePath,  PATHINFO_DIRNAME));

      if (function_exists('wp_get_original_image_url')) // WP 5.3+
        $this->fileUri = wp_get_original_image_url($image_id);
      else
        $this->fileUri = wp_get_attachment_url($image_id);

      if (!file_exists($this->filePath))
        $this->does_exist = false;

      if (! file_is_displayable_image($this->filePath)) // this is based on getimagesize
          $this->is_image = false;

      $this->metadata = wp_get_attachment_metadata($image_id);

      $is_image_mime = wp_attachment_is('image', $image_id); // this is based on post mime.
      if (! $is_image_mime && $this->is_image )
      {
        $this->fixMimeType($image_id);
      }

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
           $thumbfile = $this->getDir() . $sizedata['file'];
           if (! file_exists($thumbfile))
           {
             Log::addDebug('Thumbfile not existing. Unsetting this size', array($size, $thumbfile, $this->id));
             unset($updated_meta['sizes'][$size]);
           }
        }
      }

      $result['update'] = wp_update_attachment_metadata($this->id, $updated_meta);
      $this->metadata = wp_get_attachment_metadata($this->id);

      if ($this->do_cleanup)
      {
        $result = $this->clean($result);
      }

      return $result;
  }

  /** This function tries to find related thumbnails to the current image. If there are not in metadata after our process, assume cleanup.
  * This removes thumbnail files.
  * See ShortPixel Image Optimiser's findThumbs method
  **
  **/
  public function clean()
  {
    $mainFile = $this->filePath;
    $exclude = array();

    if (isset($this->metadata['sizes']))
    {
      foreach($this->metadata['sizes'] as $size => $data)
      {
         $exclude[] = $data['file'];
      }
    }
    $result['excluding'] = $exclude;

    $ext = pathinfo($mainFile, PATHINFO_EXTENSION); // file extension
    $base = substr($mainFile, 0, strlen($mainFile) - strlen($ext) - 1);
    $pattern = '/' . preg_quote($base, '/') . '-\d+x\d+\.'. $ext .'/';
    $thumbsCandidates = @glob($base . "-*." . $ext);

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
                $pattern = '/' . preg_quote($base, '/') . '-\d+x\d+'. $suffix . '\.'. $ext .'/';
                foreach($thumbsCandidates as $th) {
                    if(preg_match($pattern, $th)) {
                        $thumbs[]= $th;
                    }
                }
            }
        }
    }

    $result['removed'] = array();

    foreach($thumbs as $thumb) {
        if($thumb === $mainFile)
        {
          continue;
        }
        if (in_array(basename($thumb), $exclude))
        {
          continue;
        }

        if($thumb !== $mainFile) {
          $status = @unlink($thumb);
          $result['removed'][] = $thumb . "($status)";
        }
    }

    return $result;
  }

  public function exists()
  {
    return $this->does_exist;
  }

  public function isImage()
  {
      return $this->is_image;
  }

  public function getUri()
  {
    return $this->fileUri;
  }

  public function getPath()
  {
    return $this->filePath;
  }

  public function getDir()
  {
    return $this->fileDir;
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

  public function setMetaCheck($bool)
  {
    $this->do_metacheck = $bool;
  }

  public function fixMimeType($image_id)
  {
      $post = get_post($image_id);

      if ($post->post_mime_type == '')
      {
        $mime = wp_get_image_mime($this->filePath);
        $post->post_mime_type = $mime;
        Log::addDebug('Fixing File Mime for ' . $this->filePath . ' new MIME - ' . $mime);
        wp_update_post($post);
      }
  }


}
