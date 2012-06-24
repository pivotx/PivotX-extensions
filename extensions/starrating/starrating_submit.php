<?php

require_once(dirname(dirname(dirname(__FILE__))).'/lib.php');
initializePivotX(false);


// Make this user's 'unique' key:

$uniquekey = substr(md5($_SERVER['REMOTE_ADDR']. $_SERVER['HTTP_USER_AGENT']), 0, 8);



// Let's get the key from the URL..
foreach($_GET as $key=>$value) {

    list ($dummy, $uid) = explode("-", $key);

    if ((intval($uid)==0) || (intval($value)==0)) {
        continue;
    }

    // If we get here, we have a numerical UID, and a value.
    $nrstars = getDefault($PIVOTX['config']->get('starrating_nrofstars'), 5);
    if ($value < 1 || $value > $nrstars) {
        debug('No vote! -- For an unknown reason the Star Rating vote is not within the min and the max: ' . $value);
        continue;
    }

    if ($dummy=="entry") {
        $data = $PIVOTX['db']->read_entry(intval($uid));
    } elseif ($dummy=="page") {
        $data = $PIVOTX['pages']->getPage(intval($uid));
    }

    if (is_array($data['extrafields']['ratings'])) {
        $ratings = $data['extrafields']['ratings'];
    } else {
        $ratings = array();
    }

    $data['extrafields']['ratings'][$uniquekey] = $value;
    $data['extrafields']['ratingcount'] = count($data['extrafields']['ratings']);
    $data['extrafields']['ratingaverage'] = array_sum($data['extrafields']['ratings']) / $data['extrafields']['ratingcount'];

    if ($dummy=="entry") {
        $PIVOTX['db']->set_entry($data);
        $PIVOTX['db']->save_entry(true);
    } elseif ($dummy=="page") {
        $PIVOTX['pages']->savePage($data);
    }
 }

echo "<p>".__("Thank you for voting!")."</p>";
echo "<p>".__("Use your browser's back button to go back to the previous page.")."</p>";

?>
