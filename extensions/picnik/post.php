<?php

// some global initialisation stuff
if(realpath(__FILE__)=="") {
    $pivotx_path = dirname(dirname(dirname(realpath($_SERVER['SCRIPT_FILENAME']))))."/";
} else {
    $pivotx_path = dirname(dirname(dirname(realpath(__FILE__))))."/";
}
$pivotx_path = str_replace("\\", "/", $pivotx_path);

require_once($pivotx_path.'lib.php');

initializePivotX();


// Picnik's callback will be something like this:
// Array
// (
//    [file] => http://www.picnik.com/images/bnlcwzlv_nWv2bDFB3p.jpg
//    [_imageid] => j4h8853p9l0xv0|WW91IGhhdmUgdG9vIG11Y2ggZnJlZSB0aW1lLiA7LSk=
// )


list($postedsession, $targetfilename) = explode("|", $_POST['_imageid']);

// Getting back the readable filename.
$targetfilename = base64_decode($targetfilename);

// We do three checks to see if the call to post.php is legit. We do not want
// people posting random stuff to your Pivot!
if ( (!$PIVOTX['session']->isLoggedIn()) || ($postedsession != $_SESSION['pivotxsession']) ) {
    echo "<h1>Error: Image not posted!</h1>";
    echo "<p>You are not logged into Pivot. Please login first.</p>";
    die();
}

$postedurl = parse_url($_POST['file']);

if ( ($postedurl['host']!="www.picnik.com") || (strpos($postedurl['path'], 'file/')!==1)  ) {
    echo "<h1>Error: Image not posted!</h1>";
    echo "<p>The picnik extension will only fetch images from www.picnik.com.</p>";
    die();
}

$extension = strtolower(getExtension($targetfilename));

if (!in_array($extension, array('gif', 'jpg', 'png', 'bmp') )) {
    echo "<h1>Error: Image not posted!</h1>";
    echo "<p>The image should be in a common image format. Not as {$extension}.</p>";
    die();
}

// End of legitimacy checks..


// Check if we need to save the original, or just overwrite it.
$keeporiginal = getDefault($PIVOTX['config']->get('picnik_keeporiginal'), $picnik_config['picnik_keeporiginal']);

if ($keeporiginal) {
    $originalfilename = str_replace(".".$extension, ".original.".$extension, $targetfilename);

    if (!file_exists($originalfilename)) {
        rename($targetfilename,$originalfilename);
    }

}

// Now, we fetch the file, and write it to the $targetfilename

$image = getRemoteFile($_POST['file']);

if (strlen($image)>1000) {
    $fp = fopen($targetfilename, "wb");
    fwrite($fp, $image);
    fclose($fp);
} else {
    echo "<h1>Error: Image not posted!</h1>";
    echo "<p>The image could not be retrieved from Picnik. Please try again later.</p>";
    die();
}


// If we get to here all went well. Close the window.
echo "<script type=\"text/javascript\">window.opener.location.reload();self.close();</script>";

?>
