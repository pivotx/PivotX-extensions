<?php
// - Extension: Blog Stats
// - Version: 1.4.1
// - Author: Geoffrey A. Wagner (Stingray)
// - Email: stingray@afn.org
// - Site: http://pivotx.mobius-design.net/?p=blog-stats
// - Updatecheck: http://pivotx.mobius-design.net/
// - Description: A snippet extension that counts entries and comments in your PivotX across all categories or limited to selected categories and on a per-category basis.  It is based on a similar snippet for Pivot by Bram Nijmeijer (formerly known as Tenshi) called Total Posts.
// - Date: 2014-02-14
// - Identifier: blogstats

//register as a smarty tag
$PIVOTX['template']->register_function('blogstats', 'smarty_blogstats');

//this function is copied and modified from PivotX core
function authorentrieslink($name, $cats, $override_weblog="") {
    global $PIVOTX;

    //set the weblog, according to passed parameter or current weblog
    $weblog = get_default($override_weblog, $PIVOTX['weblogs']->getCurrent());

    //create the category list
    if ($cats != "all") {
        if (count($cats) > 1) {
            $catlist = implode(",", $cats);
        } else {
            $catlist = $cats[0];
        }
    }
    
    $site_url = get_default($PIVOTX['weblogs']->get($weblog, 'site_url'), $PIVOTX['paths']['site_url']);
    if ($PIVOTX['config']->get('mod_rewrite') < 1) {
        $ulink = $site_url . "?u=" . $name;
        if ($cats != "all") {
            $ulink .= "&amp;c=" . $catlist;
        }
    } else {
        $userprefix = get_default($PIVOTX['config']->get('localised_author_prefix'), "author");
        $catprefix = get_default($PIVOTX['config']->get('localised_category_prefix'), "category");
        $ulink = $site_url . makeURI($userprefix) . "/" . $name;
        if ($cats != "all") {
            $ulink .= "/" . makeURI($catprefix) . "/" . $catlist;
        }
    }

    //if we have more than one weblog, add the w=weblogname parameter (does this matter?)
/*
    if (para_weblog_needed($weblog)) {
        if ($PIVOTX['config']->get('mod_rewrite')>0) {
            // we treat it as an extra 'folder'
            $ulink .= "/" . $weblog;
        } else {
            $ulink .= "&amp;w=" . $weblog;
        }
    }
*/

    return $ulink;
}

function smarty_blogstats($params) {
    global $PIVOTX; 

    $params = clean_params($params);

    if ($params['cat_include'] != "") {
        $include_cat = array_map('trim', explode(", ", $params['cat_include']));
    } else {
        $include_cat = array();
    }
    if ($params['cat_ignore'] != "") {
        $ignore_cat = array_map('trim', explode(", ", $params['cat_ignore']));
    } else {
        $ignore_cat = array();
    }
    if ($params['user_include'] != "") {
        $include_user = array_map('trim', explode(", ", $params['user_include']));
    } else {
        $include_user = array();
    }
    if ($params['user_ignore'] != "") {
        $ignore_user = array_map('trim', explode(", ", $params['user_ignore']));
    } else {
        $ignore_user = array();
    }
    $year = $params['year'];
    $month = $params['month'];
    $prefix = $params['prefix'];
    $cprefix = $params['cat_prefix'];
    $cformat = $params['cat_format'];
    $cpostfix = $params['cat_postfix'];
    $uprefix = $params['user_prefix'];
    $uformat = $params['user_format'];
    $upostfix = $params['user_postfix'];
    $postfix = $params['postfix'];
    $sort = $params['sort'];
    if ($params['fulltotal']) {
        $fulltotal = TRUE;
    } else {
        $fulltotal = FALSE;
    }
    if ($params['totalonly']) {
        $totalonly = TRUE;
    } else {
        $totalonly = FALSE;
    }
    if ($params['usersfirst']) {
        $usersfirst = TRUE;
    } else {
        $usersfirst = FALSE;
    }

    //make sure necessary params are set or return a message stating they are not set
    if ((!$cformat) && (!$uformat) && (!$totalonly)) {
        $msg = "Parameter 'cat_format' or 'user_format' required if not doing total count only";
        debug($msg);
	return "<!-- blogstats: $msg -->";
    }
    if (($month) && (!$year)) {
        $msg = "Parameter 'month' set, but parameter 'year' not set";
        debug($msg);
	return "<!-- blogstats: $msg -->";
    }
    if ($totalonly) {
        if ((!$prefix) && (!$postfix)) {
            $msg = "Parameter 'totalonly' set, but requires parameter 'prefix' or 'postfix'";
            debug($msg);
            return "<!-- blogstats: $msg -->";
        } else {
            $format = "";
        }
    }
    if ((!$sort) && (!$include_cat)) {
        $sort = "pivotx";
    }

    //initialize
    $output = "";
    $entry_array = array();
    $user_array = array();
    $comment_array = array();
    $track_array = array();

    //get entries
    $daysinmonth = array(
        "01" => "31",
        "02" => "28",
        "03" => "31",
        "04" => "30",
        "05" => "31",
        "06" => "30",
        "07" => "31",
        "08" => "31",
        "09" => "30",
        "10" => "31",
        "11" => "30",
        "12" => "31"
    );
    if ($year) {
        if (!$month) {
            $entries_start = $year . "-01-01-00-00";
            $entries_stop = $year . "-12-31-23-59";
        } else {
            $entries_start = $year . "-" . $month . "-01-00-00";
            if (($month == "02") && (checkdate(2, 29, $year))) {
                $daysinmonth['02'] = "29";
            }
            $entries_stop = $year . "-" . $month . "-" . $daysinmonth[$month] . "-23-59";
        }
    } else {
        $entries_start = "1969-02-01-00-00";
        $entries_stop = date("Y-m-d-H-i");
    }

    $entries_params = array ("start" => $entries_start, "end" => $entries_stop, "status" => "publish");
    //$blog_array = $PIVOTX['db']->read_entries($entries_params);
    $blogdb = new db();
    $blog_array = $blogdb->read_entries($entries_params);

    //get all categories, but ignore the hidden ones
    $categories = $PIVOTX['categories']->getCategorynames();
    $category_array = array();
    foreach($categories as $category) {
        $categorydata = $PIVOTX['categories']->getCategory($category);
        if (isset($categorydata['hidden']) && ($categorydata['hidden'] == 1)) {
            $ignore_cat[] = $category;
            continue;
        }
        $category_array[$category] = $categorydata['display'];
    }
    $modifier = $PIVOTX['parser']->modifier;
    $activecats = explode(",", $modifier['category']);

    //get all users
    $users = $PIVOTX['users']->getUserNicknames();

    //count entries and comments
    $totalentries = 0;
    $totalcomments = 0;
    $totaltracks = 0;
    foreach($blog_array as $entry) {
        $count_entry = false;
        if(empty($entry['category'])) {
            debug('Blogstats warning!: entry without category ' . $entry['uid']);
            continue;
        }
        foreach($entry['category'] as $category) {
            if ((count($include_cat) > 0) && (!in_array($category, $include_cat))) {
                continue;
            } else if ((count($ignore_cat) > 0) && (in_array($category, $ignore_cat))) {
                continue;
            }
            //okay, this entry should be counted.
            $count_entry = true;
            $userkey = "user_" . $entry['user'];
            $entry_array[] = $category;
            $comment_array[$category] += $entry['commcount'];
            $track_array[$category] += $entry['trackcount'];
            $user_array[] = $entry['user'];
            $comment_array[$userkey] += $entry['commcount'];
            $track_array[$userkey] += $entry['trackcount'];
        }
        if ($count_entry) {
            $totalentries++;
            $totalcomments += $entry['commcount'];
            $totaltracks += $entry['trackcount'];
        }
    }

    //finally, set the array with number of entries per category
    $entry_array = array_count_values($entry_array);
    $user_array = array_count_values($user_array);

    if ($fulltotal) {
        $totalentries = count($blog_array);
        $totalcomments = 0;
        $totaltracks = 0;
        foreach($blog_array as $entry) {
            $totalcomments += $entry['commcount'];
            $totaltracks += $entry['trackcount'];
        }
    }

    //sorting category display
    if ((count($include_cat)>0) && (!$sort)) {
        $temp_array = array();
	foreach($include_cat as $cat) {
            $temp_array[$cat] = $entry_array[$cat];
        }
        $entry_array = $temp_array;
    }
    if ($sort) {
        if ($sort == "pivotx") {
            $temp_array = array();
            foreach($category_array as $key => $value) {
                if (array_key_exists($key, $entry_array)) {
                    $temp_array[$key] = $entry_array[$key];
                }
            }
            $entry_array = $temp_array;
        } else if ($sort == "pivotx-rev") {
            $temp_array = array();
            foreach($category_array as $key => $value) {
                if (array_key_exists($key, $entry_array)) {
                    $temp_array[$key] = $entry_array[$key];
                }
            }
            $entry_array = array_reverse($temp_array);
        } else if ($sort == "alpha-asc") {
            ksort($entry_array);
        } else if ($sort == "alpha-desc") {
            krsort($entry_array);
        } else if ($sort == "entries-asc") {
            asort($entry_array);
        } else if ($sort == "entries-desc") {
            arsort($entry_array);
        } else if ($sort == "comments-asc") {
            $temp_array = array();
            foreach($entry_array as $key => $value) {
                $temp_array[$key] = $comment_array[$key];
            }
            asort($temp_array);
            $temp_array2 = array();
            foreach($temp_array as $key => $value) {
                $temp_array2[$key] = $entry_array[$key];
            }
            $entry_array = $temp_array2;
        } else if ($sort == "comments-desc") {
            $temp_array = array();
            foreach($entry_array as $key => $value) {
                $temp_array[$key] = $comment_array[$key];
            }
            arsort($temp_array);
            $temp_array2 = array();
            foreach($temp_array as $key => $value) {
                $temp_array2[$key] = $entry_array[$key];
            }
            $entry_array = $temp_array2;
        } else if ($sort == "tracks-asc") {
            $temp_array = array();
            foreach($entry_array as $key => $value) {
                $temp_array[$key] = $tracks_array[$key];
            }
            asort($temp_array);
            $temp_array2 = array();
            foreach($temp_array as $key => $value) {
                $temp_array2[$key] = $entry_array[$key];
            }
            $entry_array = $temp_array2;
        } else if ($sort == "tracks-desc") {
            $temp_array = array();
            foreach($entry_array as $key => $value) {
                $temp_array[$key] = $tracks_array[$key];
            }
            arsort($temp_array);
            $temp_array2 = array();
            foreach($temp_array as $key => $value) {
                $temp_array2[$key] = $entry_array[$key];
            }
            $entry_array = $temp_array2;
        } else {
            $msg = "Unknown sort '$sort' selected";
            debug($msg);
            return "<!-- blogstats: $msg -->";
        }
    }

    reset($entry_array);

    if (count($entry_array) > 0) {
        if ($cformat) {
            foreach($entry_array as $category => $entries_count) {
                if ((count($ignore_cat) > 0) && (in_array($category, $ignore_cat))) {
                    continue;
                }
                $cat_display = $category_array[$category];
                if (in_array($category, $activecats)) {
                    $active = $params['isactive'];
                } else {
                    $active = "";
                }
                if (!$entries_count) {
                    $entries_count = 0;
                }
                if (!$comment_array[$category]) {
                    $comments_count = 0;
                } else {
                    $comments_count = $comment_array[$category];
                }
                if (!$track_array[$category]) {
                    $tracks_count = 0;
                } else {
                    $tracks_count = $track_array[$category];
                }
                $thisline = str_replace('%entries%', $entries_count, $cformat);
                $thisline = str_replace('%comments%', $comments_count, $thisline);
                $thisline = str_replace('%trackbacks%', $tracks_count, $thisline);
                $thisline = str_replace('%category%', $cat_display, $thisline);
                $catlink = makeCategoryLink($category);
                $thisline = str_replace('%cat-link%', $catlink, $thisline);
                $thisline = str_replace('%active%', $active, $thisline);
                $coutput .= "\t" . $thisline . "\n";
            }
        }
        if ($uformat) {
            foreach($users as $key => $value) {
                if ((count($include_user) > 0) && ((!in_array($key, $include_user)) && (!in_array($value, $include_user)))) {
                    continue;
                } else if ((count($ignore_user) > 0) && ((in_array($key, $ignore_user)) || (in_array($value, $ignore_user)))) {
                    continue;
                } else if (!$user_array[$key]) {
                    continue;
                }
                if (!$user_array[$key]) {
                    $entries_count = 0;
                } else {
                    $entries_count = $user_array[$key];
                }
                if (!$comment_array[$userkey]) {
                    $comments_count = 0;
                } else {
                    $comments_count = $comment_array[$userkey];
                }
                if (!$track_array[$userkey]) {
                    $tracks_count = 0;
                } else {
                    $tracks_count = $track_array[$userkey];
                }
                $userkey = "user_" . $key;
                $thisline = str_replace('%user%', $value, $uformat);
                $userlink = authorentrieslink($key, $include_cat);
                $thisline = str_replace('%user-link%', $userlink, $thisline);
                $thisline = str_replace('%entries%', $entries_count, $thisline);
                $thisline = str_replace('%trackbacks%', $tracks_count, $thisline);
                $thisline = str_replace('%comments%', $comments_count, $thisline);
                $uoutput .= "\t" . $thisline . "\n";
            }
        }
    }

    //add prefix and/or postfix
    if ($usersfirst) {
        $output = $uprefix . "\n" . $uoutput . $upostfix . "\n" . $cprefix . "\n" . $coutput . $cpostfix . "\n";
    } else {
        $output = $cprefix . "\n" . $coutput . $cpostfix . "\n" . $uprefix . "\n" . $uoutput . $upostfix . "\n";
    }
    $output = $prefix . "\n" . $output . $postfix . "\n";
    $output = str_replace('%entries%', $totalentries, $output);
    $output = str_replace('%comments%', $totalcomments, $output);
    $output = str_replace('%trackbacks%', $totaltracks, $output);

    return $output;
}

/* Internal Changelog */
/*
Version 1.0 - initial release (23 February 2010)
Version 1.1 - thanks to hansfn for reviewing and fixing a php amateur's attempt at an extension (03 March 2010)
    - use of different functions (in the class Categories), makes things more stable [hansfn]
    - use of makeCategoryLink for creation of links to category display [hansfn]
    - prefix/postfix are optional [hansfn - though this was a change planned by Stingray]
    - hidden categories are now ignored [hansfn]
    - streamlined/corrected counting of total entries and comments [hansfn]
    - changed sort methods pivot/pivot-rev to pivotx/pivotx-rev [hansfn]
    - added parameter 'totalonly' so that only the total entries and/or comments is displayed.
    - bug fix: what if sort method is invalid setting? [hansfn]
    - bug fix: format would not display information if only one category included [hansfn]
    - bug fix: if hidden category in include, would still output a format line (though would not include count data)
Version 1.2 - (18 March 2010)
    - new feature: can count entries per author (and comments in those entries), including a link to users' entries.
    - added a slew of new parameters (some related to new feature): user_include, user_ignore, cat_prefix, cat_postfix, user_prefix, user_format, user_postfix, usersfirst
    - changed a few parameters to match new parameters: include -> cat_incude, ignore -> cat_ignore, format -> cat_format
    - isactive parameter added: allows stylizing the currently active category or categories
Version 1.2.1 - (19 March 2010)
    - no change to the extension...corrected documentation
Version 1.3 - (5 June 2011)
    - bug fix: display 0 for categories or users with 0 entries or 0 comments
Version 1.4 - (12 December 2012)
    - removed (commented out) the use of a function no longer in use in PivotX.  It seems to be unnecessary to the snippet, anyway.
    - counts trackbacks.  Why did it take so long?  Along with this is added the ability to sort the listing according to trackbacks as with comments.
    - bug fix: invalid foreach triggered by entries not in a category (Harm).
Version 1.4 - (14 February 2014)
    - bug fix: $PIVOTX['db'] breaking some PivotX smarty tags
*/

/* Future Plans/Wish List/Etc. ... TBD */
/*
- caching = will help speed issues on larger sites
- different output if hidden category in include (like, maybe, including it in the counting)...why else include it?
- maybe some str_replace stuff with cat_/user_prefix and cat_/user_postfix, such as counting, etc. (if necessary)
*/

?>
