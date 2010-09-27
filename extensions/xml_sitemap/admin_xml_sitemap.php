<?php
// - Extension: XML Sitemap
// - Version: 1.0.5
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: An extension to provide a XML sitemap (for search engines).
// - Date: 2010-09-14
// - Identifier: xml_sitemap

global $xml_sitemap_config;

$xml_sitemap_config = array(
    'xml_sitemap_include_weblogs' => 0,
    'xml_sitemap_include_entries' => 1,
    'xml_sitemap_include_pages' => 1,
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

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'xml_sitemap_include_entries',
        'label' => __('Include entries'),
    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'xml_sitemap_include_pages',
        'label' => __('Include pages'),
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
    $frontpage = $output_frontpage;
    $frontpage = str_replace('%loc%', $PIVOTX['paths']['site_url'], $frontpage);
    $frontpages[] = $frontpage;
    if ($xml_sitemap_include_weblogs) {
        $weblogs = $PIVOTX['weblogs']->getWeblogNames();
        foreach ($weblogs as $weblog) {
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
    $chapters = $PIVOTX['pages']->getIndex();
    foreach ($chapters as $key => $chapter) {

        // If we selected to exclude pages from the XML Sitemap, break immediately.
        if (!$xml_sitemap_include_pages) {
            break;
        }

        // If there is no pages, we skip this chapter
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
