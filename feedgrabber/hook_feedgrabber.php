<?php
// - Extension: Feedgrabber
// - Version: 0.4
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: Fetch one or more RSS feeds, insert them as entries
// - Date: 2011-10-26
// - Identifier: feedgrabber
// - Required PivotX Version: 2.2.0

// Add a hook to the scheduler, to periodically fetch the feeds.
$this->addHook(
    'scheduler',
    'callback',
    'feedgrabber_callback'
);

// Add a hook to display the extra fields in the edit screen.
$this->addHook(
    'in_pivotx_template',
    'entry-bottom',
    array('callback' => 'feedgrabber_displayfields' )
);


include_once($PIVOTX['paths']['pivotx_path'].'includes/magpie/rss_fetch.inc');


/**
 * Callback
 */

function feedgrabber_callback() {
    global $PIVOTX;

    $configfile = dirname(__FILE__) . '/feedgrabber_config.php';

    if (!file_exists($configfile)) {
        debug("Feedgrabber didn't find the config file 'extensions/feedgrabber/feedgrabber_config.php'.");
        return;
    }

    // The config file contains the $feedgrabber_config array.
    include $configfile;

    if (empty($feedgrabber_config['feeds'])) {
        debug("Feedgrabber didn't feed any feeds in the config file.");
        return;
    }

    $max = 10;
    
    foreach($feedgrabber_config['feeds'] as $feedurl) {

        $rss = fetch_rss( $feedurl );

        debug("Checking feed ". $rss->channel['title'] . ". ". count($rss->items) . " items.");

        if ($rss->items) {

            $amount = 0;

            foreach($rss->items as $item) {

                $entry = array();

                $id = getDefault($item['id'], $item['guid']);
                if (empty($id)) {
                    $id = $feedurl.":".$item['date_timestamp'];
                }

                $entry['title'] = $item['title'];
                $entry['introduction'] =  getDefault($item['description'], getDefault($item['atom_content'], $rss->channel['summary']));

                $entry['extrafields']['feedgrabber_link'] =  $item['link'];
                $entry['extrafields']['feedgrabber_id'] =  $id;
                $entry['extrafields']['feedgrabber_author'] =  getDefault($item['author'], $rss->channel['managingeditor']);
                $entry['extrafields']['feedgrabber_source'] =  $rss->channel['title'];

                $entry['category'] = array($feedgrabber_config['category']);
                $entry['user'] = $feedgrabber_config['user'];
                $entry['status'] = $feedgrabber_config['status'];
                $entry['allow_comments'] = $feedgrabber_config['allow_comments'];
                $entry['date'] = date("Y-m-d H:i:s", $item['date_timestamp']);

                $entry['extrafields']['feedgrabber_checksum'] = md5(serialize($entry));

                // Check if the entry is already inserted
                $inserted = false;
                $oldentries = $PIVOTX['db']->read_entries(array(
                    'full' => true, 
                    'extrafields' => 'feedgrabber_id',)
                );
                foreach ($oldentries as $oldentry) {
                    if ($oldentry['extrafields']['feedgrabber_id'] == $entry['extrafields']['feedgrabber_id']) {
                        $inserted = true;
                        break;
                    }
                }

                if (!$inserted) {
                    $entry['code'] = '>'; // New-entry indicator for flat file database.
                    $PIVOTX['db']->set_entry($entry);
                    $PIVOTX['db']->save_entry(true);
                    debug("Inserted entry '". $entry['title']."' from ". $entry['extrafields']['feedgrabber_source']);
                } else if ($feedgrabber_config['update_items']) {
                    // Perhaps update it..

                    if ($oldentry['extrafields']['feedgrabber_checksum'] != $entry['extrafields']['feedgrabber_checksum']) {

                        $oldentry = array_merge($oldentry, $entry);

                        $PIVOTX['db']->set_entry($oldentry);
                        $PIVOTX['db']->save_entry(true);
                        debug("Updated entry '". $entry['title']."' from ". $entry['extrafields']['feedgrabber_source']);
                    }
                }

                $amount++;

                if ($amount>=$max) {
                    break;
                }

            }

        }

    }

}


function feedgrabber_displayfields($entry) {

    echo "<table class=\"formclass\" border=\"0\" cellspacing=\"0\" >
        <tbody>
          ";

    foreach ($entry['extrafields'] as $key=>$value) {
        if (substr($key, 0, 12)=="feedgrabber_") {
            printf("<tr><td><strong>%s</strong></td><td colspan='2'><small>%s</small></td></tr>", $key, $value );
        }
    }

    echo "</tbody></table>";

}

?>
