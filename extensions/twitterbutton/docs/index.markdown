
Twitter Button
======================

This extension allows you to insert a Twitter button on your pages and
entries. Using this button, your visitors can post links on Twitter to your pages
and entries, without leaving your site.


Usage
-----

To use the button in its simplest form, just add `[[twitterbutton]]` in the
template of your entrypage, in a weblog entry or on a page. This will insert
the default button, with the default options selected.

Several parameters are available to customize the button. For example, to insert
a simpler button, use:

    [[ twitterbutton text="Post this!" link="http://example.org" button="vertical" username="example" ]]

The available parameters are:

 - link - the URL to like. Usually you can leave this blank, because PivotX will
   insert the correct link to the current entry or page.
 - text - the text to tweet. Usually you can leave this blank, because PivotX will
   insert the title of the current entry or page.    
 - size - the size of the Tweet button, 'medium' (default) or 'large'.
 - button - there are three options. Default is 'horizontal'.
   - vertical - Vertical lay-out with tweet count. Button is higher than with the other options.
   - horizontal - Compact horizontal lay-out with tweet count.
   - none - Just the button. No tweet count is displayed.
 - username - the twitter user that's credited with "via @username" in the tweet.
 - related - the related user that's recommended in the tweet.
 - hashtags - one or more strings (no space in them) comma separated to show up in the tweet.
 
For more information about the Twitter button, see the Twitter goodies page
[here](http://twitter.com/goodies/tweetbutton).
