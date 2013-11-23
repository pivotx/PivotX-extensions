
Hierarchical menus Extension
============================

This extension will allow you to add hierarchical menus to your PivotX site.

Let's suppose you have the following page structure:

- Chapter 'Pages':
  - page 'First page'
  - page 'Second page'
  - page 'Third page'
- Chapter 'Second page'
  - page 'First sub-item'
  - page 'Second sub-item'

Minimal usage example: 

        [[ menu firstchapter="Pages" ]]

Here "firstchapter" tells which chapter to start from - the index chapter. You
get the following output:

- [First page](/link/to/first/page)
- [Second page](/link/to/second/page)
  - [First sub-item](/link/to/first/sub-item/of/second/page)
  - [Second sub-item](/link/to/second/sub-item/of/second/page)
- [Third page](/link/to/third/page)

Syntax
------

The snippet takes the following optional parameters

- **toplevelbegin** - The HTML to insert when the top level starts.
- **toplevelitem** - The HTML to insert for a top level item.
- **toplevelend** - The HTML to insert when the top level ends.
- **sublevelbegin** - The HTML to insert when the sub level starts.
- **sublevelitem** - The HTML to insert for a sub level item.
- **sublevelend** - The HTML to insert when the sub level ends.
- **topsubinclude** - Adds the page link of the parent also as first link to the list (0 or 1).
- **isactive** - The HTML to insert for `%active%` in the item that is active.
  There is no default value for **isactive** so if you want some output from
  `%active%` you have to use this parameter.
- **exclude** - A comma-separated list of pages to exclude from the menu.
- **sort** - Selects how to sort. You can for example use "title" instead of
  the default backend sort.
- **weblog** - Selects which weblog the pages are part of. This only affects
  the generated page links.

Example - standard
-------------------

An example where most parameters have their default values: 

        [[ menu
            firstchapter="chaptername"
            toplevelbegin="<strong>%chaptername%</strong><br /><small>%description%</small><ul>"
            toplevelitem="<li %active%><a href='%link%'>%title%</a>%sub%</li>"
            toplevelend="</ul>"
            sublevelbegin="  <ul>"
            sublevelitem="  <li %active%><a href='%link%'>%title%</a>%sub%</li>"
            sublevelend="  </ul>"
            isactive="class='activemenu'"
        ]]

You can also use `%counter%` to keep track of the number of menus:

        [[ menu
            ...
            toplevelitem="<li class='menu-%counter% %active%'><a href='%link%'>%title%</a></li>"
            ...
        ]]

Or `%subcounter%` to keep track of the number of submenus:

        [[ menu
            ...
            sublevelitem="<li class='menu-%subcounter% %active%'><a href='%link%'>%title%</a></li>"
            ...
        ]]

Example - expandable/collapsible menu
--------------------------------------

Finally, if you want a fancy expandable/collapsible menu, try the following
menu code some where in your templates:

        [[ menu
            firstchapter="chaptername"
            toplevelbegin="<ul class='menu'>"
            sublevelbegin="<ul class='acitem'>"
            isactive="class='active'"
        ]]

**and** add the following HTML code to the `head` element in your templates:

       <script type="text/javascript" src="[[extensions_dir]]menu/menu.js"></script> 

You can read more about how to configure the expandable menu (using CSS classes) 
in the beginning of the file menu.js.

To get an idea on what styling of such a fancy menu can achieve add the following HTML code to the `head` element in your templates:

       <link href="[[extensions_dir]]menu/menu.css" rel="stylesheet" type="text/css" />

and try this code:

        <div id='mainmenu'>
        [[ menu
            firstchapter="chaptername"
            toplevelbegin="<ul id='hmenu' class='menu'>"
            sublevelbegin="<ul class='acitem'>"
            isactive="class='active'"
        ]]
        </div>