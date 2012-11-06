<?php
// default provider
//$providers['default'] = array('endpoint' => 'http://oohembed.com/oohembed/', 'format' => 'json', 'type' => 'rich');
$providers['default'] = array('endpoint' => 'http://api.embed.ly/v1/api/oembed', 'format' => 'json', 'type' => 'rich');
//$providers['default'] = array('endpoint' => 'http://noembed.com/embed', 'format' => 'json', 'type' => 'rich');


// specific providers by url pattern
$providers['nos.nl'] = array(
	'endpoint' => 'http://nos.nl/service/oembed/',
	'format' => 'json',
	'type' => 'video');
$providers['youtube.com'] = array(
	'endpoint' => 'http://www.youtube.com/oembed',
	'format' => 'json',
	'type' => 'video');
$providers['flickr.com'] = array(
	'endpoint' => 'http://www.flickr.com/services/oembed/',
	'format' => 'json',
	'type' => 'photo');
$providers['viddler.com'] = array(
	'endpoint' => 'http://lab.viddler.com/services/oembed/',
	'format' => 'json',
	'type' => 'video');
$providers['qik.com'] = array(
	'endpoint' => 'http://qik.com/api/oembed.json',
	'format' => false,
	'type' => 'video');
$providers['revision3.com'] = array(
	'endpoint' => 'http://revision3.com/api/oembed/',
	'format' => 'json',
	'type' => 'video');
$providers['hulu.com'] = array(
	'endpoint' => 'http://www.hulu.com/api/oembed.json',
	'format' => false,
	'type' => 'video');
$providers['vimeo.com'] = array(
	'endpoint' => 'http://www.vimeo.com/api/oembed.json',
	'format' => false,
	'type' => 'video');
$providers['polleverywhere.com'] = array(
	'endpoint' => 'http://www.polleverywhere.com/services/oembed/',
	'format' => 'json',
	'type' => 'rich');
$providers['my.opera.com'] = array(
	'endpoint' => 'http://my.opera.com/service/oembed',
	'format' => 'json',
	'type' => 'rich');
$providers['5min.com'] = array(
	'endpoint' => 'http://api.5min.com/oembed.json',
	'format' => false,
	'type' => 'video');
$providers['blip.tv'] = array(
	'endpoint' => 'http://blip.tv/oembed/',
	'format' => 'json',
	'type' => 'video');
$providers['mob.to'] = array(
	'endpoint' => 'http://api.mobypicture.com/oEmbed',
	'format' => 'json',
	'type' => 'photo');
$providers['mobypicture.com'] = array(
	'endpoint' => 'http://api.mobypicture.com/oEmbed',
	'format' => 'json',
	'type' => 'photo');
$providers['yfrog'] = array(
	'endpoint' => 'http://api.embed.ly/v1/api/oembed',
	'format' => 'json',
	'type' => 'photo');
$providers['twitpic.com'] = array(
	'endpoint' => 'http://api.embed.ly/v1/api/oembed',
	'format' => 'json',
	'type' => 'photo');
$providers['tweetphoto.com'] = array(
	'endpoint' => 'http://api.embed.ly/v1/api/oembed',
	'format' => 'json',
	'type' => 'photo');

//$providers[''] = array('endpoint' => '', 'format' => 'json', 'type' => 'video');

?>
