jQuery Tablesorter 
==================

This extension enables you to add (client-side) sorting to any column of any table.
    
The extension uses the excellent [jQuery Tablesorter plugin](http://tablesorter.com/).

Usage
-----

The extension consists of a single snippet which has to be enabled before usage.

Snippet syntax is very simple:

    <p>The minimal usage is:</p>
    <table [[ jquery_tablesorter ]] >
    <thead>
    <tr><th>Col 1</th><th>Col 2</th></tr>
    </thead>
    <tbody>
    <tr><td>B</td><td>3</td></tr>
    <tr><td>D</td><td>6</td></tr>
    <tr><td>A</td><td>1</td></tr>
    </tbody>
    </table>


**NB!** You have to use the `thead` and `tbody` elements in your
tables to make the Tablesorter work.
    
The snippet takes five optional parameters:

* <tt>options</tt> - <a href="http://tablesorter.com/docs/#Configuration">configuration</a> 
            of the Tablesorter JS function. The configuration directives must be
            separated by commas and please remember to add some spaces between the 
            brackets if need to set the sortList.
* <tt>css_file</tt> - the styling of the table, in particular the
            sorting headers. The default is 'extensions/jquery_tablesorter/blue_theme/style.css'.
* <tt>class</tt> - the class defined in the CSS file. Default value
            is 'tablesorter'.
* <tt>id</tt> - the id for the table.
* <tt>only_head</tt> - forcing the snippet to produce no out except
            for the needed setup in the HTML-header (<tt>head</tt>). See more
            info below.

Tips
----

If you are using the Wysiwyg editor it might be very hard/impossible to 
insert `[[ jquery_tablesorter ... ]]` inside the opening
`table` tag. The solution is to insert 

    [[ jquery_tablesorter ... only_head=1 ]]

**before** the table and ensure that the class and id of
the `jquery_tablesorter` template tag matches the table.
