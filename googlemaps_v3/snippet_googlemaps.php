<?php
// - Extension: Google Maps (Version 3)
// - Version: 1.2.1
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: Insert a Google Maps with an optional KML overlay using version 3 of the API.
// - Date: 2012-02-12
// - Identifier: googlemaps_v3

global $googlemaps_v3_config;

$googlemaps_v3_config = array(
    // Default location is Molde, Norway - the home town of the extension
    // author ...
    'googlemaps_v3_lat' => 62.73754,
    'googlemaps_v3_long' => 7.158816,
    'googlemaps_v3_zoom' => 9,
    'googlemaps_v3_width' => 400,
    'googlemaps_v3_height' => 300,
    'googlemaps_v3_maptype' => 'ROADMAP',
    'googlemaps_v3_kml_preserveviewport' => false,
);

/**
 * Adds the hook for googleMapsAdmin()
 *
 * @see googleMapsAdmin()
 */
$this->addHook(
    'configuration_add',
    'googlemaps_v3',
    array("googleMapsV3Admin", "Google Maps Version 3")
);

/**
 * Registers the googlemaps_v3 template tag
 */
$PIVOTX['template']->register_function('googlemaps_v3', 'smarty_googlemaps_v3');

/**
 * The configuration screen for Google Maps
 *
 * @param unknown_type $form_html
 */
function googleMapsV3Admin(&$form_html) {
    global $PIVOTX, $googlemaps_v3_config;

    $form = $PIVOTX['extensions']->getAdminForm('googlemaps_v3');

    $types = array();
    $types['ROADMAP'] = 'roadmap';
    $types['SATELLITE'] = 'satellite';
    $types['HYBRID'] = 'hybrid';
    $types['TERRAIN'] = 'terrain';

    $form->add( array(
        'type' => 'select',
        'options' => $types,
        'name' => 'googlemaps_v3_maptype',
        'label' => __('Default map type'),
    ));

    $form->add( array(
        'type' => 'text',
        'size' => 4,
        'name' => 'googlemaps_v3_width',
        'label' => __('Default width of map'),
        'validation' => 'integer|min=1',
    ));

    $form->add( array(
        'type' => 'text',
        'size' => 4,
        'name' => 'googlemaps_v3_height',
        'label' => __('Default height of map'),
        'validation' => 'integer|min=1',
    ));

    $form->add( array(
        'type' => 'text',
        'size' => 4,
        'name' => 'googlemaps_v3_zoom',
        'label' => __('Default zoom for map'),
        'validation' => 'integer|min=1',
    ));

    $form->add( array(
        'type' => 'text',
        'size' => 10,
        'name' => 'googlemaps_v3_lat',
        'label' => __('Default latitude for map'),
        'validation' => 'float',
    ));

    $form->add( array(
        'type' => 'text',
        'size' => 10,
        'name' => 'googlemaps_v3_long',
        'label' => __('Default longitude for map'),
        'validation' => 'float',
    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'googlemaps_v3_kml_preserveviewport',
        'label' => __('Preserve viewport when inserting KML files'),
    ));

    $form->use_javascript(true);

    /**
     * Add the form to our (referenced) $form_html. Make sure you use the same key
     * as the first parameter to $PIVOTX['extensions']->getAdminForm
     */
    $form_html['googlemaps_v3'] = $PIVOTX['extensions']->getAdminFormHtml($form, $googlemaps_v3_config);

}

/**
 * Output the div where the maps is inserted.
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_googlemaps_v3($params, &$smarty) {
    global $PIVOTX, $googlemaps_v3_config;

    // We need/use jQuery for the JS onload calls.
    $PIVOTX['extensions']->addHook('after_parse', 'callback', 'jqueryIncludeCallback');

    static $initialized = false;
    static $mapcount = 0;

    if (!$initialized) {
        $initialized = true;

        $js_gmap_head = <<<EOF
<style type="text/css">
    .googlemaps div { background-color: transparent; }
</style>
<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
EOF;
        $PIVOTX['extensions']->addHook('after_parse', 'insert_before_close_head', $js_gmap_head);
    }

    $js_gmap_insert = <<<EOF
<script type="text/javascript">
    function googlemaps_v3_initialize_%n%() {
        var mapdiv = document.getElementById("googlemap_v3_%n%");
        if (!mapdiv) {
            return;
        }
        var latlng =  new google.maps.LatLng(%lat%,%long%);
        var options = {
            zoom: %zoom%,
            center: latlng,
            mapTypeId: google.maps.MapTypeId.%maptype%
        };
        var map = new google.maps.Map(mapdiv, options);
%address%
%overlay%
    }
    jQuery(document).ready(googlemaps_v3_initialize_%n%);
</script>
EOF;

    $params = cleanParams($params);

    if (isset($params['width'])) {
        $width = $params['width'];
    } else {
        $width = getDefault($PIVOTX['config']->get('googlemaps_v3_width'), $googlemaps_v3_config['googlemaps_v3_width']);
    }
    if (isset($params['height'])) {
        $height = $params['height'];
    } else {
        $height = getDefault($PIVOTX['config']->get('googlemaps_v3_height'), $googlemaps_v3_config['googlemaps_v3_height']);
    }
    if (isset($params['zoom'])) {
        $zoom = $params['zoom'];
    } else {
        $zoom = getDefault($PIVOTX['config']->get('googlemaps_v3_zoom'), $googlemaps_v3_config['googlemaps_v3_zoom']);
    }
    if (isset($params['lat'])) {
        $lat = $params['lat'];
    } else {
        $lat = getDefault($PIVOTX['config']->get('googlemaps_v3_lat'), $googlemaps_v3_config['googlemaps_v3_lat']);
    }
    if (isset($params['long'])) {
        $long = $params['long'];
    } else {
        $long = getDefault($PIVOTX['config']->get('googlemaps_v3_long'), $googlemaps_v3_config['googlemaps_v3_long']);
    }
    if (isset($params['address'])) {
        $addressstring = $params['address'];
        $address =  <<<EOF
        var geocoder = new google.maps.Geocoder();
        geocoder.geocode( { 'address': "$addressstring"}, function(results, status) {
          if (status == google.maps.GeocoderStatus.OK) {
            map.setCenter(results[0].geometry.location);
            var marker = new google.maps.Marker({
                map: map,
                position: results[0].geometry.location
            });
          }
        });
EOF;
    } else {
        $address = '';
    }
    if (isset($params['maptype'])) {
        $maptype = strtoupper($params['maptype']);
    } else {
        $maptype = getDefault($PIVOTX['config']->get('googlemaps_v3_maptype'), $googlemaps_v3_config['googlemaps_v3_maptype']);
    }

    $mapcount++;

    if (isset($params['overlay'])) {

        if (isset($params['kml_preserveviewport'])) {
            $kml_preserveviewport = $params['kml_preserveviewport'];
        } else {
            $kml_preserveviewport = getDefault($PIVOTX['config']->get('googlemaps_v3_kml_preserveviewport'),
                $googlemaps_v3_config['googlemaps_v3_kml_preserveviewport'], true);
        }
        if ($kml_preserveviewport) {
            $preserveViewport = 'true';
        } else {
            $preserveViewport = 'false';
        }

        if (substr($params['overlay'],0,4) == 'http') {
            $url = $params['overlay'];
        } else {
            $url = $PIVOTX['paths']['host'].$PIVOTX['paths']['upload_base_url'].$params['overlay'];
        }
        $overlay =  <<<EOF
        var layerOpt = {
            map: map,
            preserveViewport: $preserveViewport,
            suppressInfoWindows: false
        };
        var layer = new google.maps.KmlLayer("%url%", layerOpt);
EOF;
        $overlay = str_replace('%url%',$url,$overlay);
    } else {
        $overlay = '';
    }
    $js_gmap_insert = str_replace('%overlay%',$overlay,$js_gmap_insert);
    $js_gmap_insert = str_replace('%address%',$address,$js_gmap_insert);
    $js_gmap_insert = str_replace('%n%',$mapcount,$js_gmap_insert);
    $js_gmap_insert = str_replace('%lat%',$lat,$js_gmap_insert);
    $js_gmap_insert = str_replace('%long%',$long,$js_gmap_insert);
    $js_gmap_insert = str_replace('%zoom%',$zoom,$js_gmap_insert);
    $js_gmap_insert = str_replace('%maptype%',$maptype,$js_gmap_insert);

    $PIVOTX['extensions']->addHook('after_parse', 'insert_before_close_head', $js_gmap_insert);

    if (isset($params['format'])) {
        $format = $params['format'];
    } else {
        $format = '<div class="googlemaps" id="googlemap_v3_%n%" style="width: %w%px; height: %h%px;"></div>';
    }
    $format = str_replace('%n%', $mapcount, $format);
    $format = str_replace('%h%', $height, $format);
    $format = str_replace('%w%', $width, $format);

    return $format;

}


?>
