<?php


DEFINE('PIVOTX_INWEBLOG', TRUE);

require_once(dirname(dirname(dirname(__FILE__)))."/lib.php");

initializePivotX();

$calendar_vars = array('id', 'max_items', 'futureevents', 'orderby', 'sortorder',
    'format_date_start', 'format_date_end');

foreach ($calendar_vars as $var) {
    if (isset($_GET[$var])) {
        $$var = $_GET[$var];
    } else {
        $key = "googlecalendar_$var";
        $$var = getDefault($PIVOTX['config']->get($key), $googlecalendar_config[$key]);
    }
}

if (empty($id)) {
    echo "No Calendar ID given";
    die();
} else {
    $ids = array();
    $rawids = explode(',', $id);
    foreach ($rawids as $rawid) {
        $tempid = trim($rawid);
        // Normalize ID - hyphens and periods in local part are ignored by Google.
        list($local,$domain) = explode('@', $tempid);
        $tempid = str_replace(array('.','-'), '', $local) . '@' . $domain;
        $ids[] = $tempid;
    }
}

$query = "singleevents=true";
$query .= "&orderby=$orderby";
$query .= "&sortorder=a";  // Since we might have to merge several calendars, sortorder is irrelevant.
if ($futureevents) {
    $query .= "&futureevents=true";
}
$urls = array();
foreach ($ids as $id) {
    $tempurl = "http://www.google.com/calendar/feeds/" . urlencode($id) . "/public/full";
    $tempurl = $tempurl . '?' . $query;
    $urls[] = $tempurl;
}

$header = getDefault($PIVOTX['config']->get('googlecalendar_header'), $googlecalendar_config['googlecalendar_header']);
$format = getDefault($PIVOTX['config']->get('googlecalendar_format'), $googlecalendar_config['googlecalendar_format']);
$footer = getDefault($PIVOTX['config']->get('googlecalendar_footer'), $googlecalendar_config['googlecalendar_footer']);

include_once($PIVOTX['paths']['pivotx_path'].'includes/magpie/rss_fetch.inc');
define('MAGPIE_CACHE_AGE', 60*60*1); // 1 hours

$items = array();
foreach ($urls as $url) {
    $rss = fetch_rss($url);
    $items = array_merge($items, $rss->items);
}

$count_items = count($items);
if ($count_items>0) {

    // Loop through the items and set the correct sort key - start time or when updated.
    foreach ($items as $itemkey => $item) {
        if ($orderby == "lastmodified") {
            $items[$item['updated']] = $item;
        } else {
            $items[$item['gd']['when@starttime']] = $item;
        }
        unset($items[$itemkey]);
    }

    // Sort the array 
    ksort($items);
    
    // Slice only if there are more items than we want
    if ($max_items<$count_items) {
        // If we order by "lastmodified" we always want the last items and slice at 
        // the end, else we slice at the start because we want the first items.
        if ($orderby == "lastmodified") {
            $items = array_slice($items, -$max_items, $max_items);
        } else {
            $items = array_slice($items, 0, $max_items);
        }
    }

    $output = array();
    foreach ($items as $item) {
        $date_start = date("Y-m-d-H-i-s", strtotime($item['gd']['when@starttime']));
        $date_end = date("Y-m-d-H-i-s", strtotime($item['gd']['when@endtime']));
        $date_start = formatDate($date_start, $format_date_start);
        $date_end = formatDate($date_end, $format_date_end);
        $temp_output = $format;
        $temp_output = str_replace('%author%', $item['author'] , $temp_output );
        $temp_output = str_replace('%where%', $item['gd']['where@valuestring'] , $temp_output );
        $temp_output = str_replace('%title%', $item['title'] , $temp_output );
        $temp_output = str_replace('%link%', $item['link'] , $temp_output );
        $temp_output = str_replace('%description%', $item['atom_content'], $temp_output );
        if (strpos($format, '%date_start%') === false && strpos($format, '%date_end%') === false) {
            $temp_output = str_replace('%date%', "$date_start - $date_end", $temp_output );
        } else {
            $temp_output = str_replace('%date_start%', $date_start, $temp_output );
            $temp_output = str_replace('%date_end%', $date_end, $temp_output );
        }
        $output[] = $temp_output;
   }

    // Fix the sortorder if necessary.
    if (strpos($sortorder,'d') === 0) {
        $output = array_reverse($output);
    }

    $output = implode("\n", $output);
} else {
    debug("<p>Oops! I'm afraid I couldn't read the Google Calendar feed.</p>");
    $output = "<p>" . __("Oops! I'm afraid I couldn't read the Google Calendar feed.") . "</p>";
    debug(magpie_error());
}


echo $header.$output.$footer;

?>
