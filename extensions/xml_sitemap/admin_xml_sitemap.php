<?php
// - Extension: XML Sitemap
// - Version: 1.1
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: An extension to provide a XML sitemap (for search engines).
// - Date: 2011-04-01
// - Identifier: xml_sitemap

global $xml_sitemap_config;

$xml_sitemap_config = array(
    'xml_sitemap_include_weblogs' => 0,
    'xml_sitemap_onlyweblog' => '',
    'xml_sitemap_excludeweblog' => '',
    'xml_sitemap_include_entries' => 1,
    'xml_sitemap_onlycategory' => '',
    'xml_sitemap_excludecategory' => '',
    'xml_sitemap_include_pages' => 1,
    'xml_sitemap_onlychapter' => '',
    'xml_sitemap_excludechapter' => '',
    'xml_sitemap_content_type' => 'text/xml',
    'xml_sitemap_filter_as_any_robot' => 0,
    'xml_sitemap_additional_uris' => '',
);


/**
 * Adds the hook for xml_sitemapAdmin()
 *
 * @see xml_sitemapAdmin()
 */
$this->addHook(
    'configuration_add',
    'xml_sitemap',
    array("xml_sitemapAdmin", "XML Sitemap")
);

$this->addHook(
    'before_parse',
    'callback',
    'xml_sitemapHook'
);

/**
 * The configuration screen for XML Sitemap
 *
 * @param unknown_type $form_html
 */
function xml_sitemapAdmin(&$form_html) {
    global $PIVOTX, $xml_sitemap_config;

    $form = $PIVOTX['extensions']->getAdminForm('xml_sitemap');

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'xml_sitemap_include_weblogs',
        'label' => __('Include weblog frontpages'),
    ));

    $weblogs = $PIVOTX['weblogs']->getWeblogs();
    foreach($weblogs as $key => $weblog) {
        $weblogoptions[ $key ] = $weblog['name'];
    }


    $form->add( array(
        'type' => 'select',
        'name' => 'xml_sitemap_onlyweblog',
        'label' => __('Only weblog'),
        'text' => makeJtip(__('Only weblog'), __('Select a weblog or weblogs that should only be added to the sitemap.')),
        'value' => '',
        'options' => $weblogoptions,
        'multiple' => true,
        'size' => 5,
        'isrequired' => 0
    ));

    $form->add( array(
        'type' => 'select',
        'name' => 'xml_sitemap_excludeweblog',
        'label' => __('Exclude weblog'),
        'text' => makeJtip(__('Exclude weblog'), __('Select a weblog or weblogs that should not be added to the sitemap.')),
        'value' => '',
        'options' => $weblogoptions,
        'multiple' => true,
        'size' => 5,
        'isrequired' => 0
    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'xml_sitemap_include_entries',
        'label' => __('Include entries'),
    ));

    $allcats = $PIVOTX['categories']->getCategories();
    foreach($allcats as $cat) {
        $catoptions[$cat['name']] = $cat['display'];
    }

    $form->add( array(
        'type' => 'select',
        'name' => 'xml_sitemap_onlycategory',
        'label' => __('Only category'),
        'text' => makeJtip(__('Only category'), 
            __('Select a category or categories that should only be added to the sitemap.')),
        'value' => '',
        'options' => $catoptions,
        'multiple' => true,
        'size' => 5,
        'isrequired' => 0
    ));

    $form->add( array(
        'type' => 'select',
        'name' => 'xml_sitemap_excludecategory',
        'label' => __('Exclude category'),
        'text' => makeJtip(__('Exclude category'), 
            __('Select a category or categories that should not be added to the sitemap.')),
        'value' => '',
        'options' => $catoptions,
        'multiple' => true,
        'size' => 5,
        'isrequired' => 0
    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'xml_sitemap_include_pages',
        'label' => __('Include pages'),
    ));

    $chapters = $PIVOTX['pages']->getIndex();
    foreach ($chapters as $key => $chapter) {
        $chapoptions[$chapter['chaptername']] = $chapter['chaptername'];
    }

    $form->add( array(
        'type' => 'select',
        'name' => 'xml_sitemap_onlychapter',
        'label' => __('Only chapter'),
        'text' => makeJtip(__('Only chapter'),
            __('Select a chapter or chapters that should only be added to the sitemap.')),
        'value' => '',
        'options' => $chapoptions,
        'multiple' => true,
        'size' => 5,
        'isrequired' => 0
    ));

    $form->add( array(
        'type' => 'select',
        'name' => 'xml_sitemap_excludechapter',
        'label' => __('Exclude chapter'),
        'text' => makeJtip(__('Exclude chapter'), 
            __('Select a chapter or chapters that should not be added to the sitemap.')),
        'value' => '',
        'options' => $chapoptions,
        'multiple' => true,
        'size' => 5,
        'isrequired' => 0
    ));

    $form->add( array(
        'type' => 'custom',
        'text' => sprintf("<tr><td colspan='2'><h4>%s</h4></em></td></tr>",
            __('Advanced Configuration'))
    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'xml_sitemap_filter_as_any_robot',
        'label' => __('Filter as any robot'),
        'text' => makeJtip(__('Filter as any robot'), 
            __('Test each sitemap entry against all Disallow rules in robots.txt. User-agent qualifiers are ignored, every Disallow will contribute to URI exclusion.') . ' ' .
            __('If this doesn\'t make any sense to you, leave the check box unchecked.')),
    ));

    $form->add( array(
        'type' => 'textarea',
        'name' => 'xml_sitemap_additional_uris',
        'label' => __('Additional URIs'),
        'text' => makeJtip(__('Additional URIs'), __('Additional URIs to include in sitemap. Separate the URIs with a comma. The URIs should be absolute paths (i.e. /myuri/here). Last modified will always be set to the current day.')),
        'value' => '',
        'rows' => 6,
        'cols' => 60,
        'isrequired' => 0
    ));

    /**
     * Add the form to our (referenced) $form_html. Make sure you use the same key
     * as the first parameter to $PIVOTX['extensions']->getAdminForm
     */
    $form_html['xml_sitemap'] = $PIVOTX['extensions']->getAdminFormHtml($form, $xml_sitemap_config);

}

/**
 * Test for URI exclusion by robot disallowed settings.
 */
function xml_sitemapIsUriAllowed($disallows, $uri) {
    foreach($disallows as $disallow) {
        if(strpos($uri, $disallow) === 0) {
            return false;
        }
    }
    return true;
}

/**
 * Find suitable disallows from robots.txt. Assumes pivotx is installed
 * at domain root (i.e. robots.txt resides in site_path)
 */
function xml_sitemapParseRobotsTxt() {
    global $PIVOTX;

    $robot_txt = $PIVOTX['paths']['site_path'] . 'robots.txt';

    $entries = @file_get_contents($robot_txt);
    if ($entries) {
        preg_match_all('/Disallow:\s*(.*)/', $entries, $disallows, PREG_PATTERN_ORDER);
        return $disallows[1];
    }

    return array();
}

/**
 * Generates the XML sitemap.
 */
function xml_sitemapHook(&$params) {
    global $xml_sitemap_config, $PIVOTX;
    
    if ( !defined('PIVOTX_INWEBLOG') || (!isset($_GET['xml_sitemap'])) ) {
        return;
    }

    $host = $PIVOTX['paths']['host'];

    $output = <<<EOM
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
%frontpages%
%items%
</urlset>
EOM;

    $output_frontpage = <<<EOM
<url>
<loc>${host}%loc%</loc>
</url>
EOM;

    /* Currently not using "changefreq" or "priority". */
    $output_item = <<<EOM
<url>
<loc>${host}%loc%</loc>
<lastmod>%lastmod%</lastmod>
</url>
EOM;

    $configdata = $PIVOTX['config']->getConfigArray();
    foreach ($xml_sitemap_config as $key => $value) {
        if (isset($configdata[$key])) {
            $$key = $configdata[$key];
        } else {
            $$key = $value;
        }
    }

    $frontpages = array();
    $items = array();
    $links = array();

    if ($xml_sitemap_filter_as_any_robot) {
        $disallows = xml_sitemapParseRobotsTxt();
    } else {
        $disallows = array();
    }

    // Handle the frontpages (site_root and weblog frontpages)
    if (!empty($xml_sitemap_onlyweblog)) {
        $onlyweblog_bool = true;
        $onlyweblog_arr = explode(',', $xml_sitemap_onlyweblog);
        $onlyweblog_arr = array_map('trim', $onlyweblog_arr);
        $onlyweblog_arr = array_map('strtolower', $onlyweblog_arr);
    }    
    if (!empty($xml_sitemap_excludeweblog)) {
        $excludeweblog_bool = true;
        $excludeweblog_arr = explode(',', $xml_sitemap_excludeweblog);
        $excludeweblog_arr = array_map('trim', $excludeweblog_arr);
        $excludeweblog_arr = array_map('strtolower', $excludeweblog_arr);
    }
    $frontpage = $output_frontpage;
    $frontpage = str_replace('%loc%', $PIVOTX['paths']['site_url'], $frontpage);
    $frontpages[] = $frontpage;
    if ($xml_sitemap_include_weblogs) {
        $weblogs = $PIVOTX['weblogs']->getWeblogNames();
        foreach ($weblogs as $weblog) {
            // If 'onlyweblog' is set, we should display only those weblogs,
            // and skip all the others. 
            // If 'excludeweblog' is set, we should exclude all those weblogs. 
            // You can only use the name in both.
            if ($onlyweblog_bool) {
                $continue = false;
                foreach ($onlyweblog_arr as $onlyweblog) { 
                    if (strtolower($weblog)==$onlyweblog) {
                        $continue = true;
                        break;
                    }
                }
                if (!$continue) {
                    continue; // skip it!
                }
            } elseif ($excludeweblog_bool) {
                $continue = true;
                foreach ($excludeweblog_arr as $excludeweblog) { 
                    if (strtolower($weblog)==$excludeweblog) {
                        $continue = false;
                        break;
                    }
                }
                if (!$continue) {
                    continue; // skip it!
                }
            }
            $frontpage = $output_frontpage;
            $link = $PIVOTX['weblogs']->get($weblog,'link');
            if (xml_sitemapIsUriAllowed($disallows, $link)) {
                $frontpage = str_replace('%loc%', $link, $frontpage);
                $frontpages[] = $frontpage;
            } else {
                debug('xml_sitemap: frontpage ' . $link . ' was disallowed by robots predicate');
            }
        }
    }

    // Iterate through the entries in batches. Doing all entries at once can
    // consume too much memory on big sites (3000+ entries).
    if (!empty($xml_sitemap_onlycategory)) {
        $onlycategory_bool = true;
        $onlycategory_arr = explode(',', $xml_sitemap_onlycategory);
        $onlycategory_arr = array_map('trim', $onlycategory_arr);
        $onlycategory_arr = array_map('strtolower', $onlycategory_arr);
    }    
    if (!empty($xml_sitemap_excludecategory)) {
        $excludecategory_bool = true;
        $excludecategory_arr = explode(',', $xml_sitemap_excludecategory);
        $excludecategory_arr = array_map('trim', $excludecategory_arr);
        $excludecategory_arr = array_map('strtolower', $excludecategory_arr);
    }
    $batch_size = 100;
    $entries_count = $PIVOTX['db']->get_entries_count();
    $batches = ceil($entries_count/$batch_size);
    $offset = 0;
    for ($i = 0; $i < $batches; $i++) {
        // If we have selected to exclude entries from the XML Sitemap, break immediately.
        if (!$xml_sitemap_include_entries) {
            break;
        }

        $entries = $PIVOTX['db']->read_entries(array('show' => $batch_size, 'offset' => $offset, 
            'full' => false, 'status'=>'publish'));
        $offset += $batch_size;
        foreach ($entries as $entry) {
            // If 'onlycategory' is set, we should display only the entries in one of those categories,
            // and skip all the others. 
            // If 'excludecategory' is set, we should exclude all those categories. 
            // You can only use the name in both.
            $thiscats = $entry['category'];
            if ($onlycategory_bool) {
                if (count(array_intersect($onlycategory_arr,$thiscats)) > 0) {
                    $continue = true;
                } else {
                    $continue = false;
                }
                if (!$continue) {
                    continue; // skip it!
                }
            } else if ($excludecategory_bool) {
                if (count(array_intersect($excludecategory_arr,$thiscats)) > 0) {
                    $continue = false;
                } else {
                    $continue = true;
                }
                if (!$continue) {
                    continue; // skip it!
                }
            }
            // if the entry belongs to a hidden category, it shouldn't be in sitemap
            $continue = true;
            foreach ($thiscats as $thiscat) {
                $catinfo = $PIVOTX['categories']->getCategory($thiscat);
                if ($catinfo['hidden']==1) {
                    $continue = false;
                    break;
                }
            }
            if (!$continue) {
                continue; // skip it!
            }
            $link = $entry['link'];
            if (isset($links[$link])) {
                debug("Duplicate link found for entry " . $entry['uid'] . " and " . $links[$link]);
                continue;
            } else {
                if (xml_sitemapIsUriAllowed($disallows, $link)) {
                    $links[$link] = $entry['uid'];
                } else {
                    debug('xml_sitemap: entry ' . $link . ' was disallowed by robots predicate');
                    continue;
                }
            }

            $entry = $PIVOTX['db']->read_entry($entry['uid']);
            $item = $output_item;
            $item = str_replace('%loc%', $link, $item);
            $item = str_replace('%lastmod%', formatDate($entry['edit_date'], '%year%-%month%-%day%'), $item);
            $items[] = $item;
        }
    }

    // Iterate through the chapters and pages
    if (!empty($xml_sitemap_onlychapter)) {
        $onlychapter_bool = true;
        $onlychapter_arr = explode(',', $xml_sitemap_onlychapter);
        $onlychapter_arr = array_map('trim', $onlychapter_arr);
        $onlychapter_arr = array_map('strtolower', $onlychapter_arr);
    }    
    if (!empty($xml_sitemap_excludechapter)) {
        $excludechapter_bool = true;
        $excludechapter_arr = explode(',', $xml_sitemap_excludechapter);
        $excludechapter_arr = array_map('trim', $excludechapter_arr);
        $excludechapter_arr = array_map('strtolower', $excludechapter_arr);
    }
    $chapters = $PIVOTX['pages']->getIndex();
    foreach ($chapters as $key => $chapter) {
        // If we selected to exclude pages from the XML Sitemap, break immediately.
        if (!$xml_sitemap_include_pages) {
            break;
        }
        // If 'onlychapter' is set, we should display only the pages in one of those chapters,
        // and skip all the others. 
        // If 'excludechapter' is set, we should exclude all those chapters. 
        // You can use either the name or the uid in both.
        if ($onlychapter_bool) {
            $continue = false;
            foreach ($onlychapter_arr as $onlychapter) { 
                if (strtolower($chapter['chaptername'])==$onlychapter) {
                    $continue = true;
                    break;
                }
            }
            if (!$continue) {
                continue; // skip it!
            }
        } else if ($excludechapter_bool) {
            $continue = true;
            foreach ($excludechapter_arr as $excludechapter) { 
                if (strtolower($chapter['chaptername'])==$excludechapter) {
                    $continue = false;
                    break;
                }
            }
            if (!$continue) {
                continue; // skip it!
            }
        }
        
        // If there are no pages, we skip this chapter
        if (count($chapter['pages']) == 0) {
            continue;
        }

        // We also skip any orphaned pages
        if (strcmp($key,"orphaned") == 0) {
            continue;
        }
        
        // Iterate through the pages
        foreach ($chapter['pages'] as $page) {

            if ($page['status'] != 'publish') {
                continue; // skip it!
            }

            $link = makePageLink($page['uri'], $page['title'], $page['uid'], $page['date']);

            if (isset($links[$link])) {
                debug("Duplicate link found for page " . $page['uid'] . " and entry/page " . $links[$link]);
                continue;
            } else {
                if (xml_sitemapIsUriAllowed($disallows, $link)) {
                    $links[$link] = $page['uid'];
                } else {
                    debug('xml_sitemap: page ' . $link . ' was disallowed by robots predicate');
                    continue;
                }
            }

            $page = $PIVOTX['pages']->getPageByUri($page['uri']);
            $item = $output_item;
            $item = str_replace('%loc%', $link, $item);
            $item = str_replace('%lastmod%', formatDate($page['edit_date'], '%year%-%month%-%day%'), $item);
            $items[] = $item;
        }
    }

    // Add explicit URIs
    if($xml_sitemap_additional_uris) {
        $uris = explode(',', $xml_sitemap_additional_uris);
        foreach ($uris as $uri) {
            $uri = trim($uri);
            if (strlen($uri) == 0) {
                continue;
            }
            $item = $output_item;
            $item = str_replace('%loc%', $uri, $item);
            $item = str_replace('%lastmod%', formatDate('', '%year%-%month%-%day%'), $item);
            $items[] = $item;
        }
    }

    // Output the Sitemap file as XML
    header("content-type: $xml_sitemap_content_type; charset=utf-8"); 
    $output = str_replace('%frontpages%', implode("\n",$frontpages), $output); 
    echo str_replace('%items%', implode("\n",$items), $output); 
    die();

}

?>
