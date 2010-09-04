<?php

echo "<pre>"; 
// this example.php file was last update May 28th 2009, 10:55am NZST

require_once('analytics_api.php');

// enter your login, password and id into the variables below to try it out

$login = 'twokingshq';
$password = 'twokingshq%1';

// NOTE: the id is in the form ga:12345 and not just 12345
// if you do e.g. 12345 then no data will be returned
// read http://www.electrictoolbox.com/get-id-for-google-analytics-api/ for info about how to get this id from the GA web interface
// or load the accounts (see below) and get it from there
// if you don't specify an id here, then you'll get the "Badly formatted request to the Google Analytics API..." error message
$id = 'ga:19521634';

$api = new analytics_api();
if($api->login($login, $password)) {

	echo "login success\n";

	if(1) {
		
		// ->load_accounts() loads the accounts in your profile you have access to into ->accounts
		// read more about this at the following urls:
		// http://www.electrictoolbox.com/get-google-analytics-profile-id-accounts-list/
		// http://www.electrictoolbox.com/php-google-analytics-load-accounts-list/
		// note: you don't actually need to load the accounts to use the analytics class
		$api->load_accounts();
		print_r($api->accounts);
		
        /*
		example output:
		Array
		(
		    [www.electrictoolbox.com] => Array
		        (
		            [tableId] => ga:7426158
		            [accountId] => 144582
		            [accountName] => The Electric Toolbox
		            [profileId] => 7426158
		            [webPropertyId] => UA-144582-3
		        )


		    [www.electricbookmarks.com] => Array
		        (
		            [tableId] => ga:13502852
		            [accountId] => 144582
		            [accountName] => The Electric Toolbox
		            [profileId] => 13502852
		            [webPropertyId] => UA-144582-11
		        )

		)
		the id you need to pass to ->data() is [tableId]
		for example, if you were wanting to get the profile id for www.electrictoolbox.com you could do this:
		$api->load_accounts();
		$id = $api->accounts['www.electrictoolbox.com']['tableId'];
		be sure to also read http://www.electrictoolbox.com/google-analytics-api-php-class-qa-20090513/ 
		in the update to the section "How do I get the account name with a profile_ID" because it would seem
		that not everyone sees the index and account name the same way i have in the example above
		*/

	}
		
	// get some account summary information without a dimension
	// change to if(true) to echo the example
	if(1) {
		$data = $api->data($id, '', 'ga:bounces,ga:newVisits,ga:visits,ga:pageviews,ga:uniquePageviews');
		foreach($data as $metric => $count) {
			echo "$metric: $count\n";
		}
	}

	// get the pagePath vs pageviews & unique pageviews
	// change to if(true) to echo the example
	if(1) {
		
		$data = $api->data($id, 'ga:pagePath', 'ga:pageviews,ga:uniquePageviews');
		//print_r($data);
		
		// how to loop through the data
		foreach($data as $dimension => $metrics) {
			echo "$dimension pageviews: {$metrics['ga:pageviews']} unique pageviews: {$metrics['ga:uniquePageviews']}\n";
		}
		
	}

	// get the browser vs visits & pageviews	
	// change to if(true) to echo the example
	if(1) {
	
		$data = $api->data($id, 'ga:browser,ga:browserVersion', 'ga:visits,ga:pageviews', false, false, false, 100);
		//print_r($data);

		// you can then access the metrics for a specific dimension vs metric like e.g.
		echo $data['Internet Explorer']['8.0']['ga:pageviews'], "\n";
		// or loop through the data
		foreach($data as $dimension1 => $array) {
			foreach($array as $dimension2 => $metrics) {
				echo "$dimension1 $dimension2 visits: {$metrics['ga:visits']} pageviews: {$metrics['ga:pageviews']}\n";
			}
		}
		
	}

	// get a summary for the selected profile just for yesterday
	if(1) {
		$data = $api->get_summary($id, 'yesterday');
		print_r($data);
	}

	// get a summary for all profiles just for yesterday
	if(1) {
		$data = $api->get_summaries('yesterday');
		print_r($data);
	}
	
	if(1) {
		// get data filtered by canada, using a string as the filters parameter
		$data = $api->data($id, '', 'ga:visits,ga:pageviews', false, false, false, 10, 1, 'ga:country%3d%3dCanada');
		print_r($data);
		// get data filtered by canada and firefox browser, using a string as the filters parameter
		$data = $api->data($id, '', 'ga:visits,ga:pageviews', false, false, false, 10, 1, 'ga:country%3d%3dCanada;ga:browser%3d@Firefox');
		print_r($data);
		// same as the second example above but using the filtering class
		$filters = new analytics_filters('ga:country', '==', 'Canada');
		$filters->add_and('ga:browser', '=@', 'Firefox');
		$data = $api->data($id, '', 'ga:visits,ga:pageviews', false, false, false, 10, 1, $filters);
		print_r($data);
		// using the filtering class to filter where USA or Canada
		$filters = new analytics_filters('ga:country', '==', 'Canada');
		$filters->add_or('ga:country', '==', 'United States');
		$data = $api->data($id, '', 'ga:visits,ga:pageviews', false, false, false, 10, 1, $filters);
		print_r($data);
	}
	
}
else {

	echo "login failed\n";
	
}

echo "<p>klaar</p>";
