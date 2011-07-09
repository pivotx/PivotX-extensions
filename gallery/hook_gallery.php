<?php
// - Extension: Gallery
// - Version: 0.12.1
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: Add simple galleries to your Entries or Pages
// - Date: 2011-07-09
// - Identifier: gallery
// - Required PivotX version: 2.2

$this->addHook(
    'in_pivotx_template',
    'entry-keywords-before',
    array('callback' => 'galleryFieldExtension' )
    );

$this->addHook(
    'in_pivotx_template',
    'page-keywords-before',
    array('callback' => 'galleryFieldExtension' )
    );

/**
 * Callback function for our hook..
 */ 
function galleryFieldExtension($content) {
    
    // print("<pre>\n"); print_r($entry); print("\n</pre>\n");
    
    $output = <<< EOM
    <script src="extensions/gallery/gallery.js" type="text/javascript"></script>
    <style>
        
        #galleryrow1 {
            width: 390px;
        }
        
        #galleryrow3 {
            width: 106px;
        }
        
        #galleryrow2, #galleryrow4, #galleryrow5 {
            display: none;
        }
    
        #gallerythumbnails {
            border: 1px solid #DDD;
            background-color: F2F2F2;
            padding: 4px;
            width: 380px;
            min-height: 100px;
        }
        
        #gallerywastebin {
            border: 1px solid #DDD;
            background-color: F2F2F2;
            margin-top: 4px;
            padding: 2px;
            height: 74px;
            background-image: url(pics/delete.png);
            background-position: bottom right;
            background-repeat: no-repeat;
            width: 100px;
        }        
        
        #gallerythumbnails img, #gallerywastebin img {
            border: 1px solid #888;
            margin: 0px;
            list-style-image: none;
            width: 70px;
            height: 70px;
            margin: 2px;
            float: left;            
        }
        
        
        .ghost {
            border: 1px dashed #BBB !important;
            background-color: #EEE !important;
            width: 70px;
            height: 70px;
            background-image: url(none);
        }
        
        .ghost img {
            display: none;
            visibility: hidden;
        }
        
    </style>
    <table class="formclass" border="0" cellspacing="0" width="650">
        <tbody>


            <tr>
                <td width="150">
                    <label><strong>%title%:</strong></label>
                </td>
                <td width="400">

                    <div id='galleryrow1'>
                        <a href='javascript:showGallery()'>%edit%</a>
                    </div>
                    
                    <div id='galleryrow2'>
                        <div id="gallerythumbnails" class='gallerysortable'></div>
                        
                        <textarea id="extrafield-galleryimagelist" name="extrafields[galleryimagelist]" style="width: 400px; display: none; visibility: hidden"/>%galleryimagelist%</textarea>
                    </div>
                    
                </td>
                <td width="100" class="buttons_small">
        
                    <div id='galleryrow3'>
                       &nbsp;
                    </div>
        
                    <div id='galleryrow4'>
                        <a href="javascript:;" onclick="openGalleryUploadWindow('%label1%', $('#extrafield-image'), 'gif,jpg,png');">
                            <img src='pics/page_lightning.png' alt='' /> %label2%
                        </a>

                        <div class='cleaner'>&nbsp;</div>
                        <div id="gallerywastebin" class='gallerysortable'></div>
                    </div>    
                    
                </td>
            </tr>

            <tr id='galleryrow5'>
                <td colspan="3"><hr noshade="noshade" size="1" /></td>
            </tr>

        </tbody>
    </table>
EOM;

    // Substitute some labels..
    $output = str_replace("%title%", __("Gallery"), $output);
    $output = str_replace("%edit%", __("Edit Gallery"), $output);
    $output = str_replace("%label1%", __("Add an image"), $output);
    $output = str_replace("%label2%", __("Add"), $output);

    // For ease of use, just try to replace everything in $entry here:
    foreach($content as $key=>$value) {
        $output = str_replace("%".$key."%", $value, $output);
    }
    foreach($content['extrafields'] as $key=>$value) {
        $output = str_replace("%".$key."%", $value, $output);
    }
    // Don't keep any %whatever%'s hanging around..
    $output = preg_replace("/%([a-z0-9_-]+)%/i", "", $output);

    return $output;
    
}



// Register 'gallery' as a smarty block tag.
$PIVOTX['template']->register_block('gallery', 'smarty_gallery');

function smarty_gallery($params, $text, &$smarty) {
    global $PIVOTX;

    // This function gets called twice. Once when enter it, and once when
    // leaving the block. In the latter case we return an empty string.
    if (!isset($text)) { return ""; }

    $params = cleanParams($params);

    $vars = $smarty->get_template_vars();
    $entry = $vars['entry'];
    $page = $vars['page'];

    // Get the images from the Entry or Page..
    $gallery = getDefault($entry['extrafields']['galleryimagelist'], $page['extrafields']['galleryimagelist']);

    $output = "";
    
    if (!empty($gallery)) {
        $gallery = explode("\n", $gallery);
        $imgbeg  = getDefault($params['imgbeg'], "0");
        $imgend  = getDefault($params['imgend'], "9999");
        $maxthumb = getDefault($params['specthumbmax'], "0");
        $uplpath  = getDefault($PIVOTX['config']->get('upload_path'),"");
        /* check whether string in uplpath is OK for usage */
        $uplmain  = $uplpath;
        $slashpos = strpos($uplpath, "/");
        if ($slashpos === false) {
           if ($uplpath != "") {
               $uplmain = $uplpath . "/";
           }
        } else {
           /* get maindir out of uplpath */
           $uplmain = substr($uplpath, 0 , ($slashpos + 1));
        }
        if ($maxthumb == 0) {
           $thumbw = 200;
           $thumbh = 152;
        }
        $counter = 0;
        foreach($gallery as $image) {
            $image = trim($image);
            list($image, $title, $alttext) = explode('###',$image);

            $nicefilename = formatFilename($image);
            if (empty($alttext)) {
                $alttext = $nicefilename;
            }
            if (empty($title)) {
                $title = $nicefilename;
            }
            
            if (!empty($image)) {
            
                $counter++; 
                if ($maxthumb > 0) {
                   list($thumbw, $thumbh) = getimagesize("../".$uplpath.$image);
                   if ($thumbw > $thumbh) {
                      $thumbh = round($thumbh * ($maxthumb / $thumbw));
                      $thumbw = $maxthumb;
                   }   
                   else {
                      $thumbw = round($thumbw * ($maxthumb / $thumbh));
                      $thumbh = $maxthumb;   
                   }
                }
                $imgtag = '<img';
                if ( ($counter < $imgbeg)||($counter > $imgend) ) {
                   $imgtag = '<img style="display:none"';
                }       
                $even = ($counter%2) ? 1 : 0;
            
                $this_output = $text;
                $this_output = str_replace('<img', $imgtag, $this_output);
                $this_output = str_replace('%title%', $title, $this_output);
                $this_output = str_replace('%alttext%', $alttext, $this_output);
                $this_output = str_replace('%filename%', $image, $this_output);
                $this_output = str_replace('%nicefilename%', $nicefilename, $this_output);
                $this_output = str_replace('%uid%', $entry['uid'], $this_output);
                $this_output = str_replace('%imageurl%', $PIVOTX['paths']['upload_base_url'], $this_output);
                $this_output = str_replace('%pivotxurl%', $PIVOTX['paths']['pivotx_url'], $this_output);
                $this_output = str_replace('%count%', $counter, $this_output);
                $this_output = str_replace('%even%', $even, $this_output);
                $this_output = str_replace('%odd%', 1-$even, $this_output);
                $this_output = str_replace('%thumbw%', $thumbw, $this_output);
                $this_output = str_replace('%thumbh%', $thumbh, $this_output);

                // remove any %..% that might be left
                // $this_output = preg_replace("/%([a-z0-9_-]+)%/i", "", $this_output);
                
                $output .= $this_output;
            }
        }
    }
    
    // If a specific popup type is selected execute the callback.
    if (isset($params['popup'])) {
        $callback = $params['popup']."IncludeCallback";
        if (function_exists($callback)) {
            $PIVOTX['extensions']->addHook('after_parse', 'callback', $callback);
        } else {
            debug("There is no function '$callback' - the popups won't work.");
        }
    }

    return entifyAmpersand($output);

}

// Register 'gallery_image' as a smarty tag.
$PIVOTX['template']->register_function('gallery_image', 'smarty_gallery_image');

function smarty_gallery_image($params, &$smarty) {
    global $PIVOTX;

    $params = cleanParams($params);

    $number = getDefault($params['number'], 0);
    $attr = getDefault($params['attr'], 'src');

    $vars = $smarty->get_template_vars();
    $entry = $vars['entry'];
    $page = $vars['page'];

    // Get the images from the Entry or Page..
    $gallery = getDefault($entry['extrafields']['galleryimagelist'], $page['extrafields']['galleryimagelist']);

    $output = "";
    
    if (!empty($gallery)) {
        $gallery = explode("\n", $gallery);

        $image = trim($gallery[$number]);

        list($image, $title, $alttext) = explode('###',$image);

        if ($attr == 'src') {
            $output = $image;
        } elseif ($attr == 'title') {
            $output = $title;
        } elseif ($attr == 'alttext') {
            $output = $alttext;
        } 

    }
    
    return entifyAmpersand($output);

}

// Register 'gallery_count' as a smarty tag.
$PIVOTX['template']->register_function('gallery_count', 'smarty_gallery_count');

function smarty_gallery_count($params, &$smarty) {
    global $PIVOTX;

    $vars = $smarty->get_template_vars();
    $entry = $vars['entry'];
    $page = $vars['page'];

    // Get the images from the Entry or Page..
    $gallery = getDefault($entry['extrafields']['galleryimagelist'], $page['extrafields']['galleryimagelist']);

    $imagecount = count(explode("\n", $gallery));
    return $imagecount;
    
}
    
// Register 'gallery_imagelist' as a smarty tag.
$PIVOTX['template']->register_function('gallery_imagelist', 'smarty_gallery_imagelist');

function smarty_gallery_imagelist($params, &$smarty) {
    global $PIVOTX;

    $vars = $smarty->get_template_vars();
    $entry = $vars['entry'];
    $page = $vars['page'];

    // Get the images from the Entry or Page..
    $gallery = getDefault($entry['extrafields']['galleryimagelist'], $page['extrafields']['galleryimagelist']);
    $gallery = explode("\n", $gallery);

    $var = getDefault($params['var'], "imagelist");

    foreach($gallery as $key => $value) {
        list($image, $title, $alttext) = explode('###',$value);
        $gallery[$key] = array(
            'image' => $image, 'title' => $title, 'alt' => $alttext
        );
    }

    $smarty->assign($var, $gallery);

    return;
    
}

?>