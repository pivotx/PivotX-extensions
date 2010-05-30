# FAQ

*   Can I redirect the contactform to another page?

    Yes! If you add a `redirect` parameter to your `[[contactform]]` the form will be redirected to tha uri you set in the redirect parameter. Eg. `[[contactform redirect="/?p=thankyou"]]`.

*   Why is my form not visible in a weblog?

    If you want the contactform or orderform to be visible in a weblog listing you have to use the `showinweblog=1` parameter in your quicktags.

*   Why does [[formbuilder]] not work?

    You're working with a development version of formbuilder. Unfortunately the [[formbuilder]] tag is not finished yet, we're working on it. When it's done it will be seriously cool though.

*   Why is the advanced template output invisible?

    You have to set the `allow_php_in_templates` to 1 in the advanced configuration of your PivotX installation if you want to use this.

*   Can I override the default PivotX form output?

    You can override the form output by editing the default template in `./pivotx/extensions/formbuilder/overrides/formclass_defaulthtml.php`

*   Why will the form not be submitted?

    The formbuilder needs jQuery enabled in your site, you can add it to your theme, or use the `always_jquery` variable in your advanced configuration.

    Obviously javascript must be enabled in your browser too.
