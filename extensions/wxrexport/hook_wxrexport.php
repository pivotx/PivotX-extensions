<?php
// - Extension: WXR Export
// - Version: 0.2
// - Author: PivotX team 
// - Site: http://www.pivotx.net
// - Description: Export content in WXR (WordPress eXtended RSS) format.
// - Date: 2015-01-11
// - Identifier: wxrexport


// You can change things yourself to influence processing. These points are visible by the string @@CHANGE

$this->addHook(
    'configuration_add',
    'wxrexport',
    array('functionalCallWxrExportConfigurationAdd', 'WXR Export')
);


class pivotxWxrExport
{
    public static $itemcnt = 0;
    public static $warncnt = 0;
    public static $id_min_org = 99999999;
    public static $id_max_org = 0;
    public static $id_min_new = 99999999;
    public static $id_max_new = 0;
    // @@CHANGE 
    // If you are importing into an existing WP then you probably want to add some number to the internal ids
    // so these will be recognisable in future; also ids for pages and entries can be the same in PivotX but in WP
    // they cannot.
    // These old and new ids can also be used in the chaparray after importing the chapters and exporting the pages.
    // Vars addto... are meant to accomplish this.
    // 
    // upload_dest_def is the folder name to set in the export whenever an upload is encountered that is not in a yyyy-nn
    // subfolder (WP only uses that structure)
    //
    // upload_input is an array where you can specify which subfolder's content should be exported
    // start the value with #ROOT# to get folder from the root (it will be replaced by document root)
    // (value in upload_base_url, pivotx/pics and pivotx/includes/emoticons/trillian will be included automatically)
    //
    // thumb_repl can contain the replacement string for a thumbnail file whenever it is referenced in the content
    //
    // thumb_skip can be set to true or false to skip thumbnails from being exported
    //
    // dest_base is the base name of the folder where the wxr cms is in
    //
    // include_skip can contain the include elements in your templates folder that you do not want to include ([[ include tag)
    //
    // include_skip_all can specify whether you do not want to include any element at all ([[ include tag)
    //
    // addtoupl generates fixed ids based on the sequence in the total collection of uploads;
    // this is necessary to connect an entry or page's image field to the right WP media id
    //
    // addtogall generates fixed ids based on the sequence of encountered galleries;
    // this is necessary to add these galleries to an entry or page in WP Envira plugin
    //
    // efprefix is the prefix put in front of the exported extrafield field names
    //
    // entrysel gives you the option to only select specific categories (also valid for category export) or uids
    //
    // pagesel gives you the option to only select specific chapters (also valid for chapter export)
    //
    // defweblog is meant to specify the name of your default weblog folder name
    //
    // @@CHANGE
    public static $upload_dest_def = '2010/01';
    public static $upload_input = array('images/');  // always end the element with a "/"
    //public static $upload_input = array('images/','media/','#ROOT#/files/');  // example for 2 relative folders and 1 direct from root
    public static $thumb_repl = '';  // replacement string within content for thumbnails for images (WP uses "-200x200")
    public static $thumb_skip = true;  // skip the export of thumbnails
    public static $dest_base = '/wordpress';      // default set for WP
    public static $include_skip = array('skip_this_include.tpl','subfolder/and_this_one_too.php');  // skip include elements in content ([[ include tag)
    public static $include_skip_all = false;  // skip all includes
    public static $addtochap  = 100;
    public static $addtogall  = 150;
    public static $addtoentry = 200;
    public static $addtopage  = 500;
    public static $addtoupl   = 800;
    public static $efprefix = 'pivx_';   // only lower case!
    public static $entrysel = array('show'=>20000);   //  all categories are selected
    //public static $entrysel = array('cats'=>array('default', 'linkdump'),'show'=>20000);   // only specific categories
    //public static $entrysel = array('uid'=>array(75,85),'show'=>20000);   // only specific uids
    public static $pagesel = array();     // all chapters are selected
    //public static $pagesel = array('chapters'=>array('Pages', 'Pages2'));    // only specific chapters (upper case name!)
    public static $defweblog = 'weblog';
    
    public static function adminTab(&$form_html)
    {
        global $PIVOTX;

        $form = $PIVOTX['extensions']->getAdminForm('wxrexport');

        $output = <<<THEEND
<tr>
<td>  
<p>Optional actions before exporting content</p>
<ol>
    <li><a href="?page=wxrexport&amp;type=categories">
        Export Categories
    </a></li>
    <li><a href="?page=wxrexport&amp;type=chapters">
        Export Chapters as plain pages that can be used to parent the PivotX pages
    </a></li>
    <li><a href="?page=wxrexport&amp;type=uploads">
        Export Uploads
    </a></li>
    <li><a href="?page=wxrexport&amp;type=extrafields">
        Export Extrafields definitions like e.g. Bonusfields extension for use in ACF plugin for WP (galleries will be skipped)
    </a></li>
    <li><a href="?page=wxrexport&amp;type=galleries">
        Export Extrafields galleries for use in Envira (Lite) plugin for WP
    </a></li>
</ol>
<p>With parsing of introduction and body content</p>
<ol>
    <li><a href="?page=wxrexport&amp;type=pages">
        Export Pages
    </a></li>
    <li><a href="?page=wxrexport&amp;type=pages&amp;galleries=yes">
        Export Pages and Galleries 
    </a></li>
    <li>Export Entries
    <ul>
        <li><a href="?page=wxrexport&amp;type=entries">Without comments</a></li>
        <li><a href="?page=wxrexport&amp;type=entries+comments">Including comments</a></li>
    </ul>
    </li>
    <li>Export Entries and Galleries
    <ul>
        <li><a href="?page=wxrexport&amp;type=entries&amp;galleries=yes">Without comments</a></li>
        <li><a href="?page=wxrexport&amp;type=entries+comments&amp;galleries=yes">Including comments</a></li>
    </ul>
    </li>
</ol>
<p>Without parsing of introduction and body content (so you can check where template tags and smarty variables are used)</p>
<ol>
    <li><a href="?page=wxrexport&amp;type=pages&amp;parse=no">
        Export Pages
    </a></li>
    <li>Export Entries
    <ul>
        <li><a href="?page=wxrexport&amp;type=entries&amp;parse=no">Without comments</a></li>
        <li><a href="?page=wxrexport&amp;type=entries+comments&amp;parse=no">Including comments</a></li>
    </ul>
    </li>
</ol>
</td>
</tr>
THEEND;

        $form->add(array(
            'type' => 'custom',
            'text'=> $output
        ));

        $form_html['wxrexport'] = $PIVOTX['extensions']->getAdminFormHtml($form, false);

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
        $output = '';
        self::recordId(0, 0);   // so default of minimum gets overwritten
        foreach($PIVOTX['categories']->data as $cat) {
            if (array_key_exists('cats', self::$entrysel)) {
                if (!in_array($cat['name'], self::$entrysel['cats'])) {
                    continue;
                }
            }
            $output .= '<wp:category><wp:category_nicename>'.htmlspecialchars($cat['name']).'</wp:category_nicename><wp:category_parent></wp:category_parent><wp:cat_name><![CDATA['.$cat['display'].']]></wp:cat_name></wp:category>'."\n";
            self::$itemcnt++;
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
        $output = '';
        self::recordId(0, 0);   // so default of minimum gets overwritten

        $extrafields = self::getExtrafields();

        if ($extrafields == false) {
            $output = '<!-- Warning! You have no extension with Extrafields installed -->'."\n";
            self::$warncnt++;
        } else {
            if (!is_array($extrafields)) {
                $output = '<!-- Warning! You have no Extrafields defined -->'."\n";
                self::$warncnt++;
            } else {
                $output .= '<item>'."\n";
                $record['post_id'] = 0;
                $efdate = date('Y-m-d H:i:s', strtotime($efdate . ' - 1 day'));  // to be sure that imported item will be published
                $record['post_parent'] = '0';

                $efmeta = self::buildEFMeta('entry', $extrafields);
                if ($efmeta == '') {
                    $output .= '<!-- Warning! You have no Extrafields for entries defined -->'."\n";
                    self::$warncnt++;
                } else {
                    $output .= self::outputMap(array(
                    'title' => 'Post_extrafields',
                    'link' => '0',
                    'pubDate' => $efdate,
                    'dc:creator' => array('cdata' , 'pivx_extrafields'),
                    'guid isPermaLink="false"' => '0',
                    'wp:post_id' => $record['post_id'],
                    'wp:post_date' => $efdate,
                    'wp:post_date_gmt' => $efdate,
                    'wp:comment_status' => 'closed',
                    'wp:ping_status' => 'closed',
                    'wp:post_name' => 'acf_post_extrafields',
                    'wp:status' => 'publish',
                    'wp:post_parent' => '0',
                    'wp:menu_order' => '101',
                    'wp:post_type' => 'acf',
                    'wp:post_password' => '',
                    'wp:postmeta' => array('html', $efmeta),
                    ));
                }
                $output .= '</item>'."\n";
                self::$itemcnt++;

                $output .= '<item>'."\n";
                $record['post_id'] = 0;
                $efdate = date('Y-m-d H:i:s', strtotime($efdate . ' - 1 day'));  // to be sure that imported item will be published
                $record['post_parent'] = '0';

                $efmeta = self::buildEFMeta('page', $extrafields);
                if ($efmeta == '') {
                    $output .= '<!-- Warning! You have no Extrafields for pages defined -->'."\n";
                    self::$warncnt++;
                } else {
                    $output .= self::outputMap(array(
                    'title' => 'Page_extrafields',
                    'link' => '0',
                    'pubDate' => $efdate,
                    'dc:creator' => array('cdata' , 'pivx_extrafields'),
                    'guid isPermaLink="false"' => '0',
                    'wp:post_id' => $record['post_id'],
                    'wp:post_date' => $efdate,
                    'wp:post_date_gmt' => $efdate,
                    'wp:comment_status' => 'closed',
                    'wp:ping_status' => 'closed',
                    'wp:post_name' => 'acf_page_extrafields',
                    'wp:status' => 'publish',
                    'wp:post_parent' => '0',
                    'wp:menu_order' => '102',
                    'wp:post_type' => 'acf',
                    'wp:post_password' => '',
                    'wp:postmeta' => array('html', $efmeta),
                    ));
                }
                $output .= '</item>'."\n";
                self::$itemcnt++;

            }
        }
        return $output;
    }

    private static function outputWXR_Galleries()
    {
        global $PIVOTX;
        $output = '';
        $activeext = $PIVOTX['extensions']->getActivated();
        $galleries = self::getGalleries();
        $gallcnt = count($galleries);

        if ($gallcnt == 0) {
            $output = '<!-- Warning! There are no galleries found -->'."\n";
            self::$warncnt++;
        } else {
            $record['post_id'] = 0;
            $galldate = date('Y-m-d H:i:s', strtotime($efdate . ' - 1 day'));  // to be sure that imported item will be published
            $record['post_parent'] = '0';
            foreach ($galleries as $gallery) {
                self::recordId(0, $gallery['gall_id']);
                $output .= '<item>'."\n";
                $gallery['title'] = $gallery['gall_name'] . ' for ' . $gallery['content_uid_title'];
                $gallery['post_name'] = $gallery['gall_name'] . '_' . $gallery['content_type'] . '_' . $gallery['content_uid'];
                $gallmeta = self::buildGallMeta($gallery);
                $output .= self::outputMap(array(
                    'title' => $gallery['title'],
                    'link' => '0',
                    'pubDate' => $galldate,
                    'dc:creator' => array('cdata' , 'pivx_galleries'),
                    'guid isPermaLink="false"' => '0',
                    'wp:post_id' => $gallery['gall_id'],
                    'wp:post_date' => $galldate,
                    'wp:post_date_gmt' => $galldate,
                    'wp:comment_status' => 'closed',
                    'wp:ping_status' => 'closed',
                    'wp:post_name' => $gallery['post_name'],
                    'wp:status' => 'publish',
                    'wp:post_parent' => '0',
                    'wp:menu_order' => '0',
                    'wp:post_type' => 'envira',
                    'wp:post_password' => '',
                    'wp:postmeta' => array('html', $gallmeta),
                    ));
                $output .= '</item>'."\n";
                self::$itemcnt++;
            }
        }
        return $output;
    }

    private static function outputWXR_Tags()
    {
        // Tags can be supplied within the item (so no need for this routine any more?)
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
        global $PIVOTX;
        $record = $chapter;

        $password = '';
        $activeext = $PIVOTX['extensions']->getActivated();
        // extension passwordprotect active?
        if (in_array('passwordprotect',$activeext)) {
            $passconf = $PIVOTX['config']->get('passwordprotect');
            $passchap = explode(",",$PIVOTX['config']->get('passwordprotect_chapters'));
            $passdefp = $PIVOTX['config']->get('passwordprotect_default');
            // password protection for whole site?
            if ($passconf == '2') {
                $password = $passdefp;
            }
            // password protection partially for categories and/or chapters?
            if ($passconf == '1') {
                if (in_array('chapter_' . $record['uid'], $passchap)) {
                    $password = $passdefp;
                }
            }
        }

        $output = '';
        $output .= '<item>'."\n";
        $chapdate = date('Y-m-d H:i:s', strtotime($chapdate . ' - 1 day'));  // to be sure that imported page will be published
        $record['post_type'] = 'page';

        $record['post_id'] = $record['uid'] + self::$addtochap;
        // do not record if chapter is placed in front of page
        if ($record['forpage'] != 1) {
            self::recordId($record['uid'], $record['post_id']);
        }
        $output .= '<!-- Item for old id ' . $record['uid'] .  ' to post_id ' . $record['post_id'] . ' -->'."\n";
        $record['post_parent'] = '0';
        $output .= self::outputMap(array(
                'title' => $record['chaptername'],
                'link' => '0',
                'pubDate' => $chapdate,
                'dc:creator' => array('cdata' , 'pivx_chapter'),
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
                'wp:post_password' => $password,
            ));
        $output .= '</item>'."\n";
        self::$itemcnt++;

        return $output;
    }

    private static function outputWXR_Uploads($upload)
    {
        $record = $upload;
        $output = '';
        $output .= '<item>'."\n";
        $upldate = date('Y-m-d H:i:s');
        $record['post_id'] = $record['uid'];
        $output .= '<!-- Item for upload will have id ' . $record['post_id'] . ' -->'."\n";
        $record['post_parent'] = '0';
        self::recordId(0, $record['post_id']);
        $attmeta = "\n" . self::outputMap(array(
                'wp:meta_key' => '_wp_attached_file',
                'wp:meta_value' => array('cdata', $record['destfolder'] . '/' . $record['filename']),
            ));
        $output .= self::outputMap(array(
                'title' => $record['title'],
                'link' => '0',
                'pubDate' => $upldate,
                'dc:creator' => array('cdata' , 'pivx_upload'),
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
        self::$itemcnt++;

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

<!-- generator="PivotX/WXR-Export" created="$created"-->
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
        $itemcnt = self::$itemcnt;
        $warncnt = self::$warncnt;
        $minid_org = self::$id_min_org;
        $maxid_org = self::$id_max_org;
        $minid_new = self::$id_min_new;
        $maxid_new = self::$id_max_new;
        $extraline = '';
        if ($exporttype == 'entries' || $exporttype == 'pages') {
            $extraline .= "\n" . '<!-- Replace [imgpath] if needed (see docs) -->';
        }
        // check if maximum exceeds any of the other addto... values
        if ($maxid_new > self::$addtoentry && $minid_new < self::$addtoentry) {
            $extraline .= "\n" . '<!-- Warning! This export overlaps the id range for addtoentry! -->';
            $warncnt++;
        }
        if ($maxid_new > self::$addtopage && $minid_new < self::$addtopage) {
            $extraline .= "\n" . '<!-- Warning! This export overlaps the id range for addtopage! -->';
            $warncnt++;
        }
        if ($maxid_new > self::$addtochap && $minid_new < self::$addtochap) {
            $extraline .= "\n" . '<!-- Warning! This export overlaps the id range for addtochap! -->';
            $warncnt++;
        }
        if ($maxid_new > self::$addtogall && $minid_new < self::$addtogall) {
            $extraline .= "\n" . '<!-- Warning! This export overlaps the id range for addtogall! -->';
            $warncnt++;
        }
        if ($maxid_new > self::$addtoupl && $minid_new < self::$addtoupl) {
            $extraline .= "\n" . '<!-- Warning! This export overlaps the id range for addtoupl! -->';
            $warncnt++;
        }
        return <<<THEEND
</channel>
</rss>
<!-- This is a WordPress eXtended RSS file generated by PivotX as an export of your site. -->
<!-- It contains information about your $exporttype -->
<!-- Number of export items generated: $itemcnt -->
<!-- Number of warnings generated: $warncnt -->$extraline
<!-- The original ids encountered were: $minid_org (minimum) and $maxid_org (maximum) -->
<!-- The new ids set are: $minid_new (minimum) and $maxid_new (maximum) -->
THEEND;
    }

    private static function convertPageToItem($page, $comments)
    {
        global $PIVOTX;
        global $chaparray;
        $item = $page;
        if (true) {
            // needed to fix trimmed introductions
            $item = $PIVOTX['pages']->getPage($page['uid']);
        }
        $item['link'] = $PIVOTX['paths']['canonical_host'].makePageLink($page['uri'], $page['title'], $page['uid']);
        $item['post_type'] = 'page';
        $item['pivx_type'] = 'page';
        $item['post_id'] = $item['uid'] + self::$addtopage;
        if ($item['new_uid'] != '') {
            $item['post_id'] = $item['new_uid'];
        }

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
        $item = $entry;
        if ($comments) {
            $PIVOTX['cache']->clear();
            $item = $PIVOTX['db']->read_entry($entry['code']);
        }
        $item['link'] = $PIVOTX['paths']['canonical_host'].makeFileLink($entry, '', '');
        $item['post_type'] = 'post';
        $item['pivx_type'] = 'entry';

        $item['post_id'] = $item['uid'] + self::$addtoentry;

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
        global $UPLFILES;
        global $EXTRAFIELDS;
        global $GALLERIES;
        self::repairEmot();
        $output = '';
        $parse = isset( $_GET['parse'] ) ? $_GET['parse'] : '';
        $gallsel = isset( $_GET['galleries'] ) ? $_GET['galleries'] : '';

        $activeext = $PIVOTX['extensions']->getActivated();
        // extension passwordprotect active?
        if (in_array('passwordprotect',$activeext)) {
            $passactv = '1';
            $passconf = $PIVOTX['config']->get('passwordprotect');
            $passcats = explode(",",$PIVOTX['config']->get('passwordprotect_categories'));
            $passchap = explode(",",$PIVOTX['config']->get('passwordprotect_chapters'));
            $passdefp = $PIVOTX['config']->get('passwordprotect_default');
        } else {
            $passactv = '0';
            $passconf = '0';
        }

        foreach($data as &$record) {

            $record = call_user_func($callback, $record, $comments); 
            // xiao: something goes wrong here with the comments!!!!
            // harm: I tested with comments and all seems to process well?

            // set the $entry.field.field fields that can be used directly in the content
            if ($record['pivx_type'] == 'entry') {
                $PIVOTX['template']->assign('entry', $record);
            }
            // do the same for the $page fields
            if ($record['pivx_type'] == 'page') {
                $PIVOTX['template']->assign('page', $record);
            }
            // and for some system vars
            $PIVOTX['template']->assign('paths', $PIVOTX['paths']);
            // and fake the modifier vars
            $recmod = array();
            $recmod['pagetype'] = $recmod['action'] = $record['pivx_type'];
            $recmod['root'] = $recmod['home'] = null;
            $recmod['uid'] = $record['uid'];
            $recmod['weblog'] = self::$defweblog;
            $recmod['uri'] = $record['uri'];
            $PIVOTX['template']->assign('modifier', $recmod);

//@@CHANGE REPLACE STRINGS HERE -- start
            // replace some strings in introduction and body before parsing
            // Scan your xml output for message "Smarty error:"
            // Also the string Unrecognized template code:  means a template tag was not translated

            // Warning: files can be included in included files -- these strings cannot be seen from here

            // `$templatedir` --> your default weblog
            $record = self::replaceIt($record, "`\$templatedir`", getcwd() . "/templates/" . self::$defweblog);
            // include file="weblog/ 
            $record = self::replaceIt($record, 'include file="'.self::$defweblog.'/', 'include file="' . getcwd() . '/templates/'.self::$defweblog.'/');
            // &gt; due to editor (or the parsing?)
            $record = self::replaceIt($record, '&gt;', '>');
            // &lt; due to editor (or the parsing?)
            $record = self::replaceIt($record, '&lt;', '<');
            // skip all includes?
            if (self::$include_skip_all) {
                $record = self::replaceIt($record, '[[ include', '[[*include');
            }
            foreach (self::$include_skip as $incl_skip) {
                //echo getcwd() . "/templates/" . self::$defweblog . '/' . $incl_skip . '<br/>';
                $record = self::replaceIt($record, '[[ include file="' . getcwd() . "/templates/" . self::$defweblog . '/' . $incl_skip . '" ]]', '<!-- Include skipped! -->');
            }
            // extension imagetools active? (need at least version 0.8.1 to use this)
            if (in_array('imagetools',$activeext)) {
                $record = self::replaceIt($record, '[[ thumbnail ', '[[ thumbnail noencode=1 ');
                $record = self::replaceIt($record, '[[thumbnail ', '[[ thumbnail noencode=1 ');
            }
            // extension bonusforms active? (change the template tag to inactivate because of parsing errors)
            if (in_array('bonusforms',$activeext)) {
                $record = self::replaceIt($record, '[[ bonusform ', '[[ noexport_bonusform ');
                $record = self::replaceIt($record, '[[bonusform ', '[[ noexport_bonusform ');
            }

//@@CHANGE REPLACE STRINGS HERE -- end

            $excerpt_encoded = ''; 

            if ($parse != 'no') {
                $content_encoded = parse_intro_or_body($record['introduction']); 
                $content_encoded .= parse_intro_or_body($record['body']); 
            } else {
                $content_encoded = $record['introduction'];
                $content_encoded .= $record['body'];
            }
            $content_encoded = rawurldecode(html_entity_decode($content_encoded, ENT_QUOTES, "UTF-8"));

            // todo: scan for tag tags in content and replace them
            // todo: scan for archive links
            // todo: scan for internal links from the content itself

            $repldebug = 'item processing: ' . $record['uid'] . '|' . $record['title'];
            $content_encoded = self::contentReplParts($content_encoded, $parse, $repldebug);

            $image = '';
            $password = '';
            $passprot = '0';
            $categories      = array();
            if (isset($record['category'])) {
                $categories = $record['category'];
            }
            $extrafmeta = '';
            $extrafcnt  = 0;
            // process extrafields
            if ($record['extrafields'] != '') {
                foreach($record['extrafields'] as $extrakey=>$extrafield) {
                    // the "normal" image fields
                    if ($extrakey == 'image' || $extrakey == 'afbeelding') {
                        $image = $PIVOTX['paths']['host'].$PIVOTX['paths']['upload_base_url'] . $extrafield;
                        $uplinfo = self::searchUploadByFilename($UPLFILES, $extrafield);
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
                            self::$warncnt++;
                        }
                    // password protected?
                    } elseif ($extrakey == 'passwordprotect') {
                        $passprot = $extrafield;
                        continue;
                    } elseif ($extrakey == 'password') {
                        $password = $extrafield;
                        continue;
                    // skip these ones   todo: find a solution for them
                    } elseif ($extrakey == 'image_description') {
                        //echo 'skip extrafield ' . $extrakey . '/' . $extrafield . '<br/>';
                        continue;
                    } else {
                        // process other extrafields
                        $extrafieldtype = self::getEFType($extrakey, $record['pivx_type'], $EXTRAFIELDS);
                        if ($gallsel == 'yes' && $extrafieldtype == 'gallery') {
                            $gallkey = self::getGallKey($extrakey, $record['pivx_type'], $record['uid']);
                            if ($gallkey != 0) {
                                $content_encoded .= '<br/>[envira-gallery id="' . $gallkey . '"]';
                            } else {
                                $content_encoded .= '<!-- Warning! Gallery id not found! ' . $extrakey . ' -->';
                                self::$warncnt++;
                            }
                        } else {
                            $extrafmeta .= self::processEFExtra($extrakey, $record['pivx_type'], $EXTRAFIELDS, $extrafield, $extrafcnt);
                            $extrafcnt   = $extrafcnt + 1;
                        }
                    }
                }
            }
            // subtitle
            if ($record['subtitle'] != '') {
                $extrafmeta .= self::processEFExtra('subtitle', $record['pivx_type'], $EXTRAFIELDS, $record['subtitle'], $extrafcnt);
                $extrafcnt   = $extrafcnt + 1;
            }
            // decide whether item is really password protected
            if ($passactv != '1' || $passprot != '1') {
                $password = '';
            }
            // password protection for whole site?
            if ($passconf == '2' && $password == '') {
                $password = $passdefp;
            }
            // password protection partially for categories and/or chapters?
            if ($passconf == '1' && $password == '') {
                foreach ($categories as $category) {
                    if (in_array($category, $passcats)) {
                        $password = $passdefp;
                        //break;    // breaks the upper loop too
                    }
                }
                if ($record['chapter'] != '') {
                    if (in_array('chapter_' . $record['chapter'], $passchap)) {
                        $password = $passdefp;
                        //break;    // breaks the upper loop too
                    }
                }
            }
            $output .= '<item>'."\n";
            $output .= '<!-- Item for old id ' . $record['uid'] .  ' to post_id ' . $record['post_id'] . ' -->'."\n";
            //$output .= '<!-- ' . var_export($record, true) . ' -->';
            $recstatus = $record['status'];
            if ($recstatus == 'hold') {
                $recstatus = 'pending';
            }
            if ($recstatus == 'timed') {
                $recstatus = 'future';
            }
            self::recordId($record['uid'], $record['post_id']);
            $output .= self::outputMap(array(
                'title' => $record['title'],
                'link' => $record['link'],
                'pubDate' => array('date_2822', $record['publish_date']),
                'dc:creator' => array('cdata', $record['user']),
                '#1' => self::outputWXR_ItemCategories($categories),
                '#2' => self::outputWXR_ItemTags($record['keywords']),
                'guid isPermaLink="true"' => $record['link'],
                'description' => '',
                'image' => $image,
                'excerpt:encoded' => array('cdata', $excerpt_encoded),
                'content:encoded' => array('cdata', $content_encoded),
                'wp:post_id' => $record['post_id'],
                'wp:post_date' => array('date', $record['publish_date']),
                'wp:post_date_gmt' => array('date_gmt', $record['publish_date']),
                'wp:comment_status' => (isset($record['allow_comments']) && $record['allow_comments']) ? 'open' : 'closed',
                'wp:ping_status' => 'closed',
                'wp:status' => $recstatus,
                'wp:post_parent' => $record['post_parent'],
                'wp:menu_order' => $record['sortorder'],
                'wp:post_type' => $record['post_type'],
                'wp:post_password' => $password,
                'wp:postmeta' => array('html', $extrafmeta),
            ));
            if ($comments && ($record['comment_count'] > 0)) {
                // add comments
                $output .= self::outputWXR_Comments($record['comments']);
            }
            $output .= '</item>'."\n";
            self::$itemcnt++;
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
        global $UPLFILES;

        $output  = '';
        $output .= self::outputWXR_Header('uploads');

        $toskip     = array("index.html", ".htaccess");      // @@CHANGE
        $toskipext  = array("xyz", "123");                   // @@CHANGE

        foreach ($UPLFILES as $uplindex=>$uplfile) {
            $uplinfo    = self::createUplinfo($uplfile, $uplindex + self::$addtoupl);
            $uplinfo['index'] = $uplindex;
            // skip specific files
            if (in_array($uplinfo['filename'], $toskip)) { continue; }
            // skip specific extensions
            if (in_array($uplinfo['fileext'], $toskipext)) { continue; }
            // skip thumbnails (moved to getuplfiles)
            //if (substr($uplinfo['postname'], -6) == '.thumb') { continue; }
            $upldupl = self::searchUploadByPostname($UPLFILES, $uplinfo['postname'], $uplinfo['index'] - 1, 0);
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

    public static function exportGalleries()
    {
        global $PIVOTX;

        $output  = '';
        $output .= self::outputWXR_Header('galleries');
        $output .= self::outputWXR_Galleries();
        $output .= self::outputWXR_Footer('galleries');

        return $output;
    }

    public static function exportPages()
    {
        global $PIVOTX;
        global $chaparray;

        $chapters = $PIVOTX['pages']->getIndex();

        $output  = '';
        $output .= self::outputWXR_Header('pages');
        $output .= self::outputWXR_Tags();

        // @@CHANGE Fill chaparray with chapter ids and their corresponding WP parent ids to get their belonging pages under them
        // even when they are already in WP then they still need to be in the same WXR import to be able to function as post_parent
        $chaparray = array();
        // Hard code the desired parent ids for the chapters you wish
        //$chaparray = array(16 => 1234, 18 => 2345, 17 => 3456);
        // or build the array from the all chapters in the chapters array
        //foreach($chapters as $chapter) { 
        //    $chaparray[$chapter['uid']] = $chapter['uid']+self::$addtochap;
        //}

        foreach($chapters as $chapter) {
            $chapinfo = array('uid' => $chapter['uid'],
                              'chaptername' => $chapter['chaptername'],
                              'description' => $chapter['description'],
                              'sortorder' => $chapter['sortorder']);
            if (array_key_exists('chapters', self::$pagesel)) {
                if (!in_array($chapinfo['chaptername'], self::$pagesel['chapters'])) {
                    continue;
                }
            }
            // put version of the chapter page in front of the child pages so import knows it is OK (otherwise it won't work)
            if (array_key_exists($chapter['uid'], $chaparray)) {
                $chapinfo['new_uid'] = $chaparray[$chapter['uid']];
                $chapinfo['forpage'] = 1;
                $output .= self::outputWXR_Chapters($chapinfo);
            }

            $output .= self::outputWXR_Items($chapter['pages'], false, array('pivotxWxrExport','convertPageToItem'));
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
            if (array_key_exists('chapters', self::$pagesel)) {
                if (!in_array($chapinfo['chaptername'], self::$pagesel['chapters'])) {
                    continue;
                }
            }
            if ($chapter['chaptername'] != '') {
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
        //$output .= self::outputWXR_Categories();   // Not needed -- categories can be exported separately.
        $output .= self::outputWXR_Tags();
        $output .= self::outputWXR_Items($PIVOTX['db']->read_entries(self::$entrysel), false, array('pivotxWxrExport','convertEntryToItem'));
        $output .= self::outputWXR_Footer('entries');
        return $output;
    }

    public static function exportEntriesWithComments()
    {
        global $PIVOTX;

        $output  = '';
        $output .= self::outputWXR_Header('entries and their comments');
        //$output .= self::outputWXR_Categories();    // Not needed -- categories can be exported separately.
        $output .= self::outputWXR_Tags();

        $output .= self::outputWXR_Items($PIVOTX['db']->read_entries(self::$entrysel), true, array('pivotxWxrExport','convertEntryToItem'));

        $output .= self::outputWXR_Footer('entries and their comments');
        return $output;
    }

    public static function buildEFMeta($efsel, $extrafields) {
        // first open postmeta will be created when creating item
        $efmeta = "\n" . self::outputMap(array(
            'wp:meta_key' => '_edit_last',
            'wp:meta_value' => array('cdata', 1),
        ));
        $efmeta .= '</wp:postmeta>';
        $efselcnt = -1;
        foreach($extrafields as $extrafield) {
            //echo "extrafield: " . $extrafield['name'] . "/" . $extrafield['contenttype'] . "/" . $extrafield['type'] . "<br/>";
            if ($extrafield['contenttype'] == $efsel) {
                $efselcnt = $efselcnt + 1;
                // remove leading break (sometimes there to get description below field
                $extrafield['description'] = ltrim($extrafield['description'], '<br/>');
                $extrafield['description'] = ltrim($extrafield['description'], '<br />');
                $extrafield['description'] = ltrim($extrafield['description'], '<br>');
                // replace CR LF from description (they block the import)
                $extrafield['description'] = preg_replace( "/\r|\n/", " ", $extrafield['description'] );
                // to do: strip other html from description (like <em> <b> <i>)

                $extrafieldkey = self::getEFKey($extrafield['fieldkey'],$extrafield['contenttype'],$extrafields);

                $efmetacdata = self::buildEFMetacdata($extrafieldkey, $efselcnt, $extrafield);

                // add warning for checkbox multiple
                if ($extrafield['type'] == 'checkbox_multiple') {
                    $efmeta .= "\n" . '<!-- Warning! Extrafield "' .
                    $extrafield['name'] . '" of contenttype ' . $extrafield['contenttype'] .
                    ' is of type checkbox multiple. This type does not exist as an import type. It has been processed as single checkbox -->';
                    self::$warncnt++;
                }
                // add warning for select multiple
                if ($extrafield['type'] == 'select_multiple') {
                    $efmeta .= "\n" . '<!-- Warning! Extrafield "' .
                    $extrafield['name'] . '" of contenttype ' . $extrafield['contenttype'] .
                    ' is of type select multiple. This type does not exist as an import type. It has been processed as single select -->';
                    self::$warncnt++;
                }
                // skip gallery
                if ($extrafield['type'] == 'gallery') {
                    $efmeta .= "\n" . '<!-- Warning! Extrafield "' .
                    $extrafield['name'] . '" of contenttype ' . $extrafield['contenttype'] .
                    ' is of type gallery. This type cannot be imported in this way. Use export galleries instead -->';
                    self::$warncnt++;
                    $efmetacdata = '';
                }
                // add warning for some non processed extrafield parts
                if ($extrafield['showif_type'] != '' ||
                    $extrafield['showif'] != '') {
                    $efmeta .= "\n" . '<!-- Warning! Extrafield "' .
                    $extrafield['name'] . '" of contenttype ' . $extrafield['contenttype'] .
                    ' has a value for showif_type and/or showif that is not yet processed in this export -->';
                    self::$warncnt++;
                }
                if ($efmetacdata != '') {        
                    $efmeta .= "\n" . '<wp:postmeta>' . "\n" . self::outputMap(array(
                    'wp:meta_key' => $extrafieldkey,
                    'wp:meta_value' => array('cdata', $efmetacdata),
                    ));
                    $efmeta .= '</wp:postmeta>';
                }
            }
        }
        // rule to only show them for this selection
        $wpsel = 'post';
        if ($efsel == 'entry') { $wpsel = 'post'; }
        if ($efsel == 'page') { $wpsel = 'page'; }
        $efmeta .= "\n" . '<wp:postmeta>' . "\n" . self::outputMap(array(
            'wp:meta_key' => 'rule',
            'wp:meta_value' => array('cdata', 'a:5:{s:5:"param";s:9:"post_type";s:8:"operator";s:2:"==";s:5:"value";s:4:"' .
            $wpsel . '";s:8:"order_no";i:0;s:8:"group_no";i:0;}'),
        ));
        $efmeta .= '</wp:postmeta>';
        $efmeta .= "\n" . '<wp:postmeta>' . "\n" . self::outputMap(array(
            'wp:meta_key' => 'position',
            'wp:meta_value' => array('cdata', 'normal'),
        ));
        $efmeta .= '</wp:postmeta>';
        $efmeta .= "\n" . '<wp:postmeta>' . "\n" . self::outputMap(array(
            'wp:meta_key' => 'layout',
            'wp:meta_value' => array('cdata', 'no_box'),
        ));
        $efmeta .= '</wp:postmeta>';
        $efmeta .= "\n" . '<wp:postmeta>' . "\n" . self::outputMap(array(
            'wp:meta_key' => 'hide_on_screen',
            'wp:meta_value' => array('cdata', ''),
        ));
        // last close postmeta will be created when creating item

        if ($efselcnt == -1) {
            $efmeta = '';
        }

        return $efmeta;
    }

    public static function processEFExtra($extrakey, $pivx_type, $extrafields, $extrafield, $extrafcnt) {
        global $PIVOTX;
        global $UPLFILES;
        $extrafieldkey = self::getEFKey($extrakey, $pivx_type, $extrafields);
        $efmeta = '';
        if ($extrafieldkey == '0') {
            $efmeta .= '<!-- Warning! Extrafields key not found! ' . $extrakey . ' -->';
            self::$warncnt++;
        } else {
            $extrafieldtype = self::getEFType($extrakey, $pivx_type, $extrafields);
            if ($extrafieldtype == 'gallery') {
                $efmeta .= '<!-- Warning! Extrafields gallery skipped! ' . $extrakey . ' -->';
                self::$warncnt++;
            } else {
                if ($extrafcnt > 0) {
                    $efmeta .= '</wp:postmeta>' . "\n" . '<wp:postmeta>';
                }
                $extrafcnt   = $extrafcnt + 1;
                if ($extrafieldtype == 'checkbox' || $extrafieldtype == 'checkbox_multiple') {
                    if ($extrafield == 'on') {
                        $extrafielddata = self::getEFData($extrakey, $pivx_type, $extrafields, true);
                        $extrafield = 'a:1:{i:0;s:' . strlen($extrafielddata) . ':"' . $extrafielddata . '";}';
                    }
                }
                if ($extrafieldtype == 'choose_entry') {
                    $efentry = $PIVOTX['db']->read_entry($extrafield);
                    if ($efentry['uid'] == '') {
                        $extrafield = 'Warning! Extrafields value not found! ' . $extrafield;
                        self::$warncnt++;
                    } else {
                        $extrafield = $efentry['uid'] + self::$addtoentry;
                    }
                }
                if ($extrafieldtype == 'choose_page') {
                    $efpage = $PIVOTX['pages']->getPageByUri($extrafield);
                    if ($efpage['uid'] == '') {
                        $extrafield = 'Warning! Extrafields value not found! ' . $extrafield;
                        self::$warncnt++;
                    } else {
                        $extrafield = $efpage['uid'] + self::$addtopage;
                    }
                }
                if ($extrafieldtype == 'date' || $extrafieldtype == 'datetime') {
                    $extrafield = substr($extrafield,0,4) . substr($extrafield,5,2) . substr($extrafield,8,2);
                }
                if ($extrafieldtype == 'image' || $extrafieldtype == 'file') {
                    $uplinfo = self::searchUploadByFilename($UPLFILES, $extrafield);
                    // image/file found?
                    if (isset($uplinfo['index'])) {
                        $extrafield = $uplinfo['uid'];
                    } else {
                        $extrafield = 'Warning! Extrafields value not found! ' . $extrafield;
                        self::$warncnt++;
                    }
                }
                // ratings is an array 
                if (is_array($extrafield)) {
                    $extrafield = implode(',', $extrafield);
                }
                $efmeta .= "\n" . self::outputMap(array(
                    'wp:meta_key' => self::$efprefix . $extrakey,
                    'wp:meta_value' => array('cdata', $extrafield),
                    ));
                $efmeta .= '</wp:postmeta>' . "\n" . '<wp:postmeta>';
                $efmeta .= "\n" . self::outputMap(array(
                    'wp:meta_key' => '_' . self::$efprefix . $extrakey,
                    'wp:meta_value' => array('cdata', $extrafieldkey),
                    ));
            }
        }
        return $efmeta;
    }

    private static function getEFKey($efkey, $efctype, $extrafields) {
        $efkeycnt = 0; $efkeywxr = 0;
        foreach($extrafields as $extrafield) {
            $efkeycnt = $efkeycnt + 1;
            if ($extrafield['contenttype'] == $efctype && $extrafield['fieldkey'] == $efkey) {
                // construct key
                $effill = '';
                if ($efkeycnt < 100) { $effill = '000'; }
                if ($efkeycnt < 10) { $effill = '0000'; }
                $efkeywxr = 'field_20141116' . $effill . $efkeycnt;
                break;
            }
        }
        return $efkeywxr;
    }

    private static function getEFType($efkey, $efctype, $extrafields) {
        $eftype = '';
        foreach($extrafields as $extrafield) {
            if ($extrafield['contenttype'] == $efctype && $extrafield['fieldkey'] == $efkey) {
                $eftype = $extrafield['type'];
                break;
            }
        }
        return $eftype;
    }

    private static function getEFData($efkey, $efctype, $extrafields, $effillit) {
        $efdata = '';
        foreach($extrafields as $extrafield) {
            if ($extrafield['contenttype'] == $efctype && $extrafield['fieldkey'] == $efkey) {
                $efdata = $extrafield['data'];
                if ($efdata == '' && $effillit == true) {
                    $efdata = $extrafield['name'];
                }
                break;
            }
        }
        return $efdata;
    }

    private static function buildEFMetacdata($efkey, $efocc, $extrafield) {
        // extrafield lay-out:
        //[name] => name 
        //[fieldkey] => key 
        //[type] => type e.g. choose_page 
        //[location] => location e.g. page-introduction-before 
        //[showif_type] => cond.logic type
        //[showif] => cond.logic
        //[data] => value(s)
        //[empty_text] => placeholder text e.g. No link 
        //[description] => Description shown in editor 
        //[contenttype] => page or entry

        $efmetacdata = array(
            'key' => $efkey,
            'label' => self::$efprefix . $extrafield['name'],
            'name' => self::$efprefix . $extrafield['fieldkey'],
            'instructions' => $extrafield['description'],
            'default_value' => $extrafield['empty_text'],
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
            'order_no' => $efocc
        );

        switch ($extrafield['type']) {
            case 'input_text':
            case 'hidden':
                $efmetacdata['type'] = 'text';
                $efmetacdata['placeholder'] = $efmetacdata['prepend'] = $efmetacdata['append'] = ''; 
                $efmetacdata['maxlength'] = '';
                $efmetacdata['formatting'] = 'html';
                break;
            case 'textarea':
                $efmetacdata['type'] = 'textarea';
                $efmetacdata['placeholder'] = $efmetacdata['prepend'] = $efmetacdata['append'] = ''; 
                $efmetacdata['maxlength'] = '';
                $efmetacdata['rows'] = '';
                $efmetacdata['formatting'] = 'br';
                break;
            case 'choose_page':
                $efmetacdata['type'] = 'page_link';
                $efmetacdata['post_type'] = array('page');
                $efmetacdata['allow_null'] = '1';
                $efmetacdata['multiple'] = '0';
                unset($efmetacdata['default_value']);
                break;
            case 'choose_entry':
                $efmetacdata['type'] = 'page_link';
                $efmetacdata['post_type'] = array('post');
                $efmetacdata['allow_null'] = '1';
                $efmetacdata['multiple'] = '0';
                unset($efmetacdata['default_value']);
                break;
            case 'select':
            case 'select_multiple':
                $efmetacdata['type'] = 'select';
                $efmetacdata['choices'] = self::getEFChoices($extrafield['data'], $extrafield['name']);
                if ($extrafield['type'] == 'select_multiple') {
                    $efmetacdata['allow_null'] = '0';
                    $efmetacdata['multiple'] = '1';
                } else {
                    $efmetacdata['allow_null'] = '1';
                    $efmetacdata['multiple'] = '0';
                }
                break;
            case 'radio':
                $efmetacdata['type'] = 'radio';
                $efmetacdata['choices'] = self::getEFChoices($extrafield['data'], $extrafield['name']);
                $efmetacdata['other_choice'] = '0';
                $efmetacdata['save_other_choice'] = '0';
                $efmetacdata['layout'] = 'vertical';
                break;
            case 'checkbox':
            case 'checkbox_multiple':
                $efmetacdata['type'] = 'checkbox';
                $efmetacdata['choices'] = self::getEFChoices($extrafield['data'], $extrafield['name']);
                $efmetacdata['layout'] = 'vertical';
                break;
            case 'image':
                $efmetacdata['type'] = 'image';
                $efmetacdata['save_format'] = 'object';
                $efmetacdata['preview_size'] = 'thumbnail';
                $efmetacdata['library'] = 'all';
                unset($efmetacdata['default_value']);
                break;
            // galleries are separate entities -- will be created whenever the content contains reference to this extrafield type
            case 'gallery':
                break;
            case 'file':
                $efmetacdata['type'] = 'file';
                $efmetacdata['save_format'] = 'object';
                $efmetacdata['library'] = 'all';
                unset($efmetacdata['default_value']);
                break;
            // extrafields does not have a type number (but format still coded)
            case 'number':
                $efmetacdata['type'] = 'number';
                $efmetacdata['placeholder'] = $efmetacdata['prepend'] = $efmetacdata['append'] = ''; 
                $efmetacdata['min'] = '123';
                $efmetacdata['max'] = '123456';
                $efmetacdata['step'] = '10';
                $efmetacdata['formatting'] = 'html';
                break;
            case 'date':
            case 'datetime':
                // only date is available
                $efmetacdata['type'] = 'date_picker';
                $efmetacdata['date_format'] = 'yymmdd';    // yy is 4 char
                $efmetacdata['display_format'] = 'dd-mm-yy';   // yy is 4 char
                $efmetacdata['first_day'] = 1;
                break;
            default:
                $efmetacdata['type'] = 'text';
                $efmetacdata['placeholder'] = $efmetacdata['prepend'] = $efmetacdata['append'] = ''; 
                $efmetacdata['maxlength'] = '';
                $efmetacdata['formatting'] = 'html';
                $efmetacdata['warning'] = "Unknown Extrafields type: " . $extrafield['type'];
                self::$warncnt++;

        }
        return serialize($efmetacdata);
    }

    private static function getEFChoices($data, $name) {
        $combined_choices = explode("\r\n", $data);
        $choices = array();
        if (count($combined_choices) == 0 || $data == '') {
            $choices[$name] = $name;
        } else {
            foreach($combined_choices as $elem) {
                list($key, $value) = explode('::', $elem);
                $choices[$key] = $value;
            }
        }
        return $choices;
    }

    public static function getExtrafields() {
        global $PIVOTX;
        $extrafields = false;

        $activeext = $PIVOTX['extensions']->getActivated();
//echo print_r($activeext) . '<br/>';
        // extension bonusfields active?
        if (in_array('bonusfields',$activeext)) {
            //echo 'bonusfields active!' . '<br/>';
            if (function_exists('load_serialize')) {
                $config = load_serialize($PIVOTX['paths']['db_path'].'ser_bonusfields.php', true);
            } else if (function_exists('loadSerialize')) {
                $config = loadSerialize($PIVOTX['paths']['db_path'].'ser_bonusfields.php', true);
            }
            if ($config == true) {
                $extrafields = array();
                foreach($config['definition'] as $array_field) {
                    $extrafield = new bonusfieldsDefinition();
                    $extrafield->importFromArray($array_field);
                    $extrafields[] = $extrafield;
                }
                $efcount = count($extrafields);
                if ($efcount < 1) {
                    $extrafields = $efcount;
                } else {
                    $extrafields2 = array();
                    foreach($extrafields as $extrafield) {
                        $extrafields2[] = $extrafield->exportToArray();
                    }
                    $extrafields = $extrafields2;
                }
            }
        }
        $extadd = array(
            'name' => '',
            'fieldkey' => '',
            'type' => 'input_text',
            'location' => '',
            'showif_type' => '',
            'showif' => '',
            'data' => '',
            'empty_text' => '',
            'description' => '',
            'taxonomy' => '',
            'contenttype' => ''
        );
        // add subtitle
        $extadd['name'] = 'Subtitle';
        $extadd['fieldkey'] = 'subtitle';
        $extadd['contenttype'] = 'entry';
        array_push($extrafields, $extadd);
        $extadd['contenttype'] = 'page';
        array_push($extrafields, $extadd);
        // extension seo active?
        if (in_array('seo',$activeext)) {
            $extadd['name'] = 'SEO description';
            $extadd['fieldkey'] = 'seodescription';
            $extadd['contenttype'] = 'entry';
            array_push($extrafields, $extadd);
            $extadd['contenttype'] = 'page';
            array_push($extrafields, $extadd);
            $extadd['name'] = 'SEO keywords';
            $extadd['fieldkey'] = 'seokeywords';
            $extadd['contenttype'] = 'entry';
            array_push($extrafields, $extadd);
            $extadd['contenttype'] = 'page';
            array_push($extrafields, $extadd);
            $extadd['name'] = 'SEO title';
            $extadd['fieldkey'] = 'seotitle';
            $extadd['contenttype'] = 'entry';
            array_push($extrafields, $extadd);
            $extadd['contenttype'] = 'page';
            array_push($extrafields, $extadd);
        }
        // extension starrating active?
        if (in_array('starrating',$activeext)) {
            $extadd['name'] = 'Ratings';
            $extadd['fieldkey'] = 'ratings';
            $extadd['contenttype'] = 'entry';
            array_push($extrafields, $extadd);
            $extadd['contenttype'] = 'page';
            array_push($extrafields, $extadd);
            $extadd['name'] = 'Rating average';
            $extadd['fieldkey'] = 'ratingaverage';
            $extadd['contenttype'] = 'entry';
            array_push($extrafields, $extadd);
            $extadd['contenttype'] = 'page';
            array_push($extrafields, $extadd);
            $extadd['name'] = 'Rating count';
            $extadd['fieldkey'] = 'ratingcount';
            $extadd['contenttype'] = 'entry';
            array_push($extrafields, $extadd);
            $extadd['contenttype'] = 'page';
            array_push($extrafields, $extadd);
        }
        // extension depublish active?
        if (in_array('depublish',$activeext)) {
            $extadd['name'] = 'Depublish on';
            $extadd['fieldkey'] = 'date_depublish';
            $extadd['type'] = 'date';
            $extadd['contenttype'] = 'entry';
            array_push($extrafields, $extadd);
            $extadd['contenttype'] = 'page';
            array_push($extrafields, $extadd);
        }
//echo print_r($extrafields) . '<br/>';
        return $extrafields;
    }

    public static function getUplfiles() {
        global $PIVOTX;
        // put upload_base_url in upload_input
        $base = substr($PIVOTX['paths']['upload_base_url'],strlen($PIVOTX['paths']['site_url']));
        if (!in_array($base,self::$upload_input)) {
            self::$upload_input[] = $base;
        }
        $uplfiles  = array();
        foreach (self::$upload_input as $upload_inp) {
            //echo 'processing: ' . $upload_inp . '<br/>';
            if (substr($upload_inp,0,6) == '#ROOT#') {
                $upload_inp = str_replace('#ROOT#', $_SERVER['DOCUMENT_ROOT'], $upload_inp);
                $globfiles = _wxrexport_glob_recursive($upload_inp . "*");
            } else {
                $globfiles = _wxrexport_glob_recursive('../' . $upload_inp . "*");
            }
            // loose the entries for directories only
            foreach ($globfiles as $globfile) {
                if (!is_dir($globfile)) {
                    if (self::$thumb_skip && (strpos($globfile, '.thumb.') !== false)) {
                        continue;
                    } else {
                        $uplfiles[] = $globfile;
                        //echo print_r($globfile) . '<br/>';
                    }
                }
            }
        }
        // add the pivotx/pics
        $globfiles = _wxrexport_glob_recursive('../pivotx/pics/' . "*");
        foreach ($globfiles as $globfile) {
            if (!is_dir($globfile)) {
                if (self::$thumb_skip && (strpos($globfile, '.thumb.') !== false)) {
                    continue;
                } else {
                    $uplfiles[] = $globfile;
                }
            }
        }
        // add the pivotx/includes/emoticons/trillian
        $globfiles = _wxrexport_glob_recursive('../pivotx/includes/emoticons/trillian/' . "*.gif");
        foreach ($globfiles as $globfile) {
            if (!is_dir($globfile)) {
                if (self::$thumb_skip && (strpos($globfile, '.thumb.') !== false)) {
                    continue;
                } else {
                    $uplfiles[] = $globfile;
                }
            }
        }
        $activeext = $PIVOTX['extensions']->getActivated();
        // extension media active?
        if (in_array('media',$activeext)) {
            $globfiles = _wxrexport_glob_recursive('../pivotx/extensions/media/' . "*.jpg");
            foreach ($globfiles as $globfile) {
                if (!is_dir($globfile)) {
                    $uplfiles[] = $globfile;
                }
            }
        }
        // extension sociable active?
        if (in_array('sociable',$activeext)) {
            $globfiles = _wxrexport_glob_recursive('../pivotx/extensions/sociable/images/' . "*");
            foreach ($globfiles as $globfile) {
                if (!is_dir($globfile)) {
                    $uplfiles[] = $globfile;
                }
            }
        }
        // extension nivoslider active?
        if (in_array('nivoslider',$activeext)) {
            $globfiles = _wxrexport_glob_recursive('../pivotx/extensions/nivoslider/slides/' . "*");
            foreach ($globfiles as $globfile) {
                if (self::$thumb_skip && (strpos($globfile, '_thumb.') !== false)) {
                    continue;
                } else {
                    $uplfiles[] = $globfile;
                }
            }
        }
        // extension slidingpanel active?
        if (in_array('slidingpanel',$activeext)) {
            $globfiles = _wxrexport_glob_recursive('../pivotx/extensions/slidingpanel/icons/' . "*");
            foreach ($globfiles as $globfile) {
                if (!is_dir($globfile)) {
                    $uplfiles[] = $globfile;
                }
            }
        }
        return $uplfiles;
    }

    public static function getGalleries() {
        global $PIVOTX;
        global $EXTRAFIELDS;
        $galleries = array();
        $gallcnt = 0 + self::$addtogall;
        $entries = $PIVOTX['db']->read_entries(self::$entrysel);
        foreach($entries as $entry) {
            foreach($entry['extrafields'] as $extrakey=>$extrafield) {
                $extrafieldtype = self::getEFType($extrakey, 'entry', $EXTRAFIELDS);
                if ($extrafieldtype == 'gallery') {
                    $gallcnt++;
                    $gallarr['gall_id'] = $gallcnt;
                    $gallarr['gall_name'] = $extrakey;
                    $gallarr['gall_ftype'] = $extrafieldtype;
                    $gallarr['gall_value'] = $extrafield;
                    $gallarr['content_type'] = 'entry';
                    $gallarr['content_uid'] = $entry['uid'];
                    $gallarr['content_uid_title'] = $entry['title'];
                    $galleries[] = $gallarr;
                }
            }
        }
        $chapters = $PIVOTX['pages']->getIndex();
        foreach($chapters as $chapter) {
            foreach($chapter['pages'] as $page) {
                $page = $PIVOTX['pages']->getPage($page['uid']);
                foreach($page['extrafields'] as $extrakey=>$extrafield) {
                    $extrafieldtype = self::getEFType($extrakey, 'page', $EXTRAFIELDS);
                    if ($extrafieldtype == 'gallery') {
                        $gallcnt++;
                        $gallarr['gall_id'] = $gallcnt;
                        $gallarr['gall_name'] = $extrakey;
                        $gallarr['gall_ftype'] = $extrafieldtype;
                        $gallarr['gall_value'] = $extrafield;
                        $gallarr['content_type'] = 'page';
                        $gallarr['content_uid'] = $page['uid'];
                        $gallarr['content_uid_title'] = $page['title'];
                        $galleries[] = $gallarr;
                    }
                }
            }
        }
        //foreach($galleries as $gallery) {
        //  echo print_r($gallery) . '<br/>';
        //}
        return $galleries;
    }

    private static function getGallKey($efname, $ctype, $cuid) {
        global $GALLERIES;
        $gallkeywxr = 0;
        foreach($GALLERIES as $gallery) {
            if ($gallery['content_type'] == $ctype && $gallery['content_uid'] == $cuid && $gallery['gall_name']) {
                $gallkeywxr = $gallery['gall_id'];
                break;
            }
        }
        return $gallkeywxr;
    }

    public static function buildGallMeta($gallery) {
        global $UPLFILES;

        $gallmeta = '';
        $gallids = array();
        $gallidsdata = array(
            'id' => $gallery['gall_id'],
            'gallery' => array(),
            'config' => array(
                'columns' => '3',
                'gutter' => 10,
                'margin' => 10,
                'crop' => 0,
                'crop_width' => 960,
                'crop_height' => 300,
                'classes' => array('wxr_galleryclass'),
                'title' => $gallery['title'],
                'slug' => $gallery['post_name']
            )
        );
        $gallidsdatasrc = array(
            'status' => 'active', 
            'src' => '==uploadsrc==',
            'title' => '==title==',
            'link' => '==uploadsrc==',
            'alt' => '==alt==',
            'thumb' => ''
        );

        $galllines = preg_split('|[\r\n]+|',trim($gallery['gall_value']));
        $gallery['galllines'] = array();
        foreach ($galllines as $gallline) {
            $gallparts = explode('###',trim($gallline));
            switch (count($gallparts)) {
            case 4:
                $gallimg['data']  = trim($gallparts[3]);
            case 3:
                $gallimg['alt']   = trim($gallparts[2]);
            case 2:
                $gallimg['title'] = trim($gallparts[1]);
            case 1:
                $gallimg['image'] = trim($gallparts[0]);
                break;
            }
            $uplinfo = self::searchUploadByFilename($UPLFILES, $gallimg['image']);
            if (isset($uplinfo['index'])) {
                $gallimg['upl_uid'] = $uplinfo['uid'];
                $gallimg['upl_destfolder'] = $uplinfo['destfolder'];
                $gallimg['upl_filename'] = $uplinfo['filename'];
            } else {
                $gallimg['upl_uid'] = '0';
                $gallimg['upl_destfolder'] = 'notknown';
                $gallimg['upl_filename'] = 'warning_notfound_' . $gallimg['image'];
                self::$warncnt++;
            }
            array_push($gallery['galllines'], $gallimg);
        }

        $gallurl = self::$dest_base . '/wp-content/uploads/';
        foreach ($gallery['galllines'] as $gallline) {
            array_push($gallids, strval($gallline['upl_uid']));
            $gallidsdatasrc['src'] = $gallurl . $gallline['upl_destfolder'] . '/' . $gallline['upl_filename'];
            $gallidsdatasrc['link'] = $gallidsdatasrc['src'];
            $gallidsdatasrc['title'] = $gallline['title'];
            $gallidsdatasrc['alt'] = $gallline['alt'];
            $gallidsdata['gallery'][$gallline['upl_uid']] = $gallidsdatasrc;
        }

        $gallmeta .= "\n" . self::outputMap(array(
            'wp:meta_key' => '_eg_in_gallery',
            'wp:meta_value' => array('cdata', serialize($gallids)),
            ));
        $gallmeta .= '</wp:postmeta>';

        $gallmeta .= "\n" . '<wp:postmeta>' . "\n" . self::outputMap(array(
            'wp:meta_key' => '_eg_gallery_data',
            'wp:meta_value' => array('cdata', serialize($gallidsdata)),
            ));

        return $gallmeta;
    }

    private static function replaceIt($record, $replthis, $replby) {
        $record['introduction'] = str_replace($replthis, $replby, $record['introduction']);
        $record['body']         = str_replace($replthis, $replby, $record['body']);
        return $record;
    }

    private static function recordId($uid_org, $uid_new) {
        if ($uid_org < self::$id_min_org) { self::$id_min_org = $uid_org; }
        if ($uid_org > self::$id_max_org) { self::$id_max_org = $uid_org; }
        if ($uid_new < self::$id_min_new) { self::$id_min_new = $uid_new; }
        if ($uid_new > self::$id_max_new) { self::$id_max_new = $uid_new; }
        return;
    }

    private static function repairEmot() {
        // "repair" emoticons table (format should specify double quotes in stead of single) -- fixed in lib.php revision 4410)
        global $emot;
        foreach ($emot as $emokey=>$emotic) {
            if (substr($emotic, 0, 10) == '<img src="') {
                break;
            }
            $emotic = str_replace("<img src='", '<img src="', $emotic);
            $emotic = str_replace(".gif'", '.gif"', $emotic);
            $emotic = str_replace("alt='", 'alt="', $emotic);
            $emotic = str_replace("' />", '" />', $emotic);
            $emot[$emokey] = $emotic;
        }
    }

    private static function contentReplParts($content, $parse, $repldebug) {
        $content = self::contentReplImg($content, $repldebug);
        $content = self::contentReplLink($content, $repldebug);
        $content = self::contentReplString($content, $repldebug);
        // check/warn for remaining pivotx and other strings
        if ($parse != 'no') {
            $content = self::contentWarn($content, $repldebug);
        }
        return $content;
    }

    private static function contentReplImg($content, $repldebug) {
        global $PIVOTX;
        global $UPLFILES;
        // replace upload_base_url by something general (or a shortcode)
        $content = self::contentReplImgUploads($content, 'src=', '', '[imgpath]/', 'B', $repldebug);
        $content = self::contentReplImgUploads($content, 'href=', '', '[imgpath]/', 'B', $repldebug);
        // the same for fixed location pivotx/pics
        $findsrc = 'src="' . $PIVOTX['paths']['pivotx_url'] . 'pics/';
        $content = str_replace($findsrc, 'src="[imgpath]/', $content);
        // the same for fixed location pivotx/includes/emoticons/trillian
        $findsrc = 'src="' . $PIVOTX['paths']['pivotx_url'] . 'includes/emoticons/trillian/';
        $content = str_replace($findsrc, 'src="[imgpath]/', $content);
        // attempt to do the same for timthumb img source (can also be used as href)
        $findbetw = $PIVOTX['paths']['host'] . $PIVOTX['paths']['pivotx_url'] . 'includes/timthumb.php?src=';
        $content = self::contentReplImgUploads($content, 'src=', $findbetw, '[imgpath]/', 'B', $repldebug);
        $content = self::contentReplImgUploads($content, 'href=', $findbetw, '[imgpath]/', 'B', $repldebug);
        $findbetw = $PIVOTX['paths']['pivotx_url'] . 'includes/timthumb.php?src=';
        $content = self::contentReplImgUploads($content, 'src=', $findbetw, '[imgpath]/', 'B', $repldebug);
        $content = self::contentReplImgUploads($content, 'href=', $findbetw, '[imgpath]/', 'B', $repldebug);
        $findsrc = 'src="' . $PIVOTX['paths']['pivotx_url'] . 'includes/timthumb.php?src=';
        $content = str_replace($findsrc, 'src="[imgpath]/', $content);
        $findsrc = 'href="' . $PIVOTX['paths']['pivotx_url'] . 'includes/timthumb.php?src=';
        $content = str_replace($findsrc, 'href="[imgpath]/', $content);
        $findsrc = 'href="' . $PIVOTX['paths']['pivotx_url'] . 'includes/timwrapper.php?src=';
        $content = str_replace($findsrc, 'href="[imgpath]/', $content);

        $activeext = $PIVOTX['extensions']->getActivated();
        // extension media active?
        if (in_array('media',$activeext)) {
            // try to replace some of the flash vars
            $content = self::contentReplImgUploads($content, 'file: ' , '', '[imgpath]/', 'D', $repldebug);
            $content = self::contentReplImgUploads($content, 'soundFile: ' , '', '[imgpath]/', 'D', $repldebug);
            $content = self::contentReplImgUploads($content, 'image: ' , '', '[imgpath]/', 'D', $repldebug);
            $findsrc = 'image: "' . $PIVOTX['paths']['pivotx_url'] . 'extensions/media/';
            $content = str_replace($findsrc, 'image: "[imgpath]/', $content);
            $findsrc = 'image: "../../../' . substr($PIVOTX['paths']['upload_base_url'],strlen($PIVOTX['paths']['site_url']));
            $content = str_replace($findsrc, 'image: "[imgpath]/', $content);
            // Put in warning for the swf files to be replaced
            $findsrc = $PIVOTX['paths']['pivotx_url'] . 'extensions/media/' . 'expressInstall.swf';
            $content = str_replace($findsrc, 'Warning! Replace the flash swf files yourself! expressInstall.swf', $content, $replcnt);
            self::$warncnt = self::$warncnt + $replcnt;
            $findsrc = $PIVOTX['paths']['pivotx_url'] . 'extensions/media/' . 'audioplayer.swf';
            $content = str_replace($findsrc, 'Warning! Replace the flash swf files yourself! audioplayer.swf', $content, $replcnt);
            self::$warncnt = self::$warncnt + $replcnt;
            $findsrc = $PIVOTX['paths']['pivotx_url'] . 'extensions/media/' . 'videoplayer.swf';
            $content = str_replace($findsrc, 'Warning! Replace the flash swf files yourself! videoplayer.swf', $content, $replcnt);
            self::$warncnt = self::$warncnt + $replcnt;
        }
        // extension sociable active?
        if (in_array('sociable',$activeext)) {
            $findsrc = 'src="' . $PIVOTX['paths']['pivotx_url'] . 'extensions/sociable/images/';
            $content = str_replace($findsrc, 'src="[imgpath]/', $content);
            $findsrc = 'src="' . '/sociable/images/';
            $content = str_replace($findsrc, 'src="[imgpath]/', $content);
        }
        // extension nivoslider active?
        if (in_array('nivoslider',$activeext)) {
            $findsrc = "data-thumb='" . $PIVOTX['paths']['pivotx_url'] . 'extensions/nivoslider/slides/';
            $content = str_replace($findsrc, "data-thumb='[imgpath]/", $content);
            $findsrc = "src='" . $PIVOTX['paths']['pivotx_url'] . 'extensions/nivoslider/slides/';
            $content = str_replace($findsrc, "src='[imgpath]/", $content);
        }
        // extension slidingpanel active?
        if (in_array('slidingpanel',$activeext)) {
            $findsrc = 'src="' . $PIVOTX['paths']['host'] . $PIVOTX['paths']['pivotx_url'] . 'extensions/slidingpanel/icons/';
            $content = str_replace($findsrc, 'src="[imgpath]/', $content);
        }

        // replace the img pointer
        $findsrc = '[imgpath]/';
        $srcpos = 0; $srclen = strlen($findsrc);
        while ($srcpos !== false) {
            $srcpos = strpos($content, $findsrc, $srcpos);
            if ($srcpos !== false) {
                $endpos = strpos($content, substr($content, ($srcpos-1), 1), $srcpos+$srclen);
                if ($endpos !== false) {
                    $srcsearch = $srcimg = substr($content, $srcpos+$srclen, $endpos-($srcpos+$srclen));
                    // thumbs are skipped?
                    if (self::$thumb_skip) {
                        $srcsearch = str_replace('.thumb', '', $srcimg);
                        if (in_array('nivoslider',$activeext)) {
                            $srcsearch = str_replace('_thumb', '', $srcsearch);
                        }
                    }
                    // remnants of timthumb syntax? (&w= &h= &zc=) imagetools also uses &fit=1&type=.jpg
                    $srcparts = explode('&',$srcsearch);
                    $srcgoners = array('w=', 'h=', 'zc', 'fit=', 'type');
                    foreach ($srcparts as $srcpkey=>$srcpart) {
                        if (in_array(substr($srcpart, 0 , 2), $srcgoners)) {
                            unset($srcparts[$srcpkey]);
                        }
                        if (in_array(substr($srcpart, 0 , 4), $srcgoners)) {
                            unset($srcparts[$srcpkey]);
                        }
                    }
                    $srcsearch = implode('&',$srcparts);
                    //echo 'searching for: ' . $srcsearch . ' for ' . $repldebug . '<br/>';
                    $uplinfo = self::searchUploadByFilename($UPLFILES, $srcsearch);
                    // replace the thumb string
                    $srcimgth = str_replace('.thumb', self::$thumb_repl, $srcimg);
                    if (in_array('nivoslider',$activeext)) {
                        $srcimgth = str_replace('_thumb', self::$thumb_repl, $srcimgth);
                    }
                    $srcinbetw = '';
                    if ($srcimg != $srcimgth) {
                        $srcinbetw = self::$thumb_repl;
                    }
                    if (isset($uplinfo['index'])) {
                        $srcrepl = $uplinfo['destfolder'] . '/' .  $uplinfo['title'] . $srcinbetw . '.' . $uplinfo['fileext'];
                    } else {
                        $srcrepl = 'notknown' . '/warning_notfound_' . $srcimg;
                        self::$warncnt++;
                    }
                    $content = substr_replace($content, $srcrepl, $srcpos+$srclen, strlen($srcimg));
                } else {
                    $srcwarn = 'warning_endpos_not_found:';
                    self::$warncnt++;
                    $content = substr_replace($content, $srcwarn, $srcpos+$srclen, 0);
                }
                $srcpos = $srcpos + $srclen;
            }
        }
        return $content;
    }

    private static function contentReplImgUploads($content, $replpfx, $replbetw, $replby, $quotetype, $repldebug) {
        global $PIVOTX;
        // quote type processing? B = both / D = only double quote / S = only single quote
        if ($quotetype == 'B') {
            $content = self::contentReplImgUploads($content, $replpfx, $replbetw, $replby, 'D', $repldebug);
            $content = self::contentReplImgUploads($content, $replpfx, $replbetw, $replby, 'S', $repldebug);
            return $content;
        }
        //echo 'replstart ' . $replpfx . '|' . $replbetw . '|' . $replby . '<br/>';
        //echo 'for ' . $repldebug . '<br/>';
        if ($quotetype == 'S') {
            $replpfx .= "'";
        } else {
            $replpfx .= '"';
        }
        foreach (self::$upload_input as $upload_inp) {
            if (substr($upload_inp,0,6) == '#ROOT#') {
                $upload_inp = str_replace('#ROOT#', $PIVOTX['paths']['canonical_host'], $upload_inp);
            }
            //echo 'repl ' . $replpfx . '|' . $PIVOTX['paths']['site_url'] . $upload_inp . '<br/>';
            $content = str_replace($replpfx . $replbetw . $PIVOTX['paths']['site_url'] . $upload_inp, $replpfx . $replby, $content);
            $content = str_replace($replpfx . $replbetw . $upload_inp, $replpfx . $replby, $content);
            // sometimes only a slash in front despite other site_url
            if ($PIVOTX['paths']['site_url'] != '/') {
                $content = str_replace($replpfx . $replbetw . '/' . $upload_inp, $replpfx . $replby, $content);
            }
            // leave out first position of site_url
            $content = str_replace($replpfx . $replbetw . substr($PIVOTX['paths']['site_url'],1) . $upload_inp, $replpfx . $replby, $content);
        }
        // if replacement string contains timthumb then also without $upload_inp
        if (strpos($replbetw, 'includes/timthumb.php', 0) != 0) {
            $content = str_replace($replpfx . $replbetw . $PIVOTX['paths']['site_url'], $replpfx . $replby, $content);
            $content = str_replace($replpfx . $replbetw, $replpfx . $replby, $content);
        }
        return $content;
    }

    private static function contentReplLink($content, $repldebug) {
        global $PIVOTX;
        // todo: replace internal links
        // replace the href pointer if needed
        $findthis = 'href=';
        $posbeg = 0; $findlen = strlen($findthis);
        while ($posbeg !== false) {
            $posbeg = strpos($content, $findthis, $posbeg);
            if ($posbeg !== false) {
                $findpos1 = substr($content, $posbeg+$findlen, 1);
                //echo 'fpos1: ' . $findpos1 . '<br/>';
                //echo 'bpos : ' . $posbeg . '<br/>';
                if ($findpos1 == '"' || $findpos1 == "'") {   // real href?
                    //echo 'real href!' . '<br/>';
                    $posend = strpos($content, $findpos1, $posbeg+$findlen+1);
                    if ($posend !== false) {
                        //echo 'epos : ' . $posend . '<br/>';
                        $findsearch = strtolower($findorg = substr($content, $posbeg+$findlen+1, $posend-($posbeg+$findlen+1)));
                        //echo 'hrefbskip: ' . $findsearch . '|<br/>';
                        // skip the ones that are (already) OK
                        if (substr($findsearch,0,9) == '[imgpath]') { // do nothing (img link)
                        } elseif (substr($findsearch,0,1) == '#') { // do nothing (only hash found)
                        } elseif (substr($findsearch,0,3) == '../') { // do nothing (cannot be an internal link)
                        } elseif (substr($findsearch,0,11) == 'javascript:') { // do nothing (js call)
                        } elseif (substr($findsearch,0,1) == '"') { // do nothing (potential js var?)
                        } elseif (substr($findsearch,0,1) == "'") { // do nothing (potential js var?)
                        } elseif (substr($findsearch,0,7) == 'http://' && substr($findsearch,0,strlen($PIVOTX['paths']['canonical_host'])) != $PIVOTX['paths']['canonical_host']) { // do nothing 
                        } elseif (substr($findsearch,0,8) == 'https://' && substr($findsearch,0,strlen($PIVOTX['paths']['canonical_host'])) != $PIVOTX['paths']['canonical_host']) { // do nothing
                        } elseif (substr($findsearch,0,7) == "mailto:") { // do nothing 
                        } else {
                            //echo 'hrefaskip: ' . $findsearch . '|<br/>';
                            // potential internal link
                            if (substr($findsearch,0,strlen($PIVOTX['paths']['canonical_host'])) == $PIVOTX['paths']['canonical_host']) {
                                $findsearch = substr($findsearch, strlen($PIVOTX['paths']['canonical_host']));
                            }
                            if (substr($findsearch,0,strlen($PIVOTX['paths']['site_path'])) == $PIVOTX['paths']['site_path']) {
                                $findsearch = substr($findsearch, strlen($PIVOTX['paths']['site_path']));
                            }
                            if (substr($findsearch,0,strlen($PIVOTX['paths']['site_url'])) == $PIVOTX['paths']['site_url']) {
                                $findsearch = substr($findsearch, strlen($PIVOTX['paths']['site_url']));
                            }
                            $findpure = explode('#',$findsearch);
                            $findsearch = $findpure[0];
                            unset($findpure[0]);
                            $findhash = implode('#',$findpure);
                            //echo 'hash: ' . $findhash . '<br/>';
                            $findlinktype = ''; $findlinkvalue = '';
                            if (substr($findsearch,0,1) == '?') {
                                //echo 'query: ' . $findsearch . '|<br/>';
                                $findparts = explode('&',substr($findsearch,1));
                                foreach ($findparts as $findpart) {
                                    //echo 'qpart: ' . $findpart . '<br/>';
                                    // tag
                                    if (substr($findpart,0,2) == 't=') {
                                        $findlinktype = 'tag';
                                        $findlinkvalue = substr($findpart,2);
                                        break;
                                    }
                                    if (substr($findpart,0,2) == 'e=') {
                                        $findlinktype = 'entry';
                                        $findlinkvalue = substr($findpart,2);
                                        break;
                                    }
                                    if (substr($findpart,0,2) == 'p=') {
                                        $findlinktype = 'page';
                                        $findlinkvalue = substr($findpart,2);
                                        break;
                                    }
                                    if (substr($findpart,0,2) == 'a=') {
                                        $findlinktype = 'archive';
                                        $findlinkvalue = substr($findpart,2);
                                        break;
                                    }
                                    if (substr($findpart,0,2) == 'w=') {
                                        $findlinktype = 'weblog';
                                        $findlinkvalue = substr($findpart,2);
                                        //break;     do not break continue!
                                    }
                                    if (substr($findpart,0,2) == 'x=') {
                                        $findlinktype = 'visitor';
                                        $findlinkvalue = substr($findpart,2);
                                        break;
                                    }
                                }
                            } else {
                                //echo 'link: ' . $findsearch . '|<br/>';
                                $findparts = explode('/',$findsearch);
                                foreach ($findparts as $findkey=>$findpart) {
                                    //echo 'lpart: ' . $findkey . '|' . $findpart . '<br/>';
                                    // tag
                                    if ($findpart == 'tag') {
                                        $findlinktype = 'tag';
                                        $findlinkvalue = $findparts[$findkey+1];
                                        break;
                                    }
                                    if ($findpart == 'entry') {
                                        $findlinktype = 'entry';
                                        $findlinkvalue = $findparts[$findkey+1];
                                        break;
                                    }
                                    if ($findpart == 'page') {
                                        $findlinktype = 'page';
                                        $findlinkvalue = $findparts[$findkey+1];
                                        break;
                                    }
                                    if ($findpart == 'archive') {
                                        $findlinktype = 'archive';
                                        $findlinkvalue = $findparts[$findkey+1];
                                        break;
                                    }
                                    if ($findpart == 'category') {
                                        $findlinktype = 'category';
                                        $findlinkvalue = $findparts[$findkey+1];
                                        break;
                                    }
                                    if ($findpart == 'weblog') {
                                        $findlinktype = 'weblog';
                                        $findlinkvalue = $findparts[$findkey+1];
                                        break;
                                    }
                                    if ($findpart == 'visitor') {
                                        $findlinktype = 'visitor';
                                        $findlinkvalue = $findparts[$findkey+1];
                                        break;
                                    }
                                }
                                if ($findlinktype == '') {
                                    $findlinktype = 'entrypage';
                                    $findlinkvalue = $findparts[0];
                                }
                            }
                            //echo 'linktype: ' . $findlinktype . '|' . $findlinkvalue . '<br/>';
                            $linkentry = array(); $linkpage = array();
                            if ($findlinktype == 'entry' || $findlinktype == 'entrypage') {
                                $linkentry = $PIVOTX['db']->read_entry($findlinkvalue);
                            }
                            if ($findlinktype == 'page' || $findlinktype == 'entrypage') {
                                $linkpage = $PIVOTX['pages']->getPageByUri($findlinkvalue);
                            }
                            $unsupported_types = array('visitor','archive','category','weblog');
                            if ($findlinktype == 'entrypage' && $linkentry['uid'] != '' && $linkpage['uid'] != '') {
                                $content = substr_replace($content, 'warning_link_found_for_both_entry_and_page_', $posbeg+$findlen+1, 0);
                                echo 'entry + page link? ' . $findsearch . '|<br/>';
                                self::$warncnt++;
                            } elseif (($findlinktype == 'entrypage' || $findlinktype == 'entry' || $findlinktype == 'page') &&
                                        ($linkentry['uid'] == '' && $linkpage['uid'] == '')) {
                                $content = substr_replace($content, 'warning_uid_not_found_for_this_entry_or_page_', $posbeg+$findlen+1, 0);
                                //echo $repldebug . '<br/>';
                                //echo 'uid not found ' . $findsearch . '|<br/>';
                                self::$warncnt++;
                            } elseif ($findlinktype == 'entry' || ($findlinktype == 'entrypage' && $linkentry['uid'] != '')) {
                                $relid = $linkentry['uid'] + self::$addtoentry;
                                $content = substr_replace($content, '?p=' . $relid . $findhash, $posbeg+$findlen+1, strlen($findorg));
                            } elseif ($findlinktype == 'page' || ($findlinktype == 'entrypage' && $linkpage['uid'] != '')) {
                                $relid = $linkpage['uid'] + self::$addtopage;
                                $content = substr_replace($content, '?page_id=' . $relid . $findhash, $posbeg+$findlen+1, strlen($findorg));
                            } elseif ($findlinktype == 'tag') {
                                $content = substr_replace($content, '?tag=' . $findlinkvalue . $findhash, $posbeg+$findlen+1, strlen($findorg));
                            } elseif (in_array($findlinktype, $unsupported_types)) {
                                $content = substr_replace($content, 'warning_linktype_'.$findlinktype.'_unsupported_', $posbeg+$findlen+1, 0);
                                self::$warncnt++;
                            } else {
                                $content = substr_replace($content, 'warning_linktype_not_found_for_', $posbeg+$findlen+1, 0);
                                if ($findlinktype == '') { 
                                    //echo 'href not recognised: ' . $findsearch . '|<br/>';
                                    $content = substr_replace($content, 'nolinktype_', $posbeg+$findlen+1, 0);
                                }
                                self::$warncnt++;
                            }
                        }
                    } else {
                        $findwarn = 'warning_href_end_not_found:';
                        self::$warncnt++;
                        $content = substr_replace($content, $findwarn, $posbeg+$findlen+1, 0);
                    } 
                }
                $posbeg = $posbeg + $findlen;
            }
        }
        return $content;
    }

    private static function contentReplString($content, $repldebug) {
        // replace class pivotx-image, pivotx-popupimage and pivotx-wrapper and others @@CHANGE
        $content = str_replace('class="pivotx-image align-left"', 'class="alignleft"', $content);
        $content = str_replace('class="pivotx-image align-right"', 'class="alignright"', $content);
        $content = str_replace('class="pivotx-image"', 'class="alignnone"', $content);
        $content = str_replace('class="pivotx-popupimage align-left"', 'class="alignleft"', $content);
        $content = str_replace('class="pivotx-popupimage align-right"', 'class="alignright"', $content);
        $content = str_replace('class="pivotx-popupimage"', 'class="alignnone"', $content);
        $content = str_replace('class="pivotx-wrapper"', 'style="text-align: center;"', $content);
        $content = str_replace('class="pivotx-media', 'class="wxr-media', $content);
        $content = str_replace('class="pivotx-popup', 'class="wxr-popup', $content);
        $content = str_replace('class="pivotx-download', 'class="wxr-download', $content);
        $content = str_replace("class='pivotx-taglink'", 'class="wxr-taglink"', $content);    // quotes set differently for this link
        return $content;
    }

    private static function contentWarn($content, $repldebug) {

        // check/warn for remaining pivotx and other strings
        $contentorg = $content;
        $content2 = str_replace('/pivotx/', '@@CHANGE', $content, $replcnt);
        if ($replcnt != 0) {
            self::$warncnt++;
            $contentorg .= '<br/><!-- Warning! This content still contains ' . $replcnt . ' references to /pivotx/! -->';
        }
        $content2 = str_replace('class="pivotx', '@@CHANGE', $content, $replcnt);
        if ($replcnt != 0) {
            self::$warncnt++;
            $contentorg .= '<br/><!-- Warning! This content still contains ' . $replcnt . ' references to class=\"pivotx! (without the back slash) -->';
        }
        $content2 = str_replace($PIVOTX['paths']['host'], '@@CHANGE', $content, $replcnt);
        if ($replcnt != 0) {
            self::$warncnt++;
            $contentorg .= '<br/><!-- Warning! This content still contains ' . $replcnt . ' references to this host! -->';
        }
        if ($PIVOTX['paths']['host'] != $PIVOTX['paths']['canonical_host']) {
            $content2 = str_replace($PIVOTX['paths']['canonical_host'], '@@CHANGE', $content, $replcnt);
            if ($replcnt != 0) {
                self::$warncnt++;
                $contentorg .= '<br/><!-- Warning! This content still contains ' . $replcnt . ' references to this canonical host! -->';
            }
        }
        $content2 = str_replace('Smarty error:', '@@CHANGE', $content, $replcnt);
        if ($replcnt != 0) {
            self::$warncnt++;
            $contentorg .= '<br/><!-- Warning! This content contains ' . $replcnt . ' smarty errors! -->';
        }
        $content2 = str_replace('Unrecognized template code:', '@@CHANGE', $content, $replcnt);
        if ($replcnt != 0) {
            self::$warncnt++;
            $contentorg .= '<br/><!-- Warning! This content contains ' . $replcnt . ' unrecognised template codes (spelled unrecognized) -->';
        }
        $content2 = str_replace('href="mailto:', '@@CHANGE', $content, $replcnt);
        $content2 = str_replace("href='mailto:", '@@CHANGE', $content, $replcnt2);
        if ($replcnt != 0 || $replcnt2 != 0) {
            self::$warncnt++;
            $contentorg .= '<br/><!-- Warning! This content contains ' . ($replcnt+$replcnt2) . ' mailto links -->';
        }
        return $contentorg;
    }

    private static function createUplinfo($uplfile, $uplcounter) {
        global $PIVOTX;
        $curryear = date('Y');
        //echo 'uplinfo create for: ' . $uplfile . '<br/>';
        $inpurl   = $PIVOTX['paths']['canonical_host'] . $PIVOTX['paths']['site_url'];
        $uplinfo  = array();
        $path_parts = pathinfo($uplfile);
        $uplfilename = $path_parts['basename'];
        $inpfolder   = $path_parts['dirname'] . '/';
        $yearfolder  = self::$upload_dest_def;
        $basefolder  = '';
        // strip the main input from the total folder to check for yyyy-nn folder (and also set basefolder)
        foreach (self::$upload_input as $upload_inp) {
            if (substr($upload_inp,0,6) == '#ROOT#') {
                $upload_inp = str_replace('#ROOT#', $_SERVER['DOCUMENT_ROOT'], $upload_inp);
            } else {
                $upload_inp = '../' . $upload_inp;  //  add the prefix to get correct compare
            }
            if (substr($uplfile, 0, strlen($upload_inp)) == $upload_inp) {
                $basefolder = substr($inpfolder, strlen($upload_inp));
                $yearfolder = rtrim($basefolder,"/");
                $regex = '/\d{4}[-]\d{2}/';   //  yyyy-nn format
                if (!preg_match($regex, $yearfolder)) {
                    $yearfolder = self::$upload_dest_def;
                } else {
                    $yearparts = explode("-",$yearfolder);
                    if ($yearparts[0] < 1990 || $yearparts[0] > $curryear || $yearparts[1] < 1 || $yearparts[1] > 12) {
                        $yearfolder = self::$upload_dest_def;
                    } else {
                        $yearfolder = $yearparts[0] . '/' . $yearparts[1];
                    }
                }
                break;
            }
        }
        if (substr($inpfolder, 0, 3) == '../') {
            $inpfolder = substr($inpfolder, 3);
        }
        // put extension folder in another folder
        if ($inpfolder == 'pivotx/extensions/sociable/images/' 
            || $inpfolder == 'pivotx/extensions/nivoslider/slides/' 
            || $inpfolder == 'pivotx/extensions/slidingpanel/icons/' 
            || $inpfolder == 'pivotx/extensions/media/') {
            $yearfolder = '2000/10';    //  @@CHANGE
        }
        // root location?
        $rootloc = false;
        if (substr($inpfolder, 0, strlen($_SERVER['DOCUMENT_ROOT'])) == $_SERVER['DOCUMENT_ROOT']) {
            $rootloc = true;
            $inpurl  = $PIVOTX['paths']['canonical_host'];
            $inpfolder = substr($inpfolder, strlen($_SERVER['DOCUMENT_ROOT']));
            $yearfolder = '2000/11';    //  @@CHANGE
        }
        // put pivotx pics and emoticons in another folder
        if ($inpfolder == 'pivotx/pics/' || $inpfolder == 'pivotx/includes/emoticons/trillian/') {
            $yearfolder = '2000/12';    //  @@CHANGE
        }
//echo $uplcounter . '|' . $uplfilename . '|' . $yearfolder . '|' . $inpurl . '|' . $inpfolder . '|' . $basefolder . '<br/>';
        $uplinfo = array('uid' => $uplcounter,
                         'destfolder' => $yearfolder,
                         'filename' => $uplfilename,
                         'basefolder' => $basefolder,
                         'fileext' => $path_parts['extension'],
                         'title' => removeExtension($uplfilename),
                         'postname' => strtolower(str_replace(' ', '-', (removeExtension($uplfilename)))),
                         'rootloc' => $rootloc,
                         'inputloc' => $inpurl . $inpfolder);
        return $uplinfo;
    }

    private static function searchUploadByPostname($uplfiles, $postname, $start, $end) {
        $start = $start ?: 0;
        if (!isset($end)) { $end = (count($uplfiles) - 1); }
        $uplsrch = array();
        if ($start < $end) {
            //echo ('search up for ' . $postname . ' start-end: ' . $start . '-' . $end . '<br/>');
            for ($i = $start; $i <= $end; $i++) {
                $uplsrch = self::createUplinfo($uplfiles[$i], $i + self::$addtoupl);
                if ($postname == $uplsrch['postname']) {
                    $uplsrch['index'] = $i;
                    //echo ('found it!' . $uplsrch['index'] . '<br/>');
                    return $uplsrch;
                }
            }
        } else {
            //echo ('search down for ' . $postname . ' start-end: ' . $start . '-' . $end . '<br/>');
            for ($i = $start; $i >= $end; $i--) {
                $uplsrch = self::createUplinfo($uplfiles[$i], $i + self::$addtoupl);
                if ($postname == $uplsrch['postname']) {
                    $uplsrch['index'] = $i;
                    //echo ('found it!' . $uplsrch['index'] . '<br/>');
                    return $uplsrch;
                }
            }
        }
        return $uplsrch;
    }

    private static function searchUploadByFilename($uplfiles, $filename, $start, $end) {
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
            //echo ('search up for ' . $filename . ' in ' . $filebase . ' start-end: ' . $start . '-' . $end . '<br/>');
            for ($i = $start; $i <= $end; $i++) {
                $uplsrch = self::createUplinfo($uplfiles[$i], $i + self::$addtoupl);
                if ($filesrch == $uplsrch['filename'] && $filebase == $uplsrch['basefolder']) {
                    $uplsrch['index'] = $i;
                    //echo ('found it!' . $uplsrch['index'] . '<br/>');
                    return $uplsrch;
                }
            }
        } else {
            //echo ('search down for ' . $filename . ' in ' . $filebase . ' start-end: ' . $start . '-' . $end . '<br/>');
            for ($i = $start; $i >= $end; $i--) {
                $uplsrch = self::createUplinfo($uplfiles[$i], $i + self::$addtoupl);
                if ($filesrch == $uplsrch['filename'] && $filebase == $uplsrch['basefolder']) {
                    $uplsrch['index'] = $i;
                    //echo ('found it!' . $uplsrch['index'] . '<br/>');
                    return $uplsrch;
                }
            }
        }
        return $uplsrch;
    }
}


/**
 * functional style hook for configuration_add
 */

function functionalCallWxrExportConfigurationAdd(&$form_html)
{
/*   harm: not needed?
    if (isset($_GET['action']) && ($_GET['action'] != '')) {
        pivotxBonusfieldsInterface::actionBonusfield($_GET['action']);

        Header('Location: ?page=configuration#section-bonusfields');
        exit();
    }
*/
    return pivotxWxrExport::adminTab($form_html);
}

/**
 */
function pageWxrexport()
{
    $output = '';
    global $UPLFILES;
    global $EXTRAFIELDS;
    global $GALLERIES;

    $filename = 'blog.xml';
    if (isset($_GET['type'])) {
        switch ($_GET['type']) {
            case 'categories':
                $filename = 'categories.xml';
                $output   = pivotxWxrExport::exportCategories();
                break;
            case 'uploads':
                $filename = 'uploads.xml';
                $UPLFILES = pivotxWxrExport::getUplfiles();
                $output   = pivotxWxrExport::exportUploads();
                break;
            case 'extrafields':
                $filename = 'extrafields.xml';
                $output   = pivotxWxrExport::exportExtrafields();
                break;
            case 'galleries':
                $filename = 'galleries.xml';
                $UPLFILES = pivotxWxrExport::getUplfiles();
                $EXTRAFIELDS = pivotxWxrExport::getExtrafields();
                $output   = pivotxWxrExport::exportGalleries();
                break;
            case 'pages':
                $filename = 'pages.xml';
                $UPLFILES = pivotxWxrExport::getUplfiles();
                $EXTRAFIELDS = pivotxWxrExport::getExtrafields();
                $GALLERIES = pivotxWxrExport::getGalleries();
                $output   = pivotxWxrExport::exportPages();
                break;
            case 'chapters':
                $filename = 'chapters.xml';
                $output   = pivotxWxrExport::exportChapters();
                break;
            case 'entries':
                $filename = 'entries.xml';
                $UPLFILES = pivotxWxrExport::getUplfiles();
                $EXTRAFIELDS = pivotxWxrExport::getExtrafields();
                $GALLERIES = pivotxWxrExport::getGalleries();
                $output   = pivotxWxrExport::exportEntries();
                break;
            case 'entries comments':
                $filename = 'entries_and_comments.xml';
                $UPLFILES = pivotxWxrExport::getUplfiles();
                $EXTRAFIELDS = pivotxWxrExport::getExtrafields();
                $GALLERIES = pivotxWxrExport::getGalleries();
                $output   = pivotxWxrExport::exportEntriesWithComments();
                break;
        }
    }

    header('Content-type: text/xml');
    header('Content-disposition: attachment; filename="'.$filename.'"');
    echo $output;
}

function _wxrexport_glob_recursive($pattern, $flags = 0) {
// Does not support flag GLOB_BRACE
    $files = glob($pattern, $flags);
    foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR) as $dir) {
        $files = array_merge($files, _wxrexport_glob_recursive($dir.'/'.basename($pattern), $flags));
    }
    return $files;
}