=== reGenerate Thumbnails Advanced ===
Contributors: ShortPixel
Donate link: https://www.paypal.me/resizeImage
Tags: regenerate, thumbnail, thumbnails, thumb, thumbs, easy, media, force regenerate, image, images, pics, date
Requires at least: 4.0
Tested up to: 5.2
Stable tag: 2.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Regenerate thumbnails fast and easy while removing unused thumbnails of existing images; very useful when changing a theme.

== Description ==

**A free, fast and easy to use regenerate thumbnails plugin supported by ShortPixel :)**

Regenerate Thumbnails Advanced (RTA) plugin comes in handy when you install a new WordPress theme on your site that has different image sizes. While the newly uploaded images will be cropped and resized to fit your new theme, the old thumbnails will remain unchanged. Using RTA you can regenerate the thumbnails for all your existing images.
It is also very useful when you have many not-used-anymore thumbnails and you want to remove them while making sure you have all the needed thumbnails generated.

Features:

* Choose the quality of the regenerated thumbs.
* Option to remove not-used-anymore thumbnails.
* Remove metadata for missing images and thumbnails
* Fully integrated with <a href="https://wordpress.org/plugins/shortpixel-image-optimiser/">ShortPixel Image Optimizer</a>.
* Option to select the thumbnail size for featured and non-featured images.
* Option to easily add as many extra thumbnail sizes as you wish.
* Interval selection (last day, week, month, all) for the thumbs that will be regenerated.
* Clean and simple interface where you have a progress bar showing you the percentage of images regenerated.
* Resume function - the regeneration process will continue from where it was left in case you accidentally close the processing tab in your browser.

Support:
For support please contact us <a href="https://shortpixel.com/contact">here</a> making sure you mention RTA plugin.


== Installation ==

Nothing special about the installation process, just visit "Plugins" section in your /wp-admin, search for "regenerate thumbnails advanced", install & activate the plugin and then from Settings section you can start using it.

== Frequently Asked Questions ==

= Can I regenerate just a few images =

You have the option to select from: all, past day, past week, past month

= What happens if I close the page while the regeneration process is running? =

The script stops but it will resume after you open the settings page of the plugin once again.

== Screenshots ==

1. Plugin settings page
2. Thumbnail regeneration process in action

== Changelog ==

= 2.1.0 =

Release date: 7th June 2019
* Replace the two options Exact size for featured/non-featured images with one checkbox Only featured
* Button to stop the regeneration
* Make Keep existing be checked by default
* Change "Regenerate selected thumbnails" checklist to a settings selection
* ShortPixel Image Optimizer integration - call the 'shortpixel-thumbnails-regenerated' action passing only the changed sizes
* Fix: Security - Image sizes XSS exploit
* Fix: count(): Parameter must be an array

= 2.0.1 =

Release date: 17th April 2019
* Fix warnings related to corrupted metadata in some cases

= 2.0.0 =

Release date: 16th April 2019
* Plugin completely rewritten with additional features added:
* Add custom thumbnails
* Select which thumbnails to regenerate
* Regenerate thumbnails only for the featured images
* Rewrite the existing thumbnails or not depending on the selected options
* Integrate seamlessly with <a href="https://wordpress.org/plugins/shortpixel-image-optimiser/">ShortPixel Image Optimizer</a>
* Delete unused thumbnails from disk
* Remove metadata for missing images and thumbnails

= EARLIER VERSIONS =
* please refer to the changelog.txt file inside the plugin archive for the versions before the 2.0.0 full rewrite.
