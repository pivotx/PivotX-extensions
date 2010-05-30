
Feedgrabber
===========

The Feedgrabber extension lets you 'crawl' one or more external RSS feeds, of
which the items will be added to your PivotX as seperate entries. You can use
this for when `[[feed]]` is too restrictive to use, or if you want to show
the items from the RSS Feed as 'regular' entries on your site.

**Note:** This Extension requires PivotX with a MySQL database.

**Note 2:** If you use this extension to set up a splog, I will come over to your
house to kick your ass. Seriously!

Usage
-----

To configure this extension, open the file
`extensions/feedgrabber/hook_feedgrabber.php`, and edit the section `$feedgrabber_config`,
near the top of the file. Make a backup, before you edit the file, so you can
always revert to a working copy, should something break.

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

The options are as follows:

  * **feeds** - An array of one or more feeds, to be crawled for entries. you
  can add extra lines as needed, but make sure that every line except the last
  one ends with a comma.
  * **category** - The Category to which the Entries will be added. Make sure
  this Category exists.
  * **user** - The User to which the Entries will be added. To keep things
  organised, you probably want to add a new, specific User for this.
  * **status** - The status of the newly inserted Entries. This can be `hold` or
  `publish`.
  * **allow_comments** - Whether or not to allow comments on entries that are
  inserted by this extension.
  * **update_items** - If set to `true`, the entries will be updated in the
  database if they change in the original feed. This means that you shouldn't
  edit the entries, because any subsequent changes in the RSS feed, will
  overwrite your changes in the database.

To verify that the extension is working correctly, do the following:

  * Enable `debug` in the PivotX configuration screen.
  * Trigger the scheduled update script, by requesting the script in your
  browser: http://www.example.org/pivotx/scheduler.php?force=yes
  * Check the Debug Log for details.
  