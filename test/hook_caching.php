<?php
// - Extension: TEST - Example hooks for caching events
// - Version: 0.1
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: Some examples to demonstrate the hooks for events that are used in caching.
// - Date: 2009-09-15
// - Identifier: test

$this->addHook(
    'cache_before_write',
    'callback',
    "cacheBeforeWrite"
    );


/**
 * Example Hook Extension function for 'cache_before_write'
 *
 * @param string $template contains the filename of the HTML template (passed by reference)
 */
function cacheBeforeWrite(&$template) {
    global $PIVOTX;
    
    // Do something with $template
    
    debug("Running 'Cache before write' hook");
    
    debug('The template is ' . strlen($template) . ' characters long.');
    
    return true;
    
}



$this->addHook(
    'cache_after_write',
    'callback',
    "cacheAfterWrite"
    );


/**
 * Example Hook Extension function for 'cache_after_write'
 *
 * @param string $html contains the parsed HTML, as it was
 * just saved to the cache. (passed by reference)
 */
function cacheAfterWrite(&$html) {
    global $PIVOTX;
    
    // Do something with $html
    
    debug("Running 'Cache after write' hook");
    
    debug("The parsed HTML is: " . strlen($html) . " characters long.");
    
    return true;
    
}




$this->addHook(
    'cache_before_read',
    'callback',
    "cacheBeforeRead"
    );


/**
 * Example Hook Extension function for 'cache_before_read'
 *
 * @param string $template contains the filename of the HTML template (passed by reference)
 */
function cacheBeforeRead(&$template) {
    global $PIVOTX;
    
    // Do something with $template
    
    debug("Running 'Cache before read' hook");
    
    debug("The template is: " . $template);
    
    return true;
    
}




$this->addHook(
    'cache_after_read',
    'callback',
    "cacheAfterRead"
    );


/**
 * Example Hook Extension function for 'cache_after_read'
 *
 * @param string $html contains the parsed HTML, as it is
 * retrieved from the cache. (passed by reference)
 */
function cacheAfterRead(&$html) {
    global $PIVOTX;
    
    // Do something with $html
    
    debug("Running 'Cache after read' hook");
    
    debug("The parsed HTML is: " . strlen($html) . " characters long.");
    
    return true;
    
}




$this->addHook(
    'cache_missed_read',
    'callback',
    "cacheMissedRead"
    );


/**
 * Example Hook Extension function for 'cache_missed_read'
 *
 * @param string $template contains the filename of the HTML template (passed by reference)
 */
function cacheMissedRead(&$template) {
    global $PIVOTX;
    
    // Do something with $template
    
    debug("Running 'Cache missed read' hook");
    
    debug("Apparently, we have no cached version of this page yet");
    
    return true;
    
}



$this->addHook(
    'cache_clear',
    'callback',
    "cacheClear"
    );


/**
 * Example Hook Extension function for 'cache_clear'
 *
 * @param string $template contains the (partial) filename of the files that
 * will be removed.
 */
function cacheClear(&$template) {
    global $PIVOTX;
    
    // Do something with $template
    
    debug("Running 'Cache clear' hook");
    
    debug('The (partial) filename is ' . $template . '.');
    
    return true;
    
}



?>
