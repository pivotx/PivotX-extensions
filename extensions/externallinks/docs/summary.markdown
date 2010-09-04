
External Links
==============

This extension will make all external links open in a new browser window.
Optionally, it will add a small icon to the link, so your site's visitor will be
able to recognize the external links as such.


Usage
-----

To configure this extension, open the file
`extensions/externallinks/snippet_externallinks.php`, and edit the section
`$externallinks_config`, near the top of the file. Make a backup, before you edit the file, so you can
always revert to a working copy, should something break.

    $externallinks_config = array(
        'addimage' => true,
        'title' => "This link opens in a new window: %link%"
    );


The options are as follows:

  * **addimage** - Whether or not to add the icon to external links. Valid options are `true` and `false`.
  * **title** - The title that will be shown as a tooltip on the link. `%link%` will be replaced with the actual target URL.
