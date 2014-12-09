<?php
// - Extension: WordPress export
// - Version: 0.2
// - Author: Two Kings // Marcel Wouters (updated by Harm Kramer)
// - Email: marcel@twokings.nl
// - Site: http://www.twokings.nl/
// - Description: Export content to a WordPress-compatible format
// - Date: 2014-12-07
// - Identifier: wpexport


// You can change things yourself to influence processing. These points are visible by the string @@CHANGE
// @todo Move this configuration to the beginning of the pivotxWpExport class 
// as properties so everything is one location.

$this->addHook(
    'configuration_add',
    'wpexport',
    array('functionalCallWpExportConfigurationAdd', 'WP-Export')
);


class pivotxWpExport
{
    public static function adminTab(&$form_html)
    {
        global $PIVOTX;

        $form = $PIVOTX['extensions']->getAdminForm('wpexport');

        $output = <<<THEEND
<ul>
    <span>Optional actions before exporting content<span>
    <li><a href="?page=wpexport&type=categories">
        Export Categories
    </a></li>
    <li><a href="?page=wpexport&type=chapters">
        Export Chapters (as plain pages that can be used to parent the PivotX pages)
    </a></li>
    <li><a href="?page=wpexport&type=uploads">
        Export Uploads
    </a></li>
    <li><a href="?page=wpexport&type=extrafields">
        Export Extrafields definitions like e.g. Bonusfields extension (for use in ACF plugin for WP - galleries will be skipped)
    </a></li>
    <br/>
    <span>With parsing of introduction and body content<span>
    <li><a href="?page=wpexport&type=pages">
        Export Pages
    </a></li>
    <li><a href="?page=wpexport&type=entries">
        Export Entries (without comments)
    </a></li>
    <li><a href="?page=wpexport&type=entries+comments">
        Export Entries (including comments)
    </a></li>
    <br/>
    <span>Without parsing of introduction and body content<span>
    <li><a href="?page=wpexport&type=pages&parse=no">
        Export Pages
    </a></li>
    <li><a href="?page=wpexport&type=entries&parse=no">
        Export Entries (without comments)
    </a></li>
    <li><a href="?page=wpexport&type=entries+comments&parse=no">
        Export Entries (including comments)
    </a></li>
</ul>

THEEND;

        $form->add(array(
            'type' => 'custom',
            'text'=> $output
        ));

        $form_html['wpexport'] = $PIVOTX['extensions']->getAdminFormHtml($form, false);

        return $output;
    }

    private static function outputMap($map)
    {
        $output = '';
        foreach($map as $key => $value) {
            $close_key = explode(' ', $key);
            $close_key = array_shift($close_key);

            if (substr($key, 0, 1) == '#') {
                if ($value != '') {
                    $output .= $value;
                }
            }
            else {
                if (is_scalar($value)) {
                    $outvalue = htmlspecialchars($value);
                }
                else {
                    switch ($value[0]) {
                        case 'date_2822':
                            $outvalue = date('r', strtotime($value[1]));
                            break;
                        case 'date':
                        case 'date_gmt':        // gmt not working!
                            $outvalue = date('Y-m-d H:i:s', strtotime($value[1]));
                            break;
                        case 'cdata':
                            $outvalue = '<![CDATA['.$value[1].']]>';
                            break;
                        case 'html':
                            $outvalue = $value[1];
                            break;
                    }
                }
                $output .= '<'.$key.'>'.$outvalue.'</'.$close_key.'>'."\n";
            }
        }
        return $output;
    }

    private static function outputWXR_Categories()
    {
        global $PIVOTX;
        global $WPEXPORT;
        $output = '';
        recordId(0);   // so default of minimum gets overwritten
        foreach($PIVOTX['categories']->data as $cat) {
            $output .= '<wp:category><wp:category_nicename>'.htmlspecialchars($cat['name']).'</wp:category_nicename><wp:category_parent></wp:category_parent><wp:cat_name><![CDATA['.$cat['display'].']]></wp:cat_name></wp:category>'."\n";
            $WPEXPORT['itemcnt'] = $WPEXPORT['itemcnt'] + 1;
        }
        return $output;
    }

    private static function outputWXR_ItemCategories($categories)
    {
        global $PIVOTX;

        $output = '';
        foreach($categories as $category) {
            foreach($PIVOTX['categories']->data as $cat) {
                if ($cat['name'] == $category) {
                    $output .= '<category><![CDATA['.$cat['display'].']]></category>'."\n";
                    $output .= '<category domain="category" nicename="'.htmlspecialchars($cat['name']).'"><![CDATA['.$cat['display'].']]></category>'."\n";
                }
            }
        }
        return $output;
    }

    private static function outputWXR_Extrafields()
    {
        global $PIVOTX;
        global $WPEXPORT;
        $output = '';
        recordId(0);   // so default of minimum gets overwritten

        $bffields = get_bffields();
        if ($bffields == false) {
            $output = '<!-- Warning! you have no Bonusfields extension installed -->'."\n";
            $WPEXPORT['warncnt'] = $WPEXPORT['warncnt'] + 1;
        } else {
            if (!is_array($bffields)) {
                $output = '<!-- Warning! you have no Bonusfields defined -->'."\n";
                $WPEXPORT['warncnt'] = $WPEXPORT['warncnt'] + 1;
            } else {
                $output .= '<item>'."\n";
                $record['post_id'] = 0;
                $bfdate = date('Y-m-d H:i:s', strtotime($bfdate . ' - 1 day'));  // to be sure that imported item will be published
                $record['post_parent'] = '0';

                $bfmeta = self::build_bfmeta('entry', $bffields);
                if ($bfmeta == '') {
                    $output .= '<!-- Warning! you have no Bonusfields for entries defined -->'."\n";
                    $WPEXPORT['warncnt'] = $WPEXPORT['warncnt'] + 1;
                } else {
                    $output .= self::outputMap(array(
                    'title' => 'Post_extrafields',
                    'link' => '0',
                    'pubDate' => $bfdate,
                    'dc:creator' => 'pivx_extrafields',
                    'guid isPermaLink="false"' => '0',
                    'wp:post_id' => $record['post_id'],
                    'wp:post_date' => $bfdate,
                    'wp:post_date_gmt' => $bfdate,
                    'wp:comment_status' => 'closed',
                    'wp:ping_status' => 'closed',
                    'wp:post_name' => 'acf_post_extrafields',
                    'wp:status' => 'publish',
                    'wp:post_parent' => '0',
                    'wp:menu_order' => '101',
                    'wp:post_type' => 'acf',
                    'wp:post_password' => '',
                    'wp:postmeta' => array('html', $bfmeta),
                    ));
                }
                $output .= '</item>'."\n";
                $WPEXPORT['itemcnt'] = $WPEXPORT['itemcnt'] + 1;

                $output .= '<item>'."\n";
                $record['post_id'] = 0;
                $bfdate = date('Y-m-d H:i:s', strtotime($bfdate . ' - 1 day'));  // to be sure that imported item will be published
                $record['post_parent'] = '0';

                $bfmeta = self::build_bfmeta('page', $bffields);
                if ($bfmeta == '') {
                    $output .= '<!-- Warning! you have no Bonusfields for pages defined -->'."\n";
                    $WPEXPORT['warncnt'] = $WPEXPORT['warncnt'] + 1;
                } else {
                    $output .= self::outputMap(array(
                    'title' => 'Page_extrafields',
                    'link' => '0',
                    'pubDate' => $bfdate,
                    'dc:creator' => 'pivx_extrafields',
                    'guid isPermaLink="false"' => '0',
                    'wp:post_id' => $record['post_id'],
                    'wp:post_date' => $bfdate,
                    'wp:post_date_gmt' => $bfdate,
                    'wp:comment_status' => 'closed',
                    'wp:ping_status' => 'closed',
                    'wp:post_name' => 'acf_page_extrafields',
                    'wp:status' => 'publish',
                    'wp:post_parent' => '0',
                    'wp:menu_order' => '102',
                    'wp:post_type' => 'acf',
                    'wp:post_password' => '',
                    'wp:postmeta' => array('html', $bfmeta),
                    ));
                }
                $output .= '</item>'."\n";
                $WPEXPORT['itemcnt'] = $WPEXPORT['itemcnt'] + 1;

            }
        }
        return $output;
    }

    private static function outputWXR_Tags()
    {
        // Harm: tags can be supplied within the item (so no need for this routine any more?)
        $output = '';
        //    <wp:tag><wp:tag_slug>dunkin-donuts</wp:tag_slug><wp:tag_name><![CDATA[dunkin donuts]]></wp:tag_name></wp:tag>
        return $output;
    }

    private static function outputWXR_ItemTags($tags)
    {
        global $PIVOTX;
        $output = '';
        if ($tags != '') {
            $tag_arr = explode(' ', $tags);
            foreach($tag_arr as $tag) {
                $output .= '<category domain="post_tag" nicename="'.htmlspecialchars($tag).'"><![CDATA['.$tag.']]></category>'."\n";
            }
        }
        return $output;
    }

    private static function outputWXR_Chapters($chapter)
    {
        global $WPEXPORT;
        $record = $chapter;
        $output = '';
        $output .= '<item>'."\n";
        $chapdate = date('Y-m-d H:i:s', strtotime($chapdate . ' - 1 day'));  // to be sure that imported page will be published
        $record['post_type'] = 'page';

        $record['post_id'] = $record['uid'] + $WPEXPORT['addtochap'];
        $output .= '<!-- Item for old id ' . $record['uid'] .  ' to post_id ' . $record['post_id'] . ' -->'."\n";
        $record['post_parent'] = '0';
        $output .= self::outputMap(array(
                'title' => $record['chaptername'],
                'link' => '0',
                'pubDate' => $chapdate,
                'dc:creator' => 'pivx_chapter',
                'guid isPermaLink="true"' => '0',
                'description' => $record['description'],
                'excerpt:encoded' => array('cdata', ''),
                'content:encoded' => array('cdata', 'Chapter: ' . $record['chaptername']),    // @@CHANGE
                'wp:post_id' => $record['post_id'],
                'wp:post_date' => $chapdate,
                'wp:post_date_gmt' => $chapdate,
                'wp:comment_status' => 'closed',
                'wp:ping_status' => 'closed',
                'wp:status' => 'publish',
                'wp:post_parent' => $record['post_parent'],
                'wp:menu_order' => $record['sortorder'],
                'wp:post_type' => $record['post_type'],
                'wp:post_password' => '',
            ));
        $output .= '</item>'."\n";
        $WPEXPORT['itemcnt'] = $WPEXPORT['itemcnt'] + 1;

        return $output;
    }

    private static function outputWXR_Uploads($upload)
    {
        global $WPEXPORT;
        $record = $upload;
        $output = '';
        $output .= '<item>'."\n";
        $upldate = date('Y-m-d H:i:s');
        $record['post_id'] = $record['uid'];
        $output .= '<!-- Item for upload will have id ' . $record['post_id'] . ' -->'."\n";
        $record['post_parent'] = '0';
        $attmeta = "\n" . self::outputMap(array(
                'wp:meta_key' => '_wp_attached_file',
                'wp:meta_value' => array('cdata', $record['destfolder'] . '/' . $record['filename']),
            ));
        $output .= self::outputMap(array(
                'title' => $record['title'],
                'link' => '0',
                'pubDate' => $upldate,
                'dc:creator' => 'pivx_upload',
                'guid isPermaLink="false"' => 'uploads/' . $record['destfolder'] . '/' . $record['filename'],
                'description' => '',
                'excerpt:encoded' => '',
                'content:encoded' => '',
                'wp:post_id' => $record['post_id'],
                'wp:post_date' => $upldate,
                'wp:post_date_gmt' => $upldate,
                'wp:comment_status' => 'open',
                'wp:ping_status' => 'open',
                'wp:post_name' => $record['postname'],
                'wp:status' => 'inherit',
                'wp:post_parent' => '0',
                'wp:menu_order' => '0',
                'wp:post_type' => 'attachment',
                'wp:post_password' => '',
                'wp:attachment_url' => $record['inputloc'] . rawurlencode($record['filename']),
                'wp:postmeta' => array('html', $attmeta),
            ));
        $output .= '</item>'."\n";
        $WPEXPORT['itemcnt'] = $WPEXPORT['itemcnt'] + 1;

        return $output;
    }

    private static function outputWXR_Header($exporttype)
    {
        global $PIVOTX;

        $created = date('Y-m-d H:i');

        $channel_info = self::outputMap(array(
            'title' => '',
            'link' => $PIVOTX['paths']['canonical_host'].$PIVOTX['paths']['site_url'],
            'description' => '',
            'pubDate' => '',
            'generator' => '',
            'language' => '',
            'wp:wxr_version' => '1.0',
            'wp:base_site_url' => '',
            'wp:base_blog_url' => '',
        ));

        return <<<THEEND
<?xml version="1.0" encoding="UTF-8"?>
<!-- This is a WordPress eXtended RSS file generated by PivotX as an export of your site. -->
<!-- It contains information about your $exporttype -->
<!-- See end of this file for more detailed information -->

<!-- generator="PivotX/WP-Export" created="$created"-->
<rss version="2.0"
    xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/"
    xmlns:content="http://purl.org/rss/1.0/modules/content/"
    xmlns:wfw="http://wellformedweb.org/CommentAPI/"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:wp="http://wordpress.org/export/1.2/"
>

<channel>
$channel_info
THEEND;
    }

    private static function outputWXR_Footer($exporttype)
    {
        global $WPEXPORT;
        $itemcnt = $WPEXPORT['itemcnt'];
        $warncnt = $WPEXPORT['warncnt'];
        $minid = $WPEXPORT['id_min'];
        $maxid = $WPEXPORT['id_max'];
        return <<<THEEND
</channel>
</rss>
<!-- This is a WordPress eXtended RSS file generated by PivotX as an export of your site. -->
<!-- It contains information about your $exporttype -->
<!-- Number of export items generated: $itemcnt -->
<!-- Number of warnings generated: $warncnt -->
<!-- The original ids encountered were: $minid (minimum) and $maxid (maximum) -->
THEEND;
    }

    private static function convertPageToItem($page, $comments)
    {
        global $PIVOTX;
        global $WPEXPORT;
        global $chaparray;
        $item = $page;
        if (true) {
            // needed to fix trimmed introductions
            $item = $PIVOTX['pages']->getPage($page['uid']);
        }
        $item['link'] = $PIVOTX['paths']['canonical_host'].makePageLink($page['uri'], $page['title'], $page['uid']);
        $item['post_type'] = 'page';
        $item['pivx_type'] = 'page';

        $item['post_id'] = $item['uid'] + $WPEXPORT['addtopage'];
        if ($item['new_uid'] != '') {
            $item['post_id'] = $item['new_uid'];
        }
        recordId($item['uid']);

        if (array_key_exists($item['chapter'], $chaparray)) {
            $item['post_parent'] = $chaparray[$item['chapter']];
        } else {
            $item['post_parent'] = '0'; 
        }
        return $item;
    }

    private static function convertEntryToItem($entry, $comments)
    {
        global $PIVOTX;
        global $WPEXPORT;
        $item = $entry;
        if ($comments) {
            $PIVOTX['cache']->clear();
            $item = $PIVOTX['db']->read_entry($entry['code']);
        }
        $item['link'] = $PIVOTX['paths']['canonical_host'].makeFileLink($entry, '', '');
        $item['post_type'] = 'post';
        $item['pivx_type'] = 'entry';

        $item['post_id'] = $item['uid'] + $WPEXPORT['addtoentry'];
        recordId($item['uid']);
        $item['post_parent'] = '0'; 
        return $item;
    }

    private static function outputWXR_Comments($comments)
    {
        $output = '';
        foreach($comments as $comment) {
            $output .= '<wp:comment>';
            $output .= self::outputMap(array(
                'wp:comment_id' => $comment['uid'],
                'wp:comment_author' => $comment['name'],
                'wp:comment_author_email' => $comment['email'],
                'wp:comment_author_url' => $comment['url'],
                'wp:comment_author_IP' => $comment['ip'],
                'wp:comment_date' => array('date', $comment['date']),
                'wp:comment_date_gmt' => array('date_gmt', $comment['date']),
                'wp:comment_content' => array('cdata' , $comment['comment']),
                'wp:comment_approved' => ('moderate' == 0) ? '1' : '0',
                'wp:comment_type' => '',
                'wp:comment_parent' => '',
                'wp:comment_user_id' => '',
            ));
            $output .= '</wp:comment>';
        }
        return $output;
    }

    private static function outputWXR_Items(&$data, $comments, $callback)
    {
        global $PIVOTX;
        global $WPEXPORT;
        global $UPLFILES;
        global $BFFIELDS;
        $output = '';
        $parse = isset( $_GET['parse'] ) ? $_GET['parse'] : '';  
        foreach($data as &$record) {

            $record = call_user_func($callback, $record, $comments); // xiao: something goes wrong here with the comments!!!!
            // harm: I tested with comments and all seems to process well?

            // harm todo: find a solution for the subtitle
            // harm todo: scan for image tags in content and replace them

//@@CHANGE REPLACE STRINGS HERE -- start
            // replace some strings in introduction and body before parsing
            // Scan your xml output for message "Smarty error:"
            // Warning: files can be included in included files -- these strings cannot be seen from here

            // `$templatedir` --> your default weblog
            $record = replaceit($record, "`\$templatedir`", getcwd() . "/templates/weblog");
            // include file="weblog/ 
            $record = replaceit($record, 'include file="weblog/', 'include file="' . getcwd() . '/templates/weblog/');
            // &gt; due to editor (or the parsing?)
            $record = replaceit($record, '&gt;', '>');
            // &lt; due to editor (or the parsing?)
            $record = replaceit($record, '&lt;', '<');
//@@CHANGE REPLACE STRINGS HERE -- end

            $excerpt_encoded = ''; 

            if ($parse != 'no') {
                $content_encoded = parse_intro_or_body($record['introduction']); 
                $content_encoded .= parse_intro_or_body($record['body']); 
            } else {
                // added by Harm
                $content_encoded = $record['introduction'];
                $content_encoded .= $record['body'];
            }
            $content_encoded = html_entity_decode($content_encoded, ENT_QUOTES, "UTF-8");   

            $categories      = array();

            if (isset($record['category'])) {
                $categories = $record['category'];
            }
            $image = '';
            $extrafmeta = '';
            $extrafcnt  = 0;
            // process extrafields
            if ($record['extrafields'] != '') {
                foreach($record['extrafields'] as $extrakey=>$extrafield) {
                    // the "normal" image fields
                    if ($extrakey == 'image' || $extrakey == 'afbeelding') {
                        $image = $PIVOTX['paths']['host'].$PIVOTX['paths']['upload_base_url'] . $extrafield;
                        $uplinfo = search_upload_filename($UPLFILES, $extrafield);
                        // image found?
                        if (isset($uplinfo['index'])) {
                            if ($extrafcnt > 0) {
                                $extrafmeta .= '</wp:postmeta>' . "\n" . '<wp:postmeta>';
                            }
                            $extrafcnt   = $extrafcnt + 1;
                            $extrafmeta .= "\n" . self::outputMap(array(
                            'wp:meta_key' => '_thumbnail_id',
                            'wp:meta_value' => array('cdata', $uplinfo['uid']),
                            ));
                        } else {
                            $extrafmeta .= '<!-- Warning! extrafields image not found! ' . $extrafield . ' -->';
                            $WPEXPORT['warncnt'] = $WPEXPORT['warncnt'] + 1;
                        }
                    // skip these ones   todo: find a solution for them
                    } elseif ($extrakey == 'image_description'
                            || $extrakey == 'date_depublish'
                            || $extrakey == 'seodescription'
                            || $extrakey == 'seokeywords'
                            || $extrakey == 'seotitle'
                            || $extrakey == 'ratings'
                            || $extrakey == 'ratingaverage'
                            || $extrakey == 'ratingcount'
                            || $extrakey == 'password'
                            || $extrakey == 'passwordprotect') {
                        continue;
                    } else {
                        // process other extrafields
                        $extrafmeta .= self::process_bfextra($extrakey, $record['pivx_type'], $BFFIELDS, $extrafield, $extrafcnt);
                        $extrafcnt   = $extrafcnt + 1;
                    }
                }
            }
            $output .= '<item>'."\n";
            $output .= '<!-- Item for old id ' . $record['uid'] .  ' to post_id ' . $record['post_id'] . ' -->'."\n";
            //$output .= '<!-- ' . var_export($record, true) . ' -->';
            $output .= self::outputMap(array(
                'title' => $record['title'],
                'link' => $record['link'],
                'pubDate' => array('date_2822', $record['date']),
                'dc:creator' => array('cdata', $record['user']),
                '#1' => self::outputWXR_ItemCategories($categories),
                '#2' => self::outputWXR_ItemTags($record['keywords']),
                'guid isPermaLink="true"' => $record['link'],
                'description' => '',
                'image' => $image,
                'excerpt:encoded' => array('cdata', $excerpt_encoded),
                'content:encoded' => array('cdata', $content_encoded),
                'wp:post_id' => $record['post_id'],
                'wp:post_date' => array('date', $record['date']),
                'wp:post_date_gmt' => array('date_gmt', $record['date']),
                'wp:comment_status' => (isset($record['allow_comments']) && $record['allow_comments']) ? 'open' : 'closed',
                'wp:ping_status' => 'closed',
                'wp:status' => $record['status'] == 'publish' ? 'publish' : 'pending',
                'wp:post_parent' => $record['post_parent'],
                'wp:menu_order' => $record['sortorder'],
                'wp:post_type' => $record['post_type'],
                'wp:post_password' => '',
                'wp:postmeta' => array('html', $extrafmeta),
            ));
            if ($comments && ($record['comment_count'] > 0)) {
                // add comments
                $output .= self::outputWXR_Comments($record['comments']);
            }
            $output .= '</item>'."\n";
            $WPEXPORT['itemcnt'] = $WPEXPORT['itemcnt'] + 1;
        }
        return $output;
    }

    public static function exportCategories()
    {
        global $PIVOTX;

        $output  = '';
        $output .= self::outputWXR_Header('categories');
        $output .= self::outputWXR_Categories();
        $output .= self::outputWXR_Footer('categories');

        return $output;
    }

    public static function exportUploads()
    {
        global $PIVOTX;
        global $WPEXPORT;
        global $UPLFILES;

        $output  = '';
        $output .= self::outputWXR_Header('uploads');

        $toskip     = array("index.html", ".htaccess");      // @@CHANGE
        $toskipext  = array("xyz", "123");                   // @@CHANGE

        foreach ($UPLFILES as $uplindex=>$uplfile) {
            $uplinfo    = create_uplinfo($uplfile, $uplindex + $WPEXPORT['addtoupl']);
            $uplinfo['index'] = $uplindex;
            // skip specific files
            if (in_array($uplinfo['filename'], $toskip)) { continue; }
            // skip specific extensions
            if (in_array($uplinfo['fileext'], $toskipext)) { continue; }
            // skip thumbnails
            if (substr($uplinfo['postname'], -6) == '.thumb') { continue; }
            $upldupl = search_upload_postname($UPLFILES, $uplinfo['postname'], $uplinfo['index'] - 1, 0);
            // duplicate file name found?
            if (isset($upldupl['index']) && $uplinfo['index'] != $upldupl['index']) {
                //echo ($uplinfo['uid'] . ' duplicate of ' . $upldupl['uid'] . '<br/>');
                // postname has to be unique always
                $uplinfo['postname'] .= '_dupl.of_' . $upldupl['uid'];
                // title has to be unique within same location
                if ($uplinfo['destfolder'] == $upldupl['destfolder']) {
                    $uplinfo['title'] .= '_dupl.of_' . $upldupl['uid'];
                }
            }
            recordId($uplinfo['uid']);
            $output .= self::outputWXR_Uploads($uplinfo);
        }

        $output .= self::outputWXR_Footer('uploads');
        return $output;
    }

    public static function exportExtrafields()
    {
        global $PIVOTX;

        $output  = '';
        $output .= self::outputWXR_Header('extrafields');
        $output .= self::outputWXR_Extrafields();
        $output .= self::outputWXR_Footer('extrafields');

        return $output;
    }

    public static function exportPages()
    {
        global $PIVOTX;
        global $WPEXPORT;
        global $chaparray;

        $chapters = $PIVOTX['pages']->getIndex();

        $output  = '';
        $output .= self::outputWXR_Header('pages');
        $output .= self::outputWXR_Tags();

        // @@CHANGE Harm: fill chaparray with chapter ids and their corresponding WP parent ids to get their belonging pages under them
        // even when they are already in WP then they still need to be in the same WXR import to be able to function as post_parent
        $chaparray = array();
        // hard code the desired parent ids for the chapters you wish
        //$chaparray = array(16 => 1234, 18 => 2345, 17 => 3456);
        // or build the array from the all chapters in the chapters array
        //foreach($chapters as $chapter) { 
        //    $chaparray[$chapter['uid']] = $chapter['uid']+$WPEXPORT['addtochap'];
        //}

        foreach($chapters as $chapter) {
            $chapinfo = array('uid' => $chapter['uid'],
                              'chaptername' => $chapter['chaptername'],
                              'description' => $chapter['description'],
                              'sortorder' => $chapter['sortorder']);
            // put version of the chapter page in front of the child pages so import knows it is OK (otherwise it won't work)
            if (array_key_exists($chapter['uid'], $chaparray)) {
                $chapinfo['new_uid'] = $chaparray[$chapter['uid']];
                $output .= self::outputWXR_Chapters($chapinfo);
            }

            $output .= self::outputWXR_Items($chapter['pages'], false, array('pivotxWpExport','convertPageToItem'));
        }
        $output .= self::outputWXR_Footer('pages');
        return $output;
    }

    public static function exportChapters()
    {
        global $PIVOTX;

        $chapters = $PIVOTX['pages']->getIndex();

        $output  = '';
        $output .= self::outputWXR_Header('chapters');

        foreach ($chapters as $chapter) {
            $chapinfo = array('uid' => $chapter['uid'],
                              'chaptername' => $chapter['chaptername'],
                              'description' => $chapter['description'],
                              'sortorder' => $chapter['sortorder']);
            if ($chapter['chaptername'] != '') {
                recordId($chapter['uid']);
                $output .= self::outputWXR_Chapters($chapinfo);
            }
        }

        $output .= self::outputWXR_Footer('chapters');
        return $output;
    }

    public static function exportEntries()
    {
        global $PIVOTX;

        $output  = '';
        $output .= self::outputWXR_Header('entries');
        //$output .= self::outputWXR_Categories();   // Harm: not needed -- categories can be exported separately.
        $output .= self::outputWXR_Tags();
        $output .= self::outputWXR_Items($PIVOTX['db']->read_entries(array('show'=>20000)), false, array('pivotxWpExport','convertEntryToItem'));
        
        // example of one separate entry
        //$output .= self::outputWXR_Items($PIVOTX['db']->read_entries(array('uid'=>151,'show'=>20000)), false, array('pivotxWpExport','convertEntryToItem'));
        // example of several categories
        //$output .= self::outputWXR_Items($PIVOTX['db']->read_entries(array('cats'=>array('default', 'linkdump'),'show'=>20000)), false, array('pivotxWpExport','convertEntryToItem'));
        // example of several entries on uid
        //$output .= self::outputWXR_Items($PIVOTX['db']->read_entries(array('uid'=>array(75,85),'show'=>20000)), false, array('pivotxWpExport','convertEntryToItem'));
        
        $output .= self::outputWXR_Footer('entries');
        return $output;
    }

    public static function exportEntriesWithComments()
    {
        global $PIVOTX;

        $output  = '';
        $output .= self::outputWXR_Header('entries and their comments');
        //$output .= self::outputWXR_Categories();    // Harm: not needed -- categories can be exported separately.
        $output .= self::outputWXR_Tags();

        $output .= self::outputWXR_Items($PIVOTX['db']->read_entries(array('show'=>20000)), true, array('pivotxWpExport','convertEntryToItem'));

        $output .= self::outputWXR_Footer('entries and their comments');
        return $output;
    }

    public static function build_bfmeta($bfsel, $bffields) {
        global $WPEXPORT;
        // first open postmeta will be created when creating item
        $bfmeta = "\n" . self::outputMap(array(
            'wp:meta_key' => '_edit_last',
            'wp:meta_value' => array('cdata', 1),
        ));
        $bfmeta .= '</wp:postmeta>';
        $bfselcnt = -1;
        foreach($bffields as $bffield) {
            //echo "bffield: " . $bffield['name'] . "/" . $bffield['contenttype'] . "/" . $bffield['type'] . "<br/>";
            if ($bffield['contenttype'] == $bfsel) {
                $bfselcnt = $bfselcnt + 1;
                // remove leading break (sometimes there to get description below field
                $bffield['description'] = ltrim($bffield['description'], '<br/>');
                $bffield['description'] = ltrim($bffield['description'], '<br />');
                $bffield['description'] = ltrim($bffield['description'], '<br>');
                // replace CR LF from description (they block the import)
                $bffield['description'] = preg_replace( "/\r|\n/", " ", $bffield['description'] );
                // to do: strip other html from description (like <em> <b> <i>)

                $bffieldkey = get_bfkey($bffield['fieldkey'],$bffield['contenttype'],$bffields);

                $bfmetacdata = build_bfmetacdata($bffieldkey, $bfselcnt, $bffield);

                // add warning for checkbox multiple
                if ($bffield['type'] == 'checkbox_multiple') {
                    $bfmeta .= "\n" . '<!-- Warning! Bonusfield "' .
                    $bffield['name'] . '" of contenttype ' . $bffield['contenttype'] .
                    ' is of type checkbox multiple. This type does not exist as an import type. It has been processed as single checkbox -->';
                    $WPEXPORT['warncnt'] = $WPEXPORT['warncnt'] + 1;
                }
                // add warning for select multiple
                if ($bffield['type'] == 'select_multiple') {
                    $bfmeta .= "\n" . '<!-- Warning! Bonusfield "' .
                    $bffield['name'] . '" of contenttype ' . $bffield['contenttype'] .
                    ' is of type select multiple. This type does not exist as an import type. It has been processed as single select -->';
                    $WPEXPORT['warncnt'] = $WPEXPORT['warncnt'] + 1;
                }
                // skip gallery
                if ($bffield['type'] == 'gallery') {
                    $bfmeta .= "\n" . '<!-- Warning! Bonusfield "' .
                    $bffield['name'] . '" of contenttype ' . $bffield['contenttype'] .
                    ' is of type gallery. This type cannot be imported in this way. Use export galleries instead -->';
                    $WPEXPORT['warncnt'] = $WPEXPORT['warncnt'] + 1;
                    $bfmetacdata = '';
                }
                // add warning for some non processed bonusfield parts
                if ($bffield['showif_type'] != '' ||
                    $bffield['showif'] != '') {
                    $bfmeta .= "\n" . '<!-- Warning! Bonusfield "' .
                    $bffield['name'] . '" of contenttype ' . $bffield['contenttype'] .
                    ' has a value for showif_type and/or showif that is not yet processed in this export -->';
                    $WPEXPORT['warncnt'] = $WPEXPORT['warncnt'] + 1;
                }
                if ($bfmetacdata != '') {        
                    $bfmeta .= "\n" . '<wp:postmeta>' . "\n" . self::outputMap(array(
                    'wp:meta_key' => $bffieldkey,
                    'wp:meta_value' => array('cdata', $bfmetacdata),
                    ));
                    $bfmeta .= '</wp:postmeta>';
                }
            }
        }
        // rule to only show them for this selection
        $wpsel = 'post';
        if ($bfsel == 'entry') { $wpsel = 'post'; }
        if ($bfsel == 'page') { $wpsel = 'page'; }
        $bfmeta .= "\n" . '<wp:postmeta>' . "\n" . self::outputMap(array(
            'wp:meta_key' => 'rule',
            'wp:meta_value' => array('cdata', 'a:5:{s:5:"param";s:9:"post_type";s:8:"operator";s:2:"==";s:5:"value";s:4:"' .
            $wpsel . '";s:8:"order_no";i:0;s:8:"group_no";i:0;}'),
        ));
        $bfmeta .= '</wp:postmeta>';
        $bfmeta .= "\n" . '<wp:postmeta>' . "\n" . self::outputMap(array(
            'wp:meta_key' => 'position',
            'wp:meta_value' => array('cdata', 'normal'),
        ));
        $bfmeta .= '</wp:postmeta>';
        $bfmeta .= "\n" . '<wp:postmeta>' . "\n" . self::outputMap(array(
            'wp:meta_key' => 'layout',
            'wp:meta_value' => array('cdata', 'no_box'),
        ));
        $bfmeta .= '</wp:postmeta>';
        $bfmeta .= "\n" . '<wp:postmeta>' . "\n" . self::outputMap(array(
            'wp:meta_key' => 'hide_on_screen',
            'wp:meta_value' => array('cdata', ''),
        ));
        // last close postmeta will be created when creating item

        if ($bfselcnt == -1) {
            $bfmeta = '';
        }

        return $bfmeta;
    }

    public static function process_bfextra($extrakey, $pivx_type, $bffields, $extrafield, $extrafcnt) {
        global $PIVOTX;
        global $WPEXPORT;
        $bffieldkey = get_bfkey($extrakey, $pivx_type, $bffields);
        $bfmeta = '';
        if ($bffieldkey == '0') {
            $bfmeta .= '<!-- Warning! extrafields key not found! ' . $extrakey . ' -->';
            $WPEXPORT['warncnt'] = $WPEXPORT['warncnt'] + 1;
        } else {
            $bffieldtype = get_bftype($extrakey, $pivx_type, $bffields);
            if ($bffieldtype == 'gallery') {
                $bfmeta .= '<!-- Warning! extrafields gallery skipped! ' . $extrakey . ' -->';
                $WPEXPORT['warncnt'] = $WPEXPORT['warncnt'] + 1;
            } else {
            /*
            Todo: Bonusfield types that have not been covered and/or tested: 'textarea' / 'radio' / 'file'
                // galleries are separate entities -- so will be created whenever the content contains reference to this bonusfield type
            */
                if ($extrafcnt > 0) {
                    $bfmeta .= '</wp:postmeta>' . "\n" . '<wp:postmeta>';
                }
                $extrafcnt   = $extrafcnt + 1;
                if ($bffieldtype == 'checkbox' || $bffieldtype == 'checkbox_multiple') {
                    if ($extrafield == 'on') {
                        $bffielddata = get_bfdata($extrakey, $pivx_type, $bffields, true);
                        $extrafield = 'a:1:{i:0;s:' . strlen($bffielddata) . ':"' . $bffielddata . '";}';
                    }
                }
                if ($bffieldtype == 'choose_entry') {
                    $bfentry = $PIVOTX['db']->read_entry($extrafield);
                    if ($bfentry['uid'] == '') {
                        $extrafield = 'Warning! extrafields value not found! ' . $extrafield;
                        $WPEXPORT['warncnt'] = $WPEXPORT['warncnt'] + 1;
                    } else {
                        $extrafield = $bfentry['uid'] + $WPEXPORT['addtoentry'];
                    }
                }
                if ($bffieldtype == 'choose_page') {
                    $bfpage = $PIVOTX['pages']->getPageByUri($extrafield);
                    if ($bfpage['uid'] == '') {
                        $extrafield = 'Warning! extrafields value not found! ' . $extrafield;
                        $WPEXPORT['warncnt'] = $WPEXPORT['warncnt'] + 1;
                    } else {
                        $extrafield = $bfpage['uid'] + $WPEXPORT['addtopage'];
                    }
                }
                $bfmeta .= "\n" . self::outputMap(array(
                    'wp:meta_key' => $extrakey,
                    'wp:meta_value' => array('cdata', $extrafield),
                    ));
                $bfmeta .= '</wp:postmeta>' . "\n" . '<wp:postmeta>';
                $bfmeta .= "\n" . self::outputMap(array(
                    'wp:meta_key' => '_' . $extrakey,
                    'wp:meta_value' => array('cdata', $bffieldkey),
                    ));
            }
        }
        return $bfmeta;
    }

}


/**
 * functional style hook for configuration_add
 */
function functionalCallWpExportConfigurationAdd(&$form_html)
{
    if (isset($_GET['action']) && ($_GET['action'] != '')) {
        pivotxBonusfieldsInterface::actionBonusfield($_GET['action']);

        Header('Location: ?page=configuration#section-bonusfields');
        exit();
    }

    return pivotxWpExport::adminTab($form_html);
}

/**
 */
function pageWpexport()
{
    $output = '';
    global $WPEXPORT;
    global $UPLFILES;
    global $BFFIELDS;
    // @@CHANGE Harm: if you are importing into an existing WP then you probably want to add some number to the internal ids
    //       so these will be recognisable in future; also ids for pages and entries can be the same in PivotX but in WP
    //       they cannot.
    //       These old and new ids can also be used in the chaparray after importing the chapters and exporting the pages.
    //       Change addtoentry / addtopage / addtochap to accomplish this.
    //       upload_dest_def is the folder name to use whenever an upload is encountered that is not in a yyyy-nn subfolder (WP only uses that)
    //       addtoupl generates fixed ids based on the sequence in the total collection of uploads;
    //       this is necessary to connect an entry or page's image field to the right WP media id
    // todo: write an instruction on how to use these adds; after the import the auto_increment will have to highest value + 1
    //       and this cannot be lowered anymore in all cases.
    $WPEXPORT = array('itemcnt' => 0, 
                'warncnt' => 0,
                'id_min' => 99999999,
                'id_max' => 0,
                'upload_dest_def' => '2010/01',
                'upload_input' => '../images/',
                'addtoentry' => 100,
                'addtopage' => 300,
                'addtochap' => 500,
                'addtoupl' => 550);
    $filename = 'blog.xml';
    if (isset($_GET['type'])) {
        switch ($_GET['type']) {
            case 'categories':
                $filename = 'categories.xml';
                $output   = pivotxWpExport::exportCategories();
                break;
            case 'uploads':
                $filename = 'uploads.xml';
                $UPLFILES = get_uplfiles();
                $output   = pivotxWpExport::exportUploads();
                break;
            case 'extrafields':
                $filename = 'extrafields.xml';
                $output   = pivotxWpExport::exportExtrafields();
                break;
            case 'pages':
                $filename = 'pages.xml';
                $UPLFILES = get_uplfiles();
                $BFFIELDS = get_bffields();
                $output   = pivotxWpExport::exportPages();
                break;
            case 'chapters':
                $filename = 'chapters.xml';
                $output   = pivotxWpExport::exportChapters();
                break;
            case 'entries':
                $filename = 'entries.xml';
                $UPLFILES = get_uplfiles();
                $BFFIELDS = get_bffields();
                $output   = pivotxWpExport::exportEntries();
                break;
            case 'entries comments':
                $filename = 'entries_and_comments.xml';
                $UPLFILES = get_uplfiles();
                $BFFIELDS = get_bffields();
                $output   = pivotxWpExport::exportEntriesWithComments();
                break;
        }
    }

    header('Content-type: text/xml');
    header('Content-disposition: attachment; filename="'.$filename.'"');
    echo $output;
}

function get_bfkey($bfkey, $bfctype, $bffields) {
    $bfkeycnt = 0; $bfkeywp = 0;
    foreach($bffields as $bffield) {
        $bfkeycnt = $bfkeycnt + 1;
        if ($bffield['contenttype'] == $bfctype && $bffield['fieldkey'] == $bfkey) {
            // construct key
            $bffill = '';
            if ($bfkeycnt < 100) { $bffill = '000'; }
            if ($bfkeycnt < 10) { $bffill = '0000'; }
            $bfkeywp = 'field_20141116' . $bffill . $bfkeycnt;
        }
    }
    return $bfkeywp;
}

function get_bftype($bfkey, $bfctype, $bffields) {
    $bftype = 0;
    foreach($bffields as $bffield) {
        if ($bffield['contenttype'] == $bfctype && $bffield['fieldkey'] == $bfkey) {
            $bftype = $bffield['type'];
        }
    }
    return $bftype;
}

function get_bfdata($bfkey, $bfctype, $bffields, $bffillit) {
    $bfdata = '';
    foreach($bffields as $bffield) {
        if ($bffield['contenttype'] == $bfctype && $bffield['fieldkey'] == $bfkey) {
            $bfdata = $bffield['data'];
            if ($bfdata == '' && $bffillit == true) {
                $bfdata = $bffield['name'];
            }
        }
    }
    return $bfdata;
}

function build_bfmetacdata($bfkey, $bfocc, $bffield) {
    // bffield lay-out:
    //[name] => Bonusfield name 
    //[fieldkey] => Bonusfield key 
    //[type] => choose_page 
    //[location] => page-introduction-before 
    //[showif_type] => 
    //[showif] => 
    //[data] => 
    //[empty_text] => No link 
    //[description] => Description shown in editor 
    //[contenttype] => page

    $bfmetacdata = array(
        'key' => $bfkey,
        'label' => $bffield['name'],
        'name' => $bffield['fieldkey'],
        'instructions' => $bffield['description'],
        'default_value' => $bffield['empty_text'],
        'required' => 0,
        'conditional_logic' => array(
            'status' => 0,
            'rules' => array(array(
                'field' => 'null',
                'operator' => '==',
                'value' => ''
                )
            ),
            'allorany' => 'all'
        ),
        'order_no' => $bfocc
    );

    switch ($bffield['type']) {
        case 'input_text':
        case 'hidden':
            $bfmetacdata['type'] = 'text';
            $bfmetacdata['placeholder'] = $bfmetacdata['prepend'] = $bfmetacdata['append'] = ''; 
            $bfmetacdata['maxlength'] = '';
            $bfmetacdata['formating'] = 'html';
            break;
        case 'textarea':
            $bfmetacdata['type'] = 'textarea';
            $bfmetacdata['placeholder'] = $bfmetacdata['prepend'] = $bfmetacdata['append'] = ''; 
            $bfmetacdata['maxlength'] = '';
            $bfmetacdata['rows'] = '';
            $bfmetacdata['formating'] = 'br';
            break;
        case 'choose_page':
            $bfmetacdata['type'] = 'page_link';
            $bfmetacdata['post_type'] = array('page');
            $bfmetacdata['allow_null'] = '1';
            $bfmetacdata['multiple'] = '0';
            unset($bfmetacdata['default_value']);
            break;
        case 'choose_entry':
            $bfmetacdata['type'] = 'page_link';
            $bfmetacdata['post_type'] = array('page');
            $bfmetacdata['allow_null'] = '1';
            $bfmetacdata['multiple'] = '0';
            unset($bfmetacdata['default_value']);
            break;
        case 'select':
        case 'select_multiple':
            $bfmetacdata['type'] = 'select';
            $bfmetacdata['choices'] = explode("\r\n", $bffield['data']);
            if ($bffield['type'] == 'select_multiple') {
                $bfmetacdata['allow_null'] = '0';
                $bfmetacdata['multiple'] = '1';
            } else {
                $bfmetacdata['allow_null'] = '1';
                $bfmetacdata['multiple'] = '0';
            }
            break;
        case 'radio':
            $bfmetacdata['type'] = 'radio';
            $bfmetacdata['choices'] = explode("\r\n", $bffield['data']);
            $bfmetacdata['other_choice'] = '0';
            $bfmetacdata['save_other_choice'] = '0';
            $bfmetacdata['layout'] = 'vertical';
            break;
        case 'checkbox':
        case 'checkbox_multiple':
            $bfmetacdata['type'] = 'checkbox';
            $bfmetacdata['choices'] = explode("\r\n", $bffield['data']);
            $bfmetacdata['layout'] = 'vertical';
            break;
        case 'image':
            $bfmetacdata['type'] = 'image';
            $bfmetacdata['save_format'] = 'object';
            $bfmetacdata['preview_size'] = 'thumbnail';
            $bfmetacdata['library'] = 'all';
            unset($bfmetacdata['default_value']);
            break;
        // galleries are separate entities -- so will be created whenever the content contains reference to this bonusfield type
        case 'gallery':
            break;
        case 'file':
            $bfmetacdata['type'] = 'file';
            $bfmetacdata['save_format'] = 'object';
            $bfmetacdata['library'] = 'all';
            unset($bfmetacdata['default_value']);
            break;
        // bonusfields does not have a type number (but format still coded)
        case 'number':
            $bfmetacdata['type'] = 'number';
            $bfmetacdata['placeholder'] = $bfmetacdata['prepend'] = $bfmetacdata['append'] = ''; 
            $bfmetacdata['min'] = '123';
            $bfmetacdata['max'] = '123456';
            $bfmetacdata['step'] = '10';
            $bfmetacdata['formating'] = 'html';
            break;
        default:
            echo "Unknown bonusfields type: " . $bffield['type'] . "<br/>";
            print_r ($bffield); 
            $bfmetacdata['type'] = 'text';
            $bfmetacdata['placeholder'] = $bfmetacdata['prepend'] = $bfmetacdata['append'] = ''; 
            $bfmetacdata['maxlength'] = '';
            $bfmetacdata['formating'] = 'html';

    }
    return serialize($bfmetacdata);
}

function get_bffields() {
    global $PIVOTX;
    global $WPEXPORT;
    $bffields = false;
    if (function_exists('load_serialize')) {
        $config = load_serialize($PIVOTX['paths']['db_path'].'ser_bonusfields.php', true);
    } else if (function_exists('loadSerialize')) {
        $config = loadSerialize($PIVOTX['paths']['db_path'].'ser_bonusfields.php', true);
    }
    if ($config == true) {
        $bffields = array();
        foreach($config['definition'] as $array_field) {
            $bffield = new bonusfieldsDefinition();
            $bffield->importFromArray($array_field);
            $bffields[] = $bffield;
        }
        $bfcount = count($bffields);
        if ($bfcount < 1) {
            $bffields = $bfcount;
        } else {
            $bffields2 = array();
            foreach($bffields as $bffield) {
                $bffields2[] = $bffield->exportToArray();
            }
            $bffields = $bffields2;
        }
    }
    return $bffields;
}

function get_uplfiles() {
    global $WPEXPORT;
    $globfiles = glob_recursive($WPEXPORT['upload_input'] . "*");
    // loose the directories
    $uplfiles  = array();
    foreach ($globfiles as $globfile) {
        if (!is_dir($globfile)) {
            $uplfiles[] = $globfile;
        }
    }
    return $uplfiles;
}

function replaceit($record, $replthis, $replby) {
    $record['introduction'] = str_replace($replthis, $replby, $record['introduction']);
    $record['body']         = str_replace($replthis, $replby, $record['body']);
    return $record;
}

function recordId($uid) {
    global $WPEXPORT;
    if ($uid < $WPEXPORT['id_min']) { $WPEXPORT['id_min'] = $uid; }
    if ($uid > $WPEXPORT['id_max']) { $WPEXPORT['id_max'] = $uid; }
    return;
}

function create_uplinfo($uplfile, $uplcounter) {
    global $PIVOTX;
    global $WPEXPORT;
    $curryear = date('Y');
    $inpurl   = $PIVOTX['paths']['canonical_host'] . $PIVOTX['paths']['site_url'];
    $uplinfo  = array();
    $path_parts = pathinfo($uplfile);
    $uplfilename = $path_parts['basename'];
    $inpfolder   = $path_parts['dirname'] . '/';
    $yearfolder  = $WPEXPORT['upload_dest_def'];
    $basefolder  = '';
    // strip the main input from the total folder to check for yyyy-nn folder
    if (substr($uplfile, 0, strlen($WPEXPORT['upload_input'])) == $WPEXPORT['upload_input']) {
        $basefolder = substr($inpfolder, strlen($WPEXPORT['upload_input']));
        $yearfolder = rtrim($basefolder,"/");
        $regex = '/\d{4}[-]\d{2}/';   //  yyyy-nn format
        if (!preg_match($regex, $yearfolder)) {
            $yearfolder = $WPEXPORT['upload_dest_def'];
        } else {
            $yearparts = explode("-",$yearfolder);
            if ($yearparts[0] < 1990 || $yearparts[0] > $curryear || $yearparts[1] < 1 || $yearparts[1] > 12) {
                $yearfolder = $WPEXPORT['upload_dest_def'];
            } else {
                $yearfolder = $yearparts[0] . '/' . $yearparts[1];
            }
        }
    }
    if (substr($inpfolder, 0, 3) == '../') {
        $inpfolder = substr($inpfolder, 3);
    }
    //echo $uplcounter . '|' . $uplfilename . '|' . $yearfolder . '|' . $inpurl . $inpfolder . '|' . $basefolder . '<br/>';
    $uplinfo = array('uid' => $uplcounter,
                     'destfolder' => $yearfolder,
                     'filename' => $uplfilename,
                     'basefolder' => $basefolder,
                     'fileext' => $path_parts['extension'],
                     'title' => file_ext_strip($uplfilename),
                     'postname' => make_postname(file_ext_strip($uplfilename)),
                     'inputloc' => $inpurl . $inpfolder);
    return $uplinfo;
}

function search_upload_postname($uplfiles, $postname, $start, $end) {
    global $WPEXPORT;
    $start = $start ?: 0;
    if (!isset($end)) { $end = (count($uplfiles) - 1); }
    $uplsrch = array();
    if ($start < $end) {
        //echo ('search up for ' . $postname . ' start-end: ' . $start . '-' . $end . '<br/>');
        for ($i = $start; $i <= $end; $i++) {
            $uplsrch = create_uplinfo($uplfiles[$i], $i + $WPEXPORT['addtoupl']);
            if ($postname == $uplsrch['postname']) {
                $uplsrch['index'] = $i;
                //echo ('found it!' . $uplsrch['index'] . '<br/>');
                return $uplsrch;
            }
        }
    } else {
        //echo ('search down for ' . $postname . ' start-end: ' . $start . '-' . $end . '<br/>');
        for ($i = $start; $i >= $end; $i--) {
            $uplsrch = create_uplinfo($uplfiles[$i], $i + $WPEXPORT['addtoupl']);
            if ($postname == $uplsrch['postname']) {
                $uplsrch['index'] = $i;
                //echo ('found it!' . $uplsrch['index'] . '<br/>');
                return $uplsrch;
            }
        }
    }
    return $uplsrch;
}

function search_upload_filename($uplfiles, $filename, $start, $end) {
    global $WPEXPORT;
    $start = $start ?: 0;
    if (!isset($end)) { $end = (count($uplfiles) - 1); }
    $uplsrch = array();
    $path_parts = pathinfo($filename);
    $filesrch = $path_parts['basename'];
    $filebase = '';
    if ($path_parts['dirname'] != '.') {
        $filebase = $path_parts['dirname'] . '/';
    }
    if ($start < $end) {
        //echo ('search up for ' . $filename . ' start-end: ' . $start . '-' . $end . '<br/>');
        for ($i = $start; $i <= $end; $i++) {
            $uplsrch = create_uplinfo($uplfiles[$i], $i + $WPEXPORT['addtoupl']);
            if ($filesrch == $uplsrch['filename'] && $filebase == $uplsrch['basefolder']) {
                $uplsrch['index'] = $i;
                //echo ('found it!' . $uplsrch['index'] . '<br/>');
                return $uplsrch;
            }
        }
    } else {
        //echo ('search down for ' . $filename . ' start-end: ' . $start . '-' . $end . '<br/>');
        for ($i = $start; $i >= $end; $i--) {
            $uplsrch = create_uplinfo($uplfiles[$i], $i + $WPEXPORT['addtoupl']);
            if ($filesrch == $uplsrch['filename'] && $filebase == $uplsrch['basefolder']) {
                $uplsrch['index'] = $i;
                //echo ('found it!' . $uplsrch['index'] . '<br/>');
                return $uplsrch;
            }
        }
    }
    return $uplsrch;
}

function glob_recursive($pattern, $flags = 0) {
// Does not support flag GLOB_BRACE
    $files = glob($pattern, $flags);
    foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR) as $dir) {
        $files = array_merge($files, glob_recursive($dir.'/'.basename($pattern), $flags));
    }
    return $files;
}

function make_postname($name) {
    return $name = strtolower(str_replace(" ", "-", $name));
}

// Returns only the file extension (without the period).
function file_ext($filename) {
    if( !preg_match('/./', $filename) ) return '';
    return preg_replace('/^.*./', '', $filename);
}
// Returns the file name, less the extension.
function file_ext_strip($filename){
    return preg_replace('/.[^.]*$/', '', $filename);
}