
Fancybox Extension
==================

A snippet to replace the built-in thickbox with Fancybox popups. See
<http://www.fancybox.net/> for more details.

Usage
-----

After enabling the extension you can use the 'PivotX Popup' button in the
editor, to insert image popups, or insert the code by hand.

_You can set the options for Fancybox action itself through a config option 
(explained below)._

__Examples:__

    [[popup file="2010-01/image_name.jpg" description="(thumbnail)" alt="The name of the image"]]
  

Grouping of images using the optional __`rel_id`__ parameter (only for images):  
(popups can be viewed in sequence by clicking the arrows or using mousescroll)

    [[popup file="2010-01/image1.jpg" description="(thumbnail)" rel_id="group1"]]
    [[popup file="2010-01/image2.jpg" description="(thumbnail)" rel_id="group1"]]
    [[popup file="2010-01/image3.jpg" description="(thumbnail)" rel_id="group1"]]

You can use this extension also to insert popups of a __YouTube__ video: 
(notice the difference between url/movid parms!) 

    [[popup description="Rammstein!" fb_type="youtube" movid="WNcQ5VE1vWI"]]
    [[popup description="Epica!" fb_type="youtube" url="http://www.youtube.com/v/jVkJkcvaA1A"]]

Or show a __Vimeo__ video in a popup: 
(also notice the difference between url/movid parms!)

    [[popup description="Look at me!" fb_type="vimeo" movid="10857606"]]
    [[popup description="Insert coin" fb_type="vimeo" url="http://www.vimeo.com/moogaloop.swf?clip_id=6566857"]]
    [[popup description="Sonar" fb_type="vimeo" url="http://www.vimeo.com/5324878"]]

If a thumbnail was registered together with Youtube or Vimeo it will be displayed.

Or create textarea popups with a special piece of __Text__:
(do not use % for width/height; file location is [pivotx_path]/docs/)
    
    [[popup description="A text" title="Sample text" fb_type="text" objwidth="300px" objheight="200px" 
      text="This is a text that should contain enough characters to show what can be done by inserting
      a lot of text and using Fancybox to show it. Did this text succeed in that?"]]

    [[popup description="A filetext" title="Textfile" fb_type="text" objwidth="800px" objheight="400px" 
      text="file:FBsample.txt" txtcls="Myclass" txtcol="yellow" txtcolbg="black"]]
    
Or create an __Iframe__ to open up a weblink:

    [[popup description="A webwindow" title="Google" fb_type="iframe" url="http://www.google.com"]]

    [[popup description="Own window" title="My page" fb_type="iframe" url="pivotx/docs/FBsample.php"]]

Or popup a __SWF/Flash__:

    [[popup description="SWF in a window" title="Adobe swf" fb_type="flash" 
      url="http://www.adobe.com/jp/events/cs3_web_edition_tour/swfs/perform.swf"]]

Types "text", "iframe" and "flash" can also be shown with a thumbnail.
If the thumbnail does not exist yet it will be created.  
To do this use parms description and file just like when creating an imagepopup:

    [[popup description="(thumbnail)" title="Thumb and Text" fb_type="text" 
      text="file:FBsample.txt" file="2010-01/image1.jpg" width="200px" height="150px"]]

Parameters
----------

All sorts of additional parameters are at your disposal. For detailed information
read `snippet_fancybox.php`.  

  * **file** - specify a file that exists in `[upload_base_path]` can be used with 
  all types except 'youtube' and 'vimeo'
  * **description** - specify a string to be used as text for the pop-up or use 
  (thumbnail) to use/create the thumbnail for the specified file;
  instead of (thumbnail) you can also just specify a filename directly (perhaps of another thumbnail) 
  * **alt** - string used for title on `imagelink` if title is not specified
  * **title** - string used for title on `imagelink` (title="null" results in no title)
  * **align** - used for alignment; regular values (use `inline` for no alignment)
  * **rel_id** - specify your own grouping id (will only work for images)
  * **fb\_type** - specify your fancyboxtype: `image` (default) / `youtube` / `vimeo`
   / `text` / `iframe` / `flash`
  * **width** - regular approach; usage of % will possibly result in peculiar displays 
  * **height** - see width; both width and height are used for the thumbnail to be displayed
  * **specthumbmax** - max. size to be used as either width or height depending on dimensions of **file**
(do not use width/height together with this parameter)
  * **objwidth** - regular approach; usage of % will possibly result in peculiar displays
  * **objheight** - see objwidth; both objwidth and objheight are used for the object to be displayed (only youtube, vimeo and text)
  * **url** - specify url for iframe or flash; can also be used for youtube or 
  vimeo (movid is recommended)
  * **movid** - the movid for the YouTube (the string behind the `v=`) or Vimeo 
  video (the string behind `clip_id=`)
  * **text** - either type the whole text you want to be displayed or use the 
  structure `file:`_filename_`.txt`
  * **txtcls** - classname to be used on the textarea
  * **txtcol** - color of text in the textarea
  * **txtcolbg** - backgroundcolor of textarea

Config options
--------------

Use config option 'fancybox\_profile' to choose between different setups for 
Fancybox. Add this option to your configuration to enable it. The option will 
only have effect on the display of your images. As of this version you can also 
scroll through your grouped images by using mousescroll!  

Currently these values exist:  

  * **1** - (default) Title over image / Transition fade / Close button visible
  * **2** - Title outside image / Transition elastic / Different zoomspeed / Close button visible / Cyclic display
  * **3** - Title over image with "Image n/n" in front / Transition fade / No close button / Cyclic display / No padding
  * **4** - Defaults according to fancybox site (see <http://fancybox.net/api>)

If you want additional profiles to be created, please let us know!

Warning!
--------

If you didn't install PivotX directly under the root directory (public_html) 
then you'll have to edit the fancybox css file (IE version) to make it work 
correctly in Internet Explorer. At the end of this file several src lines are 
coded to point to the different png files needed for display.

For example:

    src='/pivotx/extensions/fancybox/fancy_close.png' 

has to be changed to:

    src='/path-to-your-site/pivotx/extensions/fancybox/fancy_close.png'