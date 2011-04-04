<?php
// - Extension: Sociable Bookmarking
// - Version: 0.4
// - Author: PivotX Team / Logfather / Michael Heca
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: A snippet extension to show links to various social bookmark sites.
// - Date: 2011-03-12
// - Identifier: sociable


global $sociable_config;

$sociable_config = array(
    'sociable_tagline' => __('Share this:'),
    'sociable_showlabels' => 0,
    'sociable_items' => '',
    'sociable_blank' => 0
);


global $sociable_sites;

// version 0.4: entries found in Sociable 3.5.2 added (except RSS)
// changed Digg/eKudos/Google to include subtitle
// made the array fill visually alphabetic so existance can be easily deciphered

// To post to a different category in Fark, see the drop-down box labeled "Link Type" at
// http://cgi.fark.com/cgi/fark/submit.pl for a complete list
$sociable_sites = array(

'Add to favorites' => array('favicon' => 'addtofavorites.png', 'url' => 'javascript:AddToFavorites();',),
'BarraPunto' => array('favicon' => 'barrapunto.png', 'url' => 'http://barrapunto.com/submit.pl?subj=%title%&amp;story=%link%',),
'Bitacoras.com' => array('favicon' => 'bitacoras.png', 'url' => 'http://bitacoras.com/anotaciones/%link%',),
'BlinkList' => array('favicon' => 'blinklist.png', 'url' => 'http://www.blinklist.com/index.php?Action=Blink/addblink.php&amp;Url=%link%&amp;Title=%title%',),
'Blogmarks' => array('favicon' => 'blogmarks.png', 'url' => 'http://blogmarks.net/my/new.php?mini=1&amp;simple=1&amp;url=%link%&amp;title=%title%',),
'Blogosphere News' => array('favicon' => 'blogospherenews.png', 'url' => 'http://www.blogospherenews.com/submit.php?url=%link%&amp;title=%title%',),
'Blogplay' => array('favicon' => 'blogplay.png', 'url' => 'http://blogplay.com', ),
'blogtercimlap' => array('favicon' => 'blogter.png', 'url' => 'http://cimlap.blogter.hu/index.php?action=suggest_link&amp;title=%title%&amp;url=%link%',),
'Connotea' => array('favicon' => 'connotea.png', 'url' => 'http://www.connotea.org/addpopup?continue=confirm&amp;uri=%link%&amp;title=%title%',),
'Current' => array('favicon' => 'current.png', 'url' => 'http://current.com/clipper.htm?url=%link%&amp;title=%title%',),
'Del.icio.us' => array('favicon' => 'delicious.png', 'url' => 'http://del.icio.us/post?url=%link%&amp;title=%title%',),
'Design Float' => array('favicon' => 'designfloat.png', 'url' => 'http://www.designfloat.com/submit.php?url=%link%&amp;title=%title%',),
'Digg' => array('favicon' => 'digg.png', 'url' => 'http://digg.com/submit?phase=2&amp;url=%link%&amp;title=%title%&amp;bodytext=%subtitle%',),
'Diggita' => array('favicon' => 'diggita.png', 'url' => 'http://www.diggita.it/submit.php?url=%link%&amp;title=%title%',),   
'Diigo' => array('favicon' => 'diigo.png', 'url' => 'http://www.diigo.com/post?url=%link%&amp;title=%title%',),
'DotNetKicks' => array('favicon' => 'dotnetkicks.png', 'url' => 'http://www.dotnetkicks.com/kick/?url=%link%&amp;title=%title%',),
// naming not sure because consisted out of asian characters
'Douban' => array('favicon' => 'douban.png', 'url' => 'http://www.douban.com/recommend/?url=%link%&amp;title=%title%',),
// naming not sure because consisted out of asian characters
'Douban9' => array('favicon' => 'douban9.png', 'url' => 'http://www.douban.com/recommend/?url=%link%&amp;title=%title%&amp;n=1',),
'DZone' => array('favicon' => 'dzone.png', 'url' => 'http://www.dzone.com/links/add.html?url=%link%&amp;title=%title%',),
'eKudos' => array('favicon' => 'ekudos.png', 'url' => 'http://www.ekudos.nl/artikel/nieuw?url=%link%&amp;title=%title%&amp;desc=%subtitle%',),
'email' => array('favicon' => 'email_link.png', 'url' => 'mailto:?subject=%title%&amp;body=%link%',),
'Facebook' => array('favicon' => 'facebook.png', 'url' => 'http://www.facebook.com/sharer.php?u=%link%&amp;t=%title%',),
'Fark' => array('favicon' => 'fark.png', 'url' => 'http://cgi.fark.com/cgi/fark/edit.pl?new_url=%link%&amp;new_comment=%title%&amp;linktype=Misc',),
'Faves' => array('favicon' => 'bluedot.png', 'url' => 'http://faves.com/Authoring.aspx?u=%link%&amp;title=%title%',),
'Fleck' => array('favicon' => 'fleck.gif', 'url' => 'http://extension.fleck.com/?v=b.0.804&amp;url=%link%',),
'FriendFeed' => array('favicon' => 'friendfeed.png', 'url' => 'http://www.friendfeed.com/share?title=%title%&amp;link=%link%',),
'FSDaily' => array('favicon' => 'fsdaily.png', 'url' => 'http://www.fsdaily.com/submit?url=%link%&amp;title=%title%',),
'Furl' => array('favicon' => 'furl.png', 'url' => 'http://www.furl.net/storeIt.jsp?u=%link%&amp;t=%title%',),
'Global Grind' => array ('favicon' => 'globalgrind.png', 'url' => 'http://globalgrind.com/submission/submit.aspx?url=%link%&amp;type=Article&amp;title=%title%',),
'Google' => array ('favicon' => 'googlebookmark.png', 'url' => 'http://www.google.com/bookmarks/mark?op=edit&amp;bkmk=%link%&amp;title=%title%&amp;annotation=%subtitle%',),
'Gwar' => array('favicon' => 'gwar.png', 'url' => 'http://www.gwar.pl/DodajGwar.html?u=%link%',),
'HackerNews' => array('favicon' => 'hackernews.png', 'url' => 'http://news.ycombinator.com/submitlink?u=%link%&amp;t=%title%',),
'Haohao' => array('favicon' => 'haohao.png', 'url' => 'http://www.haohaoreport.com/submit.php?url=%link%&amp;title=%title%',),
'HealthRanker' => array('favicon' => 'healthranker.png', 'url' => 'http://healthranker.com/submit.php?url=%link%&amp;title=%title%',),
'HelloTxt' => array('favicon' => 'hellotxt.png', 'url' => 'http://hellotxt.com/?status=%title%+%link%',),
'Hemidemi' => array('favicon' => 'hemidemi.png', 'url' => 'http://www.hemidemi.com/user_bookmark/new?title=%title%&amp;url=%link%',),
'Hyves' => array('favicon' => 'hyves.png', 'url' => 'http://www.hyves.nl/profilemanage/add/tips/?name=%title%&amp;text=%subtitle%+%link%&amp;rating=5',),
'Identi.ca' => array('favicon' => 'identica.png', 'url' => 'http://identi.ca/notice/new?status_textarea=%link%',),
'IndianPad' => array('favicon' => 'indianpad.png', 'url' => 'http://www.indianpad.com/submit.php?url=%link%',),
'Internetmedia' => array('favicon' => 'im.png', 'url' => 'http://internetmedia.hu/submit.php?url=%link%',),
'Kirtsy' => array('favicon' => 'kirtsy.png', 'url' => 'http://www.kirtsy.com/submit.php?url=%link%&amp;title=%title%',),
'laaik.it' => array('favicon' => 'laaikit.png', 'url' => 'http://laaik.it/NewStoryCompact.aspx?uri=%link%&amp;headline=%title%&amp;cat=5e082fcc-8a3b-47e2-acec-fdf64ff19d12',),
'LaTafanera' => array('favicon' => 'latafanera.png', 'url' => 'http://latafanera.cat/submit.php?url=%link%',),
'LinkaGoGo' => array('favicon' => 'linkagogo.png', 'url' => 'http://www.linkagogo.com/go/AddNoPopup?url=%link%&amp;title=%title%',),
'LinkArena' => array('favicon' => 'linkarena.png', 'url' => 'http://linkarena.com/bookmarks/addlink/?url=%link%&amp;title=%title%',),
'LinkedIn' => array('favicon' => 'linkedin.png', 'url' => 'http://www.linkedin.com/shareArticle?mini=true&amp;url=%link%&amp;title=%title%&amp;source=%sitename%&amp;summary=%subtitle%',),
'Linkter' => array('favicon' => 'linkter.png', 'url' => 'http://www.linkter.hu/index.php?action=suggest_link&amp;url=%link%&amp;title=%title%',),
'Linkuj.cz' => array('favicon' => 'linkuj-cz.gif', 'url' => 'http://linkuj.cz/?id=linkuj&amp;url=%link%&amp;title=%title%',),
'Live' => array('favicon' => 'live.png', 'url' => 'https://favorites.live.com/quickadd.aspx?marklet=1&amp;url=%link%&amp;title=%title%',),
'Ma.gnolia' => array('favicon' => 'magnolia.png', 'url' => 'http://ma.gnolia.com/bookmarklet/add?url=%link%&amp;title=%title%',),
'Meneame' => array('favicon' => 'meneame.png', 'url' => 'http://meneame.net/submit.php?url=%link%',),
'MisterWong' => array('favicon' => 'misterwong.png', 'url' => 'http://www.mister-wong.com/addurl/?bm_url=%link%&amp;bm_description=%title%&amp;plugin=soc',),
'MisterWong.DE' => array('favicon' => 'misterwong.png', 'url' => 'http://www.mister-wong.de/addurl/?bm_url=%link%&amp;bm_description=%title%&amp;plugin=soc',),
'Mixx' => array('favicon' => 'mixx.png', 'url' => 'http://www.mixx.com/submit?page_url=%link%&amp;title=%title%',),
'MOB' => array('favicon' => 'mob.png', 'url' => 'http://www.mob.com/share.php?u=%link%&amp;t=%title%',),
'MSNReporter' => array('favicon' => 'msnreporter.png', 'url' => 'http://reporter.nl.msn.com/?fn=contribute&amp;Title=%title%&amp;URL=%link%&amp;cat_id=6&amp;tag_id=31&amp;Remark=%subtitle%',),
'muti' => array('favicon' => 'muti.png', 'url' => 'http://www.muti.co.za/submit?url=%link%&amp;title=%title%',),
'MyShare' => array('favicon' => 'myshare.png', 'url' => 'http://myshare.url.com.tw/index.php?func=newurl&amp;url=%link%&amp;desc=%title%',),
'MySpace' => array('favicon' => 'myspace.png', 'url' => 'http://www.myspace.com/Modules/PostTo/Pages/?u=%link%&amp;t=%title%',),
'N4G' => array('favicon' => 'n4g.png', 'url' => 'http://www.n4g.com/tips.aspx?url=%link%&amp;title=%title%',),
'Netvibes' => array('favicon' => 'netvibes.png', 'url' =>'http://www.netvibes.com/share?title=%title%&amp;url=%link%',),
'Netvouz' => array('favicon' => 'netvouz.png', 'url' => 'http://www.netvouz.com/action/submitBookmark?url=%link%&amp;title=%title%&amp;popup=no',),
'NewsVine' => array('favicon' => 'newsvine.png', 'url' => 'http://www.newsvine.com/_tools/seed&amp;save?u=%link%&amp;h=%title%',),
'NuJIJ' => array('favicon' => 'nujij.gif', 'url' => 'http://nujij.nl/jij.lynkx?t=%title%&amp;u=%link%',),
'PDF' => array('favicon' => 'pdf.png', 'url' => 'http://www.printfriendly.com/print?url=%link%&amp;partner=sociable',),
'Ping.fm' => array('favicon' => 'ping.png', 'url' => 'http://ping.fm/ref/?link=%link%&amp;title=%title%&amp;body=%subtitle%',),
'Posterous' => array('favicon' => 'posterous.png', 'url' => 'http://posterous.com/share?linkto=%link%&amp;title=%title%&amp;selection=%subtitle%',),
'PrintFriendly' => array('favicon' => 'printfriendly.png', 'url' => 'http://www.printfriendly.com/print?url=%link%&amp;partner=sociable',),
'Propeller' => array('favicon' => 'propeller.gif', 'url' => 'http://www.propeller.com/submit/?U=%link%&amp;T=%title%',),
// naming not sure because consisted out of asian characters
'QQ' => array('favicon' => 'qq.png', 'url' => 'http://shuqian.qq.com/post?jumpback=1&amp;title=%title%&amp;uri=%link%',),    
'Ratimarks' => array('favicon' => 'ratimarks.png', 'url' => 'http://ratimarks.org/bookmarks.php/?action=add&amp;address=%link%&amp;title=%title%',),
'Rec6' => array('favicon' => 'rec6.png', 'url' => 'http://rec6.via6.com/link.php?url=%link%&amp;=%title%',),
'Reddit' => array('favicon' => 'reddit.png', 'url' => 'http://reddit.com/submit?url=%link%&amp;title=%title%',),
'Scoopeo' => array('favicon' => 'scoopeo.png', 'url' => 'http://www.scoopeo.com/scoop/new?newurl=%link%&amp;title=%title%',),
'Segnalo' => array('favicon' => 'segnalo.png', 'url' => 'http://segnalo.alice.it/post.html.php?url=%link%&amp;title=%title%',),
'SheToldMe' => array('favicon' => 'shetoldme.png', 'url' => 'http://shetoldme.com/publish?url=%link%&amp;title=%title%',),
'Simpy' => array('favicon' => 'simpy.png', 'url' => 'http://www.simpy.com/simpy/LinkAdd.do?href=%link%&amp;title=%title%',),
'Slashdot' => array('favicon' => 'slashdot.png', 'url' => 'http://slashdot.org/bookmark.pl?title=%title%&amp;url=%link%',),
'Socialogs' => array('favicon' => 'socialogs.png', 'url' => 'http://socialogs.com/add_story.php?story_url=%link%&amp;story_title=%title%',),
'SphereIt' => array('favicon' => 'sphere.png', 'url' => 'http://www.sphere.com/search?q=sphereit:%link%&amp;title=%title%',),
'Sphinn' => array('favicon' => 'sphinn.png', 'url' => 'http://sphinn.com/submit.php?url=%link%&amp;title=%title%',),
'StumbleUpon' => array('favicon' => 'stumbleupon.png', 'url' => 'http://www.stumbleupon.com/submit?url=%link%&amp;title=%title%',),
'TailRank' => array('favicon' => 'tailrank.png', 'url' => 'http://tailrank.com/share/?text=&amp;link_href=%link%&amp;title=%title%',),
'Techmeme' => array( 'favicon' => 'techmeme.png', 'url' => 'http://twitter.com/home/?status=tip%20@Techmeme%20%link%%20%title%',), 
'Technorati' => array('favicon' => 'technorati.png', 'url' => 'http://technorati.com/faves?add=%link%',),
'ThisNext' => array('favicon' => 'thisnext.png', 'url' => 'http://www.thisnext.com/pick/new/submit/sociable/?url=%link%&amp;name=%title%',),
'Tipd' => array('favicon' => 'tipd.png', 'url' => 'http://tipd.com/submit.php?url=%link%',),
'Tumblr' => array('favicon' => 'tumblr.png', 'url' => 'http://www.tumblr.com/share?v=3&amp;u=%link%&amp;t=%title%&amp;s=%subtitle%',),
'Twitter' => array('favicon' => 'twitter.png', 'url' => 'http://twitter.com/home?status=Must+check:+%title%+%link%',),
'Upnews' => array('favicon' => 'upnews.png', 'url' => 'http://www.upnews.it/submit?url=%link%&amp;title=%title%',),
'viadeo FR' => array('favicon' => 'viadeo.png', 'url' => 'http://www.viadeo.com/shareit/share/?url=%link%&amp;title=%title%&amp;urllanguage=fr',),
'vybrali.sme.sk' => array('favicon' => 'vybrali-sme-sk.gif', 'url' => 'http://vybrali.sme.sk/submit.php?url=%link%',),
'Webnews.de' => array('favicon' => 'webnews.png', 'url' => 'http://www.webnews.de/einstellen?url=%link%&amp;title=%title%',),
'Webride' => array('favicon' => 'webride.png', 'url' => 'http://webride.org/discuss/split.php?uri=%link%&amp;title=%title%',),
'Wikio' => array('favicon' => 'wikio.png', 'url' => 'http://www.wikio.com/vote?url=%link%',),
'Wikio FR' => array('favicon' => 'wikio.png', 'url' => 'http://www.wikio.fr/vote?url=%link%',),
'Wikio IT' => array('favicon' => 'wikio.png', 'url' => 'http://www.wikio.it/vote?url=%link%',),
'Wists' => array('favicon' => 'wists.png', 'url' => 'http://wists.com/s.php?c=&amp;r=%link%&amp;title=%title%',),
'Wykop' => array('favicon' => 'wykop.png', 'url' => 'http://www.wykop.pl/dodaj?url=%link%',),
'Xerpi' => array('favicon' => 'xerpi.png', 'url' => 'http://www.xerpi.com/block/add_link_from_extension?url=%link%&amp;title=%title%',),
'Yahoo! Bookmarks' => array('favicon' => 'yahoomyweb.png', 'url' => 'http://bookmarks.yahoo.com/toolbar/savebm?u=%link%&amp;t=%title%&amp;opener=bm&amp;ei=UTF-8&amp;d=%subtitle%',),
'YahooBuzz' => array('favicon' => 'yahoobuzz.png', 'url' => 'http://buzz.yahoo.com/submit/?submitUrl=%link%&amp;submitHeadline=%title%&amp;submitSummary=%subtitle%&amp;submitCategory=science&amp;submitAssetType=text',),
'Yigg' => array('favicon' => 'yiggit.png', 'url' => 'http://yigg.de/neu?exturl=%link%&amp;exttitle=%title%', )

);


/**
* Adds the hook for sociableAdmin()
*
* @see sociableAdmin()
*/
$this->addHook(
    'configuration_add',
    'sociable',
    array("sociableAdmin", "Sociable")
);




// Register 'sociable' as a smarty tag.
$PIVOTX['template']->register_function('sociable', 'smarty_sociable');


/**
* Output the Sociable buttons..
*
* @param array $params
* @param object $smarty
* @return string
*/
function smarty_sociable($params, &$smarty) {
    global $sociable_config, $sociable_sites, $PIVOTX;

    $sociable_items = explode(',', $PIVOTX['config']->get('sociable_items'));
    $tagline = getDefault($PIVOTX['config']->get('sociable_tagline'), $sociable_config['sociable_tagline']);
    $blank = getDefault($PIVOTX['config']->get('sociable_blank'), $sociable_config['sociable_blank']);
    $imagepath = $PIVOTX['paths']['extensions_url'] . 'sociable/images/';
    $csspath = $PIVOTX['paths']['extensions_url'] . 'sociable/sociable.css';
	$path = $PIVOTX['paths']['extensions_url']."sociable/";

    // if no sites are active, display nothing
    if (empty($sociable_items)) {
        return "";
    }

    // Add the hook to display the CSS..
    $PIVOTX['extensions']->addHook(
        'after_parse',
        'insert_before_close_head',
        "\t<link href=\"$csspath\" rel=\"stylesheet\" type=\"text/css\" />\n"
    );

    // Get the entry's data
    $vars = $PIVOTX['template']->get_template_vars();
    $entry = getDefault($vars['entry'], $vars['page']);

    $permalink = urlencode($PIVOTX['paths']['host'].$entry['link']);
    $title = urlencode($entry['title']);
	$subtitle = urlencode($entry['subtitle']);
	if (!$entry['subtitle'] || $entry['subtitle'] == '') {
		$subtitle = urlencode(strip_tags(substr($entry['introduction'],0,20)));
	}
	$thissite = urlencode(getDefault($PIVOTX['config']->get('sitename'), 'Specify your sitename'));
    $blank = ($blank ? "target=\"_blank\" " : "");

    $html = "\n<div class=\"sociable\">\n<span class=\"sociable_tagline\">\n";
    $html .= stripslashes($PIVOTX['config']->get('sociable_tagline'));
    $html .= "\n\t<span>" . __("These icons link to social bookmarking sites where readers can share and discover new web pages.", 'sociable') . "</span>";
    $html .= "\n</span>\n<ul>\n";

    foreach($sociable_items as $sitename) {

        // if they specify an unknown or inactive site, ignore it
        if (!isset($sociable_sites[$sitename])) {
           continue;
        }

        $site = $sociable_sites[$sitename];
        $html .= "\t<li>";

        $url = $site['url'];
        $url = str_replace('%link%', $permalink, $url);
        $url = str_replace('%title%', $title, $url);
		$url = str_replace('%subtitle%', $subtitle, $url);
		$url = str_replace('%sitename%', $thissite, $url);
		$blankinhtml = $blank;
		// special situations
		if ($url == "javascript:AddToFavorites();") {
			$blankinhtml = '';
			OutputSystem::instance()->addCode(
				'sociable-js-src',
				OutputSystem::LOC_HEADEND,
				'script',
				array('src'=>$path.'sociable_addtofavorites.js','_priority'=>OutputSystem::PRI_NORMAL+21)
			);
		}

        $html .= sprintf("<a rel=\"nofollow\" %shref=\"%s\" title=\"%s\">",
                          $blankinhtml, $url, $sitename);
        $html .= sprintf("<img src=\"%s%s\" title=\"%s\" alt=\"%s\" class=\"sociable-hovers\" />",
                          $imagepath, $site['favicon'], $sitename, $sitename);
        $html .= "</a></li>\n";
    }

    $html .= "</ul>\n</div>\n";

    return $html;

}



/**
* The configuration screen for Sociable
*
* @param unknown_type $form_html
*/
function sociableAdmin(&$form_html) {
    global $form_titles, $sociable_config, $PIVOTX, $sociable_sites;

    $form = $PIVOTX['extensions']->getAdminForm('sociable');

    $sites = array();
    foreach ($sociable_sites as $sitename => $sitedata) {
        $sites[$sitename] = $sitename;
    }

    $form->add( array(
        'type' => 'text',
        'name' => 'sociable_tagline',
        'label' => __('Tagline'),
        'value' => '',
        'error' => __('That\'s not a proper tagline!'),
        'text' => __('The text to display in front of the Social Bookmarking buttons.'),
        'size' => 50,
        'isrequired' => 1,
        'validation' => 'string|minlen=1|maxlen=80'
    ));

/*
    $form->add( array(
        'type' => 'checkbox',
        'name' => 'sociable_showlabels',
        'label' => __('Show labels for each website'),
        'text' => __('Select this, if you wish to show labels for all of the sites that are linked.')
    ));
*/

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'sociable_blank',
        'label' => __('Open in new window'),
        'text' => __('If you enable this, all links will open in a new (blank) window. Note: doing this will prevent your website from being valid XHTML.')
    ));

    $form->add( array(
        'type' => 'select',
        'name' => 'sociable_items',
        'label' => __('Show these links'),
        'value' => '',
        'options' => $sites,
        'text' => "",
        'size' => 22,
        'multiple' => true

    ));


    $form->add( array(
        'type' => 'info',
        'text' => "<p>
            Select the sites that you want to add links to in each entry, for your visitors to
            bookmark. Use CTRL-click or CMD-click to select multiple items.
            </p>

            <p>
            Make sure you add the <tt>[[sociable]]</tt> tag to your template(s). It can be placed on a
            weblogtemplate, somewhere between <tt>[[weblog]]</tt> and <tt>[[/weblog]]</tt>. To add
            links from the individual entry pages, you can place it anywhere in the entrypage template.
            </p>

            <small>This extension is based on the WP-plugin <a href='http://www.joostdevalk.nl/wordpress/sociable/'>Sociable</a>, by Peter Harkins, Joost de Valk.
            "
    ));



    /**
     * Add the form to our (referenced) $form_html. Make sure you use the same key
     * as the first parameter to $PIVOTX['extensions']->getAdminForm
     */
    $form_html['sociable'] = $PIVOTX['extensions']->getAdminFormHtml($form, $sociable_config);

}

?>
