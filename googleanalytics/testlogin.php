<?php

require_once(dirname(dirname(dirname(__FILE__))).'/lib.php');
require_once(dirname(__FILE__).'/analytics_api.php');

initializePivotX(false);

$PIVOTX['session']->minLevel(PIVOTX_UL_ADMIN);

echo "<h1>Testing Google Analytics Login</h1>"; 

$login = $PIVOTX['config']->get("ga_login");
$password = $PIVOTX['config']->get("ga_password");
$id = 'ga:'.$PIVOTX['config']->get("ga_profileid");

$api = new analytics_api();
if($api->login($login, $password)) {

	echo "<p><strong>logged in successfully!</strong></p>\n";

	
    $api->load_accounts();
    
    if (count($api->accounts)>0) {
        
        echo "<p>This login has access to the following accounts:</p>";
        
        
        
        echo "<ul style='margin-left: 20px;'>";
        
        foreach($api->accounts as $account) {
           
            printf("<li><strong>%s<small>(%s)</small></strong> - ID: %s</li>",
                $account['title'],
                $account['webPropertyId'],
                $account['profileId']
                );
            
        }

        echo "</ul>";
        
        echo "<p><strong>Note:</strong> The ID listed here, has to be filled in as the 'Profile ID' in the settings!</p> ";
        
    } else {
        
        echo "<p>No accounts found.</p>";
        
    }
        
	
}
else {

	echo "<p><strong>Login failed!</strong> Check your Google login and password..</p>\n";

}
