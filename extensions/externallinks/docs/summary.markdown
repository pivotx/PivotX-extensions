
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
        'externallinks_addimage' => true,
        'externallinks_title' => "This link opens in a new window: %link%",
		'externallinks_add_to_title' => "###DONOTUSE###"
    );


The options are as follows:

  * **addimage** - Whether or not to add the icon to external links. Valid options are `true` and `false`.
  * **title** - The title that will be shown as a tooltip on the link. `%link%` will be replaced with the actual target URL.
  * **add\_to\_title** - This option is used as soon as its value is not ###DONOTUSE### (for downward compatibility).
  Its function is that its value will be added to a link that already has a title and whenever the link has no title
  the value config option `title` will be used. `%link%` will be replaced with the actual target URL.
  So if you set this option to empty nothing will be added to links with a title and those without a title will get the 
  value of option `title`.
