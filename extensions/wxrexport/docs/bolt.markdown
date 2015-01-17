
WXR Export to Bolt
==================

To be documented the specifics on how to export to Bolt.

If you want to export all or part of your content you need to follow a certain order to get everything across in good order.  
**So please read this carefully. Re-doing part of the export is most of the times nearly impossible!**

Preparation on the Bolt side
==========================

....

Preparation on the PivotX side
==============================

Before you start creating xml files in the extension itself you should set the different variables, especially(?) the "addto" ones for the id's, to a desired value. Search file *hook_wxrexport.php* for string **@@CHANGE** to see the parts of the code where you can customize (this is not only at the beginning of the file!).  
Then decide what you want to export. It is a good approach to first create all the xml's you want to use and check their content for the warnings generated (just search for string **Warning!**; at the end of each xml file generated there is also a count of warnings issued).

Executing the Export
====================

To export everything you need to execute this in sequence (export means create the export file and import that into Bolt):

 * Export Categories (e)
 * Export Chapters (p)
 * Export Uploads (e+p)
 * Export Extrafields (e+p)
 * Export Galleries (e+p)
 * Export Pages and Galleries (p)
 * Export Entries and Galleries including Comments (e)

e = entry related  
p = page related

Checking the result and actions afterwards
==========================================

__Excluding exports from import__

If you want to exclude exported parts from the import you can manually edit the generated xml files before import. Just delete the lines beginning with the `<item>` tag and the `</item>` tag and all lines in between.