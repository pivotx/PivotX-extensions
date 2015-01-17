
WXR Export to Wordpress
=======================

If you want to export all or part of your content you need to follow a certain order to get everything across in good order.  
**So please read this carefully. Re-doing part of the export is most of the times nearly impossible!**

Preparation on the WP side
==========================

Obviously you need to have an installed version of Wordpress (WP) active. Also check that standard plugin _Wordpress importer_ is installed and updated to the latest version.
  
__Internal ID__

As all internal connections (e.g. which image is added to which post) are done through id's you need to identify a free range of id's not yet used.  
The easiest way to do that is to identify the current max id in use on your WP installation. For that log in to your SQL back-end (e.g. phpMyAdmin) and navigate to the table(s) where your WP data is in. Find the table that contains the posts (normally called wp_posts) and select it to view the table options. These options will contain the current value for AUTO-INCREMENT. All the new id's that will be imported need to be above this value to be sure that all internal connections are set correctly. If your auto-increment value seems too high you can try to lower it as MySQL will automatically set it to the real value again if your chosen value is too low.

__Dummy user__

To be able to attach some of the imports to a dummy user in stead of letting the accompanying userid be defined when importing, it is handy to define some dummy user.

__Additional plugins__

If you are going to export your Extrafields and their values you need to install plugin ACF (Advanced Custom Fields) and if your going to export your Galleries you need to install plugin Envira (Lite). 

Preparation on the PivotX side
==============================

Before you start creating xml files in the extension itself you should set the different variables, especially the "addto" ones for the id's, to a desired value. Search file *hook_wxrexport.php* for string **@@CHANGE** to see the parts of the code where you can customize (this is not only at the beginning of the file!).  
Then decide what you want to export. It is a good approach to first create all the xml's you want to use and check their content for the warnings generated (just search for string **Warning!**; at the end of each xml file generated there is also a count of warnings issued).

Executing the Export
====================

To export everything you need to execute this in sequence (export means create the export file and import that into WP):

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

Obviously all imports have to result in no errors reported. If all is well your content is visible in the Dashboard. Getting all to display correctly on your site means updating your theme :-) .

__Defining the shortcode [imgpath]__

When exporting entries and pages their content is scanned for image references. These are replaced by the correct pointer to the exported upload. This pointer contains a shortcode [imgpath] which can be replaced manually in the xml file by the url you are importing on but can also be used as a shortcode in WP. This shortcode has to be defined in functions.php of your theme so it can be displayed correctly (the image will not be shown in the visual editor):

    //image relative path
    function relativePath() {
    	return home_url( '/wp-content/uploads' );
    }
    add_shortcode('imgpath', 'relativePath');

Using this shortcode makes the content more portable to other WP's.

__Excluding exports from import__

If you want to exclude exported parts from the import you can manually edit the generated xml files before import. Just delete the lines beginning with the `<item>` tag and the `</item>` tag and all lines in between.