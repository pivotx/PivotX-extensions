<?php
// - Extension: Star rating
// - Version: 0.5
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: A snippet extension to add easy rating to your entries/pages.
// - Date: 2010-05-14
// - Identifier: starrating



global $starrating_config;

$starrating_config = array(
    'starrating_description' => "(%count% votes, averaging %average%)",
    'css_inserted' => false
);



/**
 * Adds the hook for starratingAdmin()
 *
 * @see starratingAdmin()
 */
$this->addHook(
    'configuration_add',
    'starrating',
    array("starratingAdmin", "Star Rating")
);



/**
 * Adds the hook for the actual widget. We just use the same
 * as the snippet, in this case.
 *
 * @see smarty_starrating()
 */
$this->addHook(
    'snippet',
    'starrating',
    "smarty_starrating"
);




// Register 'starrating' as a smarty tag.
$PIVOTX['template']->register_function('star', 'smarty_starrating');
$PIVOTX['template']->register_function('ratingscore', 'smarty_starrating_score');
$PIVOTX['template']->register_function('toprating', 'smarty_toprating');

/**
 * Output the starrating buttons..
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_starrating($params, &$smarty) {
    global $PIVOTX, $starrating_config;

    // If the hook for the thickbox includes in the header was not yet
    // installed, do so now..
    $PIVOTX['extensions']->addHook('after_parse', 'callback', 'jqueryIncludeCallback');

    $vars = $PIVOTX['template']->get_template_vars();
    if (isset($vars['entry'])) {
        // We're in a subweblog - on a page or in a weblog.
        $pagetype = "entry";
    } else {
        $pagetype = "page";
    }
    $extrafields = $vars[$pagetype]['extrafields'];

    if (!$starrating_config['css_inserted']) {
        $html_head = '<link type="text/css" rel="stylesheet" href="%path%jquery.rating.css"/>';
        $html_head = str_replace('%path%', $PIVOTX['paths']['extensions_url']."starrating/", $html_head);
        $PIVOTX['extensions']->addHook('after_parse', 'insert_before_close_head', $html_head);
        $starrating_config['css_inserted'] = true;
    }

    if (!$starrating_config['js_inserted']) {
        $html_head = <<< EOM
<script src="%path%jquery.rating.js" type="text/javascript" language="javascript"></script>
<script type="text/javascript" >
jQuery(function(){
    jQuery('input.star').rating({
        callback: function(value, link){
            jQuery.get("%path%starrating_submit.php?" + jQuery(this).attr('name') + "=" + this.value);
        }
    });
    jQuery('.starsubmit').hide();
});
</script>
EOM;
        $html_head = str_replace('%path%', $PIVOTX['paths']['extensions_url']."starrating/", $html_head);
        $PIVOTX['extensions']->addHook('after_parse', 'insert_before_close_head', $html_head);
        $starrating_config['js_inserted'] = true;
    }

    $html = <<< EOM
<form class="starrating" action="%path%starrating_submit.php" method="get">
    <input class="star" type="radio" name="%pagetype%-%uid%" value="1" %checked1% />
    <input class="star" type="radio" name="%pagetype%-%uid%" value="2" %checked2% />
    <input class="star" type="radio" name="%pagetype%-%uid%" value="3" %checked3% />
    <input class="star" type="radio" name="%pagetype%-%uid%" value="4" %checked4% />
    <input class="star" type="radio" name="%pagetype%-%uid%" value="5" %checked5% />
    <input type="submit" class='starsubmit' value="Submit scores!" />
</form>
EOM;

    if ($params['description']) {
        $html .= "<span class=\"star-description\">%description%</span>";
    }


    $description = getDefault($PIVOTX['config']->get('starrating_description'), "(%count% votes, averaging %average%)");

    $average = sprintf("%1.1f", $extrafields['ratingaverage']);
    $roundedaverage = round($extrafields['ratingaverage']);

    $html = str_replace('%description%', $description, $html);
    $html = str_replace('%pagetype%', $pagetype, $html);
    $html = str_replace('%uid%', $vars[$pagetype]['uid'], $html);
    $html = str_replace('%count%', intval($extrafields['ratingcount']), $html);
    $html = str_replace('%average%', $average, $html);
    $html = str_replace('%path%', $PIVOTX['paths']['extensions_url']."starrating/", $html);
    $html = str_replace('%checked1%', ( ($roundedaverage==1) ? "checked='checked'" : ""), $html);
    $html = str_replace('%checked2%', ( ($roundedaverage==2) ? "checked='checked'" : ""), $html);
    $html = str_replace('%checked3%', ( ($roundedaverage==3) ? "checked='checked'" : ""), $html);
    $html = str_replace('%checked4%', ( ($roundedaverage==4) ? "checked='checked'" : ""), $html);
    $html = str_replace('%checked5%', ( ($roundedaverage==5) ? "checked='checked'" : ""), $html);

    return $html;

}


/**
 * Output the average rating..
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_starrating_score($params, &$smarty) {
    global $PIVOTX, $starrating_config;

    // If the needed CSS isn't inserted yet, do it now.
    if (!$starrating_config['css_inserted']) {
        $starrating_config['css_inserted'] = true;
        $html = '<link type="text/css" rel="stylesheet" href="%path%jquery.rating.css"/>';
        $html = str_replace('%path%', $PIVOTX['paths']['extensions_url']."starrating/", $html);
        $PIVOTX['extensions']->addHook('after_parse', 'insert_before_close_head', $html);
    }

    $vars = $PIVOTX['template']->get_template_vars();
    if (isset($vars['entry'])) {
        // We're in a subweblog - on a page or in a weblog.
        $pagetype = "entry";
    } else {
        $pagetype = "page";
    }
    $extrafields = $vars[$pagetype]['extrafields'];

    $html = <<< EOM
<span class="star">&nbsp;</span><span class="star-label">%description%</span>
EOM;

    $description = getDefault($PIVOTX['config']->get('starrating_simpleaverage'), "(%average%)");


    $average = sprintf("%1.1f", $extrafields['ratingaverage']);
    $roundedaverage = round($extrafields['ratingaverage']);

    $html = str_replace('%description%', $description, $html);
    $html = str_replace('%average%', $average, $html);
    $html = str_replace('%path%', $PIVOTX['paths']['extensions_url']."starrating/", $html);

    return $html;

}



/**
 * Output the entries/pages with the highest ratings..
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_toprating($params, &$smarty) {
    global $PIVOTX, $starrating_config;

    // This works (for now at least) only for MySQL dbs
    if ($PIVOTX['config']->get('db_model') != "mysql") {
        return "Alas, this functionality requires a MySQL database.";
    }


    $amount = getDefault(intval($params['amount']), 5);

    $format = getDefault($params['format'], "<li><a href='%link%'>%title%</a> <small>%score% / %amount% votes</small></li>");

    $trimlength = getDefault(intval($params['trimlength']), 50);


    $entriestable = safeString($PIVOTX['config']->get('db_prefix')."entries", true);
    $extratable = safeString($PIVOTX['config']->get('db_prefix')."extrafields", true);

    $query = "SELECT e.uid
        FROM `pivot_entries` AS e
        LEFT JOIN pivot_extrafields AS ef ON ( ef.target_uid = e.uid )
        WHERE ef.contenttype = 'entry'
        AND ef.fieldkey = 'ratingaverage'
        ORDER BY value DESC
        LIMIT $amount";

    // initialize a temporary db..
    $db = new db(FALSE);

    $db->db_lowlevel->sql->query($query);

    $entries = $db->db_lowlevel->sql->fetch_all_rows();

    $output = "";

    if (!empty($entries)) {
        foreach($entries as $entry) {
            $entry = $db->read_entry($entry['uid']);

            $temp_format = $format;

            $title = trimText($entry['title'], $trimlength);

            $temp_format = str_replace("%title%", $title, $temp_format);
            $temp_format = str_replace("%link%", $entry['link'], $temp_format);
            $temp_format = str_replace("%score%", number_format($entry['extrafields']['ratingaverage'], 1), $temp_format);
            $temp_format = str_replace("%amount%", $entry['extrafields']['ratingcount'], $temp_format);

            $output .= $temp_format;

        }
    }

    return $output;

}



/**
 * The configuration screen for starrating
 *
 * @param unknown_type $form_html
 */
function starratingAdmin(&$form_html) {
    global $form_titles, $starrating_config, $PIVOTX, $starrating_sites;

    $form = $PIVOTX['extensions']->getAdminForm('starrating');

    $sites = array();
    foreach ($starrating_sites as $sitename => $sitedata) {
        $sites[$sitename] = $sitename;
    }

    $form->add( array(
        'type' => 'text',
        'name' => 'starrating_description',
        'label' => __('Rating button description'),
        'value' => '',
        'error' => __('That\'s not a proper description!'),
        'text' => __('The text to display besides the star ranking buttons. You can use %count% and %average% to insert those values.'),
        'size' => 50,
        'isrequired' => 1,
        'validation' => 'string|minlen=1|maxlen=80'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'starrating_simpleaverage',
        'label' => __('Rating average description'),
        'value' => '',
        'error' => __('That\'s not a proper description!'),
        'text' => __('The text to show when only showing a simple average. You can use %average% to insert that value.'),
        'size' => 50,
        'isrequired' => 1,
        'validation' => 'string|minlen=1|maxlen=80'
    ));


    /**
     * Add the form to our (referenced) $form_html. Make sure you use the same key
     * as the first parameter to $PIVOTX['extensions']->getAdminForm
     */
    $form_html['starrating'] = $PIVOTX['extensions']->getAdminFormHtml($form, $starrating_config);


}


?>
