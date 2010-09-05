<?php
// - Extension: Making links
// - Version: 0.1
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: An example for adding hooks to make_link (for pages and entries). Only useful as an example to figure out how to write your own hook for make_link.
// - Date: 2008-01-06

// Add the hook for make_link for pages..
$this->addHook('make_link', 'pages', "hookMakeLinkPagesCallback");

// Add the hook for make_link for entries..
$this->addHook('make_link', 'entries', "hookMakeLinkEntriesCallback");

// Add the hook for make_link for archives..
$this->addHook('make_link', 'archives', "hookMakeLinkArchivesCallback");


/**
 * Callback for make_link#pages hook. The $link parameter is passed by
 * reference, so any modifications to it will be passed back to the
 * calling function. This particular example will make the link show
 * up as UPPERCASE.
 *
 * The $params variable contains an array with the title, uri, uid and
 * date of the page.
 *
 * Tip: use $PIVOTX['config']->get('mod_rewrite') to check if 'pretty URLs' is
 * enabled in config. If the value is 0 or "", it is disabled.
 *
 * @param string $link
 * @param array $params
 */
function hookMakeLinkPagesCallback(&$link, $params) {
    global $PIVOTX;

    // uncomment the following line to see what's in the $params variable.
    //echo "<pre>\n";
    //print_r($params);
    //echo "</pre>\n";

    // Change the $link to uppercase.
    $link = strtoupper($link);


}


/**
 * Callback for make_link#entries hook. The $link parameter is passed by
 * reference, so any modifications to it will be passed back to the
 * calling function. This particular example will make the link show
 * up as UPPERCASE.
 *
 * The $params variable contains an array with the title, uri, uid and
 * date of the entry.
 *
 * Tip: use $PIVOTX['config']->get('mod_rewrite') to check if 'pretty URLs' is
 * enabled in config. If the value is 0 or "", it is disabled.
 *
 * @param string $link
 * @param array $params
 */
function hookMakeLinkEntriesCallback(&$link, $params) {
    global $PIVOTX;

    // uncomment the following line to see what's in the $params variable.
    //echo "<pre>\n";
    //print_r($params);
    //echo "</pre>\n";

    // Change the $link to uppercase.
    $link = strtoupper($link);

}


/**
 * Callback for make_link#entries hook. The $link parameter is passed by
 * reference, so any modifications to it will be passed back to the
 * calling function. This particular example will make the link show
 * up as UPPERCASE.
 *
 * The $params variable contains an array with the date that falls
 * somewhere in the range of the archive.
 * You can get the current weblog and the archive-unit(yearly, monthly,
 * weekly or 'none') from the $weblog object.
 *
 * Tip: use $PIVOTX['config']->get('mod_rewrite') to check if 'pretty URLs' is
 * enabled in config. If the value is 0 or "", it is disabled.
 *
 * @param string $link
 * @param array $params
 */
function hookMakeLinkArchivesCallback(&$link, $params) {
    global $PIVOTX;

    // uncomment the following line to see what's in the $params variable.
    //echo "<pre>\n";
    //print_r($params);
    //echo "</pre>\n";

    // Get the current weblog:
    $this_weblog = $PIVOTX['weblogs']->getCurrent();

    // Get the archive unit:
    // $archive_unit = $PIVOTX['weblogs']->get('', 'archive_unit');

    // Change the $link to uppercase.
    $link = strtoupper($link);

}


?>
