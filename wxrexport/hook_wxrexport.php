<?php
// - Extension: WXR Export
// - Version: 0.2.1
// - Author: PivotX team 
// - Site: http://www.pivotx.net
// - Description: Export content in WXR (WordPress eXtended RSS) format.
// - Date: 2015-06-21
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
    public static $size_min = 99999999;
    public static $size_max = 0;
    // Information: 
    // If you are importing into an existing WP then you probably want to add some number to the internal ids
    // so these will be recognisable in future; also ids for pages and entries can be the same in PivotX but in WP
    // they cannot.
    // These old and new ids can also be used in the chaparray after importing the chapters and exporting the pages.
    // Vars addto... are meant to accomplish this.
    //
    // addtocat sets ids based on the sequence in the total collection of categories;
    // if you use category links in your content please read the documentation on how to use this properly
    //
    // addtoupl generates fixed ids based on the sequence in the total collection of uploads;
    // this is necessary to connect an entry or page's image field to the right WP media id
    //
    // addtogall generates fixed ids based on the sequence of encountered galleries;
    // this is necessary to add these galleries to an entry or page in WP Envira plugin
    // 
    // upload_dest_def is the folder name to set in the export whenever an upload is encountered that is not in a yyyy-nn
    // subfolder (WP only uses that structure)
    //
    // upload_input is an array where you can specify which subfolder's content should be exported
    // start the value with #ROOT# to get folder from the root (it will be replaced by document root)
    // (value in upload_base_url, pivotx/pics and pivotx/includes/emoticons/trillian will be included automatically)
    //
    // upload_toskip contains full file names to have to be skipped for upload export
    //
    // upload_toskipext contains extensions that should be skipped for upload export
    //
    // upload_yearalways will set an year folder based on the file date if the file is not already in a yyyy-mm folder
    //
    // upload_small / upload_medium / upload_big specify the file size (in bytes) to be used as a maximum for the group selected
    //       so small will only contain uploaded files that are smaller or equal than that value
    //       medium files will have a size bigger than the small value and smaller or equal than the specified value
    //       big files will have a size bigger than the medium value and smaller or equal than the specified value
    //       if a file is identified to have a size even bigger than the big value than a warning will be generated
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
    // efprefix is the prefix put in front of the exported extrafield field names
    //
    // efskip gives you the option to skip the export of specific extrafields
    //
    // entrysel gives you the option to only select specific categories (also valid for category export) or uids
    //
    // pagesel gives you the option to only select specific chapters (also valid for chapter export)
    //
    // chapdate gives the fixed chapter publish date (needed when you create the import for pages later to keep chapter from created twice)
    //
    // defweblog is meant to specify the name of your default weblog folder name
    //
    // aliases is meant to specify the urls of your aliases
    //
    // gallselect is meant to select what kind of gallery code you want in your content
    //         currently supported: default - standard code
    //                              mla     - media library assistant WP plugin (WPeyec)
    //                              envira  - envira lite WP plugin (WPeyec)
    //
    // seoselect is meant to select what type of export you want for your data set through extension seo
    //         currently supported: extrafield - as an extrafield meant for import into WP plug-in ACF
    //                              aioseop    - meant for All in One SEO Pack WP plug-in
    //
    // user_locale can be used to local language
    //
    // todo: set default value examples for importing into Bolt
    // todo: identify WP specific code by eye catcher: WPeyec
    // @@CHANGE
    public static $upload_dest_def = '2010/01';
    public static $upload_input = array('images/');  // always end the element with a "/"
    //public static $upload_input = array('images/','media/','#ROOT#/files/');  // example for 2 relative folders and 1 direct from root
    public static $upload_toskip = array("index.html", ".htaccess", "readme.txt");      // specific files to skip for upload
    public static $upload_toskipext  = array("fla", "swf", "php");      // specific extensions to skip (e.g. because WP thinks they are dangerous)
    public static $upload_yearalways = true;               // generate an year folder based on file date if not already in yyyy-mm folder
    public static $upload_small  = 0500000;          // file size <= this size
    public static $upload_medium = 2500000;          // file size > small size and <= this size
    public static $upload_big    = 5000000;          // file size > medium size and <= this size (warning will be generated if something bigger comes along!)
    public static $thumb_repl = '';  // replacement string for thumbnails of images (WP: Settings/Media/Thumbnail size; defaults to "-150x150")
    public static $thumb_skip = true;  // skip the export of thumbnails
    public static $dest_base = '/wordpress';      // default set for WP
    public static $include_skip = array('skip_this_include.tpl','subfolder/and_this_one_too.php');  // skip include elements in content ([[ include tag)
    public static $include_skip_all = false;  // skip all includes
    public static $addtouser  = 30;
    public static $addtovis   = 40;
    public static $addtochap  = 100;
    public static $addtocat   = 125;    // READ DOC on how to use the addto parameters properly!
    public static $addtopage  = 200;
    public static $addtogall  = 250;
    public static $addtoentry = 300;
    public static $addtoupl   = 600;
    public static $efprefix = 'pivx_extrafield_';   // only lower case!
    public static $efskip   = array('ef_skip1','ef_skip2');   // extrafields to exclude from export
    public static $entrysel = array('show'=>20000);   //  all categories are selected
    //public static $entrysel = array('cats'=>array('default', 'linkdump'),'show'=>20000);   // only specific categories
    //public static $entrysel = array('uid'=>array(75,85),'show'=>20000);   // only specific uids
    public static $pagesel = array();     // all chapters are selected
    //public static $pagesel = array('chapters'=>array('Pages', 'Pages2'));    // only specific chapters (upper case name!)
    public static $chapdate = '2010-02-14 10:04:00';   // set fixed chapter date
    public static $defweblog = 'weblog';
    // array for your aliases (full urls with http) -- you can also enter your main url if your canonical-host setting is incorrect
    public static $aliases = array();
    //public static $aliases = array('http://www.myurl.com','http://www.my-url.com');
    // selector to decide which kind of gallery code you want in your exported content (see above for detailed description)
    public static $gallselect = array('default','mla','envira');
    //public static $gallselect = array();
    public static $seoselect = array('extrafield');
    //public static $seoselect = array('aioseop');
    public static $user_locale = '';    // if the language you use for your users is German or Danish set this var to de_DE or da_DK to get the right translation
    
    public static function adminTab(&$form_html)
    {
        global $PIVOTX;

        $form = $PIVOTX['extensions']->getAdminForm('wxrexport');

        $output = <<<THEEND
<tr>
<td>  
<p>Optional actions before exporting content</p>
<ol>
    <li><a href="?page=wxrexport&amp;type=users">
        Export Users
    </a></li>
    <li><a href="?page=wxrexport&amp;type=visitors">
        Export Registered Visitors
    </a></li>
    <li><a href="?page=wxrexport&amp;type=categories">
        Export Categories
    </a></li>
    <li><a href="?page=wxrexport&amp;type=chapters">
        Export Chapters as plain pages that can be used to parent the PivotX pages
    </a></li>
    <li>Export Uploads
    <ul>
        <li><a href="?page=wxrexport&amp;type=uploads&amp;size=all">All</a></li>
        <li><a href="?page=wxrexport&amp;type=uploads&amp;size=small">Small</a></li>
        <li><a href="?page=wxrexport&amp;type=uploads&amp;size=medium">Medium</a></li>
        <li><a href="?page=wxrexport&amp;type=uploads&amp;size=big">Big</a></li>
    </ul>
    </li>
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
        $catid = 0 + self::$addtocat;  // category does not have its own uid in PivotX
        foreach($PIVOTX['categories']->data as $cat) {
            if (array_key_exists('cats', self::$entrysel)) {
                if (!in_array($cat['name'], self::$entrysel['cats'])) {
                    continue;
                }
            }
            $catid++;
            self::recordId(0, $catid);
            $output .= '<wp:category><wp:term_id>'.$catid.'</wp:term_id><wp:category_nicename>'.htmlspecialchars($cat['name']).'</wp:category_nicename><wp:category_parent></wp:category_parent><wp:cat_name><![CDATA['.$cat['display'].']]></wp:cat_name></wp:category>'."\n";
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

    private static function outputWXR_Users()
    {
        global $PIVOTX;
        $output = '';
        $userid = 0 + self::$addtouser;  // user does not have its own uid in PivotX
        $userlvl = array('Inactive user','Moblogger','Normal','Advanced','Administrator','Superadmin');
        foreach($PIVOTX['users']->data as $user) {
            // salt / password / lastseen / text_processing not used
            $userid++;
            self::recordId(0, $userid);
            $output .= '<wp:author>'."\n";
            $output .= '<wp:author_id>'.$userid.'</wp:author_id><wp:author_login>'.self::sanitizeUserName($user['username']).'</wp:author_login><wp:author_display_name><![CDATA['.htmlspecialchars($user['nickname']).']]></wp:author_display_name><wp:author_email>'.$user['email'].'</wp:author_email><wp:author_first_name><![CDATA[]]></wp:author_first_name><wp:author_last_name><![CDATA[]]></wp:author_last_name>'."\n";
            $output .= '<!-- this user additional specifications: -->'."\n";
            $output .= '<!-- language: ' . $user['language'] . ' -->'."\n";
            $output .= '<!-- image: ' . $user['image'] . ' -->'."\n";
            $output .= '<!-- level: ' . $userlvl[$user['userlevel']+1]. ' -->'."\n";
            $output .= '</wp:author>'."\n";
            self::$itemcnt++;
        }
        return $output;
    }

    private static function outputWXR_Visitors()
    {
        global $PIVOTX;
        $output = '';
        $visid = 0 + self::$addtovis;  // visitor does not have its own uid in PivotX
        // code taken from module_userreg.php
        $visitors = array();
        $dusers = dir( $PIVOTX['paths']['db_path'].'users/');
        while(false !== ($entry = $dusers->read())) {
            $file = $PIVOTX['paths']['db_path'].'users/' . $entry;
            if (is_file($file) && (getExtension($file) == "php") && 
                    ($visitor = loadSerialize($file,true))) {
                $visitors[urlencode($visitor['name'])] = $visitor;
            }
        }
        ksort($visitors);
        foreach($visitors as $visitor) {
            //echo 'visitor: ' . '<br/>';
            //echo print_r($visitor) . '<br/>';
            // salt / pass / notify_entries / notify_default / show_address not used
            if ($visitor['verified'] == -1 || $visitor['disabled'] == 1) {
                $output .= '<!-- Warning! Visitor skipped: '.$visitor['name'].' -->'."\n";
                self::$warncnt++;
            } else {
                $visid++;
                self::recordId(0, $visid);
                $output .= '<wp:author>'."\n";
                $output .= '<wp:author_id>'.$visid.'</wp:author_id><wp:author_login>'.self::sanitizeUserName($visitor['name']).'</wp:author_login><wp:author_display_name><![CDATA['.htmlspecialchars($visitor['name']).']]></wp:author_display_name><wp:author_email>'.$visitor['email'].'</wp:author_email><wp:author_first_name><![CDATA[]]></wp:author_first_name><wp:author_last_name><![CDATA[]]></wp:author_last_name>'."\n";
                $output .= '<!-- this visitor additional specifications: -->'."\n";
                $output .= '<!-- last login: ' . $visitor['last_login'] . ' -->'."\n";
                $output .= '<!-- url: ' . $visitor['url'] . ' -->'."\n";
                $output .= '</wp:author>'."\n";
                self::$itemcnt++;
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
                // extrafields have no own publ.date; to be sure that imported item will be published use yesterday
                $efdate = date('Y-m-d H:i:s', strtotime(' - 1 day'));  
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
        // This entire routine is meant only for WP plugin Envira lite!   WPeyec
        $output = '';
        $activeext = $PIVOTX['extensions']->getActivated();
        $galleries = self::getGalleries();
        $gallcnt = count($galleries);

        if ($gallcnt == 0) {
            $output = '<!-- Warning! There are no galleries found -->'."\n";
            self::$warncnt++;
        } else {
            $record['post_id'] = 0;
            $record['post_parent'] = '0';
            if (!in_array('envira', self::$gallselect)) {  // code is only meant for WP plugin envira lite
                $output = '<!-- Warning! "envira" not selected in parm "gallselect"! -->'."\n";
                self::$warncnt++;
                return $output;
            }
            foreach ($galleries as $gallery) {
                self::recordId(0, $gallery['gall_id']);
                $output .= '<item>'."\n";
                $gallery['title'] = $gallery['gall_name'] . ' for ' . $gallery['content_uid_title'];
                $gallery['post_name'] = $gallery['gall_name'] . '_' . $gallery['content_type'] . '_' . $gallery['content_uid'];
                $gallmeta = self::buildGallMeta($gallery);
                $output .= self::outputMap(array(
                    'title' => $gallery['title'],
                    'link' => '0',
                    'pubDate' => array('date_2822', $gallery['content_uid_date']),
                    'dc:creator' => array('cdata' , 'pivx_galleries'),
                    'guid isPermaLink="false"' => '0',
                    'wp:post_id' => $gallery['gall_id'],
                    'wp:post_date' => array('date', $gallery['content_uid_date']),
                    'wp:post_date_gmt' => array('date_gmt', $gallery['content_uid_date']),
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
                'pubDate' => self::$chapdate,
                'dc:creator' => array('cdata' , 'pivx_chapter'),
                'guid isPermaLink="true"' => '0',
                'description' => $record['description'],
                'excerpt:encoded' => array('cdata', ''),
                'content:encoded' => array('cdata', 'Chapter: ' . $record['chaptername']),    // @@CHANGE
                'wp:post_id' => $record['post_id'],
                'wp:post_date' => self::$chapdate,
                'wp:post_date_gmt' => self::$chapdate,
                'wp:comment_status' => 'closed',
                'wp:ping_status' => 'closed',
                'wp:status' => $record['status'],
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
        $record = $upload; // is $uplinfo
        $output = '';
        $output .= '<item>'."\n";
        $upldate = date('Y-m-d H:i:s');
        // replace year and month depending on destfolder
        $upldate = substr_replace($upldate,substr($record['destfolder'],0,4),0,4);
        $upldate = substr_replace($upldate,substr($record['destfolder'],5,2),5,2);
        $record['post_id'] = $record['uid'];
        $output .= '<!-- Item for upload will have id ' . $record['post_id'] . ' -->'."\n";
        $output .= '<!-- Its file size is: ' . $record['filesize'] . ' bytes -->'."\n";
        $record['post_parent'] = '0';
        self::recordId(0, $record['post_id']);
        self::recordSize($record['filesize']);
        // todo: decide whether this meta is really needed as it looks like it is generated when importing
        $attmeta = "\n" . self::outputMap(array(
                'wp:meta_key' => '_wp_attached_file',
                'wp:meta_value' => array('cdata', $record['destfolder'] . '/' . $record['filename_new']),
            ));
        $output .= self::outputMap(array(
                'title' => $record['basename'],
                'link' => '0',
                'pubDate' => $upldate,
                'dc:creator' => array('cdata' , 'pivx_upload'),
                'guid isPermaLink="false"' => 'uploads/' . $record['destfolder'] . '/' . $record['filename_new'],
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
                'wp:attachment_url' => array('html', $record['inputloc'] . str_replace(' ', '%20', $record['filename'])), // only spaces have to be replaced by %20
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
            'title' => 'PivotX export in wxr format for ' . $exporttype,
            'link' => $PIVOTX['paths']['canonical_host'].$PIVOTX['paths']['site_url'],
            'description' => '',
            'pubDate' => '',
            'generator' => 'PivotX/WXR-Export',
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
        $minsize = self::$size_min;
        $maxsize = self::$size_max;
        $extraline = '';
        if ($exporttype == 'entries' || $exporttype == 'entries and their comments' || $exporttype == 'pages') {
            $extraline .= "\n" . '<!-- Replace [imgpath] and [urlhome] if needed (see docs) -->';
        }
        if ($exporttype == 'uploads') {
            $extraline .= "\n" . '<!-- File sizes - minimum: ' . $minsize . ' maximum: ' . $maxsize . ' bytes -->';
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
        if ($maxid_new > self::$addtocat && $minid_new < self::$addtocat) {
            $extraline .= "\n" . '<!-- Warning! This export overlaps the id range for addtocat! -->';
            $warncnt++;
        }
        if ($maxid_new > self::$addtouser && $minid_new < self::$addtouser) {
            $extraline .= "\n" . '<!-- Warning! This export overlaps the id range for addtouser! -->';
            $warncnt++;
        }
        if ($maxid_new > self::$addtovis && $minid_new < self::$addtovis) {
            $extraline .= "\n" . '<!-- Warning! This export overlaps the id range for addtovis! -->';
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
        if (isset($item['new_uid'])) {
            $item['post_id'] = $item['new_uid'];
        }
        $item['post_name'] = $item['uri'];

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
        $item['post_name'] = $item['uri'];

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

    private static function outputWXR_Items($data, $comments, $callback)
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
                $record = self::replaceIt($record, '[[include', '[[*include');
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
            // extension bonusforms active? (change the template tag to inactivate because of parsing errors -- leave the rest of the parms)
            if (in_array('bonusforms',$activeext)) {
                $record = self::replaceIt($record, '[[ bonusform ', '[[ldelim]] noexport_bonusform ');
                $record = self::replaceIt($record, '[[bonusform ', '[[ldelim]] noexport_bonusform ');
            }

//@@CHANGE REPLACE STRINGS HERE -- end

            $excerpt_encoded = ''; 

            if ($parse != 'no') {
                $content_encoded_i = parse_intro_or_body($record['introduction'], false, $record['convert_lb']);
                $content_encoded_b = parse_intro_or_body($record['body'], false, $record['convert_lb']);
            } else {
                $content_encoded_i = $record['introduction'];
                $content_encoded_b = $record['body'];
            }

            $content_encoded_i = rawurldecode(html_entity_decode($content_encoded_i, ENT_QUOTES, "UTF-8"));
            // replace CR LF (they can come in with included files) 
            $content_encoded_i = preg_replace( "/\r|\n/", " ", $content_encoded_i );
            $repldebug = 'item processing intro: ' . $record['uid'] . '|' . $record['title'];
            $content_encoded_i = self::contentReplParts($content_encoded_i, $parse, $repldebug);

            $content_encoded_b = rawurldecode(html_entity_decode($content_encoded_b, ENT_QUOTES, "UTF-8"));
            // replace CR LF (they can come in with included files) 
            $content_encoded_b = preg_replace( "/\r|\n/", " ", $content_encoded_b );
            $repldebug = 'item processing body: ' . $record['uid'] . '|' . $record['title'];
            $content_encoded_b = self::contentReplParts($content_encoded_b, $parse, $repldebug);

            // add a space in between for close p and start p tag
            $content_encoded = $content_encoded_i . ' ' . $content_encoded_b;
            // get the word count for the introduction
            $introwcnt = self::getIntroWcnt($content_encoded_i, $record['uid']);

            $image = '';
            $password = '';
            $passprot = '0';
            $categories      = array();
            if (isset($record['category'])) {
                $categories = $record['category'];
            }
            $extrafmeta = '';
            $extrafcnt  = 0;
            // introduction word count length
            $extrafmeta .= self::processEFExtra('intro_wordcount', $record['pivx_type'], $EXTRAFIELDS, $introwcnt, $extrafcnt);
            $extrafcnt   = $extrafcnt + 1;
            // process extrafields
            if ($record['extrafields'] != '') {
                foreach($record['extrafields'] as $extrakey=>$extrafield) {
                    if (in_array($extrakey,self::$efskip)) {
                        continue;
                    }
                    // the "normal" image fields
                    if ($extrakey == 'image' || $extrakey == 'afbeelding') {
                        // not sure whether special characters (like &) are processed correctly / WP doesn't use this tag
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
                                // add code for the gallery or galleries (intention is then that importer selects manually what they like best)
                                if (count(self::$gallselect) > 1) {
                                    $content_encoded .= '<!-- Warning! Select the gallery code you want to use; see documentation for more details. -->';
                                    self::$warncnt++;
                                }
                                $galllines = self::gallLines($extrafield, false);
                                $gallids   = array();
                                $galltitle = array();
                                $swgtitle  = 'N';
                                $gallalt   = array();
                                $swgalt    = 'N';
                                $galldata  = array();
                                $swgdata   = 'N';
                                foreach ($galllines as $gallline) {
                                    // no urlhome here because it cannot be replaced properly (length on serialized array field should also change
                                    // leaving the short code there does not work in gallery plug in
                                    if (in_array(strval($gallline['upl_uid']), $gallids)) {
                                        self::$warncnt++;
                                        $new_uid = max($gallids) + 1;
                                        $content_encoded .= '<br/><!-- Warning! Id ' . strval($gallline['upl_uid']) . ' already used in this gallery! Changed it to '. $new_uid .' so something could be displayed after import. -->';
                                        $gallline['upl_uid'] = $new_uid;
                                    }
                                    array_push($gallids, $gallline['upl_uid']);
                                    array_push($galltitle, self::replaceQuotes($gallline['title']));
                                    if ($gallline['title'] != '') { $swgtitle = 'Y'; }
                                    array_push($gallalt, self::replaceQuotes($gallline['alt']));
                                    if ($gallline['alt'] != '') { $swgalt = 'Y'; }
                                    if ($gallline['data'] != '') {
                                        // @@CHANGE Interpretation of data attribute
                                        // ennn or pnnn = entry uid or page uid
                                        if (substr($gallline['data'],0,1) == 'e' && is_numeric(substr($gallline['data'],1))) {
                                            $gallline['data'] = '?p=' . (substr($gallline['data'],1) + self::$addtoentry);
                                        } else {
                                            if (substr($gallline['data'],0,1) == 'p' && is_numeric(substr($gallline['data'],1))) {
                                                $gallline['data'] = '?page_id=' . (substr($gallline['data'],1) + self::$addtopage);
                                            }
                                        }
                                        array_push($galldata, self::replaceQuotes($gallline['data']));
                                        $swgdata = 'Y'; 
                                    } else {
                                        array_push($galldata, '?attachment_id=' . $gallline['upl_uid']);
                                    }
                                }
                                $gallidsstring = implode(',',$gallids);
                                $gallparms = '';
                                $gallmlalnk = '';
                                $gallmlaimg = '';
                                $gallmlahrf = '';
                                if ($swgdata == 'Y') {
                                    $gallparms  .= ' mla_fixed_data="';
                                    $gallparms  .= "array('" . implode("','",$galldata) . "')" . '"';
                                    $gallmlahrf .= ' mla_link_href="{+site_url+}/{+mla_fixed_data+}"';
                                }
                                if ($swgtitle == 'Y') {
                                    $gallparms .= ' mla_fixed_title="';
                                    $gallparms .= "array('" . implode("','",$galltitle) . "')" . '"';
                                    $gallmlalnk .= ' mla_link_attributes="title=' . "'" . '{+mla_fixed_title+}' . "'" . '"';
                                }
                                if ($swgalt == 'Y') {
                                    $gallparms .= ' mla_fixed_alt="';
                                    $gallparms .= "array('" . implode("','",$gallalt) . "')" . '"';
                                    $gallmlaimg .= ' mla_image_alt="' . "'" . '{+mla_fixed_alt+}' . "'" . '"';
                                }
                                if (in_array('default', self::$gallselect)) {
                                    // plain gallery code with not supported parms to show the set values
                                    $content_encoded .= '<br/>[gallery ids="' . $gallidsstring . '" link=file' . str_replace('mla_', 'nosupp_', $gallparms) . ']';
                                }
                                if (in_array('mla', self::$gallselect)) {
                                    // mla gallery code with parms meant for fixed values add on
                                    $content_encoded .= '<br/>[mla_gallery ids="' . $gallidsstring . '" link=file' . $gallparms . $gallmlahrf . $gallmlalnk . $gallmlaimg . ']';
                                }
                                if (in_array('envira', self::$gallselect)) {
                                    // envira lite gallery
                                    $content_encoded .= '<br/>[envira-gallery id="' . $gallkey . '"]';
                                }
                                if (count(self::$gallselect) == 0) {
                                    $content_encoded .= '<!-- Warning! A gallery was skipped here. -->';
                                    self::$warncnt++;
                                }
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
                'dc:creator' => array('cdata', self::sanitizeUserName($record['user'])),
                '#1' => self::outputWXR_ItemCategories($categories),
                '#2' => self::outputWXR_ItemTags($record['keywords']),
                'guid isPermaLink="true"' => $record['link'],
                'description' => '',
                'image' => $image,
                'excerpt:encoded' => array('cdata', $excerpt_encoded),
                'content:encoded' => array('cdata', $content_encoded),
                'wp:post_id' => $record['post_id'],
                'wp:post_name' => $record['post_name'],
                'wp:post_date' => array('date', $record['publish_date']),
                'wp:post_date_gmt' => array('date_gmt', $record['publish_date']),
                'wp:comment_status' => (isset($record['allow_comments']) && $record['allow_comments']) ? 'open' : 'closed',
                'wp:ping_status' => 'closed',
                'wp:status' => $recstatus,
                'wp:post_parent' => $record['post_parent'],
                'wp:menu_order' => isset($record['sortorder']) ? $record['sortorder'] : '',
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
        $output  = '';
        $output .= self::outputWXR_Header('categories');
        $output .= self::outputWXR_Categories();
        $output .= self::outputWXR_Footer('categories');

        return $output;
    }

    public static function exportUsers()
    {
        $output  = '';
        $output .= self::outputWXR_Header('users');
        $output .= self::outputWXR_Users();
        $output .= self::outputWXR_Footer('users');

        return $output;
    }

    public static function exportVisitors()
    {
        $output  = '';
        $output .= self::outputWXR_Header('visitors');
        $output .= self::outputWXR_Visitors();
        $output .= self::outputWXR_Footer('visitors');

        return $output;
    }

    public static function exportUploads()
    {
        global $PIVOTX;
        global $UPLFILES;

        $output  = '';
        $output .= self::outputWXR_Header('uploads');

        foreach ($UPLFILES as $uplindex=>$uplfile) {
            $uplinfo = self::createUplinfo($uplfile, $uplindex + self::$addtoupl);
            $uplinfo['index'] = $uplindex;
            $uplsize = isset( $_GET['size'] ) ? $_GET['size'] : 'all';
            // check file size
            if ($uplsize == 'small') {
                if ($uplinfo['filesize'] > self::$upload_small) { 
                    continue; 
                }
            } elseif ($uplsize == 'medium') {
                if ($uplinfo['filesize'] > self::$upload_medium || $uplinfo['filesize'] <= self::$upload_small) { 
                    continue; 
                }
            } elseif ($uplsize == 'big') {
                if ($uplinfo['filesize'] > self::$upload_big) { 
                    $output .= '<!-- Warning! File encountered that is larger than value set by upload_big -->'."\n";
                    $output .= '<!-- Item: ' . $uplinfo['inputloc'] . $uplinfo['filename'] . ' -->'."\n";
                    $output .= '<!-- Size: ' . $uplinfo['filesize'] . ' bytes -->'."\n";
                    $output .= '<!-- New id should be: ' . $uplinfo['index'] . ' -->'."\n";
                    self::$warncnt++;
                    continue; 
                }
                if ($uplinfo['filesize'] <= self::$upload_medium) { 
                    continue; 
                }
            }
            // skip specific files
            if (in_array($uplinfo['filename'], self::$upload_toskip)) { 
                $output .= '<!-- Warning! Upload skipped! ' . $uplinfo['inputfolder'] . $uplinfo['filename'] . ' -->' . "\n";
                self::$warncnt++;
                continue; 
            }
            // skip specific extensions
            if (in_array($uplinfo['fileext'], self::$upload_toskipext)) {
                $output .= '<!-- Warning! Upload extension skipped! ' . $uplinfo['inputfolder'] . $uplinfo['filename'] . ' -->' . "\n";
                self::$warncnt++;
                continue; 
            }

            $upldupl = self::searchUploadByPostname($UPLFILES, $uplinfo['postname'], $uplinfo['index'] - 1, 0);
            // duplicate file name found?
            if (isset($upldupl['index']) && $uplinfo['index'] != $upldupl['index']) {
                //echo ($uplinfo['uid'] . ' duplicate of ' . $upldupl['uid'] . '<br/>');
                // postname has to be unique always
                $uplinfo['postname'] .= '_dupl.of_' . $upldupl['uid'];
                $output .= '<!-- Warning! Upload had a duplicate postname! ' . $uplinfo['inputfolder'] . $uplinfo['filename'] . ' -->' . "\n";
                $output .= '<!--          Other file name info: ' . $upldupl['inputfolder'] . $upldupl['filename'] . ' -->' . "\n";
                self::$warncnt++;
            }
            $uplduplloc = self::searchUploadByDestination($UPLFILES, ($uplinfo['destfolder'] . '/' . $uplinfo['filename_new']), $uplinfo['index'] - 1, 0);
            // duplicate location found?
            if (isset($uplduplloc['index']) && $uplinfo['index'] != $uplduplloc['index']) {
                // title has to be unique within same location
                $uplinfo['basename'] .= '_dupl.of_' . $upldupl['uid'];
                $uplinfo['basename_new'] .= '_dupl.of_' . $upldupl['uid'];
                $output .= '<!-- Warning! Upload had a duplicate title! ' . $uplinfo['inputfolder'] . $uplinfo['filename'] . ' -->' . "\n";
                $output .= '<!--          Other file name info: ' . $upldupl['inputfolder'] . $upldupl['filename'] . ' -->' . "\n";
                self::$warncnt++;
            }
            $output .= self::outputWXR_Uploads($uplinfo);
        }

        $output .= self::outputWXR_Footer('uploads');
        return $output;
    }

    public static function exportExtrafields()
    {
        $output  = '';
        $output .= self::outputWXR_Header('extrafields');
        $output .= self::outputWXR_Extrafields();
        $output .= self::outputWXR_Footer('extrafields');

        return $output;
    }

    public static function exportGalleries()
    {
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
                              'sortorder' => $chapter['sortorder'],
                              'status' => 'publish');
            if (array_key_exists('chapters', self::$pagesel)) {
                if (!in_array($chapinfo['chaptername'], self::$pagesel['chapters'])) {
                    continue;
                }
            }
            // check for empty chapter
            if (count($chapter['pages']) == 0) {
                $chapinfo['status'] = 'pending';
            } else {
                // check for chapter with only hold pages
                $chkcount = 0;
                foreach($chapter['pages'] as $chkpage) {
                    if ($chkpage['status'] == 'hold') {
                        $chkcount = $chkcount + 1;
                    }
                }
                if (count($chapter['pages']) == $chkcount) {
                    $chapinfo['status'] = 'pending';
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
                              'sortorder' => $chapter['sortorder'],
                              'status' => 'publish');
            if (array_key_exists('chapters', self::$pagesel)) {
                if (!in_array($chapinfo['chaptername'], self::$pagesel['chapters'])) {
                    continue;
                }
            }
            // check for empty chapter
            if (count($chapter['pages']) == 0) {
                $chapinfo['status'] = 'pending';
            } else {
                // check for chapter with only hold pages
                $chkcount = 0;
                foreach($chapter['pages'] as $chkpage) {
                    if ($chkpage['status'] == 'hold') {
                        $chkcount = $chkcount + 1;
                    }
                }
                if (count($chapter['pages']) == $chkcount) {
                    $chapinfo['status'] = 'pending';
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
            if (in_array($extrafield['fieldkey'],self::$efskip)) {
                continue;
            }
            if ($extrafield['contenttype'] == $efsel) {
                $efselcnt = $efselcnt + 1;
                // remove leading break (sometimes there to get description below field
                $extrafield['description'] = ltrim($extrafield['description'], '<br/>');
                $extrafield['description'] = ltrim($extrafield['description'], '<br />');
                $extrafield['description'] = ltrim($extrafield['description'], '<br>');
                // replace CR LF from description (they block the import)
                $extrafield['description'] = preg_replace( "/\r|\n/", " ", $extrafield['description'] );
                // todo: strip other html from description (like <em> <b> <i>)

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
                    $efselcnt = $efselcnt - 1;
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
            'wp:meta_value' => array('cdata', serialize(array(
                'param'=>'post_type', 
                'operator' => '==', 
                'value' => $wpsel, 
                'order_no' => 0, 
                'group_no' => 0
                ))
            ),
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
        if ($extrafieldkey == 'skip') {
            return;
        }
        // SEO special?
        $extra_array = array('seodescription','seotitle','seokeywords');
        if (in_array($extrakey, $extra_array) & in_array('aioseop',self::$seoselect)) {
            $efmeta .= self::buildSEOMeta($extrakey, $extrafield, $extrafcnt);
            if ($extrafieldkey == 'nope') {
                return $efmeta;
            }
        }
        if ($extrafieldkey == 'nope') {
            $efmeta .= '<!-- Warning! Extrafields key not found! ' . $extrakey . '. Value: ' . $extrafield . ' Extension inactive? Check code to find fields. -->' . "\n" ;
            self::$warncnt++;
        } else {
            $extrafieldtype = self::getEFType($extrakey, $pivx_type, $extrafields);
            if ($extrafieldtype == 'gallery') {
                $efmeta .= '<!-- Warning! Extrafields gallery skipped! ' . $extrakey . ' -->' . "\n" ;
                self::$warncnt++;
            } else {
                if ($extrafcnt > 0) {
                    $efmeta .= '</wp:postmeta>' . "\n" . '<wp:postmeta>';
                }
                $extrafcnt   = $extrafcnt + 1;
                if ($extrafieldtype == 'checkbox' || $extrafieldtype == 'checkbox_multiple') {
                    if ($extrafield == 'on') {
                        $extrafielddata = self::getEFData($extrakey, $pivx_type, $extrafields, true);
                        $extrafield = serialize(array($extrafielddata));
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
        $efkeycnt = 0; $efkeywxr = 'nope';
        if (in_array($efkey,self::$efskip)) {
            return 'skip';
        }
        foreach($extrafields as $extrafield) {
            $efkeycnt = $efkeycnt + 1;
            if ($extrafield['contenttype'] == $efctype && $extrafield['fieldkey'] == $efkey) {
                // construct key
                $effill = '';
                if ($efkeycnt < 100) { $effill = '000'; }
                if ($efkeycnt < 10) { $effill = '0000'; }
                // part random key
                $efkeywxr = 'field_5467c15f' . $effill . $efkeycnt;                   
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
        $extrafields = self::addToEF($extrafields, $extadd);
        $extadd['contenttype'] = 'page';
        $extrafields = self::addToEF($extrafields, $extadd);
        // add intro_wordcount
        $extadd['name'] = 'Intro word count';
        $extadd['fieldkey'] = 'intro_wordcount';
        $extadd['contenttype'] = 'entry';
        $extrafields = self::addToEF($extrafields, $extadd);
        $extadd['contenttype'] = 'page';
        $extrafields = self::addToEF($extrafields, $extadd);
        // extension seo active?
        if (in_array('seo',$activeext) & in_array('extrafield',self::$seoselect)) {
            $extadd['name'] = 'SEO description';
            $extadd['fieldkey'] = 'seodescription';
            $extadd['contenttype'] = 'entry';
            $extrafields = self::addToEF($extrafields, $extadd);
            $extadd['contenttype'] = 'page';
            $extrafields = self::addToEF($extrafields, $extadd);
            $extadd['name'] = 'SEO keywords';
            $extadd['fieldkey'] = 'seokeywords';
            $extadd['contenttype'] = 'entry';
            $extrafields = self::addToEF($extrafields, $extadd);
            $extadd['contenttype'] = 'page';
            $extrafields = self::addToEF($extrafields, $extadd);
            $extadd['name'] = 'SEO title';
            $extadd['fieldkey'] = 'seotitle';
            $extadd['contenttype'] = 'entry';
            $extrafields = self::addToEF($extrafields, $extadd);
            $extadd['contenttype'] = 'page';
            $extrafields = self::addToEF($extrafields, $extadd);
        }
        // extension starrating active?
        if (in_array('starrating',$activeext)) {
            $extadd['name'] = 'Ratings';
            $extadd['fieldkey'] = 'ratings';
            $extadd['contenttype'] = 'entry';
            $extrafields = self::addToEF($extrafields, $extadd);
            $extadd['contenttype'] = 'page';
            $extrafields = self::addToEF($extrafields, $extadd);
            $extadd['name'] = 'Rating average';
            $extadd['fieldkey'] = 'ratingaverage';
            $extadd['contenttype'] = 'entry';
            $extrafields = self::addToEF($extrafields, $extadd);
            $extadd['contenttype'] = 'page';
            $extrafields = self::addToEF($extrafields, $extadd);
            $extadd['name'] = 'Rating count';
            $extadd['fieldkey'] = 'ratingcount';
            $extadd['contenttype'] = 'entry';
            $extrafields = self::addToEF($extrafields, $extadd);
            $extadd['contenttype'] = 'page';
            $extrafields = self::addToEF($extrafields, $extadd);
        }
        // extension depublish active?
        if (in_array('depublish',$activeext)) {
            $extadd['name'] = 'Depublish on';
            $extadd['fieldkey'] = 'date_depublish';
            $extadd['type'] = 'date';
            $extadd['contenttype'] = 'entry';
            $extrafields = self::addToEF($extrafields, $extadd);
            $extadd['contenttype'] = 'page';
            $extrafields = self::addToEF($extrafields, $extadd);
            $extadd['type'] = 'input_text';  /* back to default */
        }
        // extension hitcounter active?
        if (in_array('hitcounter',$activeext)) {
            $extadd['name'] = 'Hit count last week';
            $extadd['fieldkey'] = 'last_week';
            $extadd['type'] = 'number';
            $extadd['contenttype'] = 'entry';
            $extrafields = self::addToEF($extrafields, $extadd);
            $extadd['contenttype'] = 'page';
            $extrafields = self::addToEF($extrafields, $extadd);
            $extadd['name'] = 'Hit count last month';
            $extadd['fieldkey'] = 'last_month';
            $extadd['contenttype'] = 'entry';
            $extrafields = self::addToEF($extrafields, $extadd);
            $extadd['contenttype'] = 'page';
            $extrafields = self::addToEF($extrafields, $extadd);
            $extadd['type'] = 'input_text';  /* back to default */
        }
//echo print_r($extrafields) . '<br/>';
        return $extrafields;
    }

    public static function addToEF($extrafields, $extadd) {
        if (!in_array($extadd['fieldkey'],self::$efskip)) {
            array_push($extrafields, $extadd);
        }
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

    private static function getCatKey($catname) {
        global $PIVOTX;
        $catkeywxr = 0;
        $catid = 0 + self::$addtocat;  // category does not have its own uid
        foreach($PIVOTX['categories']->data as $cat) {
            $catid++;
            if ($cat['name'] == $catname) {
                $catkeywxr = $catid;
                break;
            }
        }
        return $catkeywxr;
    }

    public static function getGalleries() {
        global $PIVOTX;
        global $EXTRAFIELDS;
        $galleries = array();
        $gallcnt = 0 + self::$addtogall;
        $entries = $PIVOTX['db']->read_entries(self::$entrysel);
        foreach($entries as $entry) {
            if (!is_array($entry['extrafields'])) {
                continue;
            } 
            foreach($entry['extrafields'] as $extrakey=>$extrafield) {
                if (in_array($extrakey,self::$efskip)) {
                    continue;
                }
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
                    $gallarr['content_uid_date'] = $entry['publish_date'];
                    $galleries[] = $gallarr;
                }
            }
        }
        $chapters = $PIVOTX['pages']->getIndex();
        foreach($chapters as $chapter) {
            foreach($chapter['pages'] as $page) {
                $page = $PIVOTX['pages']->getPage($page['uid']);
                if (!is_array($page['extrafields'])) {
                    continue;
                } 
                foreach($page['extrafields'] as $extrakey=>$extrafield) {
                    if (in_array($extrakey,self::$efskip)) {
                        continue;
                    }
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
                        $gallarr['content_uid_date'] = $page['publish_date'];
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

    private static function getGallery($gallkey) {
        global $GALLERIES;
        $gallrslt = array();
        foreach($GALLERIES as $gallery) {
            if ($gallery['gall_id'] == $gallkey) {
                $gallrslt = $gallery;
                break;
            }
        }
        return $gallrslt;
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

        $gallery['galllines'] = self::gallLines($gallery['gall_value'], true);

        $gallurl = self::$dest_base . '/wp-content/uploads/';
        foreach ($gallery['galllines'] as $gallline) {
            if (in_array(strval($gallline['upl_uid']), $gallids)) {
                self::$warncnt++;
                $new_uid = max($gallids) + 1;
                $gallmeta .= '<!-- Warning! Id ' . strval($gallline['upl_uid']) . ' already used in this gallery! Changed it to '. $new_uid .' so something could be displayed after import. -->';
                $gallline['upl_uid'] = $new_uid;
            }
            array_push($gallids, strval($gallline['upl_uid']));
            $gallidsdatasrc['src'] = $gallurl . $gallline['upl_destfolder'] . '/' . $gallline['upl_filename'];
            $gallidsdatasrc['link'] = $gallidsdatasrc['src'];
            // @@CHANGE Interpretation of data attribute
            // no urlhome here because it cannot be replaced properly (length on serialized array field should also change
            // leaving the short code there does not work in gallery plug in
            if ($gallline['data'] != '') {
                // ennn or pnnn = entry uid or page uid
                if (substr($gallline['data'],0,1) == 'e' && is_numeric(substr($gallline['data'],1))) {
                    $gallidsdatasrc['link'] = '?p=' . (substr($gallline['data'],1) + self::$addtoentry);
                }
                if (substr($gallline['data'],0,1) == 'p' && is_numeric(substr($gallline['data'],1))) {
                    $gallidsdatasrc['link'] = '?page_id=' . (substr($gallline['data'],1) + self::$addtopage);
                }
            }
            $gallidsdatasrc['title'] = $gallline['title'];
            $gallidsdatasrc['alt'] = $gallline['alt'];
            $gallidsdata['gallery'][$gallline['upl_uid']] = $gallidsdatasrc;
        }
        // _eg_ meta is meant for envira gallery WP plug-in
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

    private static function gallLines($gall_value, $gall_warn) {
        global $UPLFILES;
        $gallvalues = preg_split('|[\r\n]+|',trim($gall_value));
        $galllines  = array();
        foreach ($gallvalues as $gallline) {
            $gallparts = explode('###',trim($gallline));
            $gallimg['data']  = '';
            $gallimg['alt']   = '';
            $gallimg['title'] = '';
            $gallimg['image'] = '';
            switch (count($gallparts)) {
            case 4:
                $gallimg['data']  = trim($gallparts[3]);
            case 3:
                $gallimg['alt']   = htmlentities(trim($gallparts[2]), ENT_COMPAT, 'UTF-8');
            case 2:
                $gallimg['title'] = htmlentities(trim($gallparts[1]), ENT_COMPAT, 'UTF-8');
            case 1:
                $gallimg['image'] = trim($gallparts[0]);
                break;
            }
            $uplinfo = self::searchUploadByFilename($UPLFILES, $gallimg['image']);
            if (isset($uplinfo['index'])) {
                $gallimg['upl_uid'] = $uplinfo['uid'];
                $gallimg['upl_destfolder'] = $uplinfo['destfolder'];
                $gallimg['upl_filename'] = $uplinfo['filename_new'];
            } else {
                $gallimg['upl_uid'] = '0';
                $gallimg['upl_destfolder'] = 'notknown';
                $gallimg['upl_filename'] = 'warning_notfound_' . $gallimg['image'];
                if ($gall_warn) {
                    self::$warncnt++;
                }
            }
            array_push($galllines, $gallimg);
        }
        return $galllines;
    }

    private static function buildSEOMeta($extrakey, $extrafield, $extrafcnt) {
        $seometa = '';
        $seometakey = '';
        if ($extrakey == 'seokeywords') {
            $seometakey = '_aioseop_keywords';
        }
        if ($extrakey == 'seotitle') {
            $seometakey = '_aioseop_title';
        }
        if ($extrakey == 'seodescription') {
            $seometakey = '_aioseop_description';
        }
        if ($seometakey != '') {
            if ($extrafcnt > 0) {
                $seometa .= '</wp:postmeta>';
            }
            $seometa .= "\n" . '<wp:postmeta>' . "\n" . self::outputMap(array(
            'wp:meta_key' => $seometakey,
            'wp:meta_value' => array('cdata', $extrafield),
            ));
        }
        return $seometa;
    }

    private static function replaceIt($record, $replthis, $replby) {
        $record['introduction'] = str_replace($replthis, $replby, $record['introduction']);
        $record['body']         = str_replace($replthis, $replby, $record['body']);
        return $record;
    }

    private static function replaceQuotes($text) {
        $text = str_replace("'", "&#39;", $text);
        $text = str_replace('"', "&quot;", $text);
        return $text;
    }

    private static function recordId($uid_org, $uid_new) {
        if ($uid_org < self::$id_min_org) { self::$id_min_org = $uid_org; }
        if ($uid_org > self::$id_max_org) { self::$id_max_org = $uid_org; }
        if ($uid_new < self::$id_min_new) { self::$id_min_new = $uid_new; }
        if ($uid_new > self::$id_max_new) { self::$id_max_new = $uid_new; }
        return;
    }

    private static function recordSize($size) {
        if ($size < self::$size_min) { self::$size_min = $size; }
        if ($size > self::$size_max) { self::$size_max = $size; }
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
        // replace relative img src code
        $content = self::contentReplImgRelative($content, 'src=', 'B', $repldebug);
        // replace upload locations by something general (to be used as a shortcode)
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
                        $srcrepl = $uplinfo['destfolder'] . '/' .  $uplinfo['basename_new'] . $srcinbetw . '.' . $uplinfo['fileext'];
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
        $myhost = $PIVOTX['paths']['canonical_host'];
        foreach (self::$upload_input as $upload_inp) {
            if (substr($upload_inp,0,6) == '#ROOT#') {
                $upload_inp = str_replace('#ROOT#', $PIVOTX['paths']['canonical_host'], $upload_inp);
            }
            //echo 'repl ' . $replpfx . '|' . $replbetw . '|' . $PIVOTX['paths']['site_url'] . $upload_inp . '|' . $repldebug . '<br/>';
            $content = str_replace($replpfx . $replbetw . $myhost . $PIVOTX['paths']['site_url'] . $upload_inp, $replpfx . $replby, $content);
            $content = str_replace($replpfx . $replbetw . $myhost . '/' . $upload_inp, $replpfx . $replby, $content);
            // aliases array
            foreach (self::$aliases as $alias) {
                $content = str_replace($replpfx . $replbetw . $alias . $PIVOTX['paths']['site_url'] . $upload_inp, $replpfx . $replby, $content);
                $content = str_replace($replpfx . $replbetw . $alias . '/' . $upload_inp, $replpfx . $replby, $content);
            }
            $content = str_replace($replpfx . $replbetw . $PIVOTX['paths']['site_url'] . $upload_inp, $replpfx . $replby, $content);
            $content = str_replace($replpfx . $replbetw . $upload_inp, $replpfx . $replby, $content);
            $content = str_replace($replpfx . $replbetw . '/' . $upload_inp, $replpfx . $replby, $content);
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

    private static function contentReplImgRelative($content, $replpfx, $quotetype, $repldebug) {
        global $PIVOTX;
        // quote type processing? B = both / D = only double quote / S = only single quote
        if ($quotetype == 'B') {
            $content = self::contentReplImgRelative($content, $replpfx, 'D', $repldebug);
            $content = self::contentReplImgRelative($content, $replpfx, 'S', $repldebug);
            return $content;
        }
        if ($quotetype == 'S') {
            $replpfx .= $replend = "'";
        } else {
            $replpfx .= $replend = '"';
        }
        // search for relative references (../../) -- cwd is the same as for the web site display
        // is there something relative?
        $content2 = str_replace($replpfx . '../', '==wxrexport==', $content, $replcnt);
        if ($replcnt != 0) {
            $reldepth = 5;
            //echo 'relative found: ' . $repldebug . '<br/>';
            // how many levels are there? use pivotx_url for that (could be build more efficient as arrays get filled every time)
            $lvlparts = explode('/',$PIVOTX['paths']['pivotx_url']);
            $lvlvalid = count($lvlparts) - 2;
            if ($lvlvalid < 0) { $lvlvalid = 0; }
            $replthis = array(); $replby = array(); $replstr = $replpfx;
            for ($i = 1; $i <= $reldepth; $i++) {
                $replstr .= '../';
                $replthis[$i] = $replstr;
                if ($i < $lvlvalid) {
                    for ($j = 1; $j <= $i; $j++) {
                        $replby[$i] .= $replpfx . $PIVOTX['paths']['canonical_host'] . '/' . $lvlparts[$j] . '/'; 
                    }
                } else {
                    $replby[$i] = $replpfx . $PIVOTX['paths']['canonical_host'] . '/';
                }
            }
            for ($i = $reldepth; $i > 0; $i--) {
                $content = str_replace($replthis[$i], $replby[$i], $content, $replcnt);
            }
            // something left?
            $content2 = str_replace($replby[$reldepth].'../', '==wxrexport==', $content, $replcnt);
            if ($replcnt != 0) {
                self::$warncnt++;
                $content .= '<br/><!-- Warning! This content still contains relative src references! Raising var reldepth could help. -->';
            }
        }
        return $content;
    }

    private static function contentReplLink($content, $repldebug) {
        global $PIVOTX;
        // replace the href pointer if needed
        $findthis = 'href=';
        $posbeg = 0; $findlen = strlen($findthis);
        $loopprotect = -1;
        $myhost = $PIVOTX['paths']['canonical_host'];
        while ($posbeg !== false && $posbeg != $loopprotect) {
            $loopprotect = $posbeg;
            $posbeg = strpos($content, $findthis, $posbeg);
            if ($posbeg !== false) {
                $findpos1 = substr($content, $posbeg+$findlen, 1);
                if ($findpos1 == '"' || $findpos1 == "'") {   // real href?
                    $posend = strpos($content, $findpos1, $posbeg+$findlen+1);
                    if ($posend !== false) {
                        $findsearch = strtolower($findorg = substr($content, $posbeg+$findlen+1, $posend-($posbeg+$findlen+1)));
                        // replace other links to myurl by canonical host value
                        if (substr($findsearch,0,strlen($PIVOTX['paths']['site_path'])) == $PIVOTX['paths']['site_path']) {
                            $findsearch = substr_replace($findsearch, $myhost, 0, strlen($PIVOTX['paths']['site_path']));
                        }
                        if (substr($findsearch,0,strlen($PIVOTX['paths']['site_url'])) == $PIVOTX['paths']['site_url']) {
                            $findsearch = substr_replace($findsearch, $myhost, 0, strlen($PIVOTX['paths']['site_url']));
                        }
                        // peculiar form but does work 
                        if (substr($findsearch,0,strlen('../pivotx/index.php')) == '../pivotx/index.php') {
                            $findsearch = substr_replace($findsearch, $myhost, 0, strlen('../pivotx/index.php'));
                        }
                        // aliases array
                        //echo 'checking href: ' . $findsearch . '<br/>';
                        //echo 'host: ' . $myhost . '<br/>';
                        foreach (self::$aliases as $alias) {
                            if (substr($findsearch,0,strlen($alias)) == $alias) {
                                $findsearch = substr_replace($findsearch, $myhost, 0, strlen($alias));
                                //break;  // do not use break here!?
                            }
                        }
                        // plain index.php
                        if (substr($findsearch,0,strlen($myhost . '/index.php')) == $myhost . '/index.php') {
                            $findsearch = substr_replace($findsearch, $myhost, 0, strlen($myhost . '/index.php'));
                        }
                        // skip the ones that are (already) OK
                        if (substr($findsearch,0,9) == '[imgpath]') { // do nothing (img link)
                        } elseif (substr($findsearch,0,1) == '#') { // do nothing (only hash found)
                        //} elseif (substr($findsearch,0,3) == '../') { // do nothing (cannot be an internal link) -- apparently it can be?  todo check it
                        } elseif (substr($findsearch,0,11) == 'javascript:') { // do nothing (js call)
                        } elseif (substr($findsearch,0,1) == '"') { // do nothing (potential js var?)
                        } elseif (substr($findsearch,0,1) == "'") { // do nothing (potential js var?)
                        } elseif (substr($findsearch,0,7) == 'http://' && substr($findsearch,0,strlen($myhost)) != $myhost) { // do nothing 
                        } elseif (substr($findsearch,0,8) == 'https://' && substr($findsearch,0,strlen($myhost)) != $myhost) { // do nothing
                        } elseif (substr($findsearch,0,7) == "mailto:") { // do nothing 
                        } else {
                            // findsearch contains potential internal link
                            //echo $repldebug . '|' . $findsearch . '|' . $findorg . '<br/>';
                            // strip full url parts (it could also be only a home link)
                            if (substr($findsearch,0,strlen($myhost.$PIVOTX['paths']['site_url'])) == $myhost.$PIVOTX['paths']['site_url']) {
                                $findsearch = substr($findsearch, strlen($myhost.$PIVOTX['paths']['site_url']));
                            }
                            if (substr($findsearch,0,strlen($myhost.$PIVOTX['paths']['site_url']-1)) == substr($myhost.$PIVOTX['paths']['site_url'],0,-1)) {
                                $findsearch = substr($findsearch, strlen($myhost.$PIVOTX['paths']['site_url']-1));
                            }
                            if (substr($findsearch,0,strlen($myhost)) == $myhost) {
                                $findsearch = substr($findsearch, strlen($myhost));
                            }
                            $findpure = explode('#',$findsearch);
                            $findsearch = $findpure[0];
                            unset($findpure[0]);
                            $findhash = implode('#',$findpure);
                            if ($findhash != '') {
                                $findhash = '#' . $findhash;
                            }
                            $findlinktype = ''; $findlinkvalue = '';
                            if (substr($findsearch,0,1) == '/') {  // sometimes a double slash after host?
                                $findsearch = substr($findsearch,1);
                            }
                            if (substr($findsearch,0,1) == '?') {
                                $findparts = explode('&',substr($findsearch,1));
                                foreach ($findparts as $findpart) {
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
                                    if (substr($findpart,0,2) == 'c=') {
                                        $findlinktype = 'category';
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
                                //echo 'link: |' . $findsearch . '|<br/>';
                                $findparts = explode('/',$findsearch);
                                foreach ($findparts as $findkey=>$findpart) {
                                    //echo 'lpart: ' . $findkey . '|' . $findpart . '<br/>';
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
                                    // old pivot structure? (is still working)
                                    if (substr($findpart,0,13) == 'entry.php?id=') {
                                        $findlinktype = 'entry';
                                        $findlinkvalue = substr($findpart,13);
                                        break;
                                    }
                                }
                                if ($findlinktype == '' && ($findsearch != '' && $findsearch != '/')) {
                                    $findlinktype = 'entrypage';
                                    $findlinkvalue = $findparts[0];
                                }
                            }
                            // can contain query part
                            $findpure = explode('?',$findlinkvalue);
                            $findlinkvalue = $findpure[0];
                            unset($findpure[0]);
                            $findquery = implode('?',$findpure);
                            if ($findquery != '') {
                                $findquery = '&' . $findquery;
                            }
                            $findadd = $findquery . $findhash;
                            //echo 'linktype: ' . $findlinktype . '|' . $findlinkvalue . '|' . $findadd . '|' . $findsearch . '|<br/>';
                            $linkentry = array(); $linkpage = array();
                            if ($findlinktype == 'entry' || $findlinktype == 'entrypage') {
                                $linkentry = $PIVOTX['db']->read_entry($findlinkvalue);
                            }
                            if ($findlinktype == 'page' || $findlinktype == 'entrypage') {
                                $linkpage = $PIVOTX['pages']->getPageByUri($findlinkvalue);
                            }
                            $unsupported_types = array('visitor','weblog');
                            if ($findlinktype == 'entrypage' && $linkentry['uid'] != '' && $linkpage['uid'] != '') {
                                $content = substr_replace($content, 'warning_link_found_for_both_entry_and_page_', $posbeg+$findlen+1, 0);
                                //echo 'entry + page link? ' . $findsearch . '|<br/>';
                                self::$warncnt++;
                            } elseif (($findlinktype == 'entrypage' || $findlinktype == 'entry' || $findlinktype == 'page') &&
                                        ($linkentry['uid'] == '' && $linkpage['uid'] == '')) {
                                $content = substr_replace($content, 'warning_uid_not_found_for_this_entry_or_page_', $posbeg+$findlen+1, 0);
                                //echo $repldebug . '<br/>';
                                //echo 'uid not found ' . $findsearch . '|' . $findlinktype . '|' . $findlinkvalue . '|<br/>';
                                self::$warncnt++;
                            } elseif ($findlinktype == 'entry' || ($findlinktype == 'entrypage' && $linkentry['uid'] != '')) {
                                $relid = $linkentry['uid'] + self::$addtoentry;
                                $content = substr_replace($content, '[urlhome]/?p=' . $relid . $findadd, $posbeg+$findlen+1, strlen($findorg));
                            } elseif ($findlinktype == 'page' || ($findlinktype == 'entrypage' && $linkpage['uid'] != '')) {
                                $relid = $linkpage['uid'] + self::$addtopage;
                                $content = substr_replace($content, '[urlhome]/?page_id=' . $relid . $findadd, $posbeg+$findlen+1, strlen($findorg));
                            } elseif ($findlinktype == 'tag') {
                                $content = substr_replace($content, '[urlhome]/?tag=' . $findlinkvalue . $findadd, $posbeg+$findlen+1, strlen($findorg));
                            } elseif ($findlinktype == 'category') {
                                $catid = self::getCatKey($findlinkvalue);
                                if ($catid == 0) {
                                    $content = substr_replace($content, 'warning_cat_not_found_', $posbeg+$findlen+1, 0);
                                    self::$warncnt++;
                                } else {
                                    $content = substr_replace($content, '[urlhome]/?cat=' . $catid . $findadd, $posbeg+$findlen+1, strlen($findorg));
                                }
                            } elseif ($findlinktype == 'archive') {
                                $archyear = substr($findlinkvalue,0,4);
                                $archtype = substr($findlinkvalue,4,2);
                                $archmonth = substr($findlinkvalue,6,2);
                                if ($archtype != '-y' && $archtype != '-m') {
                                    $content = substr_replace($content, 'warning_this_archive_type_not_supported_', $posbeg+$findlen+1, 0);
                                    self::$warncnt++;
                                } else {
                                    if ($archtype == '-y') {
                                        $archmonth = '';
                                    }
                                    $content = substr_replace($content, '[urlhome]/?m=' . $archyear . $archmonth . $findadd, $posbeg+$findlen+1, strlen($findorg));
                                }
                            } elseif (in_array($findlinktype, $unsupported_types)) {
                                $content = substr_replace($content, 'warning_linktype_'.$findlinktype.'_unsupported_', $posbeg+$findlen+1, 0);
                                self::$warncnt++;
                            } else {
                                if ($findsearch != '' && $findsearch != '/') {
                                    $content = substr_replace($content, 'warning_linktype_not_found_for_', $posbeg+$findlen+1, 0);
                                    if ($findlinktype == '') { 
                                        //echo 'href not recognised: ' . $findsearch . '|<br/>';
                                        $content = substr_replace($content, 'nolinktype_', $posbeg+$findlen+1, 0);
                                    }
                                    self::$warncnt++;
                                } else {
                                    $content = substr_replace($content, '[urlhome]' . $findadd, $posbeg+$findlen+1, strlen($findorg));
                                }
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
        if ($posbeg == $loopprotect && $loopprotect != 0) {
            //echo 'loopprotect!' . $loopprotect . '|' . $repldebug . '<br/>';
            self::$warncnt++;
            $content .= '<br/><!-- Warning! Something went wrong while replacing internal links! -->';
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
        // space in between so words are not considered as one
        $content = str_replace('</p><p', '</p> <p', $content);
        return $content;
    }

    private static function contentWarn($content, $repldebug) {
        global $PIVOTX;

        // check/warn for remaining pivotx and other strings
        $contentorg = $content;
        $content2 = str_replace('/pivotx/', '==wxrexport==', $content, $replcnt);
        if ($replcnt != 0) {
            self::$warncnt++;
            $contentorg .= '<br/><!-- Warning! This content still contains ' . $replcnt . ' references to /pivotx/! -->';
        }
        $content2 = str_replace('class="pivotx', '==wxrexport==', $content, $replcnt);
        if ($replcnt != 0) {
            self::$warncnt++;
            $contentorg .= '<br/><!-- Warning! This content still contains ' . $replcnt . ' references to class=\"pivotx! (without the back slash) -->';
        }
        $content2 = str_replace($PIVOTX['paths']['host'], '==wxrexport==', $content, $replcnt);
        if ($replcnt != 0) {
            self::$warncnt++;
            $contentorg .= '<br/><!-- Warning! This content still contains ' . $replcnt . ' references to this host! -->';
        }
        if ($PIVOTX['paths']['host'] != $PIVOTX['paths']['canonical_host']) {
            $content2 = str_replace($PIVOTX['paths']['canonical_host'], '==wxrexport==', $content, $replcnt);
            if ($replcnt != 0) {
                self::$warncnt++;
                $contentorg .= '<br/><!-- Warning! This content still contains ' . $replcnt . ' references to this canonical host! -->';
            }
        }
        // CDATA in content?
        $content2 = str_replace('<![CDATA[','==wxrexport==',$content, $replcnt);
        if ($replcnt != 0) {
            self::$warncnt++;
            $contentorg = str_replace('<![CDATA[','@@@CDATA_BEG@@@',$contentorg);
            $contentorg .= '<br/><!-- Warning! This content contained ' . $replcnt . ' CDATA occurrences! These are replaced by "@@@CDATA_BEG@@@" -->';
        }
        // END tag in content? Potentially belonging to CDATA?
        $content2 = str_replace(']]','==wxrexport==',$content, $replcnt);
        if ($replcnt != 0) {
            self::$warncnt++;
            $contentorg = str_replace(']]','@@@CDATA_END?@@@',$contentorg);
            $contentorg .= '<br/><!-- Warning! This content contained ' . $replcnt . ' END TAG "]]" occurrences! These are replaced by "@@@CDATA_END?@@@" -->';
        }
        // aliases array
        foreach (self::$aliases as $alias) {
            if ($alias != $PIVOTX['paths']['host'] && $alias != $PIVOTX['paths']['canonical)host']) {
                $content2 = str_replace($alias, '==wxrexport==', $content, $replcnt);
                if ($replcnt != 0) {
                    self::$warncnt++;
                    $contentorg .= '<br/><!-- Warning! This content still contains ' . $replcnt . ' references to alias ' . $alias . '! -->';
                }
            }
        }
        $content2 = str_replace('Smarty error:', '==wxrexport==', $content, $replcnt);
        if ($replcnt != 0) {
            self::$warncnt++;
            $contentorg .= '<br/><!-- Warning! This content contains ' . $replcnt . ' smarty errors! -->';
        }
        $content2 = str_replace('Unrecognized template code:', '==wxrexport==', $content, $replcnt);
        if ($replcnt != 0) {
            self::$warncnt++;
            $contentorg .= '<br/><!-- Warning! This content contains ' . $replcnt . ' unrecognised template codes (spelled unrecognized) -->';
        }
        $content2 = str_replace('href="mailto:', '==wxrexport==', $content, $replcnt);
        $content2 = str_replace("href='mailto:", '==wxrexport==', $content, $replcnt2);
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
        $filesize = filesize($uplfile);
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
                    // put them into yearfolder depending on file date?
                    if (self::$upload_yearalways === true) {
                        //echo "$uplfilename was modified: " . date ("F d Y H:i:s.", filemtime($uplfile)) . '<br/>';
                        $yearfolder = date ("Y/m", filemtime($uplfile));
                    }
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
        if ($inpfolder == 'pivotx/extensions/sociable/images/') {
            $yearfolder = '2000/07';    //  @@CHANGE
        }
        if ($inpfolder == 'pivotx/extensions/nivoslider/slides/') {
            $yearfolder = '2000/08';    //  @@CHANGE
        }
        if ($inpfolder == 'pivotx/extensions/slidingpanel/icons/') {
            $yearfolder = '2000/09';    //  @@CHANGE
        }
        if ($inpfolder == 'pivotx/extensions/media/') {
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
        if (substr($inpfolder,0,12) == 'pivotx/pics/' || $inpfolder == 'pivotx/includes/emoticons/trillian/') {
            $yearfolder = '2000/12';    //  @@CHANGE
        }
        //echo $uplcounter . '|' . $uplfilename . '|' . $yearfolder . '|' . $inpurl . '|' . $inpfolder . '|' . $basefolder . '<br/>';
        // sanitize file name with code similar to found in WP formatting / WP converts extension to lower case when uploading
        $uplfilename_new = self::sanitizeFileName(removeExtension($uplfilename))  . '.' . strtolower(getExtension($uplfilename));
        $uplinfo = array('uid' => $uplcounter,
                         'destfolder' => $yearfolder,
                         'filename' => $uplfilename,
                         'filename_new' => $uplfilename_new,
                         'basefolder' => $basefolder,
                         'filesize' => $filesize,
                         'fileext' => strtolower($path_parts['extension']),
                         'basename' => removeExtension($uplfilename),
                         'basename_new' => removeExtension($uplfilename_new),
                         'postname' => strtolower(removeExtension($uplfilename_new)),
                         'rootloc' => $rootloc,
                         'inputfolder' => $inpfolder,
                         'inputloc' => $inpurl . $inpfolder);
        return $uplinfo;
    }

    private static function searchUploadByPostname($uplfiles, $postname, $start, $end) {
        if (!isset($start)) { $start = 0; }
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

    private static function searchUploadByDestination($uplfiles, $destination, $start, $end) {
        if (!isset($start)) { $start = 0; }
        if (!isset($end)) { $end = (count($uplfiles) - 1); }
        $uplsrch = array();
        if ($start < $end) {
            //echo ('search up for ' . $destination . ' start-end: ' . $start . '-' . $end . '<br/>');
            for ($i = $start; $i <= $end; $i++) {
                $uplsrch = self::createUplinfo($uplfiles[$i], $i + self::$addtoupl);
                if ($destination == $uplsrch['destfolder'] . '/' . $uplsrch['filename_new']) {
                    $uplsrch['index'] = $i;
                    //echo ('found it!' . $uplsrch['index'] . '<br/>');
                    return $uplsrch;
                }
            }
        } else {
            //echo ('search down for ' . $destination . ' start-end: ' . $start . '-' . $end . '<br/>');
            for ($i = $start; $i >= $end; $i--) {
                $uplsrch = self::createUplinfo($uplfiles[$i], $i + self::$addtoupl);
                if ($destination == $uplsrch['destfolder'] . '/' . $uplsrch['filename_new']) {
                    $uplsrch['index'] = $i;
                    //echo ('found it!' . $uplsrch['index'] . '<br/>');
                    return $uplsrch;
                }
            }
        }
        return $uplsrch;
    }

    private static function searchUploadByFilename($uplfiles, $filename, $start, $end) {
        if (!isset($start)) { $start = 0; }
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

    private static function getIntroWcnt($contentforintrocnt, $uid) {
        // $uid is added so you can debug specific uids
        $introwcnt = 0;

        // strip tags
        $contentforintrocnt = self::wp_strip_all_tags( trim($contentforintrocnt) );

        // replace &nbsp; is UTF-8 "\xc2\xa0"  (commented out for the moment)
        //$contentforintrocnt = str_replace("\xc2\xa0", " ", $contentforintrocnt);

        // remove accents? (only works properly when it is in UTF-8) (commented out for the moment)
        //$contentforintrocnt = self::remove_accents($contentforintrocnt);
        
        //$introwcnt = str_word_count($contentforintrocnt);
        // instead of use str_word_count use the method used by WP to count the words (WPeyec)
        $words_array = preg_split( "/[\n\r\t ]+/", $contentforintrocnt, 0, PREG_SPLIT_NO_EMPTY );
        $introwcnt = count ( $words_array );

        /*
        WPeyec: the word count used in WP is done either by a js for the editor side or by excerpt functions and the wp_trim_words function.
                I am sorry to say that these 3 approaches give different results. So the word count done here has been coded to result
                as close as possible for the excerpt functions assuming that these will be used the most together with this word count value.
                WP removes in its own js all punctuation and accents before counting the words but in the php functions this does not happen. 
                preg_replace string used by WP word-count.js is /[0-9.(),;:!?%#$¿'"_+=\\/-]+/g
                The WP php functions can be found in formatting.php file.

        If other problems arise with this count perhaps removing double spaces could help as well:
        $patterns = array("/\s+/", "/\s([?.!])/");
        $replacer = array(" ","$1");
        preg_replace( $patterns, $replacer, $contentforintrocnt );
        */
        return $introwcnt;
    }


    /**
    * Strip punctuation from utf-8 text. (Source: http://nadeausoftware.com/articles/2007/9/php_tip_how_strip_punctuation_characters_web_page)
    * (This function is not used but left here to possibly cope in future with wrong word counts)
    */
    private static function strip_punctuation( $text ) {
    $urlbrackets    = '\[\]\(\)';
    $urlspacebefore = ':;\'_\*%@&?!' . $urlbrackets;
    $urlspaceafter  = '\.,:;\'\-_\*@&\/\\\\\?!#' . $urlbrackets;
    $urlall         = '\.,:;\'\-_\*%@&\/\\\\\?!#' . $urlbrackets;
 
    $specialquotes  = '\'"\*<>';
 
    $fullstop       = '\x{002E}\x{FE52}\x{FF0E}';
    $comma          = '\x{002C}\x{FE50}\x{FF0C}';
    $arabsep        = '\x{066B}\x{066C}';
    $numseparators  = $fullstop . $comma . $arabsep;
 
    $numbersign     = '\x{0023}\x{FE5F}\x{FF03}';
    $percent        = '\x{066A}\x{0025}\x{066A}\x{FE6A}\x{FF05}\x{2030}\x{2031}';
    $prime          = '\x{2032}\x{2033}\x{2034}\x{2057}';
    $nummodifiers   = $numbersign . $percent . $prime;
 
    return preg_replace(
        array(
        // Remove separator, control, formatting, surrogate,
        // open/close quotes.
            '/[\p{Z}\p{Cc}\p{Cf}\p{Cs}\p{Pi}\p{Pf}]/u',
        // Remove other punctuation except special cases
            '/\p{Po}(?<![' . $specialquotes .
                $numseparators . $urlall . $nummodifiers . '])/u',
        // Remove non-URL open/close brackets, except URL brackets.
            '/[\p{Ps}\p{Pe}](?<![' . $urlbrackets . '])/u',
        // Remove special quotes, dashes, connectors, number
        // separators, and URL characters followed by a space
            '/[' . $specialquotes . $numseparators . $urlspaceafter .
                '\p{Pd}\p{Pc}]+((?= )|$)/u',
        // Remove special quotes, connectors, and URL characters
        // preceded by a space
            '/((?<= )|^)[' . $specialquotes . $urlspacebefore . '\p{Pc}]+/u',
        // Remove dashes preceded by a space, but not followed by a number
            '/((?<= )|^)\p{Pd}+(?![\p{N}\p{Sc}])/u',
        // Remove consecutive spaces
            '/ +/',
        ),
        ' ',
        $text );
    }

    private static function sanitizeFileName($filename) {
        // code similar to WP formatting.php  (WPeyec)
        $special_chars = array("?", "[", "]", "/", "\\", "=", "<", ">", ":", ";", ",", "'", "\"", "&", "$", "#", "*", "(", ")", "|", "~", "`", "!", "{", "}", chr(0));
        $filename = preg_replace( "#\x{00a0}#siu", ' ', $filename );
        $filename = str_replace( $special_chars, '', $filename );
        $filename = str_replace( array( '%20', '+' ), '-', $filename );
        $filename = preg_replace( '/[\r\n\t -]+/', '-', $filename );
        $filename = trim( $filename, '.-_' );
        return $filename;
    }

    private static function sanitizeUserName( $username, $strict = false ) {
        // code similar to WP formatting.php (WPeyec)
        $raw_username = $username;
        $username = self::wp_strip_all_tags( $username );
        $username = self::remove_accents( $username );
        // Kill octets
        $username = preg_replace( '|%([a-fA-F0-9][a-fA-F0-9])|', '', $username );
        $username = preg_replace( '/&.+?;/', '', $username ); // Kill entities

        // If strict, reduce to ASCII for max portability.
        if ( $strict )
            $username = preg_replace( '|[^a-z0-9 _.\-@]|i', '', $username );

        $username = trim( $username );
        // Consolidate contiguous whitespace
        $username = preg_replace( '|\s+|', ' ', $username );
        return $username;
    }
    private static function wp_strip_all_tags($string, $remove_breaks = false) {
        // code similar to WP formatting.php (WPeyec)
        $string = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $string );
        $string = strip_tags($string);

        if ( $remove_breaks )
            $string = preg_replace('/[\r\n\t ]+/', ' ', $string);

        return trim( $string );
    }
    private static function remove_accents($string) {
        // code similar to WP formatting.php (WPeyec)
        if ( !preg_match('/[\x80-\xff]/', $string) ) {
            //echo 'leave!<br/>';
            return $string;
        }
        if (self::seems_utf8($string)) {
            //echo 'seems utf8<br/>';
            $chars = array(
            // Decompositions for Latin-1 Supplement
            chr(194).chr(170) => 'a', chr(194).chr(186) => 'o',
            chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
            chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
            chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
            chr(195).chr(134) => 'AE',chr(195).chr(135) => 'C',
            chr(195).chr(136) => 'E', chr(195).chr(137) => 'E',
            chr(195).chr(138) => 'E', chr(195).chr(139) => 'E',
            chr(195).chr(140) => 'I', chr(195).chr(141) => 'I',
            chr(195).chr(142) => 'I', chr(195).chr(143) => 'I',
            chr(195).chr(144) => 'D', chr(195).chr(145) => 'N',
            chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
            chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
            chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
            chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
            chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
            chr(195).chr(158) => 'TH',chr(195).chr(159) => 's',
            chr(195).chr(160) => 'a', chr(195).chr(161) => 'a',
            chr(195).chr(162) => 'a', chr(195).chr(163) => 'a',
            chr(195).chr(164) => 'a', chr(195).chr(165) => 'a',
            chr(195).chr(166) => 'ae',chr(195).chr(167) => 'c',
            chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
            chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
            chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
            chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
            chr(195).chr(176) => 'd', chr(195).chr(177) => 'n',
            chr(195).chr(178) => 'o', chr(195).chr(179) => 'o',
            chr(195).chr(180) => 'o', chr(195).chr(181) => 'o',
            chr(195).chr(182) => 'o', chr(195).chr(184) => 'o',
            chr(195).chr(185) => 'u', chr(195).chr(186) => 'u',
            chr(195).chr(187) => 'u', chr(195).chr(188) => 'u',
            chr(195).chr(189) => 'y', chr(195).chr(190) => 'th',
            chr(195).chr(191) => 'y', chr(195).chr(152) => 'O',
            // Decompositions for Latin Extended-A
            chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
            chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
            chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
            chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
            chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
            chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
            chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
            chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
            chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
            chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
            chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
            chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
            chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
            chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
            chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
            chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
            chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
            chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
            chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
            chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
            chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
            chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
            chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
            chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
            chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
            chr(196).chr(178) => 'IJ',chr(196).chr(179) => 'ij',
            chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
            chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
            chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
            chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
            chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
            chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
            chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
            chr(197).chr(130) => 'l', chr(197).chr(131) => 'N',
            chr(197).chr(132) => 'n', chr(197).chr(133) => 'N',
            chr(197).chr(134) => 'n', chr(197).chr(135) => 'N',
            chr(197).chr(136) => 'n', chr(197).chr(137) => 'N',
            chr(197).chr(138) => 'n', chr(197).chr(139) => 'N',
            chr(197).chr(140) => 'O', chr(197).chr(141) => 'o',
            chr(197).chr(142) => 'O', chr(197).chr(143) => 'o',
            chr(197).chr(144) => 'O', chr(197).chr(145) => 'o',
            chr(197).chr(146) => 'OE',chr(197).chr(147) => 'oe',
            chr(197).chr(148) => 'R',chr(197).chr(149) => 'r',
            chr(197).chr(150) => 'R',chr(197).chr(151) => 'r',
            chr(197).chr(152) => 'R',chr(197).chr(153) => 'r',
            chr(197).chr(154) => 'S',chr(197).chr(155) => 's',
            chr(197).chr(156) => 'S',chr(197).chr(157) => 's',
            chr(197).chr(158) => 'S',chr(197).chr(159) => 's',
            chr(197).chr(160) => 'S', chr(197).chr(161) => 's',
            chr(197).chr(162) => 'T', chr(197).chr(163) => 't',
            chr(197).chr(164) => 'T', chr(197).chr(165) => 't',
            chr(197).chr(166) => 'T', chr(197).chr(167) => 't',
            chr(197).chr(168) => 'U', chr(197).chr(169) => 'u',
            chr(197).chr(170) => 'U', chr(197).chr(171) => 'u',
            chr(197).chr(172) => 'U', chr(197).chr(173) => 'u',
            chr(197).chr(174) => 'U', chr(197).chr(175) => 'u',
            chr(197).chr(176) => 'U', chr(197).chr(177) => 'u',
            chr(197).chr(178) => 'U', chr(197).chr(179) => 'u',
            chr(197).chr(180) => 'W', chr(197).chr(181) => 'w',
            chr(197).chr(182) => 'Y', chr(197).chr(183) => 'y',
            chr(197).chr(184) => 'Y', chr(197).chr(185) => 'Z',
            chr(197).chr(186) => 'z', chr(197).chr(187) => 'Z',
            chr(197).chr(188) => 'z', chr(197).chr(189) => 'Z',
            chr(197).chr(190) => 'z', chr(197).chr(191) => 's',
            // Decompositions for Latin Extended-B
            chr(200).chr(152) => 'S', chr(200).chr(153) => 's',
            chr(200).chr(154) => 'T', chr(200).chr(155) => 't',
            // Euro Sign
            chr(226).chr(130).chr(172) => 'E',
            // GBP (Pound) Sign
            chr(194).chr(163) => '',
            // Vowels with diacritic (Vietnamese)
            // unmarked
            chr(198).chr(160) => 'O', chr(198).chr(161) => 'o',
            chr(198).chr(175) => 'U', chr(198).chr(176) => 'u',
            // grave accent
            chr(225).chr(186).chr(166) => 'A', chr(225).chr(186).chr(167) => 'a',
            chr(225).chr(186).chr(176) => 'A', chr(225).chr(186).chr(177) => 'a',
            chr(225).chr(187).chr(128) => 'E', chr(225).chr(187).chr(129) => 'e',
            chr(225).chr(187).chr(146) => 'O', chr(225).chr(187).chr(147) => 'o',
            chr(225).chr(187).chr(156) => 'O', chr(225).chr(187).chr(157) => 'o',
            chr(225).chr(187).chr(170) => 'U', chr(225).chr(187).chr(171) => 'u',
            chr(225).chr(187).chr(178) => 'Y', chr(225).chr(187).chr(179) => 'y',
            // hook
            chr(225).chr(186).chr(162) => 'A', chr(225).chr(186).chr(163) => 'a',
            chr(225).chr(186).chr(168) => 'A', chr(225).chr(186).chr(169) => 'a',
            chr(225).chr(186).chr(178) => 'A', chr(225).chr(186).chr(179) => 'a',
            chr(225).chr(186).chr(186) => 'E', chr(225).chr(186).chr(187) => 'e',
            chr(225).chr(187).chr(130) => 'E', chr(225).chr(187).chr(131) => 'e',
            chr(225).chr(187).chr(136) => 'I', chr(225).chr(187).chr(137) => 'i',
            chr(225).chr(187).chr(142) => 'O', chr(225).chr(187).chr(143) => 'o',
            chr(225).chr(187).chr(148) => 'O', chr(225).chr(187).chr(149) => 'o',
            chr(225).chr(187).chr(158) => 'O', chr(225).chr(187).chr(159) => 'o',
            chr(225).chr(187).chr(166) => 'U', chr(225).chr(187).chr(167) => 'u',
            chr(225).chr(187).chr(172) => 'U', chr(225).chr(187).chr(173) => 'u',
            chr(225).chr(187).chr(182) => 'Y', chr(225).chr(187).chr(183) => 'y',
            // tilde
            chr(225).chr(186).chr(170) => 'A', chr(225).chr(186).chr(171) => 'a',
            chr(225).chr(186).chr(180) => 'A', chr(225).chr(186).chr(181) => 'a',
            chr(225).chr(186).chr(188) => 'E', chr(225).chr(186).chr(189) => 'e',
            chr(225).chr(187).chr(132) => 'E', chr(225).chr(187).chr(133) => 'e',
            chr(225).chr(187).chr(150) => 'O', chr(225).chr(187).chr(151) => 'o',
            chr(225).chr(187).chr(160) => 'O', chr(225).chr(187).chr(161) => 'o',
            chr(225).chr(187).chr(174) => 'U', chr(225).chr(187).chr(175) => 'u',
            chr(225).chr(187).chr(184) => 'Y', chr(225).chr(187).chr(185) => 'y',
            // acute accent
            chr(225).chr(186).chr(164) => 'A', chr(225).chr(186).chr(165) => 'a',
            chr(225).chr(186).chr(174) => 'A', chr(225).chr(186).chr(175) => 'a',
            chr(225).chr(186).chr(190) => 'E', chr(225).chr(186).chr(191) => 'e',
            chr(225).chr(187).chr(144) => 'O', chr(225).chr(187).chr(145) => 'o',
            chr(225).chr(187).chr(154) => 'O', chr(225).chr(187).chr(155) => 'o',
            chr(225).chr(187).chr(168) => 'U', chr(225).chr(187).chr(169) => 'u',
            // dot below
            chr(225).chr(186).chr(160) => 'A', chr(225).chr(186).chr(161) => 'a',
            chr(225).chr(186).chr(172) => 'A', chr(225).chr(186).chr(173) => 'a',
            chr(225).chr(186).chr(182) => 'A', chr(225).chr(186).chr(183) => 'a',
            chr(225).chr(186).chr(184) => 'E', chr(225).chr(186).chr(185) => 'e',
            chr(225).chr(187).chr(134) => 'E', chr(225).chr(187).chr(135) => 'e',
            chr(225).chr(187).chr(138) => 'I', chr(225).chr(187).chr(139) => 'i',
            chr(225).chr(187).chr(140) => 'O', chr(225).chr(187).chr(141) => 'o',
            chr(225).chr(187).chr(152) => 'O', chr(225).chr(187).chr(153) => 'o',
            chr(225).chr(187).chr(162) => 'O', chr(225).chr(187).chr(163) => 'o',
            chr(225).chr(187).chr(164) => 'U', chr(225).chr(187).chr(165) => 'u',
            chr(225).chr(187).chr(176) => 'U', chr(225).chr(187).chr(177) => 'u',
            chr(225).chr(187).chr(180) => 'Y', chr(225).chr(187).chr(181) => 'y',
            // Vowels with diacritic (Chinese, Hanyu Pinyin)
            chr(201).chr(145) => 'a',
            // macron
            chr(199).chr(149) => 'U', chr(199).chr(150) => 'u',
            // acute accent
            chr(199).chr(151) => 'U', chr(199).chr(152) => 'u',
            // caron
            chr(199).chr(141) => 'A', chr(199).chr(142) => 'a',
            chr(199).chr(143) => 'I', chr(199).chr(144) => 'i',
            chr(199).chr(145) => 'O', chr(199).chr(146) => 'o',
            chr(199).chr(147) => 'U', chr(199).chr(148) => 'u',
            chr(199).chr(153) => 'U', chr(199).chr(154) => 'u',
            // grave accent
            chr(199).chr(155) => 'U', chr(199).chr(156) => 'u',
            );

            // Used for locale-specific rules
            //$locale = get_locale();
            $locale = self::$user_locale;   // WPeyec - changed code

            if ( 'de_DE' == $locale ) {
                $chars[ chr(195).chr(132) ] = 'Ae';
                $chars[ chr(195).chr(164) ] = 'ae';
                $chars[ chr(195).chr(150) ] = 'Oe';
                $chars[ chr(195).chr(182) ] = 'oe';
                $chars[ chr(195).chr(156) ] = 'Ue';
                $chars[ chr(195).chr(188) ] = 'ue';
                $chars[ chr(195).chr(159) ] = 'ss';
            } elseif ( 'da_DK' === $locale ) {
                $chars[ chr(195).chr(134) ] = 'Ae';
                $chars[ chr(195).chr(166) ] = 'ae';
                $chars[ chr(195).chr(152) ] = 'Oe';
                $chars[ chr(195).chr(184) ] = 'oe';
                $chars[ chr(195).chr(133) ] = 'Aa';
                $chars[ chr(195).chr(165) ] = 'aa';
            }

            $string = strtr($string, $chars);
        } else {
            //echo 'assume iso-8859-1<br/>';
            $chars = array();
            // Assume ISO-8859-1 if not UTF-8
            $chars['in'] = chr(128).chr(131).chr(138).chr(142).chr(154).chr(158)
                .chr(159).chr(162).chr(165).chr(181).chr(192).chr(193).chr(194)
                .chr(195).chr(196).chr(197).chr(199).chr(200).chr(201).chr(202)
                .chr(203).chr(204).chr(205).chr(206).chr(207).chr(209).chr(210)
                .chr(211).chr(212).chr(213).chr(214).chr(216).chr(217).chr(218)
                .chr(219).chr(220).chr(221).chr(224).chr(225).chr(226).chr(227)
                .chr(228).chr(229).chr(231).chr(232).chr(233).chr(234).chr(235)
                .chr(236).chr(237).chr(238).chr(239).chr(241).chr(242).chr(243)
                .chr(244).chr(245).chr(246).chr(248).chr(249).chr(250).chr(251)
                .chr(252).chr(253).chr(255);

            $chars['out'] = "EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy";

            $string = strtr($string, $chars['in'], $chars['out']);
            $double_chars = array();
            $double_chars['in'] = array(chr(140), chr(156), chr(198), chr(208), chr(222), chr(223), chr(230), chr(240), chr(254));
            $double_chars['out'] = array('OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th');
            $string = str_replace($double_chars['in'], $double_chars['out'], $string);
        }
        return $string;
    }
    private static function seems_utf8($str) {
        // code similar to WP formatting.php (WPeyec)
        //mbstring_binary_safe_encoding();     // WPeyec commented out
        $length = strlen($str);
        //reset_mbstring_encoding();           // WPeyec commented out
        for ($i=0; $i < $length; $i++) {
            $c = ord($str[$i]);
            if ($c < 0x80) $n = 0; // 0bbbbbbb
            elseif (($c & 0xE0) == 0xC0) $n=1; // 110bbbbb
            elseif (($c & 0xF0) == 0xE0) $n=2; // 1110bbbb
            elseif (($c & 0xF8) == 0xF0) $n=3; // 11110bbb
            elseif (($c & 0xFC) == 0xF8) $n=4; // 111110bb
            elseif (($c & 0xFE) == 0xFC) $n=5; // 1111110b
            else return false; // Does not match any model
            for ($j=0; $j<$n; $j++) { // n bytes matching 10bbbbbb follow ?
                if ((++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80))
                    return false;
            }
        }
        return true;
    }

// end private functions
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
            case 'users':
                $filename = 'users.xml';
                $output   = pivotxWxrExport::exportUsers();
                break;
            case 'visitors':
                $filename = 'visitors.xml';
                $output   = pivotxWxrExport::exportVisitors();
                break;
            case 'uploads':
                $uplsize = isset( $_GET['size'] ) ? $_GET['size'] : 'all';
                $filename = 'uploads_'.$uplsize.'.xml';
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
