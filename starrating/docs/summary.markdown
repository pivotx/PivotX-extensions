
Star Rating Extension
=====================

This extension allows visitors to vote on entries and/or pages. 
You can use this extension to create a list of 'highest rated' entries on your site.

It is based on the jQuery Star Rating plugin by [Fyneworks][1]

Usage
-----

After enabling the extension, you can set the labels in the configuration screen. There are three tags that you can add
to your templates to show the ratings and to let your visitors vote on them.

**[[star]]**

Add this to a single entry, page or inside a [[weblog]] tag, to show the Star Rating. 
The current score is shown, and the visitor can add his/her rating by clicking one of the stars.

By default only the stars are shown, and not the textual description.
You can add a description by using `[[star description=true]]`.  
The added description can be styled in css with `.star-description { ... }` 
and its format can be set in the configuration screen.  
To trigger the display of the description only when a certain amount of votes have been given you can use
`[[star description=true votes_min=5]]`.  
The amount of stars shown can be set in the configuration screen (default = 5; max = 10).

**[[ratingscore]]**

Display the rating for the current entry or page. The format of the description can be set in the configuration screen.  
The display can be filtered to only show when a certain amount of votes have been registered through `[[ratingscore votes_min=5]]`. 

**[[toprating]]**

Show a list of entries, pages or both with the highest ratings. You can modify the output, and the number of shown entries:

    <ul>
      [[ toprating amount=5 trimtext=20
        format="<li><a href='%link%'>%title%</a> <small>(%score% / %amount% votes)</small></li>"
      ]]
    <ul>

2 additional parms are available: type and votes\_min.  
type can be used to limit the display to only entries (type='entry' which is also the default), 
only pages (type='page') or pages and entries (type='both').  
votes\_min can be used to only show entries or pages that have registered at least that many votes (votes_min=5).  

Note: `[[toprating]]` works only on PivotX with MySQL as database.

[1]: http://fyneworks.com
