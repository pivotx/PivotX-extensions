
BBClone
=======

[BBclone](http://www.bbclone.de/) is a web counter written in PHP and gives a 
detailed view of the visitors of your web site.

This extension consist of two independent parts:

* A **snippet** extension that displays the selected stats.
* A **hook** extension that allows tracking your pages in BBclone.

**NB!** Both extensions assumes that BBclone is installed in a folder called
`bbclone`, which should be in the same folder/at the same level as your
`pivotx` folder.



The snippet extension
---------------------

Usage  

    [[ bbclone_stats type="SOMETYPE" ]]

where SOMETYPE can be: `hits`, `hits_entry`, `keywords`, `referer`.
 

The hook extension
------------------

After enabling the extension all visits will be tracked by BBClone.
