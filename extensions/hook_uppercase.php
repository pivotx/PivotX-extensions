<?php
// - Extension: The shouting hook
// - Version: 0.1
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: THIS WILL CHANGE ALL YOUR TEXT TO UPPERCASE!!
// - Date: 2007-05-20
// - Identifier: shoutinghook


$this->addHook(
    'after_parse',
    'callback',
    "afterParseCallback"
    );


function afterParseCallback(&$html) {
    $html = strtoupper($html);
}


?>
