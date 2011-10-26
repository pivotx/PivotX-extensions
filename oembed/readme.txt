oEmbed
======

What is it
----------
A snippet to add oEmbed (http://www.oembed.com/) to your site.

Usage
-----
After enabling the extension you can add embeds tags to your entries, pages and templates. The external media will magically be included in your entries.

	[[embed url="http://www.youtube.com/watch?v=vIuh9pKnv5Q"]]

You can also set a width and a height to the embed code. The script will then try to fetch the video or image with the largest size still within the given dimensions.

	[[embed url="http://www.youtube.com/watch?v=e57QD6Pxjeg" width="400" height="320"]]

The bookmarklet will automatically try to recognize url's from sites that are oEmbed capable, and return the embed code.

Installation
------------
Extract the archive and move the directory oembed to your PivotX extentions directory. After that, log in to PivotX and enable the snippet in the extensions configuration screen.

PivotX with a MySQL database is recommended for faster cacheing of the media code, but it is not required.

FAQ
---
Why is the size of my Youtube video is not what I expect?

If you set a width and a height it should work, if you don't set them, or only one of them, the default value will be used for the other one, and that may influence the size because youtube scales the output to fit inside the dimensions.


Version Information
-------------------
  * Version: 0.12
  * Date: 2011-10-26
  * Requirements: PivotX 2.2.0 or higher
  * Recommended: mysql, PHP 5.2.0 or higher
  * Includes: jquery-oembed (http://code.google.com/p/jquery-oembed/)

  * Author: Two Kings // Lodewijk Evers
  * E-mail: lodewijk@twokings.nl
  * Support: http://forum.pivotx.net/viewtopic.php?f=10&t=1377
  * Download: http://extensions.pivotx.net/entry/5/oembed

Maintainers
-----------
  * Two Kings: info@twokings.nl
  * Lodewijk Evers: info@twokings.nl
