<?php
// - Extension: Piwik
// - Version: 1.0
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: An extension used to add Piwik tracking code
// - Date: 2009-09-15
// - Identifier: piwik
// - Required PivotX version: 2.0.2


$this->addHook(
    'before_output',
    'callback',
    'piwikHook'
);

/**
 * Outputs the Piwik tracking code.
 *
 * @param string $html
 * @return void
 */
function piwikHook(&$html) {
    global $PIVOTX, $piwik_config;

    $str = array();
    $insert = true;

    if (!empty($_GET['previewentry']) || !empty($_GET['previewpage'])) { 
        $str[] = "    <!-- The Piwik tracking code isn't inserted in previewing mode. -->";
        $insert = false;
    }
    if ($PIVOTX['config']->get('piwik_url') == '') {
        debug("The Piwik URL isn't set. Have you enabled the Piwik admin extension?");
        $str[] = "    <!-- The Piwik URL isn't set. The tracking code can't be inserted -->";
        $insert = false;
    }

    $piwik_ignore_ip = $PIVOTX['config']->get('piwik_ignore_ip');
    if ($piwik_ignore_ip != '') {
        $ips = explode(',',$piwik_ignore_ip);
        foreach($ips as $ip) {
            $ip = trim($ip);
            if (strpos($_SERVER['REMOTE_ADDR'],$ip) === 0) { 
                $str[] = "    <!-- This IP should be ignored by Piwik. The tracking code isn't inserted. -->";
                $insert = false;
                break;
            }
        }
    }

    $piwik_js = <<<EOF
    <!-- Piwik -->
    <script type="text/javascript" src="%url%/piwik.js"></script>
    <script type="text/javascript">
        try {
            var piwikTracker = Piwik.getTracker("%url%/piwik.php", %site_id%);
            piwikTracker.trackPageView();
            piwikTracker.enableLinkTracking();
        } catch( err ) {}
    </script>
    <!-- End Piwik Tag -->
EOF;

    $piwik_js = str_replace('%url%', $PIVOTX['config']->get('piwik_url'), $piwik_js);
    $piwik_js = str_replace('%site_id%', $PIVOTX['config']->get('piwik_site_id'), $piwik_js);
    $piwik_js = str_replace('//', '/', $piwik_js);

    if ($insert) {
        $str[] = $piwik_js;
    }

    $str[] = '</body>';

    $html = str_replace('</body>', implode("\n", $str), $html);

}


?>
