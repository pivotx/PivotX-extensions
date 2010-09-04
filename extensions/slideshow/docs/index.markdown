
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

The snippet takes seven optional parameters (which override the default values):

  * **folder** - the name of the subfolder (of the images folder) that the slideshow should be built from.
  * **width** - the width of (the images in) the slide show.
  * **height** - the height of (the images in) the slide show..
  * **timeout** - the time (in milliseconds) between each image in the slideshow.
  * **limit** - the maximum number of images in the slideshow.
  * **orderby** - how the images are ordered in the slideshow. One of the
    following values: `date_asc`, `date_desc`, `random`, `alphabet`.
  * **popup** - the popup type used if the image is clicked. One of the
    following values: no, thickbox, fancybox.

