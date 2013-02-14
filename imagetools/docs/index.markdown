
Image Tools tags
================

This extension consists of a few small tools to make it easier to handle images
in your templates.

The Findimages tag
-----------------

The `[[findimages]]` tag parses a given entry or page, and returns an array with
the images in that entry or page.

Example:

    [[ findimages var=imagelist source=$entry ]]
    
    [[ print_r var=$imagelist ]]

Note: the var attribute in `[[findimages]]` is a string literal, with the name of
the variable. The result is an actual variable, hence the '`$`' symbol. 

The Thumbnail tag
-----------------

This tag allows the easy insertion of a thumbnail in a template.

Examples:

    [[ thumbnail src=$entry.extrafields.image link=1 w=120 h=120  ]]

This tag makes a 120x120 thumbnail image of `$entry.extrafields.image`, and links
to the full size image.

    [[ thumbnail src=$imagelist.0 link=1 linkmaxsize=480 w=120 h=120 htmlwrap=1 ]]

This produces the same thumbnail image, but links to an image that's constrained
within 480x480 pixels. The htmlwrap attribute makes the link go to a small
HTML page, with the target image on it. On some mobile devices this prevents
unwanted scaling of the image on the display.

    [[ thumbnail src="my_file.jpg" title="My file!" link=1 linkclass=fancybox rel=fbgroup w=200 zc=3 ]]
    
This produces a thumbnail with a width of 200 and a height in correct relation with the
image (zc=3). The image links to Fancybox and belongs to the group fbgroup.

Available parameters are:

  - src - location of the image.
  - w - width of the thumbnail.
  - h - height of the thumbnail.
  - alt / title - title of the image and link (alt used if title is absent).
  - class - CSS class given to the image.
  - zc - zoom crop value to use (see explanation in includes/timthumb.php).
  - link - whether or not to link the thumbnail to the full size image. Omit if not needed.
  - id - id to be used for image or link.

Parameters available when link is active:

  - linkmaxsize - maximum width/height of the linked image.
  - htmlwrap - whether or not to wrap the linked image in a small HTML page (htmlwrapper also accepted; does not work for Fancybox).
  - linkclass - CSS class given to the link, wrapping the image. 
  - rel - add a `rel`-attribute to the thumbnail link, so it can be grouped if using Thickbox or Fancybox.
  - target - sets the target of the link. Defaults to `_blank`.

The Stripimages modifier
------------------------

This modifier strips all images from a specified source. This includes 
directly linked images, as well as `[[image]]` and `[[popup]]` tags.

Example:

    [[ introduction|stripimages ]]

