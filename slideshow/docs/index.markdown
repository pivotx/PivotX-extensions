
Slideshow Extension Docs
========================

This extension enables you to easily display a slideshow on your site. It can
be used either as a widget or as a snippet/template tag in your
entries/pages/templates. The slideshow is build from images in a specified
folder.

Usage
-----

Before starting to use this extension (but after enabling), you should visit
the `Slideshow` tab on the `Configuration` and set the default values for your
slideshows.

Snippet syntax
--------------

The minimal usage is:

    [[ slideshow ]]

The snippet takes a lot of optional parameters (which override the default values):

  * **folder** - the name of the subfolder (of the images folder) that the slideshow should be built from.
  * **width** - the width of (the images in) the slideshow.
  * **height** - the height of (the images in) the slideshow.
  * **timeout** - the time (in milliseconds) between each image in the slideshow.
  * **limit** - the maximum number of images in the slideshow.
  * **orderby** - how the images are ordered in the slideshow. One of the
    following values: date\_asc, date\_desc, random, alphabet.
  * **popup** - the popup type used if the image is clicked. One of the
    following values: no, thickbox, fancybox.
  * **recursion** - whether images from either all subdirectories or just the leaf subdirectories should 
    be included in the slide show. One of the
    following values: no, leaf, all.
  * **nicenamewithdirs** - include directory names to the automatically generated image title.
  * **zc** - zoom crop value to be used when creating the thumbnails (see includes/timthumb.php for details);
    overrules the setting of timthumb_zc. Values from 0 to 3.
  * **css** - filename of the css to use for the slideshow (defaults to slideshow).
  * **tooltip** - when displaying the buttons (see css samples) setting tooltip to true (1) shows title
  when hovering over button.
  * **ttopacity** - opacity value to use for the tooltip (between 0.1 and 1.0).
  * **uibefore** - positions the buttons before the slideshow when set to true (1).
  * **iptcindex** - index of image title in IPTC table (Picasa coments use '2#120'). 
  * **iptcencoding** - iconv encoding name of image IPTC texts.
