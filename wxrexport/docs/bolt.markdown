
WXR Export to Bolt
==================

Currently pages and entries, with or without comments, can be exported. Any extra/bonus fields used in pages or entries are also exported. (The definition of extra fields are handled manually.)  

Preparation on the Bolt side
==========================

Install Bolt if you haven't done so already. Install the ImportWXR extension, and configure it. Check that your content types have the needed extra fields. (See below for more information about extra fields.)

Preparation on the PivotX side
==============================

Before you start creating xml files in the extension itself you should set the different variables, especially(?) the "addto" ones for the id's, to a desired value. Search file *hook_wxrexport.php* for string **@@CHANGE** to see the parts of the code where you can customize (this is not only at the beginning of the file!).  
Then decide what you want to export. It is a good approach to first create all the xml's you want to use and check their content for the warnings generated (just search for string **warning**; at the end of each xml file generated there is also a total count of warnings issued).

About extra/bonus fields
========================

To be written.

Checking the result and actions afterwards
==========================================

__[urlhome] - Your main url__

All references to your main url or your aliases (set in the aliases array) in the content of the entries and/or pages will be replaced by [urlhome]. You need to replace that string by the desired new url.

__Excluding exports from import__

If you want to exclude exported parts from the import you can manually edit the generated xml files before import. Just delete the lines beginning with the `<item>` tag and the `</item>` tag and all lines in between.