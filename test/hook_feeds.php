<?php
// - Extension: TEST - Example hooks for RSS and Atom feeds
// - Version: 0.1
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: Some examples to demonstrate the hooks for RSS and Atom feeds.
// - Date: 2008-10-02
// - Identifier: test

$this->addHook(
    'feed_rss_template',
    'callback',
    "rssTemplateCallback"
    );


/**
 * Example Hook Extension function for 'feed_rss_template'
 *
 * @param string $template contains the RSS template (passed by reference)
 */
function rssTemplateCallback(&$template) {
    global $PIVOTX;
    
    // Do something with $template
    
}




$this->addHook(
    'feed_atom_template',
    'callback',
    "atomTemplateCallback"
    );


/**
 * Example Hook Extension function for 'feed_atom_template'
 *
 * @param string $template contains the RSS template (passed by reference)
 */
function atomTemplateCallback(&$template) {
    global $PIVOTX;
    
    // Do something with $template

}




$this->addHook(
    'feed_head',
    'callback',
    "feedHeadCallback"
    );

/**
 * Example Hook Extension function for 'feed_head'
 *
 * $replace will contain an array with all the %key% -> value pairs, used
 * for replacing in the Feed's head section
 *
 * Array (
 *   [%sitename%] => PivotX Powered
 *   [%title%] => My weblog
 *   [%sitename_safe%] => pivotx-powered
 *   [%title_safe%] => my-weblog
 *   [%link%] => http://example.org/weblog/
 *   ..
 * )
 * 
 * @param array $replace (passed by reference)
 */
function feedHeadCallback(&$replace) {
    global $PIVOTX;
    
    // Do something with $template
    
    // To view all elements, use 
    // print_r($replace);
}



$this->addHook(
    'feed_entry',
    'callback',
    "feedEntryCallback"
    );

/**
 * Example Hook Extension function for 'feed_entry'
 *
 * $replace will contain an array with all the %key% -> value pairs, used
 * for replacing in each of the Feed's entry sections
 *
 * Array (
 *   [%title%] => Entry title goes here
 *   [%subtitle%] =>
 *   [%link%] => http://example.org/archive/2008-09-29/test-3
 *   [%description%] => <p>Lorum ipsum dolor sit amet</p>
 * )
 *  
 * @param array $replace (passed by reference)
 */
function feedEntryCallback(&$replace) {
    global $PIVOTX;
    
    // Do something with $template

    // To view all elements, use 
    // print_r($replace);
    
}



$this->addHook(
    'feed_comment',
    'callback',
    "feedCommentCallback"
    );

/**
 * Example Hook Extension function for 'feed_comment'
 *
 * $replace will contain an array with all the %key% -> value pairs, used
 * for replacing in each of the Feed's comment sections
 * 
 * @param array $replace (passed by reference)
 */
function feedCommentCallback(&$replace) {
    global $PIVOTX;
    
    // Do something with $template

    // To view all elements, use 
    // print_r($replace);
    
}


$this->addHook(
    'feed_finish',
    'callback',
    "feedFinishCallback"
    );


/**
 * Example Hook Extension function for 'feed_rss_template'. $feed contains the
 * entire parsed feed, just before output.
 *
 * @param string $feed (passed by reference)
 */
function feedFinishCallback(&$feed) {
    global $PIVOTX;
    
    // Do something with $feed
    
}


?>
