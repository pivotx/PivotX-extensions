<?php
// - Extension: Piwik
// - Version: 1.0
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: An extension to add Piwik tracking code.
// - Date: 2009-09-15
// - Identifier: piwik
// - Required PivotX version: 2.0.2

global $piwik_config;

$piwik_config = array(
    'piwik_url' => '/piwik',
    'piwik_site_id' => 1,
    'piwik_ignore_ip' => ''
);


/**
 * Adds the hook for piwikAdmin()
 *
 * @see piwikAdmin()
 */
$this->addHook(
    'configuration_add',
    'piwik',
    array("piwikAdmin", "Piwik")
);



/**
 * The configuration screen for Piwik
 *
 * @param unknown_type $form_html
 */
function piwikAdmin(&$form_html) {
    global $PIVOTX, $piwik_config;

    $form = $PIVOTX['extensions']->getAdminForm('piwik');

    $form->add( array(
        'type' => 'text',
        'size' => 100,
        'name' => 'piwik_url',
        'label' => __('Piwik URL'),
        'isrequired' => 1,
        'text' => makeJtip(__('Piwik URL'), __('Either the global or local URL to where you have installed Piwik.')),
    ));

    $form->add( array(
        'type' => 'text',
        'size' => 4,
        'name' => 'piwik_site_id',
        'label' => __('Piwik Website ID '),
        'validation' => 'integer',
        'isrequired' => 1,
        'text' => makeJtip(__('Piwik Website ID '), __('The id for the website as found in the Piwik website overview.')),
    ));

    $form->add( array(
        'type' => 'text',
        'size' => 100,
        'name' => 'piwik_ignore_ip',
        'label' => __('Ignore IP'),
        'text' => makeJtip(__('Ignore IP'), __('A comma separated list of IP that shouldn\'t be registered in Piwik.')),
    ));

    /**
     * Add the form to our (referenced) $form_html. Make sure you use the same key
     * as the first parameter to $PIVOTX['extensions']->getAdminForm
     */
    $form_html['piwik'] = $PIVOTX['extensions']->getAdminFormHtml($form, $piwik_config);

}


?>
