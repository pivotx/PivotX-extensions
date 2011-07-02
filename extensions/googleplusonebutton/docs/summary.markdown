
Google +1 button
================

This extension allows you to insert a Google +1 button on your pages and
entries. Using this button, your visitors can '+1' your pages
and entries, without leaving your site.


Usage
-----

To use the button in it's simplest form, just add `[[plusonebutton]]` in the
template of your entrypage, in a weblog entry, or on a page. This will insert
the default button, with the default options selected.

Several parameters are available to customize the button. For example, to insert
a smaller button, use:

	[[ plusonebutton count=0 size=small ]]

The available parameters are:

 - link - The URL to like. Usually you can leave this blank, because PivotX will
   insert the correct link to the current entry or page.
 - size - there are four options for differently sized buttons: `small`, `medium`, 
   `standard` or `tall`.
 - count - set this to `1` or `0` to set whether or not to display the number of 
   'plusses'. The count is always shown, when size is `tall`
 - lang - set this to the desired language 
   [valid codes](http://code.google.com/intl/nl/apis/+1button/#languages).
   When not set PivotX setting will be used.

For more information about the Google +1 button, see page
[here](http://code.google.com/apis/+1button/).