<?php
// - Extension: Sociable Bookmarking
// - Version: 0.3
// - Author: PivotX Team / Logfather / Michael Heca
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: A snippet extension to show links to various social bookmark sites.
// - Date: 2010-05-20
// - Identifier: sociable


global $sociable_config;

$sociable_config = array(
    'sociable_tagline' => "Bookmark this entry:",
    'sociable_showlabels' => 0,
    'sociable_items' => '',
    'sociable_blank' => 0
);


global $sociable_sites;

$sociable_sites = array(

   'Del.icio.us' => array(
      'favicon' => 'delicious.png',
      'url' => 'http://del.icio.us/post?url=%link%&amp;title=%title%',
   ),

   'Digg' => array(
      'favicon' => 'digg.png',
      'url' => 'http://digg.com/submit?phase=2&amp;url=%link%&amp;title=%title%',
   ),

   'Google' => array (
      'favicon' => 'googlebookmark.png',
      'url' => 'http://www.google.com/bookmarks/mark?op=edit&amp;bkmk=%link%&amp;title=%title%'
   ),


   'Reddit' => array(
      'favicon' => 'reddit.png',
      'url' => 'http://reddit.com/submit?url=%link%&amp;title=%title%',
   ),

   'StumbleUpon' => array(
      'favicon' => 'stumbleupon.png',
      'url' => 'http://www.stumbleupon.com/submit?url=%link%&amp;title=%title%',
   ),

   'Technorati' => array(
      'favicon' => 'technorati.png',
      'url' => 'http://technorati.com/faves?add=%link%',
   ),

   'eKudos' => array(
      'favicon' => 'ekudos.gif',
      'url' => 'http://www.ekudos.nl/artikel/nieuw?url=%link%&amp;title=%title%',
   ),

   'Facebook' => array(
      'favicon' => 'facebook.png',
      'url' => 'http://www.facebook.com/sharer.php?u=%link%&amp;t=%title%',
   ),

   'Blogmarks' => array(
      'favicon' => 'blogmarks.png',
      'url' => 'http://blogmarks.net/my/new.php?mini=1&amp;simple=1&amp;url=%link%&amp;title=%title%',
   ),

   'Connotea' => array(
      'favicon' => 'connotea.png',
      'url' => 'http://www.connotea.org/addpopup?continue=confirm&amp;uri=%link%&amp;title=%title%',
   ),


   'Fark' => array(
      'favicon' => 'fark.png',
      'url' => 'http://cgi.fark.com/cgi/fark/edit.pl?new_url=%link%&amp;new_comment=%title%&amp;linktype=Misc',
      // To post to a different category, see the drop-down box labeled "Link Type" at
      // http://cgi.fark.com/cgi/fark/submit.pl for a complete list
   ),

   'Furl' => array(
      'favicon' => 'furl.png',
      'url' => 'http://www.furl.net/storeIt.jsp?u=%link%&amp;t=%title%',
   ),

   'Fleck' => array(
      'favicon' => 'fleck.gif',
      'url' => 'http://extension.fleck.com/?v=b.0.804&amp;url=%link%',
   ),

   'Linkuj.cz' => array(
      'favicon' => 'linkuj-cz.gif',
      'url' => 'http://linkuj.cz/?id=linkuj&url=%link%&amp;title=%title%',
   ),

   'Live' => array(
      'favicon' => 'live.png',
      'url' => 'https://favorites.live.com/quickadd.aspx?marklet=1&amp;url=%link%&amp;title=%title%',
   ),

   'Ma.gnolia' => array(
      'favicon' => 'magnolia.png',
      'url' => 'http://ma.gnolia.com/bookmarklet/add?url=%link%&amp;title=%title%',
   ),

   'NewsVine' => array(
      'favicon' => 'newsvine.png',
      'url' => 'http://www.newsvine.com/_tools/seed&amp;save?u=%link%&amp;h=%title%',
   ),

   'NuJIJ' => array(
      'favicon' => 'nujij.gif',
      'url' => 'http://nujij.nl/jij.lynkx?t=%title%&amp;u=%link%',
   ),

   'Propeller' => array(
      'favicon' => 'propeller.gif',
      'url' => 'http://www.propeller.com/submit/?U=%link%&amp;T=%title%',
   ),


   'Slashdot' => array(
      'favicon' => 'slashdot.png',
      'url' => 'http://slashdot.org/bookmark.pl?title=%title%&amp;url=%link%',
   ),

   'Sphinn' => array(
      'favicon' => 'sphinn.png',
      'url' => 'http://sphinn.com/submit.php?url=%link%&amp;title=%title%',
   ),


   'TailRank' => array(
      'favicon' => 'tailrank.png',
      'url' => 'http://tailrank.com/share/?text=&amp;link_href=%link%&amp;title=%title%',
   ),

   'Twitter' => array(
      'favicon' => 'twitter.png',
      'url' => 'http://twitter.com/?status=Must+check:+%title%+%link%',
   ),

   'vybrali.sme.sk' => array(
      'favicon' => 'vybrali-sme-sk.gif',
      'url' => 'http://vybrali.sme.sk/submit.php?url=%link%',
   )

);


/**
* Adds the hook for deliciousAdmin()
*
* @see deliciousAdmin()
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

        $html .= sprintf("<a rel=\"nofollow\" %shref=\"%s\" title=\"%s\">",
                          $blank, $url, $sitename);
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
        'label' => "Tagline",
        'value' => '',
        'error' => 'That\'s not a proper tagline!',
        'text' => "The text to display in front of the Social Bookmarking buttons.",
        'size' => 50,
        'isrequired' => 1,
        'validation' => 'string|minlen=1|maxlen=80'
    ));

/*
    $form->add( array(
        'type' => 'checkbox',
        'name' => 'sociable_showlabels',
        'label' => "Show labels for each website",
        'text' => "Select this, if you wish to show labels for all of the sites that are linked."
    ));
*/

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'sociable_blank',
        'label' => "Open in new window",
        'text' => "If you enable this, all links will open in a new (blank) window. Note: doing this will prevent your website from being valid XHTML."
    ));

    $form->add( array(
        'type' => 'select',
        'name' => 'sociable_items',
        'label' => "Show these links",
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

            <small>This extension is based on the WP-plugin <a href='http://www.joostdevalk.nl/wordpress/sociable/'>Sociable</a>, by Peter Harkins, Joost de valk.
            "
    ));



    /**
     * Add the form to our (referenced) $form_html. Make sure you use the same key
     * as the first parameter to $PIVOTX['extensions']->getAdminForm
     */
    $form_html['sociable'] = $PIVOTX['extensions']->getAdminFormHtml($form, $sociable_config);

}

?>