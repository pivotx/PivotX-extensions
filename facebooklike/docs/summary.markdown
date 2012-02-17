 
Facebook 'Like' Button
======================

This extension allows you to insert a Facebook 'Like' button on your pages and
entries. Using this button, your visitors can 'Like' your pages and entries,
without leaving your site.


Usage
-----

To use the button in its simplest form, just add `[[facebook_like]]` in the
template of your entrypage, in a weblog entry, or on a page. This will insert
the default button, with the default options selected.

Several parameters are available to customize the button. For example, to insert
a simpler button, use:

    [[ facebook_like layout="button_count" width="90" height="20" ]]

The URL parameters are (use only one of these):

 - canonical - set to 'true' to set the URL to the current page (PivotX 2.3+).
 - uri - the entry or page uri to like (produces same link as [[ link ]]).
 - link - the URL to like. 
 - (none of the above) - either the URL to the current page or the URL to the currently loaded $entry or $page.

The available other parameters are:

 - layout - there are three options:
   - standard - displays social text to the right of the button and friends'
     profile photos below. Minimum width: 225 pixels. Default width: 450 pixels. 
     Height: 35 pixels (without photos) or 80 pixels (with photos).
   - button_count - displays the total number of likes to the right of the button.
     Minimum width: 90 pixels. Default width: 90 pixels. Height: 20 pixels.
   - box_count - displays the total number of likes above the button. Minimum width: 55 pixels. 
     Default width: 55 pixels. Height: 65 pixels.
 - show_faces - specifies whether to display profile photos below the button (standard layout only).
 - width - the width of the Like button.
 - height - the height of the Like button.
 - action - the verb to display on the button. Options: 'like', 'recommend'.
 - font - the font to use for the button. Options: 'arial', 'lucida grande', 
   'segoe ui', 'tahoma', 'trebuchet ms', 'verdana'.
 - colorscheme - the color scheme for the like button. Options: 'light', 'dark'.
 - ref - a label for tracking referrals; must be less than 50 characters and can contain alphanumeric 
   characters and some punctuation (currently +/=-.:_). The ref attribute causes two parameters to be 
   added to the referrer URL when a user clicks a link.
 - locale - the internationalization code to use instead of the one deduced from the URL (Like text
   will change accordingly). Strings like 'en`_`US', 'nl`_`NL' and 'fr`_`FR' are valid. For possible 
   combinations [see](http://developers.facebook.com/docs/internationalization/).
 
For more information about the Facebook 'Like' button, see the Facebook developers page
[here](http://developers.facebook.com/docs/reference/plugins/like).
