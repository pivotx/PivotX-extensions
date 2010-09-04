<?php
// - Extension: BBclone
// - Version: 1.1
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: A snippet extension that enables you to display stats for your pages collected by BBclone.
// - Date: 2008-09-09
// - Identifier: bbclone
// - Required PivotX version: 2.0.2

$PIVOTX['template']->register_function('bbclone_stats', 'smarty_bbclone_stats');

/**
 * Displays the selected stats.
 *
 * This extension assumes that BBclone is installed in a folder called 'bbclone',
 * which should be in the same folder as your 'pivotx' folder.
 *
 * Usage:  [[ bbclone_stats type="SOMETYPE" ]]
 *
 * where SOMETYPE can be: hits, hits_entry, keywords, referer.
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_bbclone_stats($params, &$smarty) {
    global $PIVOTX;

    $bbclone_path = fixPath($PIVOTX['paths']['pivotx_path'].'../bbclone/');
    $PIVOTX['paths']['bbclone_path'] = $bbclone_path;

    if(!file_exists($bbclone_path)) {
        debug("bbclone directory not found - expected location: $bbclone_path");
        return;
    }

    switch ($params['type']) {
        case 'hits':
            return bbclone_stats_hits();
            break;
        case 'hits_entry':
            return bbclone_stats_hits_entry($params['format']);
            break;
        case 'keywords':
            return bbclone_stats_keywords();
            break;
        case 'referer':
            return bbclone_stats_referers();
            break;
        default:
            debug('Unknown or missing type for bbclone_stats');
    }
            
}

function bbclone_stats_hits_entry($format) {
    global $PIVOTX;

    require($PIVOTX['paths']['bbclone_path']."var/access.php");

    // The determination of the title must be the same as in hook_bbclone.php
    $vars = $PIVOTX['template']->get_template_vars();

    if ($vars['pagetype']=="entry" || $vars['pagetype']=="page") {
        $title = smarty_title(array(), $PIVOTX['template']);
    } else {
        $title = smarty_sitename(array(), $PIVOTX['template']);
    }

    // bbclone cuts some characters if title is too long (>60) and adds "..."
    // and replaces a few character by its entities
    if (strlen($title) > 60){
        $title = substr($title,(strlen($title) - 57));
        $title = "...".$title;
    }
    $title = htmlspecialchars($title,ENT_QUOTES);

    $hits = $access["page"][$title]["count"];
    if (empty($hits)) { $hits = "0"; }
    
    if (empty($format)) {
        $format = __("%n visitors on this page");
    }
    $output = str_replace("%n",$hits,$format);

    unset($access);

    return $output;
}

function bbclone_stats_hits() {
    global $PIVOTX;

    require($PIVOTX['paths']['bbclone_path']."var/access.php");

    $totalvisits   = $access["stat"]["totalvisits"];
    $totalcount    = $access["stat"]["totalcount"];
    $visitorsmonth = $access["time"]["month"][date("n")-1];
    $visitorstoday = $access["time"]["wday"][date("w")];
    $wday          = $access["time"]["wday"];

    for($week = 0; list(,$wdays) = each($wday); $week += $wdays);

    $output = sprintf("<p class=\"bbclone-stats\">\n%s: <strong>%s</strong><br />\n", __('Total visits'), $totalvisits);
    $output .= sprintf("%s: <strong>%s</strong><br />\n", __('Unique visitors'), $totalcount);
    $output .= sprintf("%s: <strong>%s</strong><br />\n", __('This month'), $visitorsmonth);
    $output .= sprintf("%s: <strong>%s</strong><br />\n", __('This week'), $week);
    $output .= sprintf("%s: <strong>%s</strong>\n</p>\n", __('Today'), $visitorstoday);

    unset($access);

    return $output;

}

function bbclone_stats_keywords() {
    global $PIVOTX;

    require($PIVOTX['paths']['bbclone_path']."var/access.php");

    // User config
    $max_keys = 10;
    $output_start = '<table><tr><th>'.__('Keyword').'</th><th>'.__('Frequency').'</th></tr>';
    $item_format = '<tr><td>%keyword%</td><td>%score% (%score_percent%)</td></tr>';
    $output_end = '</table>';
    // End user config

    if (!isset($access['key'])) {
        // No keywords to display yet
        return; 
    }

    // Code based on show_global.php in BBClone
    $key_tab = isset($access['key']) ? $access['key'] : array();

    for ($key_total = 0; list(, $key_score) = each($key_tab); $key_total += $key_score);

    arsort($key_tab);
    reset($key_tab);

    for ($k = 0; ($k < $max_keys) && (list($key_name, $key_score) = each($key_tab)); $k++) {
        $key_score_percent = sprintf("%.2f%%", (round(10000 * $key_score / $key_total) / 100));
        $item = str_replace("%keyword%", $key_name, $item_format);
        $item = str_replace("%score%", $key_score, $item);
        $item = str_replace("%score_percent%", $key_score_percent, $item);
        $text .= "$item\n";
    }

    unset($access);

    return "$output_start\n$text$output_end\n";

}

function bbclone_stats_referers() {
    global $PIVOTX;

    require($PIVOTX['paths']['bbclone_path']."var/last.php");

    // textual markers for search engines
    $engines['google'] = "[Go] ";
    $engines['alltheweb'] = "[Al] ";
    $engines['vivisimo'] = "[Viv] ";
    $engines['vinden'] = "[Vin] ";
    $engines['altavista'] = "[Av] ";
    $engines['aol'] = "[Ao] ";
    $engines['lycos'] = "[Ly] ";
    $engines['msn'] = "[Ms] ";
    $engines['mysearch'] = "[My] ";
    $engines['yahoo'] = "[Y] ";

    $output = "<p class=\"bbclone-referers\">\n";

    $last['traffic'] = array_reverse($last['traffic']);

    foreach($last['traffic'] as $line) {

        // skip 'unknown'..
        if (($line['referer']=="unknown") || ($line['referer']=="ignored")) { continue; }

        // Get the search engine, if any was used
        if ( ($line['search']!="") && ($line['search']!="-") ){

            $line['searchengine'] = "[S] ";

            foreach($engines as $engine => $name) {
                if (strpos($line['referer'],$engine.".")>0) { $line['searchengine'] = $name;}
            }

            $title = $line['searchengine']." ".$line['search'];

        } else {
            $title = bbclone_stats_referer_disptitle($line['referer']);
        }

        $output .= sprintf("%s ", date("H:i",$line['time']));
        $output .= sprintf("<a href=\"%s\">%s</a>", $line['referer'], trimtext($title,20));
        $output .= "<br />\n";

        $count++;

        if ($count>15) { break; }

    }

    $output .= "</p>\n";

    unset($last);

    return $output;
}



/**
 * Formats an url for display. Index files, http://
 * prefixes and 'www.' are ignored.
 *
 * @param string $text
 * @return string
 */
function bbclone_stats_referer_disptitle($text) {
    if (strpos($text, "?")) { $text=substr($text,0, strpos($text, "?") ); }
    $text=str_replace("/index.php", "", $text);
    $text=str_replace("/index.html", "", $text);
    $text=str_replace("/index.htm", "", $text);
    $text=str_replace("/index.shtml", "", $text);
    if ((strlen($text) - strrpos($text, "/")) == 1 ) { $text=substr($text,0,(strlen($text)-1)); }

    $text = str_replace('http://', '', $text);
    $text = str_replace('www.', '', $text);

    $text = stripslashes(htmlspecialchars($text));
    return $text;

}

?>
