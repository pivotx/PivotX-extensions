
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

The snippet takes a lot of optional parameters (which override the default values set in `Configuration`):

  * **folder** - the name of the subfolder (of the images folder) that the slideshow should be built from.
  * **width** - the width of (the images in) the slideshow. (0 is no width)
  * **height** - the height of (the images in) the slideshow.
  * **timeout** - the time (in milliseconds) between each image in the slideshow.
  * **animtime** - the time (in milliseconds) for the animation to take.
  * **limit** - the maximum number of images in the slideshow.
  * **orderby** - how the images are ordered in the slideshow. One of the
    following values: date\_asc, date\_desc, random, alphabet.
  * **popup** - the popup type used if the image is clicked. One of the
    following values: no, thickbox, fancybox.
  * **recursion** - whether images from either all subdirectories or just the leaf subdirectories should 
    be included in the slide show. One of the
    following values: no, leaf, all.
  * **nicenamewithdirs** - include directory names to the automatically generated image title.
  * **zc** - zoom crop value to be used when creating the thumbnails (see includes/timthumb.php for details).  
    Values from 0 to 3. This setting will not have much effect because of the fixed dimensions of the slideshow.
  * **css** - (`Configuration` only) file name of the css to use for the slideshow (defaults to slideshow).
  * **tooltip** - when displaying the buttons setting tooltip to true (1) shows special title display
  when hovering over button.
  * **ttopacity** - opacity value to use for the tooltip (between 0.1 and 1.0).
  * **uishow** - show the buttons together with the slideshow when set to true (1).
  * **uibefore** - positions the buttons before the slideshow when set to true (1).
  * **iptcindex** - index of image title in IPTC table (Picasa comments use '2#120').  
  Get an IPTC viewer (like IExif) to see the detailed IPTC information.  
  The index is sometimes translated to "tag" where 2:120 stands for 2#120.  
  Common IPTC indexes are (notice the zeroes in for example 2#005; values editable through for example Irfanview):  
  2#005 - Object name  
  2#025 - Keyword(s) (if you use this only the first keyword will be displayed)  
  2#105 - Headline  
  2#110 - Credit  
  2#115 - Source  
  2#116 - Copyright  
  2#120 - Caption  
  2#122 - Writer  
  If nothing is found for the index specified then the normal title approach will be used.
  * **iptcencoding** - iconv encoding name of image IPTC texts.
