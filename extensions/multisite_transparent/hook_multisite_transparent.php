<?php
// - Extension: Multisite Transparent
// - Version: 0.4
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: This hook will make the paths to resources (like images and CSS files) much cleaner for a site that is run by PivotX in multi-site mode..
// - Date: 2013-02-12
// - Identifier: multisite-transparent
// - Required PivotX version: 2.0.2

$this->addHook(
    'before_output',
    'callback',
    'multisiteTransparentHook'
    );


function multisiteTransparentHook(&$html) {
    global $PIVOTX;
    if ($PIVOTX['config']->get('minify_frontend')) {
        debug("The Multisite Transparent hook isn't run since Minify (in the frontend) is enabled");
    } elseif ($PIVOTX['multisite']->isActive()) {
        $html = str_replace('pivotx/' . $PIVOTX['multisite']->getPath(), '', $html);
    } 
    return $html;
}


?>
