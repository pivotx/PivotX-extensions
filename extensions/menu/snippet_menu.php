<?php
// - Extension: Hierarchical menus
// - Version: 1.1
// - Author: PivotX Team / Harm Kramer
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: Add hierarchical menus to your website
// - Date: 2013-11-21
// - Required PivotX version: 2.0.2
// - Identifier: menu

// Register 'menu' as a smarty tag.
$PIVOTX['template']->register_function('menu', 'smarty_menu');

/**
 * Outputs a hierarchical menu
 *
 *
 * @param array $params
 * @return string
 */
function smarty_menu($params, &$smarty) {
    global $PIVOTX;

    
    $params = cleanParams($params);

    $firstchapter = getDefault($params['firstchapter'], "1", true);
    $toplevelbegin = getDefault($params['toplevelbegin'], "<strong>%chaptername%</strong><br /><small>%description%</small><ul>", true);
    $toplevelitem = getDefault($params['toplevelitem'], "<li %active%><a href='%link%'>%title%</a>%sub%</li>");
    $toplevelend = getDefault($params['toplevelend'], "</ul>", true);
    $sublevelbegin = getDefault($params['sublevelbegin'], "<ul>", true);
    $sublevelitem = getDefault($params['sublevelitem'], "<li %active%><a href='%link%'>%title%</a>%sub%</li>", true);
    $sublevelend = getDefault($params['sublevelend'], "</ul>", true);
    $topsubinclude = getDefault($params['topsubinclude'], 0, true);
    // Parameters 'sort' and 'exclude' are used below..

    // If we use 'isactive', set up the $pageuri and $isactive vars.
    if (!empty($params['isactive'])) {
        // Get the current page uri.
        $smartyvars = $smarty->get_template_vars();
        $pageuri = getDefault($smartyvars['pageuri'], "");
        $isactive = $params['isactive'];
    } else {
        $pageuri = "";
        $isactive = "";
    }


    $chapters = $PIVOTX['pages']->getIndex();

    $output = "";
    $counter = 0;

    // Iterate through the chapters, find the one we need to start with
    foreach ($chapters as $chapter) {
        if ($chapter['uid']==$firstchapter || makeURI($chapter['chaptername'])==makeURI($firstchapter)) {
            $thischapter = $chapter;
            break;
        }
    }
    
    if (empty($thischapter)) {
        debug("No suitable toplevel chapter found for '$firstchapter'.");
        return "<!-- No suitable toplevel chapter found for '$firstchapter'. -->";
    }

    // Add the toplevelbegin to output
    $temp_output = $toplevelbegin;
    $temp_output = str_replace("%chaptername%", $chapter['chaptername'], $temp_output);
    $temp_output = str_replace("%description%", $chapter['description'], $temp_output);
    $output = $temp_output . "\n";

    if($params['sort'] == "title") {
        asort($thischapter['pages']);
    }
    
    // Iterate through the pages
    foreach ($thischapter['pages'] as $page) {
    
        if(in_array($page['uri'], explode(",", $params['exclude']))) {
            continue;
        }

        if ($page['status'] != 'publish') {
            continue; // skip it!
        }

        // Increase the counter, that keeps track of the number of menus
        $counter++;

        // Check if the current page is the 'active' one.
        if (!empty($isactive) && ($page['uri']==$pageuri)) {
            $thisactive = $isactive;
        } else {
            $thisactive = "";
        }

        $pagelink = makePageLink($page['uri'], $page['title'], $page['uid'], $page['date'], $params['weblog']);

        // add the page to output
        $temp_output = $toplevelitem;
        $temp_output = str_replace("%title%", $page['title'], $temp_output);
        $temp_output = str_replace("%subtitle%", $page['subtitle'], $temp_output);
        $temp_output = str_replace("%user%", $page['user'], $temp_output); // To do: filter this to nickname, email, etc.
        $temp_output = str_replace("%date%", $page['date'], $temp_output); // To do: allow output formatting.
        $temp_output = str_replace("%link%", $pagelink, $temp_output);
        $temp_output = str_replace("%uri%", $page['uri'], $temp_output);
        $temp_output = str_replace("%active%", $thisactive, $temp_output);
        $temp_output = str_replace("%counter%", $counter, $temp_output);

        $addtoplvl   = '';
        // Include the toplevel link on the sublevel list?
        if ($topsubinclude) {
            $addtoplvl  = $temp_output;
        }
    
        // check if the current page has an uri that is the same as its own chapter
        $page['chaptername'] = $chapters[ $page['chapter'] ]['chaptername'];
        if ( makeURI($page['chaptername'])==makeURI($page['uri']) ) {
            $thispage = $page['uri'];
            debug("Loop prevented! Page '$thispage' has the same uri as its own chapter.");
            return "<!-- Loop prevented! Page '$thispage' has the same uri as its own chapter. -->";
        }

        // Check if the current page has an uri that matches another chapter. If so, add a submenu
        foreach($chapters as $chapter) {
            if ( makeURI($chapter['chaptername'])==makeURI($page['uri']) ) {

                // Get the submenu..
                $ind = "\n\t\t";

                $sub_output = __menu_helper($params, $chapters, makeURI($page['uri']), 
                            "$ind$sublevelbegin", "$ind\t$sublevelitem", "$ind$sublevelend\n\t", 
                            $isactive, $pageuri, $counter, $addtoplvl, $sublevelbegin, $sublevelitem, $sublevelend);

                // Insert or append it, dependent on whether %sub% is in the temp_output..
                if (strpos($temp_output, "%sub%")>0) {
                    $temp_output = str_replace("%sub%", $sub_output, $temp_output);
                } else {
                    $temp_output .= $sub_output;
                }
            }
        }
        
        $temp_output = str_replace("%sub%", "", $temp_output);
        $output .= "\t" . $temp_output . "\n";

    }

    // Add the toplevelend to output
    $temp_output = $toplevelend;
    $temp_output = str_replace("%chaptername%", $chapter['chaptername'], $temp_output);
    $temp_output = str_replace("%description%", $chapter['description'], $temp_output);
    $output .= $temp_output . "\n";

    return $output;

}



function __menu_helper($params, $chapters, $chaptername, $begin, $item, $end, $isactive, $pageuri, $counter, $addtoplvl, $sublevelbegin, $sublevelitem, $sublevelend) {
    global $PIVOTX;

    $subcounter = 0;    
    // Iterate through the chapters, find the one we need to start with
    foreach ($chapters as $chapter) {
        if ($chapter['uid']==$chaptername || makeURI($chapter['chaptername'])==$chaptername) {
            $thischapter = $chapter;
            break;
        }
    }
    
    if (empty($thischapter)) {
        debug("No suitable submenu chapter found for '$thischapter'. This should never happen.");
        return "<!-- No suitable submenu chapter found for '$thischapter'. This should never happen. -->";
    }
    // Add the toplevelbegin to output
    $temp_output = $begin;
    $temp_output = str_replace("%chaptername%", $chapter['chaptername'], $temp_output);
    $temp_output = str_replace("%description%", $chapter['description'], $temp_output);
    $temp_output = str_replace("%counter%", $counter, $temp_output);
    $output = $temp_output;
    
    // Iterate through the pages
    foreach ($thischapter['pages'] as $page) {

        if(in_array($page['uri'], explode(",", $params['exclude']))) {
            continue;
        }

        if ($page['status'] != 'publish') {
            continue; // skip it!
        }
        // Increase the counter, that keeps track of the number of submenus
        $subcounter++;

        // Check if the current page is the 'active' one.
        if (!empty($isactive) && ($page['uri']==$pageuri)) {
            $thisactive = $isactive;
        } else {
            $thisactive = "";
        }

        $pagelink = makePageLink($page['uri'], $page['title'], $page['uid'], $page['date'], $params['weblog']);

        // add the page to output
        $temp_output = $item;

        $temp_output = str_replace("%title%", $page['title'], $temp_output);
        $temp_output = str_replace("%subtitle%", $page['subtitle'], $temp_output);
        $temp_output = str_replace("%user%", $page['user'], $temp_output); // To do: filter this to nickname, email, etc.
        $temp_output = str_replace("%date%", $page['date'], $temp_output); // To do: allow output formatting.
        $temp_output = str_replace("%link%", $pagelink, $temp_output);
        $temp_output = str_replace("%uri%", $page['uri'], $temp_output);
        $temp_output = str_replace("%active%", $thisactive, $temp_output);
        $temp_output = str_replace("%counter%", $counter, $temp_output);
        $temp_output = str_replace("%subcounter%", $subcounter, $temp_output);

        // Include the toplevel link on the next sublevel list?
        if ($addtoplvl != '') {
            $nextaddtoplvl  = $temp_output;
        }
        // addtoplvl?
        if ($subcounter == 1) {
            $temp_output = $addtoplvl . $temp_output;
        }

        // Check if the current page has an uri that matches another chapter. If so, add (another) submenu
        foreach($chapters as $chapter) {
            if ( makeURI($chapter['chaptername'])==makeURI($page['uri']) ) {
                // Get the submenu..
                $ind = "\n\t\t\t";
                $sub_output = __menu_helper($params, $chapters, makeURI($page['uri']), 
                            "$ind$sublevelbegin", "$ind\t$sublevelitem", "$ind$sublevelend\n\t\t", 
                            $isactive, $pageuri, $subcounter, $nextaddtoplvl, $sublevelbegin, $sublevelitem, $sublevelend);
                
                // Insert or append it, dependent on whether %sub% is in the temp_output..
                if (strpos($temp_output, "%sub%")>0) {
                    $temp_output = str_replace("%sub%", $sub_output, $temp_output);
                } else {
                    $temp_output .= $sub_output;
                }                    
            }
        }
        
        $temp_output = str_replace("%sub%", "", $temp_output);
        $output .= $temp_output;

    }

    // Add the sublevelend to output
    $temp_output = $end;
    $temp_output = str_replace("%chaptername%", $chapter['chaptername'], $temp_output);
    $temp_output = str_replace("%description%", $chapter['description'], $temp_output);
    $output .= $temp_output;

    return $output;
    
    
}

?>
