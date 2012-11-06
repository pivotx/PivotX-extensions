<?php
// - Extension: oEmbed
// - Version: 0.13
// - Author: Two Kings // Lodewijk Evers
// - Email: lodewijk@twokings.nl
// - Site: http://extensions.pivotx.net/entry/5/oembed
// - Description: Add [[embed]] snippets to your entries and pages
// - Date: 2012-11-06
// - Identifier: oembed
// - Required PivotX version: 2.2

if($PIVOTX['config']->get('db_model')=='mysql') {
    // only load the sql for the extension if it is needed
    $oembedbasedir = dirname(__FILE__);
    if(file_exists($oembedbasedir.'/oembed_sql.php')) {
        require_once($oembedbasedir.'/oembed_sql.php');
    }
}
/*
// silentify
 else {
    debug("oEmbed is working without database\noEmbed will still work, but it can't use the cache so it will be slower.");
}
*/

// Register 'embed' as a smarty tag.
$PIVOTX['template']->register_function('oembed', 'smarty_oembed');
$PIVOTX['template']->register_function('embed', 'smarty_oembed');

/**
 * oEmbed snippet
 *
 * @param array $params
 * @param object $smarty
 * @return unknown
 */
function smarty_oembed($params, &$smarty) {
    global $PIVOTX;

    $params = clean_params($params);

    if (empty($params['url'])) {

        debug("oEmbed called without an url.");
        debug_printr($params);
        return '<span class="error">You need at least an url to embed.</span>';

    }

    // check if installation is okay
    if($PIVOTX['config']->get('db_model')=='mysql') {
        oembedCheckTables();
    }

    // set default parameters
    $params['maxwidth'] = getDefault($params['width'], $PIVOTX['config']->get('oembed_maxwidth'));
    $params['maxheight'] = getDefault($params['height'], $PIVOTX['config']->get('oembed_maxheight'));
    $params['provider'] = getDefault($params['provider'], 'default');

    // try to get the code from the cache
    $oembed = oembedLoadContent($params['url'], 'embed', $params['maxwidth'], $params['maxheight'], $params['provider']);

    if($params['assign']) {
        $smarty->assign($params['assign'], $oembed);
    } else {
    	return $oembed;
    }
}

// Register a hook, to insert oembed tags in the bookmarklet..
$this->addHook('begin_bookmarklet', 'callback', 'oembedBookmarkletCallback');

/**
 * oembedBookmarkletCallback
 *
 * Callback function, which is hooked into the bookmarklet: When the bookmarklet
 * is initialised, this function will be called. If the page that is opened
 * is an oembed provider, we return the appropriate [[embed]] tag.
 */
function oembedBookmarkletCallback(&$entry) {

    if (!empty($_GET['url'])) {
        $embedcode = oembedLoadContent($_GET['url'], 'silent');

        if (!empty($embedcode)) {
            $entry['introduction'] .= sprintf("\n\n<p>[[embed url=\"%s\"]]</p>\n\n", $_GET['url']);
        }

    }
}

/**
 * oembedLoadContent
 *
 * Checks in the cache table if the url has been seen recently
 * if the url is not cached, fetch it from the provider through oembed and add it to the cache
 * return te cached response
 *
 * @param string $url
 * @param int $maxwidth
 * @param int $maxheight
 * @param string $provider
 *
 * @return string
 */
function oembedLoadContent($url, $mode='embed', $maxwidth=false, $maxheight=false, $provider="default") {
    global $PIVOTX;

    $url = trim($url);
    //debug("oembedLoadContent('$url, $maxwidth, $maxheight, $provider'");

    if(!function_exists('json_decode')) {
        // cache will not work if json functions don't exist
        debug("oembedLoadContent will not cache because json_decode is only available with PHP 5 >= 5.2.0 or PECL json >= 1.2.0 installed\nusing jquery fallback");

        // go to jquery fallback
        $cachedembed = false;
    } elseif ($PIVOTX['config']->get('db_model')!='mysql') {
        // cache will not work if json functions don't exist
        debug("oembedLoadContent will not cache because the recommended mysql database is not used\nusing jquery fallback");

        // go to jquery fallback
        $cachedembed = false;
    } else {
        // Get the default values for the parameters, if not specified.

        // check cache
        $oembed = new OembedCacheSql();
        $cachedresource = $oembed->getOembedCache($url, $maxwidth, $maxheight);
        //debug("getOembedCache($url, $maxwidth, $maxheight)");
        //debug_printr($cachedresource);

        // fetch external resource if not cached
        if ($cachedresource['response']) {
            // return cached resource
            $outputresource = unserialize($cachedresource['response']);
            debug("loading oEmbed resource from cache: ".$cachedresource['source_url']);
            //debug_printr($outputresource);
        } else {
            $cachedresource['oembed_uid'] = (!empty($cachedresource['response']))?$cachedresource['oembed_uid']:null;

            // check if a cacheable provider exists
            $endpoint = oembedCheckEndpoint($url, $provider);

            // get external resource
            $full_url = $endpoint."?url=".$url."&maxwidth=".$maxwidth."&maxheight=".$maxheight."&format=json";

            debug("adding new oEmbed resource to cache: ".$full_url);

            $externalresource = file_get_contents($full_url);
            // this function is php 5.2.0 or higher but in that case we wouldn't even get here
            $externalresource = json_decode($externalresource);
            //debug_printr($externalresource);

            // if $externalresource isn't an array or stdobject, we've got nothing..
            if (empty($externalresource)) {
                $cachedembed = false;
            }

            // save external resource to cache
            $oembed->saveOembedCache(array(
                'oembed_uid' => $cachedresource['oembed_uid'],
                'source_url' => $url,
                'max_width' => $maxwidth,
                'max_height' => $maxheight,
                'provider_url' => $endpoint,
                'response' => serialize($externalresource),
                'status' => 0
            ));
            // return external resource
            $outputresource = $externalresource;
        }

        switch($outputresource->type) {
            case 'video':
            case 'rich':
                $cachedembed = $outputresource->html;

                break;
            case 'photo':
                $titleclean = str_replace(array('"', "'"), array('&quot;', '&#039;'), htmlspecialchars($outputresource->title));
                $image = '<img src="'.$outputresource->url.'" alt="'. $titleclean. '" width="'. 
                    $outputresource->width .'" height="'. $outputresource->height .'" />';
                if($outputresource->author_url) {
                    $image = '<a href="'. $outputresource->author_url . '">'.$image."</a>";
                }
                $cachedembed = $image;

                break;
            default:
                // TODO: fix special cases
                $cachedembed = false;
                // ugly dump everything fallback
                debug("oEmbed got an unknown type:");
                debug_printr($outputresource);

                break;
        }
    }

    if ($mode=="silent" && !$cachedembed) {

        // display nothing if no cached content is found and the bookmarklet was called
        return false;

    } elseif($cachedembed) {

        // display cached content
        return $cachedembed;

    } else {

        // on the fly fallback for content that is not cached (and if a embed tag was called)
        if(empty($params['title'])) {
            //$title = 'loading something from '. $provider;
            $title = $url;
        }
        $PIVOTX['extensions']->addHook('after_parse', 'callback', 'oembedIncludeCallback');

        debug('oEmbed jquery fallback for: '. $url);

        $template = '<a href="%oembedurl%" class="oembed">%oembedtitle%</a>';
        $template = str_replace('%oembedurl%', $url, $template);
        $template = str_replace('%oembedtitle%', $title, $template);

        return $template;

    }
}

/**
 * lookup a provider
 */
function oembedCheckEndpoint($url, $provider='default') {
    global $PIVOTX;

    $endpoint = false;

    // include providers
    $oembedbasedir = dirname(__FILE__);
    if(file_exists($oembedbasedir.'/oembed_providers.php')) {
        include($oembedbasedir.'/oembed_providers.php');
    } else {
        debug('The oEmbed installation is broken, the providers file is not accessible.');
        // there's something serieusly wrong in the installation
        //$providers['default'] = array('endpoint' => 'http://oohembed.com/oohembed/', 'format' => 'json');
        $providers['default'] = array('endpoint' => 'http://api.embed.ly/v1/api/oembed', 'format' => 'json');

    }

    // check if the provider is known and set the endpoints
    if($provider!='default') {
        $params['custom_provider'] = $provider;
        // debug('custom oEmbed provider: '.$provider);
        $patterns = array_keys($providers);
        foreach($patterns as $key => $pattern) {
            if(stristr($url, $pattern)) {
                $provider = $pattern;
                // debug('custom oEmbed endpoint exists in defaults: '.$provider);
                break;
            }
        }
        if($params['custom_provider'] == $provider) {
            debug('custom oEmbed endpoint does not exist in defaults: '.$params['custom_provider'] ."\ntrying to continue with custom oEmbed provider endpoint");
            $providers[$provider] = array('endpoint' => $provider, 'format' => 'json');
        }
    } else {
        $patterns = array_keys($providers);
        //debug_printr($patterns);
        foreach($patterns as $key => $pattern) {
            //debug($pattern);
            if(stristr($url, $pattern)) {
                $provider = $pattern;
                break;
            }
        }
        debug('default oEmbed endpoint: '.$providers[$provider]['endpoint']);
    }

    $endpoint = $providers[$provider]['endpoint'];
    return $endpoint;
}

/**
 * Try to insert the includes for oEmbed in the <head> section of the HTML
 * that is to be outputted to the browser. Inserts Jquery if not already
 * included. (This is just the default "thickboxIncludeCallback" function
 * adapted to oEmbed.)
 *
 * @param string $html
 */
function oembedIncludeCallback(&$html) {
    global $PIVOTX;
    static $initialized = false;

    if ($initialized) {
        return;
    }
    $initialized = true;

    // If we've set the hidden config option for 'never_jquery', just return without doing anything.
    if ($PIVOTX['config']->get('never_jquery') == 1) {
        debug("jQuery is disabled by the 'never_jquery' config option. oEmbed won't work. You must enable jQuery first.");
        return;
    }
    
    OutputSystem::instance()->enableCode('jquery');
    OutputSystem::instance()->addCode(
        'oembed-js',
        OutputSystem::LOC_HEADEND,
        'script',
        array(),
        'jQuery(document).ready(function($) { $("a.oembed").oembed(); });'
    );
    OutputSystem::instance()->addCode(
        'oembed-jssrc',
        OutputSystem::LOC_HEADEND,
        'script',
        array('src'=>$PIVOTX['paths']['extensions_url'].'oembed/js/jquery.oembed.min.js')
    );
}

/**
 * Check if database table for oEmbed exist, otherwise create it
 * run other update functions ans set variables
 */
function oembedCheckTables() {
    global $PIVOTX;

    if($PIVOTX['config']->data['oembed_version']<1) {
        $result = oembedInstallTables();
        if($result) {
            $PIVOTX['config']->set('oembed_version', '1');
            debug('installed oEmbed cache tables');
        } else {
            debug('installation of oEmbed cache tables went wrong - please create or modify your database manually');
        }
    }

    if ($PIVOTX['config']->data['oembed_version'] > 0) {
        if($PIVOTX['config']->data['oembed_version'] < 2) {
            $PIVOTX['config']->set('oembed_version', '2');
            $PIVOTX['config']->set('oembed_maxwidth', '400');
            $PIVOTX['config']->set('oembed_maxheight', '260');
            debug('updated oEmbed cache tables to version 2, installed default values');
        }

        if($PIVOTX['config']->data['oembed_version'] < 3) {
            // stuff to do for the next update
            $x = oembedUpdateTables_3();
            $PIVOTX['config']->set('oembed_version', '3');
            debug('updated oEmbed cache tables to version 3');
        }

        if($PIVOTX['config']->data['oembed_version'] < 4) {
            // stuff to do for the next update
            $x = oembedUpdateTables_4();
            $PIVOTX['config']->set('oembed_version', '4');
            debug('updated oEmbed cache tables to version 4');
        }

    }
}

/**
 * Create basic oEmbed database table
 */
function oembedInstallTables() {
    global $PIVOTX;

    $oembed = new OembedCacheSql();

    $oembed->sql->query("SHOW TABLES LIKE '" . $PIVOTX['config']->get('db_prefix') . "%'");
    $tables = $oembed->sql->fetch_all_rows('no_names');
    $tables = make_valuepairs($tables, '', '0');

    if (!in_array($oembed->oemcachetable, $tables)) {
        $queries[] = "CREATE TABLE IF NOT EXISTS ".$oembed->oemcachetable." (
            oembed_uid int(10) NOT NULL auto_increment,
            source_url mediumtext NOT NULL,
            provider_url mediumtext NOT NULL,
            last_updated timestamp NOT NULL,
            response text NOT NULL,
            status tinyint(4) NOT NULL,
            PRIMARY KEY    (oembed_uid)
        );";
        $queries[] = "ALTER TABLE ".$oembed->oemcachetable." CHANGE last_updated last_updated timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ";
        $queries[] = "ALTER TABLE ".$oembed->oemcachetable." ADD INDEX source_urls (source_url (5))";

        foreach($queries as $query) {
            $oembed->sql->query($query);
        }
        return true;
    }

    // something went wrong - the table probably exists
    return false;
}
/**
 * Update tables to version 3
 */
function oembedUpdateTables_3() {
    global $PIVOTX;

    $maxwidth = getDefault(400, $PIVOTX['config']->get('oembed_maxwidth'));
    $maxheight = getDefault(260, $PIVOTX['config']->get('oembed_maxheight'));

    $oembed = new OembedCacheSql();
    $queries[] = "ALTER TABLE ".$oembed->oemcachetable." ADD COLUMN max_width int(11) NULL DEFAULT ".$maxwidth;
    $queries[] = "ALTER TABLE ".$oembed->oemcachetable." ADD COLUMN max_height int(11) NULL DEFAULT ".$maxheight;

    foreach($queries as $query) {
        $oembed->sql->query($query);
    }
	// lets just pretend that worked
    return true;
}
/**
 * Update tables to version 4
 */
function oembedUpdateTables_4() {
    global $PIVOTX;

    $maxwidth = getDefault(640, $PIVOTX['config']->get('oembed_maxwidth'));
    $maxheight = getDefault(385, $PIVOTX['config']->get('oembed_maxheight'));

    return true;
}

// Add a hook to the scheduler, to periodically empty the cache tables
$this->addHook(
    'scheduler',
    'callback',
    'oembedSchedulerCallback'
    );

function oembedSchedulerCallback() {
	global $PIVOTX;
    // check if installation is okay
    if($PIVOTX['config']->get('db_model')=='mysql') {
	    $oembed = new OembedCacheSql();
	    $lastweek = time() - (7 * 24 * 60 * 60);
	    $queries[] = sprintf("DELETE FROM %s WHERE last_updated < %d", $oembed->oemcachetable, $lastweek);

	    foreach($queries as $query) {
	        $oembed->sql->query($query);
	    }
	    debug('cleaned up old entries from the oEmbed cache table');
		// lets just pretend that worked
	    return true;
    }
}
