 
Facebook 'Like' Button
======================

This extension allows you to insert a Facebook 'Like' button on your pages and
entries. Using this button, your visitors can 'Like' your pages and entries,
without leaving your site.


Usage
-----

To use the button in it's simplest form, just add `[[facebook_like]]` in the
template of your entrypage, in a weblog entry, or on a page. This will insert
the default button, with the default options selected.

Several parameters are available to customize the button. For eample, to insert
a simpler button, use:

	[[ facebook_like layout="button_count" width="90" height="20" ]]

The available parameters are:

 - href - the URL to like. Usually you can leave this blank, because PivotX will
   insert the correct link to the current entry or page. 
 - layout - there are two options.
   - standard - displays social text to the right of the button and friends'
     profile photos below. Minimum width: 225 pixels. Default width: 450 pixels. 
     Height: 35 pixels (without photos) or 80 pixels (with photos).
   - button_count - displays the total number of likes to the right of the button.
     Minimum width: 90 pixels. Default width: 90 pixels. Height: 20 pixels.
 - show_faces - specifies whether to display profile photos below the button
   (standard layout only)
 - width - the width of the Like button.
 - height - the height of the Like button.
 - action - the verb to display on the button. Options: 'like', 'recommend'
 - font - the font to display in the button. Options: 'arial', 'lucida grande', 
   'segoe ui', 'tahoma', 'trebuchet ms', 'verdana'
 - colorscheme - the color scheme for the like button. Options: 'light', 'dark'
 
For more information about the Facebook 'Like' button, see the Facebook developers page
[here](http://developers.facebook.com/docs/reference/plugins/like).
 
 