<?php
// - Extension: Google Maps admin page
// - Version: 1.0 Alpha 1
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: An extension to display Google maps with KML overlay. 
// - Date: 2008-06-26
// - Identifier: googlemaps

global $googlemaps_config;

$googlemaps_config = array(
    // Default location is Molde, Norway - the home town of the extension 
    // author ...
    'googlemaps_lat' => 62.73754,
    'googlemaps_long' => 7.158816,
    'googlemaps_zoom' => 9,
    'googlemaps_width' => 400,
    'googlemaps_height' => 300,
    'googlemaps_apikey' => 'MUST_BE_CHANGED'
);


/**
 * Adds the hook for googleMapsAdmin()
 *
 * @see googleMapsAdmin()
 */
$this->addHook(
    'configuration_add',
    'googlemaps',
    array("googleMapsAdmin", "Google Maps")
);


/**
 * The configuration screen for Google Maps
 *
 * @param unknown_type $form_html
 */
function googleMapsAdmin(&$form_html) {
    global $PIVOTX, $googlemaps_config;

    $form = $PIVOTX['extensions']->getAdminForm('googlemaps');

    $form->add( array(
        'type' => 'text',
        'size' => 100,
        'name' => 'googlemaps_apikey',
        'label' => __('Google Maps API key'),
    ));

    $form->add( array(
        'type' => 'text',
        'size' => 4,
        'name' => 'googlemaps_width',
        'label' => __('Default width of map'),
        'validation' => 'integer',
    ));

    $form->add( array(
        'type' => 'text',
        'size' => 4,
        'name' => 'googlemaps_height',
        'label' => __('Default height of map'),
        'validation' => 'integer',
    ));

    $form->add( array(
        'type' => 'text',
        'size' => 4,
        'name' => 'googlemaps_zoom',
        'label' => __('Default zoom for map'),
        'validation' => 'integer',
    ));

    $form->add( array(
        'type' => 'text',
        'size' => 10,
        'name' => 'googlemaps_lat',
        'label' => __('Default latitude for map'),
        'validation' => 'integer',
    ));

    $form->add( array(
        'type' => 'text',
        'size' => 10,
        'name' => 'googlemaps_long',
        'label' => __('Default longitude for map'),
        'validation' => 'integer',
    ));

    /**
     * Add the form to our (referenced) $form_html. Make sure you use the same key
     * as the first parameter to $PIVOTX['extensions']->getAdminForm
     */
    $form_html['googlemaps'] = $PIVOTX['extensions']->getAdminFormHtml($form, $googlemaps_config);

}


?>
