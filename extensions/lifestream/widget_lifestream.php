<?php
// - Extension: Lifestream Widget
// - Version: 1.0
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: A widget to display your lifestream updates from Twitter, Jaiku and other sources.
// - Date: 2008-12-06
// - Identifier: lifestream
// - Required PivotX version: 2.0.2


global $lifestream_config;

$lifestream_config = array(
    'lifestream_twitterusername' => "",
    'lifestream_twitterpassword' => "",
    'lifestream_jaikuusername' => "",
    'lifestream_jaikuapikey' => "",
    'lifestream_summize' => "",
    'lifestream_lastfmusername' => "",
    'lifestream_flickrfeed' => "",
    'lifestream_tumblrusername' => "",
    'lifestream_max_items' => 8,
    'lifestream_max_perfeed' => 5,
    'lifestream_style' => 'widget-lg',
    'lifestream_header' => "<p><strong>What I'm doing:</strong></p>\n<ul class='iconlist'>",
    'lifestream_footer' => "</ul>",
    'lifestream_format' => "<li style=\"background-image: url(%icon%);\">\n%title% - \n<span class='date'><a href=\"%link%\" rel=\"nofollow\" target=\"_blank\">%date%</a></span>\n</li>",
);



/**
 * Adds the hook for deliciousAdmin()
 *
 * @see deliciousAdmin()
 */
$this->addHook(
    'configuration_add',
    'lifestreamupdates',
    array("lifestreamAdmin", "Lifestream Configuration")
);



/**
 * Adds the hook for the actual widget. We just use the same
 * as the snippet, in this case.
 *
 * @see smarty_delicious()
 */
$this->addHook(
    'widget',
    'Status Updates',
    "smarty_lifestream"
);

// If the hook for the jQuery include in the header was not yet installed, do so now..
$this->addHook('after_parse', 'callback', 'jqueryIncludeCallback');



// Register 'delicious' as a smarty tag.
$PIVOTX['template']->register_function('lifestream', 'smarty_lifestream');

/**
 * Output a lifestream feed
 *
 * @param array $params
 * @return string
 */
function smarty_lifestream($params) {
    global $lifestream_config, $PIVOTX;

    $style = getDefault($PIVOTX['config']->get('lifestream_style'), $lifestream_config['lifestream_style']);

    $output = $PIVOTX['extensions']->getLoadCode('defer_file', 'lifestream/lifestream.php', $style);

    return $output;

}



/**
 * The configuration screen for Del.iciou.us
 *
 * @param unknown_type $form_html
 */
function lifestreamAdmin(&$form_html) {
    global $form_titles, $lifestream_config, $PIVOTX;

    $form = $PIVOTX['extensions']->getAdminForm('lifestreamupdates');

    $form->add( array(
        'type' => 'text',
        'name' => 'lifestream_twitterusername',
        'label' => "Twitter username",
        'value' => '',
        'error' => 'That\'s not a proper username!',
        'size' => 20,
        'isrequired' => 0,
        'validation' => 'ifany|string|minlen=3|maxlen=60'
    ));


    $form->add( array(
        'type' => 'password',
        'name' => 'lifestream_twitterpassword',
        'label' => "Twitter password",
        'value' => '',
        'error' => 'That\'s not a proper password!',
        'text' => "Fill out your <a href='http://twitter.com'>Twitter</a> username and password. If you only want to display Twitter updates in the widget, you can leave the password empty. If you want to post to Twitter from PivotX, you have to give your password as well.",
        'size' => 20,
        'isrequired' => 0,
        'validation' => 'ifany|string|minlen=3|maxlen=60'
    ));


    $form->add( array(
        'type' => "hr"
    ));



    $form->add( array(
        'type' => 'text',
        'name' => 'lifestream_lastfmusername',
        'label' => "Last.fm username",
        'value' => '',
        'error' => 'That\'s not a proper username!',
        'text' => "Fill out your <a href='http://last.fm'>Last.fm</a> username. The username is used to get your latest scrobbled songs from Last.fm, and displays these in the widget.",
        'size' => 20,
        'isrequired' => 0,
        'validation' => 'ifany|string|minlen=3|maxlen=60'
    ));


    $form->add( array(
        'type' => "hr"
    ));



    $form->add( array(
        'type' => 'text',
        'name' => 'lifestream_flickrfeed',
        'label' => "Flickr feed",
        'value' => '',
        'error' => 'That\'s not a proper username!',
        'text' => "Fill out a <a href='http://flickr.com'>Flickr.com</a> rss feed. You can get the URL to this feed from the bottom of your Photostream page. It should look something like <tt>http://api.flickr.com/services/feeds/photos_public.gne?id=51692319@N00&amp;lang=en-us&amp;format=rss_200</tt>",
        'size' => 70,
        'isrequired' => 0,
        'validation' => 'ifany|string|minlen=3|maxlen=100'
    ));




    $form->add( array(
        'type' => "hr"
    ));



    $form->add( array(
        'type' => 'text',
        'name' => 'lifestream_summize',
        'label' => "Summize",
        'value' => '',
        'error' => 'That\'s not a proper keyword!',
        'text' => "Fill out a <a href='http://search.twitter.com/'>Twitter Search</a> searchterm. This is used get the results from Twitter Search, and displays these in the widget.",
        'size' => 20,
        'isrequired' => 0,
        'validation' => 'ifany|string|minlen=3|maxlen=60'
    ));


    $form->add( array(
        'type' => "hr"
    ));


    $form->add( array(
        'type' => 'text',
        'name' => 'lifestream_tumblrusername',
        'label' => "Tumblr username",
        'value' => '',
        'error' => 'That\'s not a proper username!',
        'text' => "Fill out your <a href='http://tumblr.com'>Tumblr</a> username. The username is used to get your latest 'public notes' from Tumblr, and displays these in the widget.",
        'size' => 20,
        'isrequired' => 0,
        'validation' => 'ifany|string|minlen=3|maxlen=60'
    ));


    $form->add( array(
        'type' => "hr"
    ));


    $form->add( array(
        'type' => 'text',
        'name' => 'lifestream_max_items',
        'label' => "Max. items",
        'value' => '',
        'error' => 'That\'s not a proper amount!',
        'text' => "The maximum amount of items to show from combined feeds.",
        'size' => 5,
        'isrequired' => 1,
        'validation' => 'integer|min=1|max=60'
    ));


    $form->add( array(
        'type' => 'text',
        'name' => 'lifestream_max_perfeed',
        'label' => "Max. items",
        'value' => '',
        'error' => 'That\'s not a amount!',
        'text' => "The maximum amount of items to show from a single feed. Setting this number lower than the one above will make sure that not all items in your lifestream come from the same source.",
        'size' => 5,
        'isrequired' => 1,
        'validation' => 'integer|min=1|max=60'
    ));



    $form->add( array(
        'type' => 'select',
        'name' => 'lifestream_style',
        'label' => "Widget Style",
        'value' => '',
        'options' => getDefaultWidgetStyles(),
        'error' => 'That\'s not a proper style!',
        'text' => "Select the style to use for this widget.",

    ));


    $form->add( array(
        'type' => 'textarea',
        'name' => 'lifestream_header',
        'label' => "Header format",
        'error' => 'Error!',
        'size' => 20,
        'cols' => 70,
        'rows' => 3,
        'validation' => 'ifany|string|minlen=2|maxlen=4000'
    ));

    $form->add( array(
        'type' => 'textarea',
        'name' => 'lifestream_format',
        'label' => "Output format",
        'error' => 'Error!',
        'size' => 20,
        'cols' => 70,
        'rows' => 5,
        'validation' => 'string|minlen=2|maxlen=4000'
    ));


    $form->add( array(
        'type' => 'textarea',
        'name' => 'lifestream_footer',
        'label' => "Footer format",
        'error' => 'Error!',
        'size' => 20,
        'cols' => 70,
        'rows' => 3,
        'validation' => 'ifany|string|minlen=2|maxlen=4000'
    ));


    /**
     * Add the form to our (referenced) $form_html. Make sure you use the same key
     * as the first parameter to $PIVOTX['extensions']->getAdminForm
     */
    $form_html['lifestreamupdates'] = $PIVOTX['extensions']->getAdminFormHtml($form, $lifestream_config);


}


?>
