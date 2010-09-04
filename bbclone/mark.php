 <?php

$referer = base64_decode($_GET['referer']); 
$request = base64_decode($_GET['request']); 
 
// Check for illegal access..
if (md5($request.$referer) != $_GET['hash']) {
    die("That does not compute.");
}

// some global initialisation stuff
if(realpath(__FILE__)=="") {
    $pivotx_path = dirname(dirname(dirname(realpath($_SERVER['SCRIPT_FILENAME']))))."/";
} else {
    $pivotx_path = dirname(dirname(dirname(realpath(__FILE__))))."/";
}
$pivotx_path = str_replace("\\", "/", $pivotx_path);

require_once($pivotx_path.'lib.php');

initializePivotX();

if (!empty($_GET['title'])) {
    $title = $_GET['title'];
} else {
    $title = $PIVOTX['config']->get('sitename');
}

$_SERVER["HTTP_REFERER"] = $referer;
$_SERVER["SCRIPT_FILENAME"] = $_SERVER["PATH_TRANSLATED"] = $_SERVER['REQUEST_URI'] =
    $_SERVER["SCRIPT_NAME"] = $_SERVER["PHP_SELF"] = $request;

define("_BBC_PAGE_NAME", $title);  
define("_BBCLONE_DIR", fixPath($PIVOTX['paths']['pivotx_path'].'../bbclone/'));  
define("COUNTER", _BBCLONE_DIR."mark_page.php");  
if (is_readable(COUNTER)) include_once(COUNTER);  

?> 
