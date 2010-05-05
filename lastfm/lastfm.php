<?php


DEFINE('PIVOTX_INWEBLOG', TRUE);

require_once(dirname(dirname(dirname(__FILE__)))."/lib.php");

initializePivotX();

$color = getDefault($PIVOTX['config']->get('lastfm_color'), $lastfm_config['lastfm_color']);
$userid = $PIVOTX['config']->get('lastfm_userid');

if (empty($userid)) {
    echo "No Last.fm userid set!";
    die();
}

$file = implode("", file(dirname(__FILE__)."/template.html"));

$file = str_replace("%color%", $color, $file);
$file = str_replace("%userid%", $userid, $file);

echo $file;

?>
