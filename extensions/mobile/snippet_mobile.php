<?php
// - Extension: Mobile Browser Extension
// - Version: 0.8
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: A snippet extension to detect mobile browsers, and redirect them to a specific page.
// - Date: 2013-08-24
// - Identifier: mobile
// - Required PivotX version: 2.2.3

global $PIVOTX, $mobiledetect_config;

$mobiledetect_config = array(
    'mobile_detection' => 1,
    'mobile_redirect' => 1,
    'mobile_redirectlink' => __("Click here to visit the mobile version of this site."),
    'mobile_domain' => "m.example.org",
    'mobile_frontpage' => "mobile/front.tpl",
    'mobile_archivepage' => "mobile/archive.tpl",
    'mobile_entrypage' => "mobile/entry.tpl",
    'mobile_page' => "mobile/page.tpl",
    'mobile_extrapage' => "mobile/search.tpl",
    'mobile_use_weblogdir' => 0,
    'mobile_cookie_pfx' => '',
    'mobile_treat_tablet_as_mobile' => 0
);


$this->addHook(
    'before_parse',
    'callback',
    "mobileHook"
    );

/**
 * Adds the hook for mobileAdmin()
 *
 * @see mobileAdmin()
 */
$this->addHook(
    'configuration_add',
    'mobile',
    array("mobileAdmin", "Mobile version")
);





// Register 'mobiledetect' as a smarty tag.
$PIVOTX['template']->register_function('mobiledetect', 'smarty_mobiledetect');


// Register 'mobilelink' as a smarty tag.
$PIVOTX['template']->register_function('mobilelink', 'smarty_mobilelink');

// Check if we need to set cookies..
$mobile_cookiename = 'mobileversion';
// prefix the cookie name?
if ($PIVOTX['config']->get('mobile_cookie_pfx')) {
    $mobile_cookiename = $PIVOTX['config']->get('mobile_cookie_pfx') . $mobile_cookiename;
}

if (!empty($_GET['mobilecookie'])) {
    $sess = $PIVOTX['session'];
    if ($_GET['mobilecookie']=="1") {
        setcookie($mobile_cookiename, "full", time() + $sess->cookie_lifespan, $sess->cookie_path, $sess->cookie_domain );
        $_COOKIE[$mobile_cookiename]="full";
    } else {
        setcookie($mobile_cookiename, "", time() + $sess->cookie_lifespan, $sess->cookie_path, $sess->cookie_domain );
        unset($_COOKIE[$mobile_cookiename]);  
    }
}

// Define isTablet, if it's not already..
if (!function_exists("isTablet")) {
    /**
     * Determine if the current browser is a tablet device or not.
     *
     * For now this is specific for iPads, but more devices can be added, once they
     * are on the market.
     *
     * @return boolean
     */
    function isTablet() {

        $ua = strtolower($_SERVER['HTTP_USER_AGENT']);

        $isTablet = strpos($ua, 'ipad') !== false;

        // var_dump($isTablet);

        return $isTablet;
    }
}

// If enabled, detect mobile browsers, and redirect or present a link..
if ( defined('PIVOTX_INWEBLOG') && ($PIVOTX['config']->get('mobile_domain') != $_SERVER['HTTP_HOST']) ) {

    if ($PIVOTX['config']->get('mobile_detection')==1) {

        // Is the current browser a Mobile Device? (this includes tablets)
        $isMobile = isMobile();

        // Should we treat tablet devices as 'Full browsers'?
        $allowTablet = ( isTablet() && !$PIVOTX['config']->get('mobile_treat_tablet_as_mobile') );

        if (!$isMobile || $allowTablet) {
            return "";
        } else {
            $link = "http://" . $PIVOTX['config']->get('mobile_domain') . $_SERVER['REQUEST_URI'];
            $linktext = sprintf("<a href='%s'>%s</a>", $link, $PIVOTX['config']->get('mobile_redirectlink') );
            
            if ( ($PIVOTX['config']->get('mobile_redirect')==1) && empty($_COOKIE[$mobile_cookiename]) ) {
                header("location: " . $link);
                echo "<script type=\"text/javascript\">window.location=\"".$link."\";</script>\n";
                echo $linktext;
                die();    
            } else {
                $linktext = "<div style='border: 1px solid #000; background-color: #FFF; padding: 4px;'>$linktext</div>";
                $this->addHook(
                    'after_parse',
                    'insert_after_open_body',
                    $linktext
                );
            }
        }
    }
}



function mobileHook(&$params) {
    global $PIVOTX;

    // Only run this hook if we are on a mobile device (or a tablet and we 
    // treat it as a mobile).
    $allowTablet = ( isTablet() && !$PIVOTX['config']->get('mobile_treat_tablet_as_mobile') );
    if (!isMobile() || $allowTablet) {
        return;
    }
    $mobile_cookiename = 'mobileversion';
    // prefix the cookie name?
    if ($PIVOTX['config']->get('mobile_cookie_pfx')) {
        $mobile_cookiename = $PIVOTX['config']->get('mobile_cookie_pfx') . $mobile_cookiename;
    } 
    // Only change the templates when we're at the correct (sub)domain or we 
    // haven't chosen to see the full version.
    if (($PIVOTX['config']->get('mobile_domain') != $_SERVER['HTTP_HOST']) || !empty($_COOKIE[$mobile_cookiename])){
        return;
    }

    // Make sure we're allowed to override the templates.
    $PIVOTX['config']->set('allow_template_override', 1);
    
    $hostname = $_SERVER['http_host'];
    $guesser1 = '';
    $guesser2 = '';
    
    switch ($params['action']) {
        
        case "weblog":
            $params['template'] = $PIVOTX['config']->get('mobile_frontpage');
            $guesser1 = 'front';
            $guesser2 = 'weblog';
            // archive set?
            if ($params['archive'] != '') {
                $params['template'] = $PIVOTX['config']->get('mobile_archivepage');
                $guesser1 = 'archive';
                if ($params['template'] == '') {
                    $params['template'] = $PIVOTX['config']->get('mobile_frontpage');
                    $guesser1 = 'front';
                }
            }
            break;
        
        case "entry":
            $params['template'] = $PIVOTX['config']->get('mobile_entrypage');
            $guesser1 = 'entry';
            break;
                
        case "page":
            $params['template'] = $PIVOTX['config']->get('mobile_page');
            $guesser1 = 'page'; 
            break;

        case "tag":
        case "search":
            $params['template'] = $PIVOTX['config']->get('mobile_extrapage');
            $guesser1 = 'search';
            $guesser2 = 'extra';
            break;

        // This default case is needed because of a bug in renderTag in PivotX 
        // before version 2.3.2, that causes the "tag" case above to not work.
        default:
            $params['template'] = $PIVOTX['config']->get('mobile_extrapage');
            $guesser1 = 'front';
            break;
    }
    
    // overwrite or add weblog folder to template name?
    if ($PIVOTX['config']->get('mobile_use_weblogdir')) {
        $dirmob = dirname($params['template']);
        $currblog = $PIVOTX['weblogs']->getCurrent();
        $weblog = $PIVOTX['weblogs']->getWeblog($currblog);
        $dirname = dirname($weblog['front_template']);
        $replcnt = 0;
        $repltpl = '';
        if ($dirmob != '' && $dirmob != '.') {
            $repltpl = preg_replace('#'.$dirmob.'#', $dirname, $params['template'], 1 , $replcnt);
        }
        if ($replcnt == 0) {
            $repltpl = $dirname . '/' . $params['template'];
        }
        if (!file_exists($PIVOTX['paths']['templates_path'].$repltpl)) {
            debug('Mobile template does not exist in weblog folder: ' . $repltpl . ' using extension defined default.');
        } else {
            $params['template'] = $repltpl;
        }       
    }
    
    if (!file_exists($PIVOTX['paths']['templates_path'].$params['template'])) {
        debug('Your mobile template does not exist: ' . $params['template'] . ' trying to guess a fall-back template.');
        $params['template'] = templateGuess($params['action']);
        if ($params['template'] == '' ) { $params['template'] = templateGuess($guesser1); }
        if ($params['template'] == '' && $guesser2 != '') { $params['template'] = templateGuess($guesser2); }
        if ($params['template'] == '' ) { $params['template'] = templateGuess('front'); }
        if ($params['template'] == '' ) { debug('Could not guess the right template!'); }
    }
}


/**
 * Detect a mobile browser, and perhaps redirect them to a different page..
 *
 * Not very useful, since pivotx can do this automatically by checking the option in the 'mobile version'
 * configuration screen. If, however, you'd like to redirect people to a specific page, you can use
 * [[ mobiledetect redirect="http://example.org/page/mobile" ]]
 *
 * Note: insert this tag at the very top of your template, for the best results..
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_mobiledetect($params, &$smarty) {
    global $PIVOTX, $mobiledetect_config;

    // Is the current browser a Mobile Device? (this includes tablets)
    $isMobile = isMobile();

    // Should we treat tablet devices as 'Full browsers'?
    $allowTablet = ( isTablet() && !$PIVOTX['config']->get('mobile_treat_tablet_as_mobile') );

    if (!$isMobile || $allowTablet) {
        return "";
    } else {
        
        // Redirect to a different page..
        if (!empty($params['redirect'])) {
            header("location: ".$params['redirect']);
            echo "<script type=\"text/javascript\">window.location=\"".$params['redirect']."\";</script>\n";
            echo "<a href=\"".$params['redirect']."\">Go to the mobile version of this page at ".$params['redirect'].".</a>";
            die();
        }


    }

}


/**
 * Link to or from a mobile version of the site, giving users the option to switch
 * between the two versions. 
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_mobilelink($params, &$smarty) {
    global $PIVOTX, $mobiledetect_config; 

    // Make sure $to is either 'full' or 'mobile'..
    $to = getDefault($params['to'], "mobile");

    if ($to != "mobile") {
        $link = $PIVOTX['config']->get('canonical_host');
        $query = 'mobilecookie=1';
        $text = getDefault($params['text'], __("View the full version of this site."));
    } else {
        $link = "http://" . $PIVOTX['config']->get('mobile_domain');
        $query = 'mobilecookie=-1';
        $text = getDefault($params['text'], __("View the mobile version of this site."));   
    }

    if (strlen($_SERVER['REQUEST_URI'])>1) {
        $link .= $_SERVER['REQUEST_URI'];
    }

    if (strpos($link, 'mobilecookie=') > 0) {
        $link = str_replace(array('mobilecookie=1', 'mobilecookie=-1'), $query, $link);
    } elseif (strpos($link, '?') === false) {
        $link .= '?' . $query;
    } else {
        $link .= '&amp;' . $query;
    }

    
    $output = sprintf("<a href='%s'>%s</a>", $link, $text);
    
    return $output; 

}


/**
 * The configuration screen for Mobile version
 *
 * @param object $form_html
 */
function mobileAdmin(&$form_html) {
    global $mobiledetect_config, $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(3);

    // When running for the first time, set the default options, if they are not available in config..
    foreach ($mobiledetect_config as $key => $value) {
        if ($PIVOTX['config']->get($key) === false) {
            $PIVOTX['config']->set($key, $value);
        }
    }


    $form = $PIVOTX['extensions']->getAdminForm('mobile', 'mobileAdmin');


    $form->add( array(
        'type' => 'checkbox',
        'name' => 'mobile_detection',
        'label' => __("Detect mobile visitors"),
        'text' => __("Yes, detect visitors that use a mobile browser.")
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'mobile_redirectlink',
        'label' => __("Redirect link"),
        'size' => 60,
    ));
    
    $form->add( array(
        'type' => 'checkbox',
        'name' => 'mobile_redirect',
        'label' => __("Immediate redirect"),
        'text' => __("Yes, redirect the visitor to the mobile version immediately.<br/>If this is disabled, the user will be presented with a link at the top of the page.")
    ));

    $form->add( array(
       'type' => 'custom',
       'text' => "<tr><td colspan='3'><hr size='1' noshade='1' /></td></tr>"
        
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'mobile_domain',
        'label' => __("Mobile Domain name"),
        'value' => '',
        'error' => __('That\'s not a proper domain name!'),
        'text' => __("The domain name of the mobile version, for example m.example.org.<br/>Do not include the 'http://' part.<br/>
        You can also use the regular www.example.org to display the mobile layout."),
        'size' => 40,
        'isrequired' => 1,
        'validation' => 'ifany|string|minlen=5|maxlen=80'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'mobile_frontpage',
        'label' => __("Frontpage Template") . " " . makeJtip(__('Frontpage Template'), 
        __('The Template which determines the layout of the index page of this weblog.')),
        'value' => '',
        'error' => __('That\'s not a proper filename!'),
        'text' => "",
        'size' => 40,
        'isrequired' => 1,
        'validation' => 'ifany|string|minlen=5|maxlen=80'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'mobile_archivepage',
        'label' => __('Archivepage Template') . " " . makeJtip(__('Archivepage Template'), 
        __('The Template which determines the layout of your archives. This can be the same as "Frontpage Template".')),
        'value' => '',
        'error' => __('That\'s not a proper filename!'),
        'text'=> "",
        'size' => 40,
        'isrequired' => 0,
        'validation' => 'ifany|string|minlen=5|maxlen=80'));

    $form->add( array(
        'type' => 'text',
        'name' => 'mobile_entrypage',
        'label' => __("Entry Template") . " " . makeJtip(__('Entrypage Template'), 
        __('The Template which determines the layout of single entries.')),
        'value' => '',
        'error' => __('That\'s not a proper filename!'),
        'text' => "",
        'size' => 40,
        'isrequired' => 1,
        'validation' => 'ifany|string|minlen=5|maxlen=80'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'mobile_page',
        'label' => __("Page Template") . " " . makeJtip(__('Page Template'), 
        __('The Template that defines how a page will look like if you haven\'t specified a template for it.')),
        'value' => '',
        'error' => __('That\'s not a proper filename!'),
        'text' => "",
        'size' => 40,
        'isrequired' => 1,
        'validation' => 'ifany|string|minlen=5|maxlen=80'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'mobile_extrapage',
        'label' => __("Extra Template") . " " . makeJtip(__('Extra Template'), 
        __('The Template that defines how a search, tag or other special page will look like.')),
        'value' => '',
        'error' => __('That\'s not a proper filename!'),
        'text' => "",
        'size' => 40,
        'isrequired' => 1,
        'validation' => 'ifany|string|minlen=5|maxlen=80'
    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'mobile_use_weblogdir',
        'label' => __("Use folder name of active weblog"),
        'text' => __("Yes, use the folder name of the active weblog in stead of the mobile folder name (or add it in front of the template name).")
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'mobile_cookie_pfx',
        'label' => __("Mobile cookie prefix"),
        'value' => '',
        'error' => __('That\'s not a proper cookie prefix!'),
        'text' => __("Here you can specify whether you want the cookie name created when linking to the full site to have a prefix."),
        'size' => 20,
        'isrequired' => 0,
        'validation' => 'ifany|string|minlen=5|maxlen=80'
    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'mobile_treat_tablet_as_mobile',
        'label' => __("Use Mobile version for tablets"),
        'text' => __("Yes, show the Mobile version of the website to visitors on tablet devices, such as the iPad.")
    ));


    /**
     * Add the form to our (referenced) $form_html. Make sure you use the same key
     * as the first parameter to $PIVOTX['extensions']->getAdminForm
     */
    $form_html['mobile'] = $PIVOTX['extensions']->getAdminFormHtml($form, $mobiledetect_config);


}


?>
