<?php
// - Extension: External links
// - Version: 1.3
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: Open external links in a new window (or tab).
// - Date: 2011-10-07
// - Identifier: externallinks


global $externallinks_config;

$externallinks_config = array(
    'externallinks_addimage' => true,
    'externallinks_title' => "This link opens in a new window: %link%"
);

/* ------------------------------------------------------------------------ */

$script = "
        <script type=\"text/javascript\">
        //<![CDATA[
        
        jQuery(function($) {
            // Open external links in a new window (or tab). Ignores 'www.' part
            // when determining if something is 'internal'..
            
            var hostname = document.location.hostname.replace('www.', '');
            var eimage = ' <img src=\"%path%extensions/externallinks/elink.gif\" alt=\"\" style=\"border:0;\" />';
            jQuery('a').each(function(){
                if ((typeof(this.hostname)!=\"undefined\") && (typeof(this.hostname) != \"unknown\")) {
                    var thishost = this.hostname.replace('www.', '');
                    if (this.href && (thishost != hostname) && (this.protocol=='http:' || this.protocol=='https:') && (this.innerHTML.length<50) ) {
                        this.target='_blank';
                        %add%
                    }
                }
            });
        });
        
        //]]>
        </script>
";

if ($externallinks_config['externallinks_addimage']) {
    $script = str_replace("%add%", "this.innerHTML = this.innerHTML+eimage;
                        %add%", $script);
}

if (!empty($externallinks_config['externallinks_title'])) {
    $script = str_replace("%add%",
        "this.title = this.title + \"" . addslashes($externallinks_config['externallinks_title']) . "\";",
        $script);    
}


$script = str_replace("%add%", "", $script);
$script = str_replace("%link%", '" + this.href + "', $script);
$script = str_replace("%path%", $PIVOTX['paths']['pivotx_url'], $script);


$this->addHook(
    'after_parse',
    'insert_before_close_head',
    $script
);

// If the hook for the jQuery include in the header was not yet installed, do so now..
$this->addHook('after_parse', 'callback', 'jqueryIncludeCallback');


?>
