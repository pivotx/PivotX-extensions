
Twitter Button
======================

This extension allows you to insert a Twitter button on your pages and
entries. Using this button, your visitors can post links on twitter to your pages
and entries, without leaving your site.


Usage
-----

To use the button in it's simplest form, just add `[[twitterbutton]]` in the
template of your entrypage, in a weblog entry, or on a page. This will insert
the default button, with the default options selected.

Several parameters are available to customize the button. For example, to insert
a simpler button, use:

	[[ twitterbutton text="Post on Twitter" link="http://example.org" button="vertical" username="example" ]]

The available parameters are:

 - link - The URL to like. Usually you can leave this blank, because PivotX will
   insert the correct link to the current entry or page.
 - text - The text to tweet. Usually you can leave this blank, because PivotX will
   insert the title of the current entry or page.    
 - size - The size of the Tweet button, 'medium' (default) or 'large'.
 - button - there are three options. Omit this parameter for 'horizontal'
   - vertical - Vertical layout. Button is higher than with the other options.
   - horizontal - Compact horizontal layout.
   - none - Just the button. No Tweet-count is displayed
 - username - The twitter user, that's credited with "via @username" in the tweet.
 
For more information about the Tweetbutton, see the Twitter goodies page
[here](http://twitter.com/goodies/tweetbutton).
