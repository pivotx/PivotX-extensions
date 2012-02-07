<?php
// - Extension: Mobile Browser Extension
// - Version: 0.7
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: A snippet extension to detect mobile browsers, and redirect them to a specific page.
// - Date: 2012-02-07
// - Identifier: mobile
// - Required PivotX version: 2.2.3

global $PIVOTX, $mobiledetect_config;

$mobiledetect_config = array(
    'mobile_detection' => 1,
    'mobile_redirect' => 1,
    'mobile_redirectlink' => "Click here to visit the mobile version of this site.",
    'mobile_domain' => "m.example.org",
    'mobile_frontpage' => "mobile/frontpage_template.html",
    'mobile_entrypage' => "mobile/entrypage_template.html",
    'mobile_page' => "mobile/page_template.html",
    'mobile_extrapage' => "mobile/search_template.html",
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
if (!empty($_GET['mobilecookie'])) {
    $sess = $PIVOTX['session'];
    if ($_GET['mobilecookie']=="1") {
        setcookie("mobileversion", "full", time() + $sess->cookie_lifespan, $sess->cookie_path, $sess->cookie_domain );
        $_COOKIE['mobileversion']="full";
    } else {
        setcookie("mobileversion", "", time() + $sess->cookie_lifespan, $sess->cookie_path, $sess->cookie_domain );
        unset($_COOKIE['mobileversion']);  
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
            
            if ( ($PIVOTX['config']->get('mobile_redirect')==1) && empty($_COOKIE['mobileversion']) ) {
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
 
    // Only change the templates when we're at the correct (sub)domain or we 
    // haven't chosen to see the full version.
    if (($PIVOTX['config']->get('mobile_domain') != $_SERVER['HTTP_HOST']) || !empty($_COOKIE['mobileversion'])){
        return;
    }

    // Make sure we're allowed to override the templates.
    $PIVOTX['config']->set('allow_template_override', 1);
    
    $hostname = $_SERVER['http_host'];
    
    switch ($params['action']) {
        
        case "weblog":
            $params['template'] = $PIVOTX['config']->get('mobile_frontpage');
            break;
        
        case "entry":
            $params['template'] = $PIVOTX['config']->get('mobile_entrypage');
            break;
                
        case "page":
            $params['template'] = $PIVOTX['config']->get('mobile_page');
            break;

        case "search":
            $params['template'] = $PIVOTX['config']->get('mobile_extrapage');
            break;

    }

}



/**
 * Detect a mobile browser, and perhaps redirect them to a different page..
 *
 * Not very useful, since pivotx can do this automatically by checking the option in de 'mobile version'
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
        
        $to = "full";
        
        $link = $PIVOTX['config']->get('canonical_host');
        if (strlen($_SERVER['REQUEST_URI'])>1) {
            $link .= $_SERVER['REQUEST_URI'] . "&amp;mobilecookie=1";
        } else {
            $link .= "?mobilecookie=1";
        }
        
        $text = getDefault($params['text'], "View the full version of this site.");
        
    } else {
        
        $link = "http://" . $PIVOTX['config']->get('mobile_domain');
        if (strlen($_SERVER['REQUEST_URI'])>1) {
            $link .= $_SERVER['REQUEST_URI'] . "&amp;mobilecookie=-1";
        } else {
            $link .= "?mobilecookie=-1";
        }
        
        $text = getDefault($params['text'], "View the mobile version of this site.");   
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
        'label' => "Detect mobile visitors",
        'text' => "Yes, detect visitors that use a mobile browser."
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'mobile_redirectlink',
        'label' => "Redirect link",
        'size' => 60,
    ));
    
    $form->add( array(
        'type' => 'checkbox',
        'name' => 'mobile_redirect',
        'label' => "Immediate redirect",
        'text' => "Yes, redirect the visitor to the mobile version immediately. If this is disabled, the user will be presented with a link at the top of the page."
    ));

    $form->add( array(
       'type' => 'custom',
       'text' => "<tr><td colspan='3'><hr size='1' noshade='1' /></td></tr>"
        
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'mobile_domain',
        'label' => "Mobile Domain name",
        'value' => '',
        'error' => 'That\'s not a proper domain name!',
        'text' => "The domain name of the mobile version, for example m.example.org. Do not include the 'http://' part.",
        'size' => 26,
        'isrequired' => 1,
        'validation' => 'ifany|string|minlen=5|maxlen=80'
    ));



    $form->add( array(
        'type' => 'text',
        'name' => 'mobile_frontpage',
        'label' => "Frontpage template",
        'value' => '',
        'error' => 'That\'s not a proper filename!',
        'text' => "",
        'size' => 40,
        'isrequired' => 1,
        'validation' => 'ifany|string|minlen=5|maxlen=80'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'mobile_entrypage',
        'label' => "Entry template",
        'value' => '',
        'error' => 'That\'s not a proper filename!',
        'text' => "",
        'size' => 40,
        'isrequired' => 1,
        'validation' => 'ifany|string|minlen=5|maxlen=80'
    ));



    $form->add( array(
        'type' => 'text',
        'name' => 'mobile_page',
        'label' => "Page template",
        'value' => '',
        'error' => 'That\'s not a proper filename!',
        'text' => "",
        'size' => 40,
        'isrequired' => 1,
        'validation' => 'ifany|string|minlen=5|maxlen=80'
    ));


    $form->add( array(
        'type' => 'text',
        'name' => 'mobile_extrapage',
        'label' => "Extra (search) template",
        'value' => '',
        'error' => 'That\'s not a proper filename!',
        'text' => "",
        'size' => 40,
        'isrequired' => 1,
        'validation' => 'ifany|string|minlen=5|maxlen=80'
    ));


    $form->add( array(
        'type' => 'checkbox',
        'name' => 'mobile_treat_tablet_as_mobile',
        'label' => "Use Mobile version for tablets",
        'text' => "Yes, show the Mobile version of the website to visitors on tablet devices, such as the iPad."
    ));


    /**
     * Add the form to our (referenced) $form_html. Make sure you use the same key
     * as the first parameter to $PIVOTX['extensions']->getAdminForm
     */
    $form_html['mobile'] = $PIVOTX['extensions']->getAdminFormHtml($form, $mobiledetect_config);


}


?>
