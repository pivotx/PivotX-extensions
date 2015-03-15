
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
  
__Internal ID Category__

***If you do not use category links in your content you can skip this part.***  
Categories are not normal posts within WP. They are so called taxonomies. These also use internal ids but their value cannot be forced when importing.  
In the code of wxrexport there is an addtocat variable that works like all other addto variables. But to set its correct value you have to check the value for AUTO-INCREMENT in the table that is normally called wp_terms. Set the value to the value of AUTO-INCREMENT to get the right combination.

__Dummy user__

To be able to attach some of the imports to a dummy user in stead of letting the accompanying userid be defined when importing, it is handy to define some dummy user.

__Thumbnail size__

If you want to set the replacement string thumb_repl correctly first check out your setting of it by looking into Settings / Media / Thumbnail size. By default it is set to 150x150 but you can select another value. If you do then set the replacement string in the code to the same value (if you want to use it of course). 

__Additional plugins__

If you are going to export your Extrafields and their values you need to install plugin ACF (Advanced Custom Fields) and if your going to export your Galleries you can install plugin Envira (Lite) or Media Library Assistant. 

Preparation on the PivotX side
==============================

Before you start creating xml files in the extension itself you should set the different variables, especially the "addto" ones for the id's, to a desired value. Search file *hook_wxrexport.php* for string **@@CHANGE** to see the parts of the code where you can customize (this is not only at the beginning of the file!).  
Then decide what you want to export. It is a good approach to first create all the xml's you want to use and check their content for the warnings generated (just search for string **warning**; at the end of each xml file generated there is also a total count of warnings issued).

Executing the Export/Import
===========================

To export everything you need to execute this in sequence (export means create the export file and import that into WP):

 * Export Categories (e)
 * Export Chapters (p)
 * Export Users (s)
 * Export Uploads (e+p)
 * Export Extrafields (e+p)
 * Export Galleries (e+p)
 * Export Pages and Galleries (p)
 * Export Entries and Galleries including Comments (e)

e = entry related  
p = page related  
s = system related

Checking the result and actions afterwards
==========================================

Obviously all imports have to result in no errors reported. If all is well your content is visible in the Dashboard. Getting all to display correctly on your site means updating your theme :-) .

__[urlhome] - Your main url__

All references to your main url or your aliases (set in the aliases array) in the content of the entries and/or pages will be replaced by [urlhome]. You need to replace that string by the desired new url.

__[imgpath] - Defining the shortcode__

When exporting entries and pages their content is scanned for image references. These are replaced by the correct pointer to the exported upload. This pointer contains a shortcode [imgpath] which can be replaced manually in the xml file by the url you are importing on but can also be used as a shortcode in WP. This shortcode has to be defined in functions.php of your theme so it can be displayed correctly (the image will not be shown in the visual editor):

    //image relative path
    function relativePath() {
    	return home_url( '/wp-content/uploads' );
    }
    add_shortcode('imgpath', 'relativePath');

Using this shortcode makes the content more portable to other WP's.

__Excluding exports from import__

If you want to exclude exported parts from the import you can manually edit the generated xml files before import. Just delete the lines beginning with the `<item>` tag and the `</item>` tag and all lines in between.

__Check errors__

If the import reports errors you should check these errors obviously. Also important is to check the ids of the imports just in front of the failing import. The WP importer is known to set these ids to the id of the failing import...... If these imported parts are used somewhere (i.e. connected through its id) then this connection will be wrong. 
In the footer of the generated export the number of warnings is displayed. Check for the string "warning" in the generated file to see what the warning is about.

__Users__

Exporting and importing your users can be done too. If you change nothing on WP side the users will only be defined with their login names. The rest is skipped. Better is that also email and display name are defined.  
For that an add on to the WP importer has been created: pivx\_wp\_import\_users.php  
Put this file in the same folder as wordpress-importer is in.  
After that you need to change the code of wordpress-importer.php around line 355: 

	 } else if ( $create_users ) {
         if ( ! empty($_POST['user_new'][$i]) ) {
             // add extra code for PivotX user import
             include dirname( __FILE__ ) . '/pivx_wp_import_users.php';
             $user_id = wp_create_user( $_POST['user_new'][$i], wp_generate_password() );

If you use this code the normal importer will give an error message because the user has already been defined by pivx\_wp\_import\_users.

Note: imported users will always have level "Subscriber". See the export file to see what their original level was and change manually in WP accordingly.  

Note 2: PivotX does not support first and last names. The export file already has the tags for that so you fill them before importing.

__Galleries__

If you select to export the galleries together with your entries or pages there can be several gallery codes to be found in the end result depending on the values you set in parameter $gallselect in the code (defaults to all possible). You can find those by searching for string "Select the gallery code you want to use" in your output xml files.
Currently there are 3 possibilities:  

1. Plain Gallery code (WP built in)  
 The title/alt/data attributes are displayed as nosupp_ (not supported) parms. So you can see what has been set but it will have no effect in WP itself. 
2. Gallery code for WP plugin Media Library Assistant.  
To get the accompanying parms active you need to install mla\_fixed\_values add on which is in the examples of the plug-in itself. This example is also in this extension. Just upload the folder to the wp-content/plugins and activate it in the WP dashboard.
3. Gallery code for Envira (Lite) plugin code  
This sets the pointer to the gallery defined in Envira. You need to export galleries too for this to work.

__Peculiar things__

1. If the importer encounters an audio file with an image embedded it will create this image as a separate entity. These entities do not have a title. The author of the entity will be the user that does the import (so the user selected is not used).
2. After import of the uploads check all media for creation of thumbnails. If there is no thumbnail visible in the list it could be that the file name contains unwanted characters.