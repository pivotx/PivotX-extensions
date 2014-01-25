<?php
// - Extension: Fancybox
// - Version: 0.23
// - Author: PivotX Team / Harm Kramer
// - Email: admin@pivotx.net / harm.kramer@hccnet.nl
// - Site: http://www.pivotx.net
// - Description: Replace boring old Thickbox with a FancyBox!
// - Date: 2014-01-25
// - Identifier: fancybox 
// - Required PivotX version: 2.2


// Register 'fancybox' as a smarty tag, and override 'popup'
$PIVOTX['template']->register_function('fancybox', 'smarty_fancybox');
$PIVOTX['template']->unregister_function('popup');
$PIVOTX['template']->register_function('popup', 'smarty_fancybox');

// Register 'fancybox_setup' as a smarty tag
$PIVOTX['template']->register_function('fancybox_setup', 'smarty_fancybox_setup');

/**
 * Insert the includes for Fancybox in the <head> section of the HTML.
 * Very useful if you don't use PivotX's popup template tag, but want to use 
 * Fancybox manually.
 *
 * @param array $params
 * @return void
 */
function smarty_fancybox_setup($params) {
    global $PIVOTX;
    
    $PIVOTX['extensions']->addHook('after_parse', 'callback', 'fancyboxIncludeCallback');
}
 
/**
 * Outputs the Fancybox popup code.
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_fancybox($params, &$smarty) {
    global $PIVOTX;

    // If we've set the hidden config option for 'never_jquery', just return without doing anything.
    if ($PIVOTX['config']->get('never_jquery') == 1) {
        debug("JQuery is disabled by the 'never_jquery' config option. FancyBox won't work.");
        return;   
    }

    $params = cleanParams($params);

    $filename      = $params['file'];
    $thumbname     = getDefault($params['description'], "(thumbnail)");
    $org_thumbname = $thumbname;
    $alt           = $params['alt'];
    $title         = $params['title'];
    $align         = getDefault($params['align'], "center");
    // rel_id can be used to specify your own prefix; all fancybox images with the same prefix will become a gallery
    $rel_id        = getDefault($params['rel_id'], "entry-");
    // fb_type can be used to specify the type of the fancybox
    // image (default) - selfexplanatory
    // youtube - creates an embedded object with the youtube link (use url for that)
    $fb_type       = getDefault($params['fb_type'], "image");
    $width         = getDefault($params['width'], "560");
    $height        = getDefault($params['height'], "340");
    $objwidth      = getDefault($params['objwidth'], "0");
    $objheight     = getDefault($params['objheight'], "0");
    $maxthumb      = getDefault($params['specthumbmax'], "0");
    $txtcol        = getDefault($params['txtcol'], "black");
    $txtcolbg      = getDefault($params['txtcolbg'], "white");
    $txtcls        = getDefault($params['txtcls'], "pivotx-popupimage");
    // this one can be used together with fb_type="youtube" and "vimeo"
    // !! structure should be like explained on youtube e.g. http://www.youtube.com/v/MOVID
    // or for vimeo: http://www.vimeo.com/moogaloop.swf?clip_id=CLIPID
    // it's better to just use movid to specify youtube or clipid for vimeo
    // url can also be used for fb type="iframe" or "flash"
    $url           = $params['url'];
    $url           = strip_tags($url);
    $movid         = $params['movid'];
    $text          = getDefault($params['text'], "Specify your text in parm 'text'.");
    // $border = getDefault($params['border'], 0);
    $imgw          = getDefault($PIVOTX['config']->get('upload_thumb_width'), 200);
    $imgh          = getDefault($PIVOTX['config']->get('upload_thumb_height'), 200);
    $uplbasepath   = $PIVOTX['paths']['upload_base_path'];
    // Config option 'fancybox_thumbnail' can be added and used as default for thumbnail behaviour
    // 1 = always make sure the dimensions of the img tag are the same irrelevant of current thumbnail size
    //     (this means that when thumbnail gets created the upload width/height settings are used)
    // 2 = if thumbnail already exists always use its dimensions for the img tag (default)
    // 3 = if thumbnail exists and doesn't adhere to current width/height setting recreate it
    $fbthumb       = getDefault($PIVOTX['config']->get('fancybox_thumbnail'), 2);
    $fbthumb       = getDefault($params['thumbbehav'], $fbthumb);

    // debug("fb info: '$filename'-'$thumbname'-'$title'-'$alt'-'$align'-'$fb_type'");

    if (($align=='center'||($align=='inline'))){
        $fbclass = 'pivotx-popupimage';
        $txclass = 'pivotx-popuptext';
    } else {
        $fbclass = 'pivotx-popupimage align-'.$align;
        $txclass = 'pivotx-popuptext align-'.$align;
    }

    // Get the UID for the page or entry
    $vars = $smarty->get_template_vars();
    $uid = intval($vars['uid']);

    if ( empty($alt) ) {
        $alt = $filename;
    }
    if ($objwidth == "0") {
        $objwidth = $width; 
    }
    if ($objheight == "0") {
        $objheight = $height;
    }
    // Fix Thumbname, perhaps use a thumbname, instead of textual link
    // and try to fill both alt and title if still empty
    if ( $thumbname=="(thumbnail)" ) {
        if ( empty($filename) ) {
            debug ("No filename specified for thumbnail to process");          
        } else {
            $thumbname = makeThumbname($filename);
            // If thumbnail exists and option 3 is chosen then check the dimensions for possible recreation
            $recreate = 0;
            if( file_exists( $PIVOTX['paths']['upload_base_path'].$thumbname ) && $fbthumb == 3 ) {
                list($thumbw, $thumbh) = getimagesize($uplbasepath.$thumbname);
                //debug("dimensions of thumbnail: " . $thumbw . "/" . $thumbh);
                //debug("imgw/h: " . $imgw . "/" . $imgh);
                //debug("maxthumb: " . $maxthumb);
                if ($maxthumb > 0) {
                // specthumbmax specified: calculate the right values (useful for vertical images)
                    if ($thumbw > $thumbh) {
                        $imgh = round($thumbh * ($maxthumb / $thumbw));
                        $imgw = $maxthumb;
                    } else {
                        $imgw = round($thumbw * ($maxthumb / $thumbh));
                        $imgh = $maxthumb;   
                    }
                }
                if ($thumbw != $imgw || $thumbh != $imgh) {
                    $recreate = 1;
                    //debug("thumb will be recreated");
                }
            }
            // If the thumbnail does not exist and extension is jpg or png then try to create it
            // gif could be problematic so don't try it here......
            // filename could contain a subdir! this part is removed by auto_thumbnail
            // so save it through specifying a folder var            
            if( !file_exists( $PIVOTX['paths']['upload_base_path'].$thumbname ) || $recreate == 1) {
                $ext = strtolower(getExtension($filename));
                if(($ext=="jpeg")||($ext=="jpg")||($ext=="png")) {
                    require_once($PIVOTX['paths']['pivotx_path'].'modules/module_imagefunctions.php');
                    $folder = $PIVOTX['paths']['upload_base_path'];
                    $dirpart = dirname($filename);
                    $basename = basename($filename);
                    $action = "Fancybox";
                    if (($dirpart != "") && ($dirpart != ".")) {
                        $folder = $folder . $dirpart . "/";
                    }
                    if (!auto_thumbnail($basename, $folder, $action, $maxthumb)) {
                        debug("Failed to create thumbnail for " . $filename);
                    } 
                } else {
                    debug("Unable to create thumbnail for this extension " . $filename);
                }
            }    
        }   
    }
    if ( empty($alt) ) {
        $alt = $thumbname;
    }    
    if ( empty($title) ) {
        $title = $alt;
    }
    // special string "null" to get rid of any title/alt
    if (($title=="null")||($alt=="null")) {
       $title = "";
       $alt = "";
    }

    // Clean title and alternative text before using in generated html
    $title = cleanAttributes($title);
    $alt = cleanAttributes($alt);

    // If the thumbnail exists, make the HTML for it, else just use the text for a link.
    // use the current settings for uploadwidth/height because thumb can have diff.size
    if( file_exists( $PIVOTX['paths']['upload_base_path'].$thumbname )) {

        $ext=strtolower(getExtension($thumbname));

        if ( ($ext=="jpg")||($ext=="jpeg")||($ext=="gif")||($ext=="png") ) {
            // get image dimensions
            list($thumbw, $thumbh) = getimagesize($uplbasepath.$thumbname);
            if ($maxthumb > 0) {
               // specthumbmax specified: calculate the right values (useful for vertical images)
               if ($thumbw > $thumbh) {
                  $imgh = round($thumbh * ($maxthumb / $thumbw));
                  $imgw = $maxthumb;
               }   
               else {
                  $imgw = round($thumbw * ($maxthumb / $thumbh));
                  $imgh = $maxthumb;   
               }
            }
            // thumbnail behaviour 2: always use the dimensions of the found thumbnail
            if ($fbthumb == 2) {
                $imgw = $thumbw;
                $imgh = $thumbh;
                //debug("dimensions of found thumb used: " . $thumbw . "/" . $thumbh);
            }
            // if parms width or height have been specified they should be used!
            if (isset($params['width'])) {
               $imgw = $width;
            }
            if (isset($params['height'])) {
               $imgh = $height;
            }
            $thumbname = sprintf("<img src=\"%s%s\" alt=\"%s\" title=\"%s\" class=\"%s\" width=\"%s\" height=\"%s\" />",
                $PIVOTX['paths']['upload_base_url'], $thumbname, $alt, $title, $fbclass, $imgw, $imgh
            );
        } else {
            $thumbname = $org_thumbname;
        }
    } else {
        $thumbname = $org_thumbname;
    }
    // pack text in aligned paragraph (thumbname has been unchanged by the above)
    if ($thumbname == $org_thumbname) {
        if (strlen($org_thumbname)<2) {
            $org_thumbname = "popup";
        }   
        $thumbname = sprintf("<span class=\"%s\">%s</span>",
            $txclass,
            $org_thumbname
        );
    }


    // Prepare the HMTL for the link to the popup..
    // fb_type image
    if ($fb_type=='image') {
        if( file_exists( $PIVOTX['paths']['upload_base_path'].$filename )) {

            $filename = $PIVOTX['paths']['upload_base_url'].$filename ;

            $code = sprintf( "<a href=\"%s\" class=\"fancybox\" title=\"%s\" rel=\"%s%s\" >%s</a>",
                $filename,
                $title,
                $rel_id,
                $uid,
                $thumbname
            );
            if( 'center'==$align ) {
                $code = '<p class="pivotx-wrapper">'.$code.'</p>' ;
            }
        } else {
            debug("Rendering error: could not popup '$filename'. File does not exist.");
            $code = "<!-- Rendering error: could not popup '$filename'. File does not exist. -->";
        }
    } else if (($fb_type=='youtube')||($fb_type=="vimeo")) {
        // filename is not mandatory so fix an empty one with dummy string so code gets returned
        if (empty($filename)) {
            $filename = '==fbdummy==';
        }
        // use random number to be fairly sure that constructed href will be unique 
        // if by chance the number is the same then movie shown (when clicked) will be the first one
        // this is because a gallery of movies is not possible yet
        // uploadwidth/height is not used here because default youtube images are smaller
        $randnum = rand();
        if (empty($movid) && empty($url)) {
            debug("Popup type youtube/vimeo needs either a 'movid' or a fully qualified 'url' parm!");
        } 
        $movstart = 0;
        if (empty($movid)) {
            $movthumb = formatFilename($url);
            $movthumb = str_replace('watch?v=', '', $movthumb);
            $movtime  = '';
            // link contains time parm? &t=
            if (strpos($movthumb, "&t=")) { 
                $timepos  = strpos($movthumb, "&t=");
                $movtime  = substr($movthumb,$timepos+3);
                $movthumb = substr($movthumb,0,$timepos); 
            } 
            // short link supplied with time parm?
            if (strpos($movthumb, "?t=")) { 
                $timepos  = strpos($movthumb, "?t=");
                $movtime  = substr($movthumb,$timepos+3);
                $movthumb = substr($movthumb,0,$timepos); 
            } 
            // calculate the amount of seconds to supply to the player
            if ($movtime != '') {
                $movh = 0; $movm = 0; $movs = 0;
                $hpos = strpos($movtime, "h");
                if ($hpos) { $movh = substr($movtime,0,$hpos); $movtime = substr($movtime,$hpos+1); }
                $mpos = strpos($movtime, "m");
                if ($mpos) { $movm = substr($movtime,0,$mpos); $movtime = substr($movtime,$mpos+1); }
                $spos = strpos($movtime, "s");
                if ($spos) { $movs = substr($movtime,0,$spos); $movtime = substr($movtime,$spos+1); }
                if (is_numeric($movh)) { $movstart = ($movh * 3600); } 
                if (is_numeric($movm)) { $movstart = $movstart + ($movm * 60); } 
                if (is_numeric($movs)) { $movstart = $movstart + $movs; } 
            }
            // formatFilename replaces underscore by space -- undo this
            $movthumb = str_replace(' ', '_', $movthumb);
            if ($fb_type=="vimeo"){
                // possible formats: http://www.vimeo.com/moogaloop.swf?clip_id=6566857 
                //                   http://www.vimeo.com/5324878
                $pos = strpos($url, "clip_id=");
                if ($pos !== false){
                    $pos = $pos + 8;
                    $movthumb = substr($url,$pos);
                } else {
                    $pos = strpos($url, "vimeo.com/");
                    if ($pos !== false){
                        $pos = $pos + 10;
                        $movthumb = substr($url,$pos);
                        // if this format is received rewrite it to embed format
                        $url = "http://www.vimeo.com/moogaloop.swf?clip_id=" . $movthumb;
                    }
                }
            }
        } else {
            $movthumb = $movid;
        }
        if ($fb_type=="youtube"){
            $urlthumb = "http://i2.ytimg.com/vi/" . $movthumb . "/default.jpg";
        } else if ($fb_type=="vimeo"){
            $urlvimphp = "http://vimeo.com/api/v2/video/" . $movthumb . ".php";
            $vimeocontents = @file_get_contents($urlvimphp);
            $thumbcontents = @unserialize(trim($vimeocontents));
            $urlthumb = $thumbcontents[0][thumbnail_small];
            if (empty($urlthumb)){
                $urlthumb = $thumbcontents[0][user_thumbnail_small];
            }
        }

        $code = sprintf( "<a href=\"#%s%s\" class=\"fancytube\" title=\"%s\" rel=\"%s%s\" ><img src=\"%s\" class=\"%s\" alt=\"%s\" /></a>",
            $rel_id,
            $randnum,
            $title,
            $rel_id,
            $uid,
            $urlthumb,
            $fbclass,
            $alt
        );
        
        // some extra options for youtube (end with ampersand)
        // for explanation see http://code.google.com/intl/nl/apis/youtube/player_parameters.html
        // hl = language
        // autoplay: 1 = autoplay; 0 = click to play
        // rel = play related videos (0 = no)
        // fs = fullscreen allowed

        // options for vimeo just found by browsing through Google
        if (empty($movid)) {
            $urlmain = str_replace('watch?v=', 'v/', $url);
            $urlmain = str_replace('/embed/', '/v/', $urlmain);
            // convert a short link to a long one otherwise it won't work (if parms were in link they are now gone)
            // also if time parm was found the link needs to be reformatted to obligatory format
            if (strpos($urlmain, "//youtu.be/") || $movstart != 0) { 
                $urlmain = "http://www.youtube.com/v/" . $movthumb;
            }
            $urlid   = "";
        } else {
            if ($fb_type=="youtube"){
                $urlmain = "http://www.youtube.com/v/";
                $urlid   = $movid;
            } else if ($fb_type=="vimeo"){
                $urlmain = "http://www.vimeo.com/moogaloop.swf?clip_id=";
                $urlid   = $movid;            
            }   
        }
        if ($fb_type=="youtube"){
            $urlextra = "&amp;hl=en&amp;autoplay=1&amp;rel=0&amp;fs=1&amp;start=" . $movstart;
        } else if ($fb_type=="vimeo"){
            $urlextra = "&amp;server=vimeo.com&amp;autoplay=1&amp;fullscreen=1&amp;show_title=1&amp;show_byline=0&amp;show_portrait=0";
        }    
        $anchor_obj = sprintf( "<span style=\"display: none\"><span id=\"%s%s\" ><object type=\"application/x-shockwave-flash\" data=\"%s%s%s\" width=\"%s\" height=\"%s\"><param name=\"movie\" value=\"%s%s%s\"></param><param name=\"allowFullScreen\" value=\"true\"></param><param name=\"allowscriptaccess\" value=\"always\"></param></object></span></span>",
            $rel_id,
            $randnum,
            $urlmain,
            $urlid,
            $urlextra,
            $objwidth,
            $objheight,
            $urlmain,
            $urlid,
            $urlextra            
        );
        $code = $code.$anchor_obj ;
        if( 'center'==$align ) {
            $code = '<p class="pivotx-wrapper">'.$code.'</p>' ;
        }
    }  else if ($fb_type=='text') {
        // filename is not mandatory so fix an empty one with dummy string so code gets returned
        if (empty($filename)) {
            $filename = '==fbdummy==';
        }
        // use random number to be fairly sure that constructed href will be unique 
        // if by chance the number is the same then text shown (when clicked) will be the first one
        // also use this random number to construct a unique rel because grouping results
        // in array-reverse errors and crashing of the webpage when scrolling with the mouse!
        $randnum = rand();
        $code = sprintf( "<a href=\"#%s%s\" class=\"fancytext\" title=\"%s\" rel=\"%s%s%s\" >%s</a>",
            $rel_id,
            $randnum,
            $title,
            $rel_id,
            $uid,
            $randnum,
            $thumbname
        );
        $textbegin = substr($text,0,5);
        $textrest  = substr($text,5);
        if ($textbegin !== "file:") {
            $lines = $text;
        } else {
            $docfile = $PIVOTX['paths']['pivotx_path']."docs/".$textrest ;
            if( file_exists($docfile) && is_readable($docfile) && ($handle = fopen($docfile, 'r'))) {
                $lines    = fread($handle, filesize($docfile));
                fclose($handle);
            } else {
                debug("Specified file cannot be found or read:'$docfile'");
            }
        }
        // check whether the lines contain html.
        // If there are the popup will still function but with visible elements
        // better use iframe for text with html
        if (strlen($lines) != strlen(strip_tags($lines))) {
            debug("Popup: '$rel_id$randnum' contains HTML elements.");
            debug("A text popup should only contain plain text.");
            debug("Try using fb_type iframe with an url pointing to a saved file instead.");
        }

        // couldn't get it to work correctly with an object (kept on forcing its own default size)
        // just specifying a span had the same result; can't use div and so on because pop-up
        // can be within an open paragraph 
        // so switched to textarea (which is more customisable anyway); cols and rows are there for valid html
        $anchor_obj = sprintf( "<span style=\"display: none\"><span id=\"%s%s\"><textarea class=\"%s\" style=\"width: %s; height: %s; overflow: auto; color: %s; background-color: %s\" readonly=\"readonly\" cols=\"\" rows=\"\">%s</textarea></span></span>",
            $rel_id,
            $randnum,
            $txtcls,
            $objwidth,
            $objheight,
            $txtcol,
            $txtcolbg,
            $lines
        );
        
        $code = $code.$anchor_obj ;
        if( 'center'==$align ) {
            $code = '<p class="pivotx-wrapper">'.$code.'</p>' ;
        }
    } else if ($fb_type=='iframe') {
        // filename is not mandatory so fix an empty one with dummy string so code gets returned
        if (empty($filename)) {
            $filename = '==fbdummy==';
        }
        // use random number to be fairly sure that constructed rel will be unique 
        // if by chance the number is the same then iframe will open but clicking 
        // in the frame itself will be impossible
        $randnum = rand();
        $code = sprintf( "<a href=\"%s\" class=\"fancyframe\" title=\"%s\" rel=\"%s%s%s\" >%s</a>",
            $url,
            $title,
            $rel_id,
            $uid,
            $randnum,
            $thumbname
        );
        if( 'center'==$align ) {
            $code = '<p class="pivotx-wrapper">'.$code.'</p>' ;
        }
    } else if ($fb_type=='flash') {
        // filename is not mandatory so fix an empty one with dummy string so code gets returned
        if (empty($filename)) {
            $filename = '==fbdummy==';
        }
        // use random number to be fairly sure that constructed rel will be unique 
        // if by chance the number is the same then flash will open but clicking 
        // in the window itself will be impossible
        $randnum = rand();
        $code = sprintf( "<a href=\"%s\" class=\"fancyflash\" title=\"%s\" rel=\"%s%s%s\" >%s</a>",
            $url,
            $title,
            $rel_id,
            $uid,
            $randnum,
            $thumbname
        );
        if( 'center'==$align ) {
            $code = '<p class="pivotx-wrapper">'.$code.'</p>' ;
        }
    }  

    $PIVOTX['extensions']->addHook('after_parse', 'callback', 'fancyboxIncludeCallback');

    // not every type uses parm file so var filename gets a dummy value in those types
    if (!empty($filename) ) {
        return $code;
    } else {
        return "";
    }

}

/**
 * Try to insert the includes for fancybox in the <head> section of the HTML
 * that is to be outputted to the browser. Inserts Jquery if not already 
 * included. (This is just the default "thickboxIncludeCallback" function 
 * adapted to Fancybox.)
 *
 * @param string $html
 */
function fancyboxIncludeCallback(&$html) {
    global $PIVOTX;

    // If we've set the hidden config option for 'never_jquery', just return without doing anything.
    if ($PIVOTX['config']->get('never_jquery') == 1) {
        debug("JQuery is disabled by the 'never_jquery' config option. FancyBox won't work.");
        return;   
    }

    OutputSystem::instance()->enableCode('jquery');

    // Get config setting for changing the close button default (array)
    $fbclbutt = explode(",",($PIVOTX['config']->get('fancybox_button_default')));
    // Is config option 'fancybox_profile' added and has an expected value?
    $fbprof = $PIVOTX['config']->get('fancybox_profile');
    // for parms explanation -- see http://www.fancybox.net/api
    $fbparms = "\t\tjQuery(\"a.fancybox\").fancybox({ ";
    // default profile (downwards compatible)
    $fbparms .= "padding: 2, ";
    $fbparms .= "'titlePosition': 'over', ";
    $fbparms .= "'overlayShow': true, 'overlayOpacity': 0.25, ";
    $fbparms .= "'opacity': true, 'speedIn': 100, 'speedOut': 100, 'changeSpeed': 100, ";
    if (in_array("image", $fbclbutt)) {
        $fbparms .= "'showCloseButton': false ";
    } else {
        $fbparms .= "'showCloseButton': true ";
    }
    $fbparms .= "});\n";   
    if ($fbprof == '') {
        // profile will be default = nr. 1
    } elseif ($fbprof == 1) {
        // is the default
    } elseif ($fbprof == 2) {       
        $fbparms = "\t\tjQuery(\"a.fancybox\").fancybox({ ";
        // title outside / elastic transition / diff.speed / cyclic
        // -- although FB specifies to use titlePosition outside their js only uses float for outside title build-up
        $fbparms .= "padding: 2, ";
        $fbparms .= "'titlePosition': 'float', ";
        $fbparms .= "'transitionIn': 'elastic', 'transitionOut': 'elastic', ";
        $fbparms .= "'easingIn': 'easeOutBack', 'easingOut': 'easeInBack', "; 
        $fbparms .= "'overlayShow': true, 'overlayOpacity': 0.3, ";
        $fbparms .= "'opacity': true, 'speedIn': 300, 'speedOut': 300, 'changeSpeed': 300, ";
        if (in_array("image", $fbclbutt)) {
            $fbparms .= "'showCloseButton': false, 'cyclic': true ";
        } else {
            $fbparms .= "'showCloseButton': true, 'cyclic': true ";
        }
        $fbparms .= "});\n";           
    } elseif ($fbprof == 3) {
        $fbparms = "\t\tjQuery(\"a.fancybox\").fancybox({ ";
        // no padding / no close button / Image 1/n before title (over) / cyclic
        $fbparms .= "padding: 0, ";
        $fbparms .= "'titlePosition': 'over', ";
        $fbparms .= "'transitionIn': 'fade', 'transitionOut': 'fade', ";
        $fbparms .= "'overlayShow': true, 'overlayOpacity': 0.25, ";
        $fbparms .= "'opacity': true, 'speedIn': 100, 'speedOut': 100, 'changeSpeed': 100, ";
        if (in_array("image", $fbclbutt)) {
            $fbparms .= "'showCloseButton': true, 'cyclic': true, ";
        } else {
            $fbparms .= "'showCloseButton': false, 'cyclic': true, ";
        }
        $fbparms .= "'titleFormat': function(title, currentArray, currentIndex, currentOpts) { return '<span id=\"fancybox-title-over\">";
        $fbparms .= __("Image");
        $fbparms .= " ' + (currentIndex + 1) + ' / ' + currentArray.length + (title.length ? ' &nbsp; ' + title : '') + '</span>';}";
        $fbparms .= "});\n";
    } elseif ($fbprof == 4) {
        $fbparms = "\t\tjQuery(\"a.fancybox\").fancybox({ ";
        // default profile according to fancybox.net (changed titlePosition outside to float)
        $fbparms .= "padding: 10, margin: 20,";
        $fbparms .= "'titlePosition': 'float', ";
        $fbparms .= "'transitionIn': 'fade', 'transitionOut': 'fade', ";
        $fbparms .= "'overlayShow': true, 'overlayOpacity': 0.3, ";
        $fbparms .= "'opacity': false, 'speedIn': 300, 'speedOut': 300, 'changeSpeed': 300, ";
        if (in_array("image", $fbclbutt)) {
            $fbparms .= "'showCloseButton': false, 'cyclic': false, ";
        } else {
            $fbparms .= "'showCloseButton': true, 'cyclic': false, ";
        }
        $fbparms .= "'titleFormat': null";
        $fbparms .= "});\n";
    } else {              
        debug("Config option fancybox_profile has an incorrect value, profile set to default.");   
    } 
    // standard fancybox youtube/vimeo profile   
    $fbytube = "\t\tjQuery(\"a.fancytube\").fancybox({ ";
    $fbytube .= "padding: 0, autoScale: false, centerOnScroll: true, ";
    $fbytube .= "'transitionIn': 'none', 'transitionOut': 'none', ";
    $fbytube .= "'overlayShow': true, 'overlayOpacity': 0.7, "; 
    $fbytube .= "'hideOnContentClick': true, ";     // doesn't work for youtube (yet?)
    $fbytube .= "'titlePosition': 'outside', ";
    if (in_array("youtube", $fbclbutt) || in_array("vimeo", $fbclbutt)) {
        $fbytube .= "'showCloseButton': true ";
    } else {
        $fbytube .= "'showCloseButton': false ";
    }
    $fbytube .= "});\n";    

    // standard fancybox text profile   
    $fbtext  = "\t\tjQuery(\"a.fancytext\").fancybox({ ";
    $fbtext .= "padding: 5, autoScale: true, centerOnScroll: true, ";
    $fbtext .= "'transitionIn': 'none', 'transitionOut': 'none', ";
    $fbtext .= "'overlayShow': true, 'overlayOpacity': 0.7, "; 
    $fbtext .= "'titlePosition': 'outside', ";
    if (in_array("text", $fbclbutt)) {
        $fbtext .= "'showCloseButton': false, 'cyclic': false ";
    } else {
        $fbtext .= "'showCloseButton': true, 'cyclic': false ";
    }
    $fbtext .= "});\n"; 

    // standard fancybox iframe profile   
    $fbifram = "\t\tjQuery(\"a.fancyframe\").fancybox({ ";
    $fbifram .= "padding: 3, autoScale: false, centerOnScroll: true, ";
    $fbifram .= "'transitionIn': 'none', 'transitionOut': 'none', ";
    $fbifram .= "'overlayShow': true, 'overlayOpacity': 0.7, "; 
    $fbifram .= "'width': '75%', 'height': '75%', ";
    $fbifram .= "'type': 'iframe', ";
    $fbifram .= "'titlePosition': 'outside', ";
    if (in_array("iframe", $fbclbutt)) {
        $fbifram .= "'showCloseButton': false ";
    } else {
        $fbifram .= "'showCloseButton': true ";
    }
    $fbifram .= "});\n";    

    // standard fancybox swf/flash profile   
    $fbflash = "\t\tjQuery(\"a.fancyflash\").fancybox({ ";
    $fbflash .= "padding: 0, autoScale: false, ";
    $fbflash .= "'transitionIn': 'none', 'transitionOut': 'none', ";
    if (in_array("flash", $fbclbutt)) {
        $fbflash .= "'showCloseButton': false ";
    } else {
        $fbflash .= "'showCloseButton': true ";
    }
    $fbflash .= "});\n";    

    // insert html comment within this script to fool markup validation
    $customjs  = "\n<!--\n";
    $customjs .= "\tjQuery(document).ready(function() {\n";
    $customjs .= $fbparms;
    $customjs .= $fbytube;
    $customjs .= $fbtext;
    $customjs .= $fbifram;
    $customjs .= $fbflash;
    $customjs .= "\t});\n";    
    // insert html comment within this script to fool markup validation
    $customjs .= "\t// -->\n";

    $path = $PIVOTX['paths']['extensions_url']."fancybox/";

    OutputSystem::instance()->addCode(
        'fancybox-stylehref',
        OutputSystem::LOC_HEADEND,
        'link',
        array('href'=>$path.'jquery.fancybox-1.3.4.css','media'=>'screen','_priority'=>OutputSystem::PRI_NORMAL+10)
    );

    OutputSystem::instance()->addCode(
        'fancybox-stylehref-ie6',
        OutputSystem::LOC_HEADEND,
        'link',
        array('href'=>$path.'jquery.fancybox_IE6_-1.3.4.css','media'=>'screen','_ms-expression'=>'if lt IE 7','_priority'=>OutputSystem::PRI_NORMAL+11)
    );
    
    OutputSystem::instance()->addCode(
        'fancybox-stylehref-ie7',
        OutputSystem::LOC_HEADEND,
        'link',
        array('href'=>$path.'jquery.fancybox_IE_-1.3.4.css','media'=>'screen','_ms-expression'=>'if IE 7','_priority'=>OutputSystem::PRI_NORMAL+11)
    );

    OutputSystem::instance()->addCode(
        'fancybox-stylehref-ie8',
        OutputSystem::LOC_HEADEND,
        'link',
        array('href'=>$path.'jquery.fancybox_IE_-1.3.4.css','media'=>'screen','_ms-expression'=>'if IE 8','_priority'=>OutputSystem::PRI_NORMAL+11)
    );
    // easing only needed for elastic transition
    if ($fbprof == 2){
        OutputSystem::instance()->addCode(
            'fancybox-js-easing',
            OutputSystem::LOC_HEADEND,
            'script',
            array('src'=>$path.'jquery.easing-1.3.js','_priority'=>OutputSystem::PRI_NORMAL+20)
        );
    }
    // only add mousewheel when fancybox_profile has been set to something
    if (!$fbprof == ''){
        OutputSystem::instance()->addCode(
            'fancybox-js-mousewheel',
            OutputSystem::LOC_HEADEND,
            'script',
            array('src'=>$path.'jquery.mousewheel-3.0.4.js','_priority'=>OutputSystem::PRI_NORMAL+20)
        );
    }

    OutputSystem::instance()->addCode(
        'fancybox-js-src',
        OutputSystem::LOC_HEADEND,
        'script',
        array('src'=>$path.'jquery.fancybox-1.3.4.js','_priority'=>OutputSystem::PRI_NORMAL+21)
    );

    OutputSystem::instance()->addCode(
        'fancybox-js',
        OutputSystem::LOC_HEADEND,
        'script',
        array('_priority'=>OutputSystem::PRI_NORMAL+22),
        $customjs
    );
}



?>
