<?php
// - Extension: Slideshow
// - Version: 0.10
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: A snippet and widget to add a slideshow to your site or entries/pages/templates.
// - Date: 2012-12-19
// - Identifier: slideshow
// - Required PivotX version: 2.3.6


global $slideshow_config;

$slideshow_config = array(
    'slideshow_width' => "250",
    'slideshow_height' => "180",
    'slideshow_zc' => "",
    'slideshow_css' => "slideshow",
    'slideshow_folder' => "slideshow",
    'slideshow_timeout' => "4000",
    'slideshow_animtime' => "1200",
    'slideshow_limit' => "15",
    'slideshow_orderby' => "date_desc",
    'slideshow_popup' => 'no',
    'slideshow_only_snippet' => 0,
    'slideshow_recursion' => 'no',
    'slideshow_nicenamewithdirs' => 0,
    'slideshow_tooltip' => 0,
    'slideshow_ttopacity' => "0.5",
    'slideshow_uibefore' => 0,
    'slideshow_iptcindex' => "",
    'slideshow_iptcencoding' => "",
    'slideshow_uishow' => 0
);

/**
 * Adds the hook for slideshowAdmin()
 *
 * @see slideshowAdmin()
 */
$this->addHook(
    'configuration_add',
    'slideshow',
    array("slideshowAdmin", "Slideshow")
);


/**
 * Adds the hook for the actual widget. We just use the same
 * as the snippet, in this case.
 *
 * @see smarty_slideshow()
 */
$this->addHook(
    'widget',
    'slideshow',
    "widget_slideshow"
);


/**
 * Add some javascript to the header..
 */
$css = getDefault($PIVOTX['config']->get('slideshow_css'), $slideshow_config['slideshow_css']);
$this->addHook(
    'after_parse',
    'insert_before_close_head',
    "
    <!-- Includes for slideshow extension -->
    <script type='text/javascript' src='[[pivotx_dir]]extensions/slideshow/jquery.slideviewer.1.2.1.js'></script>
    <script type='text/javascript' src='[[pivotx_dir]]extensions/slideshow/jquery.easing.1.3.js'></script>
    <script type='text/javascript'>
        var slideshow_pathToImage = '[[pivotx_dir]]extensions/slideshow/spinner.gif';
    </script>
    <link href='[[pivotx_dir]]extensions/slideshow/" . $css . ".css' rel='stylesheet' type='text/css' />\n"
);

// If the hook for the jQuery include in the header was not yet installed, do so now..
$this->addHook('after_parse', 'callback', 'jqueryIncludeCallback');


// Register 'slideshow' as a smarty tag.
$PIVOTX['template']->register_function('slideshow', 'smarty_slideshow');

/**
 * Output a slideshow feed as a widget
 *
 * @return string
 */
function widget_slideshow() {
    global $PIVOTX, $slideshow_config;

    $key = 'slideshow_only_snippet';
    $enabled = getDefault($PIVOTX['config']->get($key), $slideshow_config[$key]);
    if ($enabled) {
        return;
    } else {
        $output = smarty_slideshow(array());
        $output = "\n<div class='widget-lg'>$output</div>\n"; 
        return $output;
    }
}

/**
 * Returns a list of sub directories with absolute paths
 */
function slideshowGetDirs($dir, $recursion='all') {
    $array = array();
    $d = dir($dir);
    while (false !== ($entry = $d->read())) {
        if ($entry!='.' && $entry!='..' && $entry!='.svn') {
            $entry = $entry.DIRECTORY_SEPARATOR;
            if (is_dir($dir.$entry)) {
                $subdirs = slideshowGetDirs($dir.$entry);
                if ($recursion=='all' || count($subdirs) == 0) {
                    $array[] = $dir.$entry;
                }
                if (count($subdirs) > 0) {
                    $array = array_merge($array, $subdirs);
                }
            }
        }
    }
    $d->close();
    return $array;
}

/**
 * Output a slideshow feed as a template
 *
 * @param array $params
 * @return string
 */
function smarty_slideshow($params) {
    global $PIVOTX, $slideshow_config;

    static $slideshowcount = 0;

    $js_insert = <<<EOF
<script type="text/javascript">
var slideshowtimeout%count% = null;
var slideNext_%count%_currentslide = -1;
var realtimeout%count% = %timeout% + %animtime%;
var currtime%count% = %animtime% / 2; 

jQuery(window).bind("load", function(){
    jQuery("div#pivotx-slideshow-%count%").slideView(%parms%);
    slideshowtimeout%count% = window.setTimeout('slideNext_%count%()', realtimeout%count%);
});

function slideclick_%count%(clickval%count%) {
    clickval%count% = clickval%count% - 1;
    slideNext_%count%_currentslide = clickval%count%; 
    // reset the running timeout and set a new one
    window.clearTimeout(slideshowtimeout%count%);
    slideshowtimeout%count% = setTimeout('slideNext_%count%()', realtimeout%count%);
    return;
}

function slideNext_%count%() {
    if( slideNext_%count%_currentslide == -1 ) {
        slideNext_%count%_currentslide = 0;
        jQuery("#stripTransmitter%count% a").click(function() { slideclick_%count%(this.innerHTML) });
    }

    var slidewidth = jQuery("div#pivotx-slideshow-%count%").find("li").find("img").width();
    var amountofslides = %amount% - 1; 

    if (amountofslides > slideNext_%count%_currentslide) {
        slideNext_%count%_currentslide++;
    } else {
        slideNext_%count%_currentslide = 0;
    }
    var xpos = (-slidewidth * slideNext_%count%_currentslide);
    jQuery("div#pivotx-slideshow-%count%").find("ul").animate({ left: xpos}, %animtime%, "easeInOutExpo");
    // using this construction due to the fact that animate callback does not pick up the jquery elements
    // and it offers the possibility to time the change during the animation
    setTimeout(function (){
        jQuery("#stripTransmitter%count% a").removeClass("current");
        jQuery("#stripTransmitter%count% a:eq("+slideNext_%count%_currentslide+")").addClass("current");
    }, currtime%count%); 
    slideshowtimeout%count% = window.setTimeout('slideNext_%count%()', realtimeout%count%);
}
</script>
EOF;

    $params = clean_params($params);
    foreach(array('folder','width','height','timeout','animtime','limit','orderby',
	'popup','recursion','nicenamewithdirs','zc','tooltip','ttopacity',
	'uishow','uibefore','iptcindex','iptcencoding') as $key) {
        if (isset($params[$key])) {
            $$key = $params[$key];
        } else {
            $$key = getDefault($PIVOTX['config']->get('slideshow_'.$key), $slideshow_config['slideshow_'.$key]);
        }
    }


    $imagefolder = addTrailingSlash($PIVOTX['paths']['upload_base_path'].$folder);
    $ok_extensions = explode(",", "jpg,jpeg,png,gif");

    if (!file_exists($imagefolder) || !is_dir($imagefolder)) {
        debug("Image folder $imagefolder does not exist.");
        echo("Image folder $imagefolder does not exist.");
        return "";
    } else if (!is_readable($imagefolder)) {
        debug("Image folder $imagefolder is not readable.");
        echo("Image folder $imagefolder is not readable.");
        return "";
    }

    $images = array();

    $key = "";

    if ($recursion == 'no') {
        $dirs = array($imagefolder);
    } else {
        $dirs = slideshowGetDirs($imagefolder, $recursion);
        if ($recursion == 'all') {
            array_unshift($dirs, $imagefolder);
        }
    }
    foreach($dirs as $folder) {
        $dir = dir($folder);
        while (false !== ($entry = $dir->read())) {
            if ( in_array(strtolower(getExtension($entry)), $ok_extensions) ) {
                if (strpos($entry, ".thumb.")>0) {
                    continue;
                }
                $entry = $folder.$entry;
                if ($orderby=='date_asc' || $orderby=='date_desc') {
                    $key = filemtime($entry).rand(10000,99999);
                    $images[$key] = $entry;
                } else {
                    $images[] = $entry;
                }
            }
        }
        $dir->close();
    }

    if ($orderby=='date_asc') {
        ksort($images);
    } else if ($orderby=='date_desc') {
        ksort($images);
        $images = array_reverse($images);
    } else if ($orderby=='alphabet') {
        natcasesort($images);
    } else {
        shuffle($images);
    }

    // Cut it to the desired length..
    $images = array_slice($images, 0, $limit);
    
    // Built the parms
    $zcimg = '';
    if (isset($zc)) {
        $zcimg = '&amp;zc=' . $zc;
    }
    if ( $tooltip == 1 ) {
        $parms = "{toolTip: true";
    } else {
        $parms = "{toolTip: false";
    }
    $parms .= ", ttOpacity: " . $ttopacity;

    if ( $uishow == 1) {
        $parms .= ", uiShow: true";
    } else {
        $parms .= ", uiShow: false";
    }

    if ( $uibefore == 1) {
        $parms .= ", uiBefore: true}";
    } else {
        $parms .= ", uiBefore: false}";
    }

    $js_insert = str_replace('%timeout%', $timeout, $js_insert);
    $js_insert = str_replace('%animtime%', $animtime, $js_insert);
    $js_insert = str_replace('%count%', $slideshowcount, $js_insert);
    $js_insert = str_replace('%amount%', count($images), $js_insert);
    $js_insert = str_replace('%parms%', $parms, $js_insert);
    $PIVOTX['extensions']->addHook('after_parse', 'insert_before_close_head', $js_insert);

    // If a specific popup type is selected execute the callback.
    if ($popup != 'no') {
        $callback = $popup."IncludeCallback";
        if (function_exists($callback)) {
            $PIVOTX['extensions']->addHook('after_parse', 'callback', $callback);
        } else {
            debug("There is no function '$callback' - the popups won't work.");
        }
    }
    $output = "\n<div id=\"pivotx-slideshow-$slideshowcount\" class=\"svw\">\n<ul>\n";

    foreach ($images as $image) {
        $file = $image;
        $image = str_replace($PIVOTX['paths']['upload_base_path'], '', $image);
        $image = str_replace(DIRECTORY_SEPARATOR, '/', $image);
        $nicefilename = formatFilename($image, $nicenamewithdirs);

        $title = false;
        if ($iptcindex) {
            getimagesize($file, $iptc);
            if (is_array($iptc) && $iptc['APP13']) {
                $iptc = iptcparse($iptc['APP13']);
                $title = $iptc[$iptcindex][0];
                if ($iptcencoding) {
                    $title = iconv($iptcencoding, 'UTF-8', $title);
                }
                $title = cleanAttributes($title);
            }
        }
        if (!$title) {
            $title = $nicefilename;
        }
        $line = "<li>\n";
        if ($popup != 'no') {
            $line .= sprintf("<a href=\"%s%s\" class=\"$popup\" rel=\"slideshow\" title=\"%s\">\n",
                $PIVOTX['paths']['upload_base_url'], $image, $title);
        }
        $thumbdims = '';
        $imgdims = '';
        if ($width > 0) { $thumbdims .= '&amp;w='.$width; $imgdims .= ' width="'.$width.'"'; }
        if ($height > 0) { $thumbdims .= '&amp;h='.$height; $imgdims .= ' height="'.$height.'"'; }
        $line .= sprintf("<img src=\"%sincludes/timthumb.php?src=%s%s%s\" " .
                "alt=\"%s\" title=\"%s\" %s />\n",
            $PIVOTX['paths']['pivotx_url'], rawurlencode($image), $thumbdims, $zcimg, $title, $title, $imgdims);
        if ($popup != 'no') {
            $line .= "</a>";
        }
        $line .= "</li>\n";
        $output .= $line;

    }

    $output .= "</ul>\n</div>\n";
    
    $slideshowcount++;

    return $output;
}

/**
 * The configuration screen for slideshow
 *
 * @param unknown_type $form_html
 */
function slideshowAdmin(&$form_html) {
    global $PIVOTX, $slideshow_config;

    $form = $PIVOTX['extensions']->getAdminForm('slideshow');

    $form->add( array(
        'type' => 'text',
        'name' => 'slideshow_folder',
        'label' => __('Default folder name'),
        'value' => '',
        'error' => __('That\'s not a proper folder name!'),
        'text' => __("The name of the folder in where the images are that Slideshow should use. " . 
            "This should be a folder inside your <tt>images</tt> folder. " .
            "So if you input <tt>slideshow</tt>, the slideshow will look in the " .
            "<tt>/images/slideshow/</tt> folder. Don't start or finish with a slash."),
        'size' => 32,
        'isrequired' => 1,
        'validation' => 'string|minlen=1|maxlen=32'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'slideshow_timeout',
        'label' => __('Default timeout'),
        'value' => '',
        'error' => __('Error!'),
        'text' => __('The time (in milliseconds) between each image in the slideshow.'),
        'size' => 6,
        'isrequired' => 1,
        'validation' => 'integer|min=1|max=10000'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'slideshow_animtime',
        'label' => __('Default animation time'),
        'value' => '',
        'error' => __('Error!'),
        'text' => __('The time (in milliseconds) for the animation of one image to the next.'),
        'size' => 6,
        'isrequired' => 1,
        'validation' => 'integer|min=1|max=10000'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'slideshow_width',
        'label' => __('Default width'),
        'value' => '',
        'error' => __('Error!'),
        'text' => "",
        'size' => 6,
        'isrequired' => 1,
        'validation' => 'integer|min=1|max=500'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'slideshow_height',
        'label' => __('Default height'),
        'value' => '',
        'error' => __('Error!'),
        'text' => __("The width and height of the thumbnails in the widget. " . 
            "The borders are added to this, so the total dimensions of the widget can be wider and taller."),
        'size' => 6,
        'isrequired' => 1,
        'validation' => 'integer|min=1|max=500'
    ));
    
    $form->add( array(
        'type' => 'text',
        'name' => 'slideshow_zc',
        'label' => __('Default zoom crop'),
        'value' => '',
        'error' => __('Error!'),
        'text' => __("The zoom crop value to use when creating thumbnails. " .
            "See includes/timthumb.php for an explanation."),
        'size' => 3,
        'isrequired' => 0,
        'validation' => 'integer|min=0|max=3'
    ));  
    
    $form->add( array(
        'type' => 'text',
        'name' => 'slideshow_css',
        'label' => __('Default css'),
        'value' => '',
        'error' => __('Error!'),
        'text' => __("The css to use for slideshow."),
        'size' => 50,
        'isrequired' => 1,
        'validation' => 'string|minlen=3|maxlen=50'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'slideshow_limit',
        'label' => __('Limit'),
        'value' => '',
        'error' => __('Error!'),
        'text' => __("This limits the number of items that are shown. " .
            "If you set it too high, it will take longer to load your site."),
        'size' => 6,
        'isrequired' => 1,
        'validation' => 'string|min=1|max=500'
    ));

    $form->add( array(
        'type' => 'select',
        'name' => 'slideshow_orderby',
        'label' => __('Order by'),
        'value' => '',
        'firstoption' => __('Select'),
        'options' => array(
            'date_asc' => __("Date ascending"),
            'date_desc' => __("Date descending"),
            'alphabet' => __("Alphabet"),
            'random' => __("Random")
        ),
        'isrequired' => 1,
        'validation' => 'any',
        'text' => __("Select the order in which the images are shown.")
    ));

    $form->add( array(
        'type' => 'select',
        'name' => 'slideshow_popup',
        'label' => __("Use popup"),
        'options' => array(
            'no' => __("No"),
            'thickbox' => __("Thickbox"),
            'fancybox' => __("Fancybox"),
        ),
        'isrequired' => 1,
        'validation' => 'any',
        'text' => __("Select which popup type images are displayed in when clicked.")
    ));

    $form->add( array(
        'type' => 'select',
        'name' => 'slideshow_recursion',
        'label' => __('Use recursion'),
        'options' => array(
            'no' => __("No"),
            'leaf' => __("Leaf"),
            'all' => __("All"),
        ),
        'isrequired' => 1,
        'validation' => 'any',
        'text' => sprintf('<p>%s</p>', 
            __("If recursion is enabled images from either all subdirectories or just the leaf " .
                "subdirectories will be included in the slide show."))
    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'slideshow_only_snippet',
        'label' => __("Use only as snippet"),
        'text' => sprintf(__("Yes, I don't want %s to appear among the widgets."), "Slideshow")
    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'slideshow_nicenamewithdirs',
        'label' => __("Use directories in title"),
        'text' => __("Yes, include directory names to the automatically generated image titles.")
    ));
    
    $form->add( array(
        'type' => 'custom',
        'text' => sprintf("<tr><td colspan='2'><h3>%s</h3> <em>(%s)</em></td></tr>",
            __('Buttons'))
    ));
    
    $form->add( array(
        'type' => 'checkbox',
        'name' => 'slideshow_tooltip',
        'label' => __("Show title when hovering over buttons"),
        'text' => __("Yes, include the title as a tooltip for the buttons.")
    ));
    
    $form->add( array(
        'type' => 'text',
        'name' => 'slideshow_ttopacity',
        'label' => __('Tooltip opacity'),
        'value' => '',
        'error' => __('Error!'),
        'text' => __("Set the opacity of the shown tooltip."),
        'size' => 3,
        'isrequired' => 1,
        'validation' => 'string|min=0.1|max=1.0'
    )); 

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'slideshow_uishow',
        'label' => __("Show buttons with the slideshow"),
        'text' => __("Yes, show the buttons to go with the slideshow.")
    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'slideshow_uibefore',
        'label' => __("Show buttons before slideshow"),
        'text' => __("Yes, show the buttons first and then the slideshow.")
    ));

    $form->add( array(
        'type' => 'custom',
        'text' => sprintf("<tr><td colspan='2'><h3>%s</h3> <em>(%s)</em></td></tr>",
            __('Advanced Configuration'),
            __('Warning! These features are experimental, so use them with caution!') )
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'slideshow_iptcindex',
        'label' => __('IPTC Index'),
        'value' => '',
        'text' => __("Index of image title in IPTC table. (Picasa comments use '2#120'). " .
            "Leave blank to generate a nicename."),
        'size' => 32,
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'slideshow_iptcencoding',
        'label' => __('IPTC Encoding'),
        'value' => '',
        'text' => __("Encoding of image IPTC texts. (Use the iconv encoding names.) " .
            "Leave blank for no decoding."),
        'size' => 32,
    ));

    $form->use_javascript(true);

    /**
     * Add the form to our (referenced) $form_html. Make sure you use the same key
     * as the first parameter to $PIVOTX['extensions']->getAdminForm
     */
    $form_html['slideshow'] = $PIVOTX['extensions']->getAdminFormHtml($form, $slideshow_config);



}


?>
