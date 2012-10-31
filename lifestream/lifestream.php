<?php


DEFINE('PIVOTX_INWEBLOG', TRUE);

require_once(dirname(dirname(dirname(__FILE__)))."/lib.php");

initializePivotX();


/**
 * Getting some variables from config. If they are not set, revert to the defaults
 * from widget_lifestream.php
 *
 */
$twittername = getDefault($PIVOTX['config']->get('lifestream_twitterusername'), $lifestream_config['lifestream_twitterusername']);
$twitterpass = getDefault($PIVOTX['config']->get('lifestream_twitterpassword'), $lifestream_config['lifestream_twitterpassword']);
$summize = getDefault($PIVOTX['config']->get('lifestream_summize'), $lifestream_config['lifestream_summize']);
$tumblrname = getDefault($PIVOTX['config']->get('lifestream_tumblrusername'), $lifestream_config['lifestream_tumblrusername']);
$flickrfeed = getDefault($PIVOTX['config']->get('lifestream_flickrfeed'), $lifestream_config['lifestream_flickrfeed']);
$lastfmname = getDefault($PIVOTX['config']->get('lifestream_lastfmusername'), $lifestream_config['lifestream_lastfmusername']);
$max = getDefault($PIVOTX['config']->get('lifestream_max_items'), $lifestream_config['lifestream_max_items']);
$maxperfeed = getDefault($PIVOTX['config']->get('lifestream_max_perfeed'), $lifestream_config['lifestream_max_perfeed']);
$header = getDefault($PIVOTX['config']->get('lifestream_header'), $lifestream_config['lifestream_header']);
$format = getDefault($PIVOTX['config']->get('lifestream_format'), $lifestream_config['lifestream_format']);
$footer = getDefault($PIVOTX['config']->get('lifestream_footer'), $lifestream_config['lifestream_footer']);

if ( empty($twittername) && empty($summize) && empty($tumblrname) && empty($flickrfeed) && empty($lastfmname) ) {
    $text = "At least one username must be entered in the Lifestream configuration.";
    echo $text;
    debug($text);
    die();
}


define("MAGPIE_CACHE_AGE", 600);
include_once($PIVOTX['paths']['pivotx_path'].'includes/magpie/rss_fetch.inc');


$iconpath = sprintf("%slifestream/%s", $PIVOTX['paths']['extensions_url'], '%icon%' );

$items = array();


$count = 0;

/**
 * First get updates from Summize.. (because these will weigh least heavy, when
 * ordering later on)
 */
if (!empty($summize)) {
    $url = "http://search.twitter.com/search.atom?q=". urlencode($summize);

    $rss = fetch_rss($url);

    if ($magpie_error!="") {
        debug("Lifestream parser / Twitter Search: " . $magpie_error('', true) . "\nurl: " . $url);   
    }


    if (count($rss->items)>0) {
        foreach($rss->items as $item) {

            // Get the author from the summize feed.
            $authorname = $item['author'];
            list($authorname) = explode(" (", $authorname);
            $authorlink = $item['author_uri'];

            $tempitem = array();    
            $tempitem['title'] = sprintf("<a href='%s'>%s</a>: %s", $authorlink, $authorname, $item['title'] );
            $tempitem['link'] = $item['link'];
            $tempitem['date_timestamp'] = date("Y-m-d H-i-s", $item['date_timestamp']);
            $tempitem['source'] = "summize";
            $tempitem['icon'] = str_replace("%icon%", "summize.gif", $iconpath);
    
            $items[ $tempitem['date_timestamp'] ] = $tempitem;

            $count++;
            if ($count>= $maxperfeed) { break; }
        }
    } else {
        debug("Twitter search feed contains no data.");
        debug("feed url: $url");
    }

}



$count = 0;




/**
 * Then get the updates from Twitter..
 */
if (!empty($twittername)) {

    $url = "http://api.twitter.com/1/statuses/user_timeline.rss?screen_name=".$twittername;

    $rss = fetch_rss($url);

    if ($magpie_error!="") {
        debug("Lifestream parser / Twitter: " . $magpie_error('', true) . "\nurl: " . $url);   
    }

    if (count($rss->items)>0) {
        foreach($rss->items as $item) {

            $tempitem = array();
            $tempitem['title'] = str_replace($twittername.": ", "", $item['title'] );
            $tempitem['link'] = $item['link'];
            $tempitem['date_timestamp'] = date("Y-m-d H-i-s", $item['date_timestamp']);
            $tempitem['source'] = "twitter";
            $tempitem['icon'] = str_replace("%icon%", "twitter.png", $iconpath);

            $items[ $tempitem['date_timestamp'] ] = $tempitem;

            $count++;
            if ($count>=$maxperfeed) { break; }
        }
    } else {
        debug("Twitter feed contains no data.");
        debug("feed url: $url");
    }

}


$count = 0;

/**
 * Then get updates from Tumblr..
 */
if (!empty($tumblrname)) {
    
    $url = "http://".$tumblrname.".tumblr.com/rss";

    $rss = fetch_rss($url);

    if ($magpie_error!="") {
        debug("Lifestream parser / Tumblr: " . $magpie_error('', true) . "\nurl: " . $url);   
    }

    if (count($rss->items)>0) {
        foreach($rss->items as $item) {

            $tempitem = array();
            $tempitem['title'] = str_replace($twittername.": ", "", $item['title'] );
            $tempitem['link'] = $item['link'];
            $tempitem['date_timestamp'] = date("Y-m-d H-i-s", $item['date_timestamp']);
            $tempitem['source'] = "tumblr";
            $tempitem['icon'] = str_replace("%icon%", "tumblr.gif", $iconpath);

            $items[ $tempitem['date_timestamp'] ] = $tempitem;

            $count++;
            if ($count>=$maxperfeed) { break; }
        }
    } else {
        debug("Tumblr feed contains no data.");
        debug("feed url: $url");
    }

}

$count = 0;


/**
 * Then get updates from Last.fm..
 */
if (!empty($lastfmname)) {
    $url = "http://ws.audioscrobbler.com/1.0/user/". urlencode($lastfmname) . "/recenttracks.rss";

    $rss = fetch_rss($url);

    if ($magpie_error!="") {
        debug("Lifestream parser / Lastfm: " . $magpie_error('', true) . "\nurl: " . $url);   
    }

    if (count($rss->items)>0) {
        foreach($rss->items as $item) {
            
            $tempitem = array();
            $tempitem['title'] = $item['title'];
            $tempitem['link'] = $item['link'];
            $tempitem['date_timestamp'] = date("Y-m-d H-i-s", $item['date_timestamp']);
            $tempitem['source'] = "lastfm";
            $tempitem['icon'] = str_replace("%icon%", "lastfm.gif", $iconpath);
    
            $items[ $tempitem['date_timestamp'] ] = $tempitem;

            $count++;
            if ($count>=$maxperfeed) { break; }
        }
    } else {
        debug("Last.fm feed contains no data.");
        debug("feed url: $url");
    }

}


$count = 0;

/**
 * Then get updates from Flickr..
 */
if (!empty($flickrfeed)) {
    $url = $flickrfeed;

    $rss = fetch_rss($url);

    if ($magpie_error!="") {
        debug("Lifestream parser / Flickr: " . $magpie_error('', true) . "\nurl: " . $url);   
    }

    if (count($rss->items)>0) {
        foreach($rss->items as $item) {
            
            $tempitem = array();
            
            $url = lightbox::find_photo($item['summary']);
            $thumb_url = lightbox::photo($url, "square");

            $tempitem['title'] = sprintf("<a href=\"%s\"><img src=\"%s\" alt=\"%s\" border='0' /></a>", $item['link'], $thumb_url, $item['title'] );
            $tempitem['link'] = $item['link'];
            $tempitem['date_timestamp'] = date("Y-m-d H-i-s", $item['date_timestamp']);
            $tempitem['source'] = "flickr";
            $tempitem['icon'] = str_replace("%icon%", "flickr.gif", $iconpath);
    
            $items[ $tempitem['date_timestamp'] ] = $tempitem;

            $count++;
            if ($count>=$maxperfeed) { break; }
        }
    } else {
        debug("Flickr feed contains no data.");
        debug("feed url: $url");
    }

}


// sort all items..
ksort($items);

$items2 = array();

// Now filter out double items.
foreach ($items as $key=>$item) {
    foreach($items2 as $item2) {
        if ($item['title']==$item2['title']){
            continue(2);
        }
    }
    $items2[] = $item;
}

$items2 = array_reverse($items2);


// Now, loop through them, grouping together the last.fm and Flickr items.
foreach ($items2 as $key => $item) {
    if ($item['source']==$items2[($key+1)]['source'] && $item['source']=="lastfm") {
        $items2[($key+1)]['title'] = $items2[$key]['title'] . ", " . $items2[($key+1)]['title'];
        $items2[($key+1)]['date_timestamp'] = $items2[$key]['date_timestamp'];
        unset($items2[$key]);
    }
    if ($item['source']==$items2[($key+1)]['source'] && $item['source']=="flickr") {
        $items2[($key+1)]['title'] = $items2[$key]['title'] . " " . $items2[($key+1)]['title'];
        $items2[($key+1)]['date_timestamp'] = $items2[$key]['date_timestamp'];
        unset($items2[$key]);
    }
        
}


$output = "";


// Slice it, so no more than $max items will be shown.
$items2 = array_slice($items2, 0, $max);

foreach($items2 as $item) {

    // Convert links to a clickable '[link]'..
    $item['title'] = preg_replace("/([ \t]|^)www\./mi", "\\1http://www.", $item['title']);
    $item['title'] = preg_replace("#([ \t\(]|^)(http://[^ :)\r\n]+)#mi",
        "\\1<a href=\"\\2\" title=\"\\2\" rel=\"nofollow\" target=\"_blank\">[link]</a>", $item['title']);

    // Make the '@name' in twitter into links..
    if ($item['source']=="twitter" || $item['source']=="summize") {
        $item['title'] = preg_replace("/@(\w+)/i", "@<a href='http://twitter.com/$1' rel=\"nofollow\" target=\"_blank\">$1</a>", $item['title']);
    }

    $temp_output = $format;
    $temp_output = str_replace('%title%', $item['title'], $temp_output );
    $temp_output = str_replace('%link%', $item['link'], $temp_output );
    $temp_output = str_replace('%date%', formatDate($item['date_timestamp'], "%fuzzy%"), $temp_output );
    $temp_output = str_replace('%source%', $item['source'], $temp_output );
    $temp_output = str_replace('%icon%', $item['icon'], $temp_output );
    $output .= $temp_output."\n";

}


echo $header.$output.$footer;




/**
 * A simple class to get stuff from the Flickr Feed.
 *
 * @see: http://simplepie.org/wiki/tutorial/flickr_lightbox
 */

class lightbox
{
	/**
	 * Function that removes double-quotes so they don't interfere with the HTML.
	 */
	function cleanup($s = null)
	{
		if (!$s) return false;
		else
		{
			return str_replace('"', '', $s);
		}
	}
 
	/**
	 * Function that returns the correctly sized photo URL.
	 */
	function photo($url, $size)
	{
		$url = explode('/', $url);
		$photo = array_pop($url);
 
		switch($size)
		{
			case 'square':
				$r = preg_replace('/(_(s|t|m|b))?\./i', '_s.', $photo);
				break;
			case 'thumb':
				$r = preg_replace('/(_(s|t|m|b))?\./i', '_t.', $photo);
				break;
			case 'small':
				$r = preg_replace('/(_(s|t|m|b))?\./i', '_m.', $photo);
				break;
			case 'large':
				$r = preg_replace('/(_(s|t|m|b))?\./i', '_b.', $photo);
				break;
			default: // Medium
				$r = preg_replace('/(_(s|t|m|b))?\./i', '.', $photo);
				break;
		}
 
		$url[] = $r;
		return implode('/', $url);
	}
 
	/**
	 * Function that looks through the description and finds the first image.
	 */
	function find_photo($data)
	{
		preg_match_all('/<img src="([^"]*)"([^>]*)>/i', $data, $m);
		return $m[1][0];
	}
}



?>
