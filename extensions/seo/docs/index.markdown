SEO - Search Engine Optimization
================================

This extension allows you to easily add several meta-tags to your website. It does
this by adding relevant `<meta>`-tags to all pages of your site. The frontpage and
other general pages will have generic tags, while the Entries and Pages will have
tags specifically for that page.

This extension will automagically set sensible defaults for the `<meta>`-tags,
but when you're editing an Entry or a Page, you can set the 'keywords' and 'description'
and 'title' manually. You can find the fields below the 'body', when editing an
Entry or a Page.

Defaults and options can be set in the Administration &raquo; Configure Extensions
 &raquo; SEO screen.  
You can define default keywords (shown on non-entries and non-pages), extra keywords
(always added), fixed author, copyright text, extra full metatags and whether you
want to have DC "[Dublin Core](http://dublincore.org/)" tags defined as well.
 
Screenshot of added fields in editor:

<img src="extensions/seo/docs/seo.png" alt="screenshot" style='border: 1px solid #CCC;'/>

The following meta-tags are added by this extension:

- `<meta name="author">` - The author of the page/entry (or fixed author when defined).
- `<meta name="copyright">` - Your own copyright text (when set in options).
- `<meta name="revised">` - The date on which the entry/page was last revised.
- `<meta name="keywords">` - The keywords for the page/entry. If not set manually
  this will be filled with the tags used in the entry/page. Extra keywords are added 
  (when defined) and default keywords will be used when not on a page/entry. 
- `<meta name="description">` - The description of the page/entry. If not set
  manually, the first part of the introduction will be used. On the homepage of 
  the site, the "site description" that was set in Configuration will be used.
- `<title>` - By default the `<title>` tag is not altered by this extension, so
  it will be as defined in the theme. If you set this manually, the `<title>` tag
  in the HTML will be changed by the extension.
- all tags added through extratags in the options. Remember that those have to have the correct
  syntax to be processed correctly e.g. `<meta name="web_author" content="You!" />`

Note that this extension will _not_ magically make your site be the first result
on Google, whenever someone is searching for a term that is used on your site.
Having the search engines give your site a good ranking takes a lot of work, and
this extension takes care of _some_ of that work.
Here are some other things you might want to look into, if you want to make sure
that your site is indexed properly in the search engines:

 - Write relevant content.
 - Make sure your HTML is semantic. Use `<h1>` for the first header, etc.
 - Enable 'mod_rewrite' on your website.
 - Don't be fooled by people who tell you they can get your site to a top position without effort.
 - Write relevant content. (this is so important, it's listed twice)

To learn more about properly optimizing your site for the search engines, read this
relevant document by Google "[Search Engine Optimization Starter Guide](http://www.google.com/webmasters/docs/search-engine-optimization-starter-guide.pdf)"
