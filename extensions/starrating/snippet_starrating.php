<?php
// - Extension: Star rating
// - Version: 0.9
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: A snippet extension to add easy rating to your entries/pages.
// - Date: 2012-06-24
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


/** 
 * Add a hook, so we can add hidden fields to entries, so the ratings don't 
 * get lost when editing an entry.
 */
$this->addHook(
    'in_pivotx_template',
    'entry-bottom',
    array('callback' => 'starratingTemplateHook' )
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

    $nrstars = getDefault($PIVOTX['config']->get('starrating_nrofstars'), 5);
    $minvotes = getDefault($params['votes_min'], 0);
    $currvotes = intval($extrafields['ratingcount']);

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
    jQuery('.starsubmit').hide();
    jQuery('input.star').rating({
        callback: function(value, link){
            jQuery.get("%path%starrating_submit.php?" + jQuery(this).attr('name') + "=" + this.value);
        }
    });
});
</script>
EOM;
        $html_head = str_replace('%path%', $PIVOTX['paths']['extensions_url']."starrating/", $html_head);
        $PIVOTX['extensions']->addHook('after_parse', 'insert_before_close_head', $html_head);
        $starrating_config['js_inserted'] = true;
    }

    $html = '<form class="starrating" action="%path%starrating_submit.php" method="get">';

    for ($i = 1; $i <= $nrstars; $i++) {
        $html .= '<input class="star" type="radio" name="%pagetype%-%uid%" value="' . $i . '" %checked' . $i . '% />';
    }
    $html .= '<input type="submit" class="starsubmit" value="Submit scores!" /></form>';

    if ($params['description'] && $currvotes >= $minvotes) {
        $html .= "<span class=\"star-description\">%description%</span>";
    }

    $description = getDefault($PIVOTX['config']->get('starrating_description'), "(%count% votes, averaging %average%)");

    $average = sprintf("%1.1f", $extrafields['ratingaverage']);
    $roundedaverage = round($extrafields['ratingaverage']);

    $html = str_replace('%description%', $description, $html);
    $html = str_replace('%pagetype%', $pagetype, $html);
    $html = str_replace('%uid%', $vars[$pagetype]['uid'], $html);
    $html = str_replace('%count%', $currvotes, $html);
    $html = str_replace('%average%', $average, $html);
    $html = str_replace('%path%', $PIVOTX['paths']['extensions_url']."starrating/", $html);
    for ($i = 1; $i <= $nrstars; $i++) {
        $html = str_replace('%checked'.$i.'%', ( ($roundedaverage==$i) ? "checked='checked'" : ""), $html);
    }

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

    $minvotes = getDefault($params['votes_min'], 0);
    $currvotes = intval($extrafields['ratingcount']);
    $html = '';

    if ($currvotes >= $minvotes) {
        $html = <<< EOM
<span class="star starscore">&nbsp;</span><span class="star-label">%description%</span>
EOM;

        $description = getDefault($PIVOTX['config']->get('starrating_simpleaverage'), "(%average%)");

        $average = sprintf("%1.1f", $extrafields['ratingaverage']);
        $roundedaverage = round($extrafields['ratingaverage']);

        $html = str_replace('%description%', $description, $html);
        $html = str_replace('%average%', $average, $html);
        $html = str_replace('%path%', $PIVOTX['paths']['extensions_url']."starrating/", $html);
    }

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
    
    $ratingtype = getDefault($params['type'], 'entry');
    $minvotes = getDefault($params['votes_min'], 0);

    if ($ratingtype == 'entry' || $ratingtype == 'both') {
        $entriestable = safeString($PIVOTX['config']->get('db_prefix')."entries", true);
    }
    if ($ratingtype == 'page' || $ratingtype == 'both') {
        $pagestable = safeString($PIVOTX['config']->get('db_prefix')."pages", true);
    }
    $extratable = safeString($PIVOTX['config']->get('db_prefix')."extrafields", true);

    if ($ratingtype == 'entry') {
        $query = "SELECT e.uid, ef.contenttype
        FROM `$entriestable` AS e
        LEFT JOIN $extratable AS ef ON ( ef.target_uid = e.uid )
        WHERE ef.contenttype = 'entry'
        AND ef.fieldkey = 'ratingaverage'
        ORDER BY value DESC
        LIMIT $amount";
    } else if ($ratingtype == 'page') {
        $query = "SELECT p.uid, ef.contenttype
        FROM `$pagestable` AS p
        LEFT JOIN $extratable AS ef ON ( ef.target_uid = p.uid )
        WHERE ef.contenttype = 'page'
        AND ef.fieldkey = 'ratingaverage'
        ORDER BY value DESC
        LIMIT $amount";
    } else if ($ratingtype == 'both') {
        $query = "SELECT DISTINCT ef.target_uid uid, ef.contenttype
        FROM `$extratable` ef, `$pagestable` p, `$entriestable` e
        WHERE ef.fieldkey = 'ratingaverage'
        AND ((ef.contenttype = 'page' AND ef.target_uid = p.uid)
        OR (ef.contenttype = 'entry' AND ef.target_uid = e.uid))
        ORDER BY value DESC
        LIMIT $amount";
    }

    // initialize a temporary db..
    $db = new db(FALSE);

    $db->db_lowlevel->sql->query($query);

    $matches = $db->db_lowlevel->sql->fetch_all_rows();

    $output = "";

    if (!empty($matches)) {
        foreach($matches as $match) {
            $temp_format = $format;
            if ($match['contenttype'] == 'entry') {
                $entry = $db->read_entry($match['uid']);
                $temp_title = trimText($entry['title'], $trimlength);
                $temp_link  = $entry['link'];
                $temp_score = number_format($entry['extrafields']['ratingaverage'], 1);
                $temp_amount = $entry['extrafields']['ratingcount'];
            } else {
                $page = $PIVOTX['pages']->getPage($match['uid']);
                $temp_title = trimText($page['title'], $trimlength);
                $temp_link  = $page['link'];
                $temp_score = number_format($page['extrafields']['ratingaverage'], 1);
                $temp_amount = $page['extrafields']['ratingcount'];
            }

            if ($temp_amount >= $minvotes) {
                $temp_format = str_replace("%title%", $temp_title, $temp_format);
                $temp_format = str_replace("%link%", $temp_link, $temp_format);
                $temp_format = str_replace("%score%", $temp_score, $temp_format);
                $temp_format = str_replace("%amount%", $temp_amount, $temp_format);

                $output .= $temp_format;
            }

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

    $form->add( array(
        'type' => 'text',
        'name' => 'starrating_nrofstars',
        'label' => __('Number of stars'),
        'value' => '5',
        'error' => __('That\'s not a correct number!'),
        'text' => __('Amount of stars to show for people to vote from.'),
        'size' => 1,
        'isrequired' => 1,
        'validation' => 'integer|min=1|max=10'
    ));


    /**
     * Add the form to our (referenced) $form_html. Make sure you use the same key
     * as the first parameter to $PIVOTX['extensions']->getAdminForm
     */
    $form_html['starrating'] = $PIVOTX['extensions']->getAdminFormHtml($form, $starrating_config);


}



/**
 * Callback function for our hook..
 */
function starratingTemplateHook($content) {
    global $PIVOTX;

    $output = <<< EOM
<input id="extrafield-ratingaverage" name="extrafields[ratingaverage]" value='%ratingaverage%' type="hidden" />   
<input id="extrafield-ratingcount" name="extrafields[ratingcount]" value='%ratingcount%' type="hidden" />    
<input id="extrafield-ratings" name="extrafields[ratings]" value='%ratings%' type="hidden" />    
EOM;

    // For ease of use, just try to replace everything in $entry here:
    foreach($content as $key=>$value) {
        $output = str_replace("%".$key."%", $value, $output);
    }
    foreach($content['extrafields'] as $key=>$value) {
        if (is_array($value)) { $value = serialize($value); }
        $output = str_replace("%".$key."%", $value, $output);
    }
    // Don't keep any %whatever%'s hanging around..
    $output = preg_replace("/%([a-z0-9_-]+)%/i", "", $output);

    return $output;

}

?>
