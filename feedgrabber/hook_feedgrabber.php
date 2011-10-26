<?php
// - Extension: Feedgrabber
// - Version: 0.3
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: Fetch one or more RSS feeds, insert them as entries
// - Date: 2010-05-30
// - Identifier: feedgrabber
// - Required PivotX Version: 2.1.0


global $feedgrabber_config;

$feedgrabber_config = array(
    'feeds' => array(
        'http://pivotx.net/rss',
        'http://newsrss.bbc.co.uk/rss/newsonline_uk_edition/world/rss.xml'
    ),
    'category' => 'feed',
    'user' => 'feed',
    'status' => 'publish',
    'allow_comments' => false,
    'update_items' => true
);

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
    global $feedgrabber_config, $PIVOTX;

    $max = 10;

    // Initialize a new sql connection..
    $sql = new sql('mysql', $PIVOTX['config']->get('db_databasename'),
        $PIVOTX['config']->get('db_hostname'), $PIVOTX['config']->get('db_username'), $PIVOTX['config']->get('db_password') );
    $extrafieldstable = safeString($PIVOTX['config']->get('db_prefix')."extrafields", true);

    foreach($feedgrabber_config['feeds'] as $feedurl) {

        $rss = fetch_rss( $feedurl );

        debug("Checking feed ". $rss->channel['title'] . ". ". count($rss->items) . " items.");

        if ($rss->items) {

            $amount = 0;

            foreach($rss->items as $item) {

                // var_dump($item);

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

                // var_dump($entry);

                // Check if the entry is already inserted
                $sql->query("SELECT * FROM $extrafieldstable WHERE fieldkey='feedgrabber_id' AND value=" .
                            $sql->quote($entry['extrafields']['feedgrabber_id']) );

                $row = $sql->fetch_row();

                if (empty($row)) {
                    // If $row is empty, insert it..
                    $PIVOTX['db']->set_entry($entry);
                    $PIVOTX['db']->save_entry(true);
                    debug("Inserted entry '". $entry['title']."' from ". $entry['extrafields']['feedgrabber_source']);
                } else if ($feedgrabber_config['update_items']) {
                    // Perhaps update it..

                    $updatedentry = $PIVOTX['db']->read_entry($row['target_uid']);

                    if ($updatedentry['extrafields']['feedgrabber_checksum'] != $entry['extrafields']['feedgrabber_checksum']) {

                        $updatedentry = array_merge($updatedentry, $entry);

                        $PIVOTX['db']->set_entry($updatedentry);
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
