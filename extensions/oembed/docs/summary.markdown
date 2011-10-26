oEmbed
======

What is it
----------
A snippet to add oEmbed (<a href="http://www.oembed.com/" target="_blank">http://www.oembed.com/</a>) to your site.

Usage
-----
After enabling the extension you can add embeds tags to your entries, pages
and templates. The external media will magically be included in your entries.

	[[embed url="http://www.youtube.com/watch?v=R3jkUhG68wY"]]

You can also set a width and a height to the embed code. The script will then
try to fetch the video or image with the largest size still within the given
dimensions.

	[[embed url="http://www.youtube.com/watch?v=R3jkUhG68wY" width="400" height="320"]]

The bookmarklet will automatically try to recognize url's from sites that are
oEmbed capable, and return the embed code.

PivotX with a MySQL database is recommended for faster caching of the media code, but it is not required.

FAQ
---
Why is the size of my Youtube video is not what I expect?

If you set a width and a height it should work, if you don't set them, or only
one of them, the default value will be used for the other one, and that may
influence the size because youtube scales the output to fit inside the
dimensions.

Information
-----------

  * Author: Two Kings // Lodewijk Evers 
  * E-mail: <a href="mailto:lodewijk@twokings.nl">lodewijk@twokings.nl</a>
  * Support: <a href="http://forum.pivotx.net/viewtopic.php?f=10&t=1377">http://forum.pivotx.net/viewtopic.php?f=10&t=1377</a>
  * Download: <a href="http://extensions.pivotx.net/entry/5/oembed">http://extensions.pivotx.net/entry/5/oembed</a>
  * Includes: <a href="http://code.google.com/p/jquery-oembed/">jquery-oembed</a>
