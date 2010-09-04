<?php
// - Extension: Google Analytics
// - Version: 0.5 
// - Author: Wim Bekkers / Bob den Otter
// - Email: wim@tellertest.com / Bob@pivotx.net
// - Site: http://tellertest.com/pivotextensions/
// - Description: Add the Google Analytics code to your PivotX pages with full control over tracking code customization. Display an overview of stats on the Dashboard screen
// - Date: 2010-06-14
// - Identifier: googleanalytics


global $PIVOTX;
global $googleanalytics_config;
global $googleanalytics_version;

$googleanalytics_version = "0.5";

$googleanalytics_config = array(
    'ga_UAcode' => "UA-xxxxxx-x",
    'ga_setDomainName' => "",
    'ga_setCampNameKey' => "",
    'ga_setCampMediumKey' => "",
    'ga_setCampSourceKey' => "",
    'ga_setCampTermKey' => "",
    'ga_setCampContentKey' => "",
    'ga_setAllowLinker' => "",
    'ga_setSessionTimeout' => "",
    'ga_setLocalServerMode' => "",
    'ga_setTransactionDelim' => "",
    'ga_addIgnoredOrganic' => "",
    'ga_addOrganic' => "",
    'ga_useASAC' => false
);


// Add a hook for googleanalyticsHook that will put the Google Analytics javascript 
// tracking code in the html for each entry
$this->addHook('after_parse', 'callback', 'googleanalyticsHook');

// Add a hook to the scheduler, to periodically update the stats.
$this->addHook('scheduler', 'callback', 'googleanalyticsScheduler');

// Add a hook to the scheduler, to periodically update the stats.
$this->addHook('in_template', 'dashboard-before-news', 'googleanalyticsScheduler');


// Add a hook, to output the stats on the Dashboard.
$this->addHook(
    'in_pivotx_template',
    'dashboard-before-news',
    array('callback' => 'googleanalyticsDashboard' )
    );


// Add a hook, to output the stats on the Dashboard.
$this->addHook(
    'in_pivotx_template',
    'mobile-dashboard-before-comments',
    array('callback' => 'googleanalyticsDashboard' )
    );




function googleanalyticsHook(&$html) {
    global $PIVOTX;
    global $googleanalytics_config;
    global $googleanalytics_version;

    // Set the $ga_ variables used below based on the keys in $googleanalytics_config.
    $configdata = $PIVOTX['config']->getConfigArray();
    foreach ($googleanalytics_config as $key => $value) {
        if (isset($configdata[$key])) {
            $$key = $configdata[$key];
        } else {
            $$key = $value;
        }
    }

    if (!isset($_GET['previewentry']) && !isset($_GET['previewpage'])) {

        // not in preview mode: go ahead!

        if ($ga_useASAC) {
            // add optional AdSense Analytics Code
            $output = '<script type="text/javascript">window.google_analytics_uacct = "'.$ga_UAcode.'";</script>';
            $html = preg_replace('#</head#si', "$output\n</head", $html);
        }

        $output = '<!-- Start of Google Analytics Code - Google Analytics extension for PivotX -->
<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src=\'" + gaJsHost + "google-analytics.com/ga.js\' type=\'text/javascript\'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
try{
var pageTracker = _gat._getTracker("'.$ga_UAcode.'");';

if ($ga_setDomainName != "") $output .= '
pageTracker._setDomainName("'.$ga_setDomainName.'");';
if ($ga_setCampNameKey != "") $output .= '
pageTracker._setCampNameKey("'.$ga_setCampNameKey.'");';
if ($ga_setCampMediumKey != "") $output .= '
pageTracker._setCampMediumKey("'.$ga_setCampMediumKey.'");';
if ($ga_setCampSourceKey != "") $output .= '
pageTracker._setCampSourceKey("'.$ga_setCampSourceKey.'");';
if ($ga_setCampTermKey != "") $output .= '
pageTracker._setCampTermKey("'.$ga_setCampTermKey.'");';
if ($ga_setCampContentKey != "") $output .= '
pageTracker._setCampContentKey("'.$ga_setCampContentKey.'");';
if ($ga_setAllowLinker != "") $output .= '
pageTracker._setAllowLinker(true);';
if ($ga_setSessionTimeout != "") $output .= '
pageTracker._setSessionTimeout("'.$ga_setSessionTimeout.'");';
if ($ga_setLocalServerMode != "") $output .= '
pageTracker._setLocalRemoteServerMode();';
if ($ga_setTransactionDelim != "") $output .= '
pageTracker._setTransactionDelim("'.$ga_setTransactionDelim.'");';

        $output .= '
pageTracker._trackPageview();
} catch(err) {}
</script>
<!-- End of Google Analytics Code - Google Analytics extension version '.$googleanalytics_version.' -->
';

        // add the javascript code to the page

        $html = preg_replace("#</body#si", $output."</body", $html);

    } else {
       return;
    }
}



/**
 * Adds the hook for googleanalyticsAdmin()
 *
 * @see googleanalyticsAdmin()
 */

$this->addHook(
    'configuration_add',
    'googleanalytics',
    array("googleanalyticsAdmin", "googleanalytics")
);

/**
 * The configuration screen for googleanalytics
 *
 * @param unknown_type $form_html
 */
function googleanalyticsAdmin(&$form_html) {
    global $PIVOTX, $googleanalytics_config;

    $form = $PIVOTX['extensions']->getAdminForm('googleanalytics');

    $form->add( array(
        'type' => 'text',
        'name' => 'ga_UAcode',
        'label' => "UA tracking code",
        'value' => '',
        'error' => 'Error - input needs to be in the form of UA-xxxxxx-x',
        'text' => 'Enter your personal Google Analytics code. This looks like UA-xxxxxx-x. Check your account information after logging in to Google Analytics',
        'size' => 15,
        'isrequired' => 0,
        'validation' => 'string|minlen=11|maxlen=11'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'ga_login',
        'label' => "Google account login",
        'value' => '',
        'error' => 'Error - input needs to be text',
        'text' => '',
        'size' => 40,
        'isrequired' => 0,
        'validation' => 'string|minlen=1|maxlen=255'
    ));
    
    $form->add( array(
        'type' => 'text',
        'name' => 'ga_password',
        'label' => "Google account password",
        'value' => '',
        'error' => 'Error - input needs to be text',
        'text' => 'If you want to display a summary of the statisctics on the Dashboard, fill in the specifics of a Google Account that has access to these statisctics. After doing this, click <a class="dialog" href="extensions/googleanalytics/testlogin.php" id="analyticsFetchID">this link</a> to test the login, and fetch the id, to fill in below.',
        'size' => 40,
        'isrequired' => 0,
        'validation' => 'string|minlen=1|maxlen=255'
    ));
    
    
    $form->add( array(
        'type' => 'text',
        'name' => 'ga_profileid',
        'label' => "Profile ID",
        'value' => '',
        'error' => 'Error - input needs to be a number',
        'text' => 'The (secret) Profile ID number, that will be used to display the reports. See above, on how to get this number.',
        'size' => 15,
        'isrequired' => 0,
        'validation' => 'integer|minlen=1|maxlen=12'
    ));
        

    $form->add( array(
        'type' => "hr"
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'ga_setDomainName',
        'label' => "Advanced: _setDomainName",
        'value' => '',
        'error' => 'Error - input needs to be text',
        'text' => 'Sets the domain name for cookies, e.g. .example.com, allows tracking of all subdomains in one profile',
        'size' => 40,
        'isrequired' => 0,
        'validation' => 'string|minlen=1|maxlen=255'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'ga_setCampNameKey',
        'label' => "Advanced: _setCampNameKey",
        'value' => '',
        'error' => 'Error - input needs to be text',
        'text' => 'Custom campaign variables: Campaign Name',
        'size' => 32,
        'isrequired' => 0,
        'validation' => 'string|minlen=1|maxlen=255'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'ga_setCampMediumKey',
        'label' => "Advanced: _setCampMediumKey",
        'value' => '',
        'error' => 'Error - input needs to be text',
        'text' => 'Custom campaign variables: Campaign Medium',
        'size' => 32,
        'isrequired' => 0,
        'validation' => 'string|minlen=1|maxlen=255'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'ga_setCampSourceKey',
        'label' => "Advanced: _setCampSourceKey",
        'value' => '',
        'error' => 'Error - input needs to be text',
        'text' => 'Custom campaign variables: Campaign Source',
        'size' => 32,
        'isrequired' => 0,
        'validation' => 'string|minlen=1|maxlen=255'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'ga_setCampTermKey',
        'label' => "Advanced: _setCampTermKey",
        'value' => '',
        'error' => 'Error - input needs to be text',
        'text' => 'Custom campaign variables: Campaign Term',
        'size' => 32,
        'isrequired' => 0,
        'validation' => 'string|minlen=1|maxlen=255'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'ga_setCampContentKey',
        'label' => "Advanced: _setCampContentKey",
        'value' => '',
        'error' => 'Error - input needs to be text',
        'text' => 'Custom campaign variables: Campaign Content',
        'size' => 32,
        'isrequired' => 0,
        'validation' => 'string|minlen=1|maxlen=255'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'ga_setAllowLinker',
        'label' => "Advanced: _setsetAllowLinker",
        'value' => '',
        'error' => 'Error - input needs to be text',
        'text' => 'If set to true, enable linker functionality, e.g. in combination with a 3rd-party shopping cart',
        'size' => 11,
        'isrequired' => 0,
        'validation' => 'string|minlen=1|maxlen=255'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'ga_setSessionTimeout',
        'label' => "Advanced: _setSessionTimeout",
        'value' => '',
        'error' => 'Error - input needs to be text',
        'text' => 'Set the inactive session timeout in seconds',
        'size' => 20,
        'isrequired' => 0,
        'validation' => 'string|minlen=1|maxlen=255'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'ga_setLocalServerMode',
        'label' => "Advanced: _setLocalRemoteServerMode",
        'value' => '',
        'error' => 'Error - input needs to be text',
        'text' => 'If set to true, allow Analytics to be used in conjunction with Urchin',
        'size' => 11,
        'isrequired' => 0,
        'validation' => 'string|minlen=1|maxlen=255'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'ga_setTransactionDelim',
        'label' => "Advanced: _setTransactionDelim",
        'value' => '',
        'error' => 'Error - input needs to be text',
        'text' => 'Use a character other than "|" as the separator for UTM:T and UTM:I fields',
        'size' => 11,
        'isrequired' => 0,
        'validation' => 'string|minlen=1|maxlen=255'
    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'ga_useASAC',
        'label' => "Advanced: use AdSense Analytics Code",
        'text' => 'Generate code for AdSense for Analytics reporting. <a href="http://www.google.com/support/googleanalytics/bin/answer.py?hl=en&answer=94743">See description.</a>',
    ));


    /**
     * Add the form to our (referenced) $form_html. Make sure you use the same key
     * as the first parameter to $PIVOTX['extensions']->getAdminForm
     */
    $form_html['googleanalytics'] = $PIVOTX['extensions']->getAdminFormHtml($form, $googleanalytics_config);



}



/**
 * Scheduler hook, to gather the data..
 *
 */
function googleanalyticsScheduler() {
    global $PIVOTX;
    
    require_once(dirname(__FILE__).'/analytics_api.php');

    
    $login = $PIVOTX['config']->get("ga_login");
    $password = $PIVOTX['config']->get("ga_password");
    $id = 'ga:'.$PIVOTX['config']->get("ga_profileid");
    
    if (empty($login) || empty($password) || $PIVOTX['config']->get("ga_profileid")=="" ) {
        return "";
    }
    
    $statistics = array();
    
    $api = new analytics_api();
    
    if($api->login($login, $password)) {
    
        
        // totals for 'forever', this month, this week, today.
        $statistics['totals'] = $api->data($id, '', 'ga:visits,ga:pageviews', '', "2005-01-01", false, 15, 1);
        
        $statistics['month'] = $api->data($id, '', 'ga:visits,ga:pageviews');
        
        $startdate = date("Y-m-d", mktime(1,1,1,date('m'),date('d')-7,date('Y')));        
        $statistics['week'] = $api->data($id, '', 'ga:visits,ga:pageviews', '', $startdate, false, 15, 1);
        
        $startdate = date("Y-m-d", mktime(1,1,1,date('m'),date('d'),date('Y')));        
        $statistics['today'] = $api->data($id, '', 'ga:visits,ga:pageviews', '', $startdate, $startdate, 15, 1);
        
        // Viewed pages, this week.. 
        $startdate = date("Y-m-d", mktime(1,1,1,date('m'),date('d')-7,date('Y')));  
        $data = $api->data($id, 'ga:pagePath', 'ga:pageviews', '', $startdate, false, 10, 1);
        
        foreach($data as $key=>$value) {
            $statistics['pages'][$key] = $value['ga:pageviews'];
        }
        
        // Referers, this week
        $data = $api->data($id, 'ga:source,ga:referralpath', 'ga:pageviews', '', $startdate, false, 50, 1);
        
        foreach($data as $host=>$value) {
            foreach($value as $path=>$value) {
                if ($path=="(not set)") {
                    $path="";
                }
                
                if (strpos($host, 'google')!==false) {
                    $statistics['referers']['google.com'] += $value['ga:pageviews'];            
                } else {
                    $statistics['referers'][$host.$path] = $value['ga:pageviews'];
                    
                }
                
            }
        }
         
        // Graph of the past three weeks..
        
        $startdate = date("Y-m-d", mktime(1,1,1,date('m'),date('d')-21,date('Y')));
        
        $data = $api->data($id, 'ga:date', 'ga:visits,ga:pageviews', 'ga:date', $startdate, false, 22, 1);
        // print_r($data);
        
        $visits = array();
        $pageviews = array();
        $labels = array();
        
        $counter=0;
        
        foreach($data as $date=>$point) {
            
            $visits[] = $point['ga:visits'];
            $pageviews[] = $point['ga:pageviews'];
        
            if(($counter % 3) == 2) {
                $date = (0+substr($date, 4,2)) . "/" . (0+substr($date, 6,2));
            } else {
                $date = "";
            }
            $counter++;
        
            $labels[] = urlencode($date);
        }
        
        $max = round(max($pageviews) * 1.1);
        
        $labels = implode('|', $labels);
        
        $url = "http://chart.apis.google.com/chart".
            "?cht=lc" .
            "&chs=312x160" .
            "&chxt=x,y" .
            "&chxr=0,0," . count($visits). "|1,0,". $max .
            "&chxl=0:|".$labels . 
            "&chdlp=b&" .
            "&chls=2,1,0|2,1,0" . 
            "&chdl=Visits|Pageviews,+last+21+days".
            "&chco=6F8082,404F52".
            "&chm=b,9FB0B2,0,1,0|B,AFC0C2,0,1,0". 
            "&chds=0," . $max .
            "&chxtc=0,3|1,3" .  // Tickmarks
            "&chd=t:" . implode(",", $visits) . "|" . implode(",", $pageviews);
        
        
        $statistics['chart'] = $url;
        
        save_serialize($PIVOTX['paths']['db_path'].'analytics.php', $statistics);
    
    }
        
    
}


/**
 * Hook function, to display the stats on the dashboard..
 */
function googleanalyticsDashboard() {
    global $PIVOTX;
    
    
    $output = '<div class="news" style="margin-bottom: 16px;">
        <h2><img src="pics/newspaper.png" alt="" height="16" width="16" style="border-width: 0px; margin-bottom: -3px;" />
            <strong>Google Analytics stats</strong>
        </h2>';
    
    $data = load_serialize($PIVOTX['paths']['db_path'].'analytics.php', true);
             
    if (empty($data)) {
        
        $output .= "<p>No data yet. Come back later.</p>";
             
    } else {

        $output .= "<div id='ga-simpletabs'>
            <span id='simpletab1' class='first'>Visits</span><span id='simpletab2'>Totals</span><span id='simpletab3'>Pages</span><span id='simpletab4'>Referers</span>
            </div>";
        
        $output .= "<div id='ga-simpletab1' class='ga-simpletab'>";
        
        $output .= "<img src='". $data['chart']."'/>";
        
        $output .= "</div>";

        $output .= "<div id='ga-simpletab2' class='ga-simpletab'>";
        
        $output .= sprintf("<table class='googleanalytics'><tr><td>&nbsp;</td><th style='text-align: right;'>Visits</th><th style='text-align: right;'>Pageviews</th></tr>");

        $output .= sprintf("<tr><th>Total:</th><td>%s</td><td>%s</td></tr>", $data['totals']['ga:visits'], $data['totals']['ga:pageviews'] );
        $output .= sprintf("<tr><th>This month:</th><td>%s</td><td>%s</td></tr>", $data['month']['ga:visits'], $data['month']['ga:pageviews'] );
        $output .= sprintf("<tr><th>This week:</th><td>%s</td><td>%s</td></tr>", $data['week']['ga:visits'], $data['week']['ga:pageviews'] );
        $output .= sprintf("<tr><th>Today:</th><td>%s</td><td>%s</td></tr>", $data['today']['ga:visits'], $data['today']['ga:pageviews'] );
        
        $output .= "</table>";
        
        $output .= "</div>";

        $output .= "<div id='ga-simpletab3' class='ga-simpletab'>";
        
        $data['pages'] = array_slice($data['pages'], 0, 8);
        
        foreach ($data['pages'] as $page=>$hits) {
            
            if ($page=="/") {
                $title = "(home)";
            } else {
                $title = trimtext($page, 40);
            }
            
            $output .= sprintf("<a href='%s' title='%s'>%s</a> <em><small>(%s pageviews)</small></em><br />", $page, $page, $title, $hits );
            
        }
        
        
        $output .= "</div>";
        
        $output .= "<div id='ga-simpletab4' class='ga-simpletab'>";

        $data['referers'] = array_slice($data['referers'], 0, 8);
        
        foreach ($data['referers'] as $page=>$hits) {
            
            if ($page=="/") {
                $title = "(home)";
            } else {
                $title = trimtext($page, 40);
            }
            
            $output .= sprintf("<a href='http://%s' title='%s'>%s</a> <em><small>(%s pageviews)</small></em><br />", $page, $page, $title, $hits );
            
        }

        $output .= "</div>";        

        
    }
    

    $output .= "</div>";

    $path = $PIVOTX['paths']['extensions_url'];

    $output .= "<script src=\"{$path}googleanalytics/googleanalytics.js\" type=\"text/javascript\"></script>\n";

    if (isMobile()) {
        $output .= "<link rel=\"stylesheet\" href=\"{$path}googleanalytics/googleanalytics_mobile.css\" type=\"text/css\" />\n";
    } else {
        $output .= "<link rel=\"stylesheet\" href=\"{$path}googleanalytics/googleanalytics.css\" type=\"text/css\" />\n";        
    }


    return $output;
    
}

?>
