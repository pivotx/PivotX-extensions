
PivotX Media Extension
==========================

This is a simple extension that allows you to embed audio files and 
local video files, as well as videos from Youtube and Vimeo.

Installation
------------

- Upload the 'media' folder to your pivotx/extensions/ folder
- In PivotX, go to 'Extensions' and enable the 'Media Player' extension.


Usage
-----

Audio:

    [[ audio file="media/03-strange-times.mp3" ]]
    [[ audio url="http://melodiefabriek.nl/audio/flick_radio/master/flickradio.mp3" ]]

Youtube videos:

    [[ youtube url="http://www.youtube.com/v/EBM854BTGL0" ]]

Vimeo videos:

    [[ vimeo url="http://www.vimeo.com/moogaloop.swf?clip_id=290410" ]]

Local videos in flv or swf format:

    [[ video file="media/afraid.flv" ]]
    [[ video url="http://example.org/video/afraid.flv" ]]

For local files, the path is relative to the Site root. 

You can add a width and height parameter, like this: 

    [[ video file="..." width=480 height=320 ]]

The width and height parameters work for [[vimeo]], [[youtube]], [[video]]

In addition you can specify a description and if you want to add an infobox 
(a div element with CSS class "pivotx-media-MEDIATYPE-infobox") which contains 
the description. The description is normally only used in the fall back for 
users that have disabled Javascript. Examples:

    [[ youtube url="http://www.youtube.com/v/EBM854BTGL0" 
               description="Star Wars acording to a 3 year old." ]]
    [[ audio url="http://melodiefabriek.nl/audio/flick_radio/master/flickradio.mp3" 
             useinfobox=1 ]]

Credits
-------

* [SWFObject](http://code.google.com/p/swfobject/) - Bobby van der Sluis / Geoff Stearns
* [JW media player](http://www.longtailvideo.com/players/jw-flv-player/) - Jeroen Weijering
* [Wordpress Audio Player Plugin](http://wpaudioplayer.com/) - Martin Laine
