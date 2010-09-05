<?php


// ---------------------------------------------------------------------------
//
// PIVOTX - LICENSE:
//
// This file is part of PivotX. PivotX and all its parts are licensed under
// the GPL version 2. see: http://docs.pivotx.net/doku.php?id=help_about_gpl
// for more information.
//
// $Id: insert_upload.php 1672 2009-01-08 23:50:54Z hansfn $
//
// ---------------------------------------------------------------------------


require_once(dirname(dirname(dirname(__FILE__))).'/lib.php');

initializePivotX();

// Make sure the person requesting this page is logged in:
$PIVOTX['session']->isLoggedIn();
$PIVOTX['session']->minLevel(1);


if (isset($_GET['f_target'])) {
	$target= $_GET['f_target'];
} else {
	$target= $_POST['f_target'];
}


$imagename= "";

if (isset($_GET['f_image'])) {
	$imagename = $_GET['f_image'];
} else if ($success) {
	$imagename = $my_uploader->file['name'];
}

// Show a warning if we're on 'localhost'.
$host = parse_url($PIVOTX['paths']['host']);

if ($host['host']=="localhost") {
    $PIVOTX['template']->assign('msg', __("The Uploader does not work well from 'localhost'. Please use the server's (internal) IP-address instead."));
}


$PIVOTX['template']->assign('target', $target);
$PIVOTX['template']->assign('imagename', $imagename);
$PIVOTX['template']->assign('text', $text);
$PIVOTX['template']->assign('pivotxsession', $_COOKIE['pivotxsession']);
$PIVOTX['template']->assign('title', __("Upload a file"));
$PIVOTX['template']->assign('paths', $PIVOTX['paths']);

$PIVOTX['template']->display($PIVOTX['paths']['extensions_path']."gallery/upload_images.tpl");

?>
