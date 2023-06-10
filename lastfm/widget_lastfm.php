<?php
// - Extension: Last.fm
// - Version: 0.2.x
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: A widget to display your Last.fm tunes
// - Identifier: lastfm

global $lastfm_config;

$lastfm_config = array(
    'lastfm_color' => "red",
    'lastfm_userid' => ""
);

/**
 * Adds the hook for lastfmAdmin()
 *
 * @see lastfmAdmin()
 */
$this->addHook(
    'configuration_add',
    'lastfm',
    array("lastfmAdmin", "Last.fm")
);

/**
 * Adds the hook for the actual widget. We just use the same
 * as the snippet, in this case.
 *
 * @see smarty_lastfm()
 */
$this->addHook(
    'widget',
    'lastfm',
    "smarty_lastfm"
);

// Register 'lastfm' as a smarty tag.
$PIVOTX['template']->register_function('lastfm', 'smarty_lastfm');

/**
 * Output a lastfm feed
 *
 * @param array $params
 * @return string
 */
function smarty_lastfm($params = []) {
    global $PIVOTX;

    $output = $PIVOTX['extensions']->getLoadCode('defer_file', 'lastfm/lastfm.php');

    return $output;
}

/**
 * The configuration screen for Last.FM
 *
 * @param unknown_type $form_html
 */
function lastfmAdmin(&$form_html) {
    global $PIVOTX, $lastfm_config;

    $form = $PIVOTX['extensions']->getAdminForm('lastfm');

    $form->add( array(
        'type' => 'select',
        'name' => 'lastfm_color',
        'label' => "Color Scheme",
        'value' => '',
        'firstoption' => __('Select'),
        'options' => array(
           'red' => __("Red"),
           'blue' => __("Blue"),
           'black' => __("Black"),
           'grey' => __("Grey")
           ),
        'isrequired' => 1,
        'validation' => 'any',
        'text' => __('Select the color scheme to use for the widget.')
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'lastfm_userid',
        'label' => __("Your last.fm login name"),
        'value' => '',
        'error' => __('That\'s not a proper login name!'),
        'text' => __("The name you use to log in at <a href='http://last.fm'>Last.fm</a>."),
        'size' => 32,
        'isrequired' => 1,
        'validation' => 'string|min=1|max=32'
    ));

    /**
     * Add the form to our (referenced) $form_html. Make sure you use the same key
     * as the first parameter to $PIVOTX['extensions']->getAdminForm
     */
    $form_html['lastfm'] = $PIVOTX['extensions']->getAdminFormHtml($form, $lastfm_config);
}

?>
