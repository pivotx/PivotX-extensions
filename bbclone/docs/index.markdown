
BBClone
=======

[BBClone](http://www.bbclone.de/) is a web counter written in PHP that gives a 
detailed view of the visitors on your web site.

This extension consist of two independent parts:

* A **snippet** extension that displays the selected stats.
* A **hook** extension that allows tracking your pages in BBClone.

Installing BBClone
------------------

Download BBClone from the [site](http://www.bbclone.de/) and install but skip the step of
adding the activation code (installing the hook will take care of that).

**Warning!** Both extensions assume that BBClone is installed in a folder called
`bbclone`, which should be in the same folder/at the same level as your
`pivotx` folder.

The snippet extension
---------------------

This part of the extension is only needed when you want to display stats yourself. 

Usage  

    [[ bbclone_stats type="SOMETYPE" ]]

where SOMETYPE can be: `hits`, `hits_entry`, `keywords`, `referer`.  

`hits_entry` takes some parms:  
**format** - specify what text will be displayed (default is "%n visitors on this page" where %n will be replaced by the number of hits).  
**title** - specify a specific title of an entry or a page (useful on front page when going through entries).  
For example  

    [[ bbclone_stats type="hits_entry" format="Viewed %n times" title=$entry.title ]]

The hook extension
------------------

After enabling the extension all visits will be tracked by BBClone.
