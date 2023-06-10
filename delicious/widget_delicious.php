<?php
// - Extension: Del.ico.us
// - Version: 0.3.x
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: A widget to display your Delicous.com feed.
// - Identifier: delicious

global $delicious_config;

$delicious_config = array(
    'delicious_url' => "",
    'delicious_max_items' => 8,
    'delicious_style' => 'widget-lg',
    'delicious_header' => "<p><strong>My latest Delicious bookmarks</strong></p>\n<ul>",
    'delicious_footer' => "<ul>",
    'delicious_format' => "<li><a href=\"%link%\" title=\"%description%\">%title%</a> <small>(%tags%)</small></li>",
);

/**
 * Adds the hook for deliciousAdmin()
 *
 * @see deliciousAdmin()
 */
$this->addHook(
    'configuration_add',
    'delicious',
    array("deliciousAdmin", "Del.icio.us")
);

/**
 * Adds the hook for the actual widget. We just use the same
 * as the snippet, in this case.
 *
 * @see smarty_delicious()
 */
$this->addHook(
    'widget',
    'delicious',
    "smarty_delicious"
);

// Register 'delicious' as a smarty tag.
$PIVOTX['template']->register_function('delicious', 'smarty_delicious');

/**
 * Output a delicious feed
 *
 * @param array $params
 * @return string
 */
function smarty_delicious($params = []) {
    global $delicious_config, $PIVOTX;

    $style = getDefault($PIVOTX['config']->get('delicious_style'), $delicious_config['delicious_style']);

    $output = $PIVOTX['extensions']->getLoadCode('defer_file', 'delicious/delicious.php', $style);

    return $output;
}

/**
 * The configuration screen for Del.iciou.us
 *
 * @param unknown_type $form_html
 */
function deliciousAdmin(&$form_html) {
    global $form_titles, $delicious_config, $PIVOTX;

    $form = $PIVOTX['extensions']->getAdminForm('delicious');

    $form->add( array(
        'type' => 'text',
        'name' => 'delicious_url',
        'label' => "Feed",
        'value' => '',
        'error' => 'That\'s not a proper url!',
        'text' => "The URL to your del.icio.us feed. This should look something like 'http://feeds.delicious.com/rss/username'.",
        'size' => 50,
        'isrequired' => 1,
        'validation' => 'string|minlen=5|maxlen=60'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'delicious_max_items',
        'label' => "Max. items",
        'value' => '',
        'error' => 'That\'s not a proper nickname!',
        'text' => "The maximum amount of items to show from your Del.icio.us feed.",
        'size' => 5,
        'isrequired' => 1,
        'validation' => 'integer|min=1|max=60'
    ));

    $form->add( array(
        'type' => 'select',
        'name' => 'delicious_style',
        'label' => "Widget Style",
        'value' => '',
        'options' => getDefaultWidgetStyles(),
        'error' => 'That\'s not a proper style!',
        'text' => "Select the style to use for this widget.",

    ));

    $form->add( array(
        'type' => 'textarea',
        'name' => 'delicious_header',
        'label' => "Header format",
        'error' => 'Error!',
        'size' => 20,
        'cols' => 70,
        'rows' => 3,
        'validation' => 'ifany|string|minlen=2|maxlen=4000'
    ));

    $form->add( array(
        'type' => 'textarea',
        'name' => 'delicious_format',
        'label' => "Output format",
        'error' => 'Error!',
        'size' => 20,
        'cols' => 70,
        'rows' => 5,
        'validation' => 'string|minlen=2|maxlen=4000'
    ));

    $form->add( array(
        'type' => 'textarea',
        'name' => 'delicious_footer',
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
    $form_html['delicious'] = $PIVOTX['extensions']->getAdminFormHtml($form, $delicious_config);

}

?>
