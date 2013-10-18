
Google +1 button
================

This extension allows you to insert a Google +1 button on your pages and
entries. Using this button, your visitors can '+1' your pages
and entries, without leaving your site.


Usage
-----

To use the button in its simplest form, just add `[[plusonebutton]]` in the
template of your entrypage, in a weblog entry or on a page. This will insert
the default button, with the default options selected.

Several parameters are available to customize the button. For example, to insert
a smaller button, use:

    [[ plusonebutton size=small ]]

The available parameters are:

 - link - the URL to like. Usually you can leave this blank, because PivotX will
   insert the correct link to the current entry or page. If you do specify it then make sure
   it's a full URL (incl. http).
 - size - there are four options for differently sized buttons: `small`, `medium`, 
   `standard` or `tall`. Default is tall.
 - count - deprecated -- use annotation.
 - annotation - specifies where you want additional information to be displayed. 
   Possible values: `none`, `inline`, `bubble`. Default is none. When size is tall then default is bubble.
   (Previous value of ballon is still supported and replaced by bubble)
 - bubble - specifies where the mouse-over bubble will be displayed. 
   Possible values: `top`, `bottom`, `left`, `right`. Default is bottom.
 - recommend - show recommendations in mouse-over bubble. Values: `true` or `false`. Default false.
 - width - width of the display. Default is 450 (for inline), 120 (for ballon) or not used (for none). Minimum 120 (if specified).
 - align - alignment of the display. Values: `left` or `right`. Default left.
 - lang - set this to the desired language 
   [valid codes](http://code.google.com/intl/nl/apis/+1button/#languages).
   When not set PivotX setting will be used.

For more information about the Google +1 button, see page
[here](http://code.google.com/apis/+1button/).