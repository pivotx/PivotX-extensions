<?php


DEFINE('PIVOTX_INWEBLOG', TRUE);

require_once(dirname(dirname(dirname(__FILE__)))."/lib.php");

initializePivotX();


$url = $PIVOTX['config']->get('delicious_url');

if (empty($url)) {
    echo "No Del.iciou.us URL set in the Del.icio.us admin screen!";
    die();
}

$max = getDefault($PIVOTX['config']->get('delicious_max_items'), $delicious_config['delicious_max_items']);
$header = getDefault($PIVOTX['config']->get('delicious_header'), $delicious_config['delicious_header']);
$format = getDefault($PIVOTX['config']->get('delicious_format'), $delicious_config['delicious_format']);
$footer = getDefault($PIVOTX['config']->get('delicious_footer'), $delicious_config['delicious_footer']);



define("MAGPIE_CACHE_AGE", 600);
include_once($PIVOTX['paths']['pivotx_path'].'includes/magpie/rss_fetch.inc');

// Parse it
$rss = fetch_rss($url);

$output = "";

if (count($rss->items)>0) {

    // Slice it, so no more than $max items will be shown.
    $rss->items = array_slice($rss->items, 0, $max);

    foreach($rss->items as $item) {

        // Prepare the tags..
        $tags = explode(" ", $item['dc']['subject']);
        foreach($tags as $key=>$tag) {
            $tags[$key] = sprintf("<a href='http://del.icio.us/tag/%s'>%s</a>", $tag, $tag);
        }
        $tags = implode(", ", $tags);

        $title = wordwrap($item['title'],30, " ", true);
        $description = wordwrap($item['description'],30, " ", true);

        $temp_output = $format;
        $temp_output = str_replace('%title%', $title, $temp_output );
        $temp_output = str_replace('%link%', $item['link'], $temp_output );
        $temp_output = str_replace('%description%', $description, $temp_output );
        $temp_output = str_replace('%tags%', $tags, $temp_output );

        $output .= $temp_output."\n";

    }
    
} else {
    
    $output = "<p>" . __("No items in Delicious Feed. Please check the settings in the PivotX backend.") . "</p>";
    
}


echo $header.$output.$footer;

?>
