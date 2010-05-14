
Star Rating Extension
=====================

This extension allows visitors to vote on entries and/or pages. You can use this extension to create a list of 'highest rated' entries on your site.

It is based on the jQuery Star Rating plugin by [Fyneworks][1]

Usage
-----

After enabling the extension, you can set the labels in the configuration screen. There are three tags that you can add
to your templates, to show the ratings, and to let your visitors vote on them.

**[[star]]**

Add this to a single entrypage, or inside a [[weblog]] tag, to show the Star Rating. The current score is shown, and the visitor can add his/her rating by clicking one of the stars.

By default only the stars are shown, and not the textual description. You can add the description by using `[[star description=true]]`. The added description can be styled in css with `.star-description { ... }`

The format of the description can be set in the configuration screen.

**[[ratingscore]]**

Display the rating for the current entry. The format of the description can be set in the configuration screen.

**[[toprating]]**

Show a list of entries with the highest ratings. You can modify the output, and the number of shown entries:

    <ul>
      [[ toprating amount=5 trimtext=20
        format="<li><a href='%link%'>%title%</a> <small>(%score% / %amount% votes)</small></li>"
      ]]
    <ul>

Note: `[[ratingscore]]` works only on PivotX with MySQL as database.

[1]: http://fyneworks.com
