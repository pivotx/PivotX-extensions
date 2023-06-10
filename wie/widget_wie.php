<?php
// - Extension: WIE
// - Version: 0.2.x
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: A widget to show who's online - wieonline.nl
// - Identifier: wie
// - Required PivotX version: 2.0.2

global $wie_config;

$wie_config = array(
    'wie_userid' => ""
);

/**
 * Adds the hook for wieAdmin()
 *
 * @see wieAdmin()
 */
$this->addHook(
    'configuration_add',
    'wie',
    array("wieAdmin", "W.I.E.")
);

/**
 * Adds the hook for the actual widget. We just use the same
 * as the snippet, in this case.
 *
 * @see smarty_wie()
 */
$this->addHook(
    'widget',
    'wie',
    "smarty_wie"
);

// Register 'wie' as a smarty tag.
$PIVOTX['template']->register_function('wie', 'smarty_wie');

/**
 * Output a wie feed
 *
 * @param array $params
 * @return string
 */
function smarty_wie($params = []) {
    global $PIVOTX;

    $userid = $PIVOTX['config']->get('wie_userid');

    if (empty($userid)) {
        return "No WIE user id set!";
    }

    $url = "http://www.wieonline.nl/wie/wie.php?user=" . $userid ;

    $output = $PIVOTX['extensions']->getLoadCode('immediate_script', $url);
    //$output = $PIVOTX['extensions']->getLoadCode('defer_script', $url);

    return $output;
}

/**
 * The configuration screen for WIE
 *
 * @param unknown_type $form_html
 */
function wieAdmin(&$form_html) {
    global $PIVOTX, $wie_config;

    $form = $PIVOTX['extensions']->getAdminForm('wie');

    $form->add( array(
        'type' => 'text',
        'name' => 'wie_userid',
        'label' => __("Your WIE login name"),
        'value' => '',
        'error' => __('That\'s not a proper username!'),
        'text' => sprintf(__("The name you use to log in at %s"),
            "<a href='http://www.wieonline.nl/'>wieonline.nl</a>"),
        'size' => 32,
        'isrequired' => 1,
        'validation' => 'string|min=1|max=32'
    ));

    /**
     * Add the form to our (referenced) $form_html. Make sure you use the same key
     * as the first parameter to $PIVOTX['extensions']->getAdminForm
     */
    $form_html['wie'] = $PIVOTX['extensions']->getAdminFormHtml($form, $wie_config);
}

?>
