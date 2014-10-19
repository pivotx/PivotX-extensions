<?php
// - Extension: WordPress export
// - Version: 0.2
// - Author: Two Kings // Marcel Wouters (updated by Harm Kramer)
// - Email: marcel@twokings.nl
// - Site: http://www.twokings.nl/
// - Description: Export content to a WordPress-compatible format
// - Date: 2014-10-19
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
    <li><a href="?page=wpexport&type=categories">
        Export Categories
    </a></li>
    <li><a href="?page=wpexport&type=chapters">
        Export Chapters (as plain pages that can be used to parent the PivotX pages)
    </a></li>
    <li><a href="?page=wpexport&type=uploads">
        Export Uploads
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
        $minid = $WPEXPORT['id_min'];
        $maxid = $WPEXPORT['id_max'];
        return <<<THEEND
</channel>
</rss>
<!-- This is a WordPress eXtended RSS file generated by PivotX as an export of your site. -->
<!-- It contains information about your $exporttype -->
<!-- Number of export items generated: $itemcnt -->
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
        $output = '';
        $parse = isset( $_GET['parse'] ) ? $_GET['parse'] : '';  
        foreach($data as &$record) {

            $record = call_user_func($callback, $record, $comments); // xiao: something goes wrong here with the comments!!!!
            // harm: I tested with comments and all seems to process well?

            // harm todo: find a solution for the subtitle

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
            // harm: image does not get picked up by WP?
            // todo: hook the image up with the id in the uploads process
            $image = '';
            $extimage = '';
            $thumbmeta = '';
            if (isset($record['extrafields']['image']) && ($record['extrafields']['image'] != '')) {
                $image = $PIVOTX['paths']['host'].$PIVOTX['paths']['upload_base_url'] . $record['extrafields']['image'];
                $extimage = $record['extrafields']['image'];
            }
            else if (isset($record['extrafields']['afbeelding']) && ($record['extrafields']['afbeelding'] != '')) {
                $image = $PIVOTX['paths']['host'].$PIVOTX['paths']['upload_base_url'] . $record['extrafields']['afbeelding'];
                $extimage = $record['extrafields']['afbeelding'];
            }
            if ($extimage != '') {
                $uplinfo = search_upload_filename($UPLFILES, $extimage);
                // image found?
                if (isset($uplinfo['index'])) {
                    $thumbmeta = "\n" . self::outputMap(array(
                    'wp:meta_key' => '_thumbnail_id',
                    'wp:meta_value' => array('cdata', $uplinfo['uid']),
                    ));
                } else {
                    $thumbmeta = '<!-- Warning! extrafields image not found! ' . $extimage . ' -->';
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
                'wp:postmeta' => array('html', $thumbmeta),
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
            case 'pages':
                $filename = 'pages.xml';
                $UPLFILES = get_uplfiles();
                $output   = pivotxWpExport::exportPages();
                break;
            case 'chapters':
                $filename = 'chapters.xml';
                $output   = pivotxWpExport::exportChapters();
                break;
            case 'entries':
                $filename = 'entries.xml';
                $UPLFILES = get_uplfiles();
                $output   = pivotxWpExport::exportEntries();
                break;
            case 'entries comments':
                $filename = 'entries_and_comments.xml';
                $UPLFILES = get_uplfiles();
                $output   = pivotxWpExport::exportEntriesWithComments();
                break;
        }
    }

    header('Content-type: text/xml');
    header('Content-disposition: attachment; filename="'.$filename.'"');
    echo $output;
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