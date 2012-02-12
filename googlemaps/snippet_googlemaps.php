<?php
// - Extension: Google Maps
// - Version: 1.0.1
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: Insert a Google Maps with an optional KML overlay.
// - Date: 2012-02-12
// - Identifier: googlemaps

$PIVOTX['template']->register_function('googlemaps', 'smarty_googlemaps');

/**
 * Output the div where the maps is inserted.
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_googlemaps($params, &$smarty) {
    global $PIVOTX, $googlemaps_config;

    static $initialized = false;
    static $mapcount = 0;

    if (!$initialized) {
        $initialized = true;

        $apikey = getDefault($PIVOTX['config']->get('googlemaps_apikey'), $googlemaps_config['googlemaps_apikey']);

        $js_gmap_head = <<<EOF
<style type="text/css">
    .googlemaps div { background-color: transparent; }
</style>
<script type="text/javascript" src="http://www.google.com/jsapi?key=$apikey"></script>
<script type="text/javascript">
    google.load("maps", "2");
</script>
EOF;

        $PIVOTX['extensions']->addHook('after_parse', 'insert_before_close_head', $js_gmap_head);
    }

    $js_gmap_insert = <<<EOF
<script type="text/javascript">
    function googlemaps_initialize_%n%() {
        var mapdiv = document.getElementById("googlemap_%n%");
        if (!mapdiv) {
            return;
        }
        var map = new google.maps.Map2(mapdiv);
        map.setCenter(new GLatLng(%lat%,%long%), %zoom%); 
        map.addControl(new GLargeMapControl());%overlay%
    }
    google.setOnLoadCallback(googlemaps_initialize_%n%);
</script>
EOF;

    $params = cleanParams($params);

    if (isset($params['width'])) {
        $width = $params['width'];
    } else {
        $width = getDefault($PIVOTX['config']->get('googlemaps_width'), $googlemaps_config['googlemaps_width']);
    }
    if (isset($params['height'])) {
        $height = $params['height'];
    } else {
        $height = getDefault($PIVOTX['config']->get('googlemaps_height'), $googlemaps_config['googlemaps_height']);
    }
    if (isset($params['zoom'])) {
        $zoom = $params['zoom'];
    } else {
        $zoom = getDefault($PIVOTX['config']->get('googlemaps_zoom'), $googlemaps_config['googlemaps_zoom']);
    }
    if (isset($params['lat'])) {
        $lat = $params['lat'];
    } else {
        $lat = getDefault($PIVOTX['config']->get('googlemaps_lat'), $googlemaps_config['googlemaps_lat']);
    }
    if (isset($params['long'])) {
        $long = $params['long'];
    } else {
        $long = getDefault($PIVOTX['config']->get('googlemaps_long'), $googlemaps_config['googlemaps_long']);
    }

    $mapcount++;

    if (isset($params['overlay'])) {
        if (substr($params['overlay'],0,4) == 'http') {
            $url = $params['overlay'];
        } else {
            $url = $PIVOTX['paths']['host'].$PIVOTX['paths']['upload_base_url'].$params['overlay'];
        }
        $overlay = '
        var geoXml = new GGeoXml("'.$url.'");
        map.addOverlay(geoXml);';
    } else {
        $overlay = '';
    }
    $js_gmap_insert = str_replace('%overlay%',$overlay,$js_gmap_insert);
    $js_gmap_insert = str_replace('%n%',$mapcount,$js_gmap_insert);
    $js_gmap_insert = str_replace('%lat%',$lat,$js_gmap_insert);
    $js_gmap_insert = str_replace('%long%',$long,$js_gmap_insert);
    $js_gmap_insert = str_replace('%zoom%',$zoom,$js_gmap_insert);

    $PIVOTX['extensions']->addHook('after_parse', 'insert_before_close_head', $js_gmap_insert);

    if (isset($params['format'])) {
        $format = $params['format'];
    } else {
        $format = '<div class="googlemaps" id="googlemap_%n%" style="width: %w%px; height: %h%px;"></div>';
    }
    $format = str_replace('%n%', $mapcount, $format);
    $format = str_replace('%h%', $height, $format);
    $format = str_replace('%w%', $width, $format);

    return $format;

}


?>
