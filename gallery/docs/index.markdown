Gallery Extension Docs
======================

This extension enables you to add simple galleries to your entries or pages.

Usage
-----

**gallery snippet**

A typical example:

    <div class="gallery">
    [[gallery popup="thickbox"]]
      <a href='%imageurl%%filename%' class="thickbox" title="%title%" rel="gallery-%uid%" >
      <img src="%pivotxurl%includes/timthumb.php?src=%filename%&w=106&h=80&zc=1" alt="%alttext%" />
      </a>
    [[/gallery]]
    </div>

You can style the output in your CSS. A very basic example would be:

    div.gallery { margin: 0; padding: 0; }
    div.gallery img { margin: 2px; padding: 0; } 

If you have enabled the fancybox extension and want to use it instead, replace the two "thickbox" strings with "fancybox" in the example above.

You can limit the display of the thumbs (<img) by using parms imgbeg and imgend (the whole set will appear in the popup box; imgbeg = imgend is allowed):

    [[gallery popup="fancybox" imgbeg="1" imgend="3"]]

If you want the thumbnails to have the same dimensions as their original (e.g. square or vertical) use
the specthumbmax parameter and variables %thumbw% and %thumbh%. This specifies the maximum size to be used for width or height depending on its dimensions:

    <div class="gallery">
    [[gallery popup="thickbox" specthumbmax="150"]]
      <a href='%imageurl%%filename%' class="thickbox" title="%title%" rel="gallery-%uid%" >
      <img src="%pivotxurl%includes/timthumb.php?src=%filename%&w=%thumbw%&h=%thumbh%&zc=1" alt="%alttext%" />
      </a>
    [[/gallery]]
    </div>

The variables that are replaced are:

  * %title% - The title of the current image. 
  * %alttext% - The alt-text for the current image.
  * %filename% - The filename of the current image.
  * %nicefilename% - A 'print friendly' version of the filename of the current image.
  * %uid% - The unique ID of the current entry.
  * %imageurl% - The (absolute) URL to the base folder where the images are uploaded
  * %pivotxurl% - The (absolute) URL to the PivotX install
  * %count% - Outputs a counter for each of the images in the gallery
  * %even% - Outputs '1' if the counter is even, and '0' is de counter is odd.
  * %odd% - Outputs '1' if the counter is even, and '0' is de counter is odd.
  * %thumbw% - Calculated width; used when parm specthumbmax is used.
  * %thumbh% - Calculated height; used when parm specthumbmax is used.

**gallery_image snippet**

There is also a gallery_image template tag so you can use just one of the images in the gallery as a preview in the entry introduction for example. Typical usage is (if you want a thumbnail):

    <img src="[[pivotx_dir]]includes/timthumb.php?src=[[gallery_image]]&w=106&h=80&zc=1" />

Parameters for gallery_image is "number" - the position in the gallery starting from 0 (default) - and "attr" - the wanted attribute from the selected image; "src" (default), "title" or "alttext". In other words,

    [[gallery_image]]

is equivalent to

    [[gallery_image number=0 attr=src]]

**gallery_count**

Use [[gallery_count]] to display the number of items in the gallery

**gallery_imagelist**

The `[[gallery_imagelist]]` tag can be used to retrieve a list of the images in the gallery as an associative array. The 'var' attribute specifies the name of the variable that contains the image list.

    [[gallery_imagelist var="imagelist" ]]

    [[ print_r var=$imagelist ]]
    