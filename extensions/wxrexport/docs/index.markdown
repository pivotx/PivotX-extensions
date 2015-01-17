
WXR Export Extension Docs
==========================

This extension enables you to export your content, entries and pages with or without
comments, in the WXR format supported by WordPress and many other blogging solutions.

WXR stands for WordPress eXtended RSS and is a XML file. Using the various links
on the extension configuration page you can generate WXR files with the content you 
need.

Currently the following export choices exists:

 * Optional actions before exporting content
   * Export Categories
   * Export Chapters as plain pages that can be used to parent the PivotX pages
   * Export Uploads
   * Export Extrafields definitions like e.g. Bonusfields extension for use in ACF plugin for WP (galleries will be skipped)
   * Export Extrafields galleries for use in Envira (Lite) plugin for WP
 * With parsing of introduction and body content
   * Export Pages
   * Export Pages and Galleries
   * Export Entries (with and without comments)
   * Export Entries and Galleries (with and without comments)
 * Without parsing of introduction and body content (so you can check where template tags or smarty variables are used)
   * Export Pages
   * Export Entries (with and without comments)

Remarks:

1. If you use extension Imagetools be sure to have at least version 0.8.1 installed.
2. Template tags for extension Bonusforms are disabled when exporting because they give errors when parsing and also can switch the entire extension seemingly off.