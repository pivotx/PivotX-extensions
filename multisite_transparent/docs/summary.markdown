Multi-site Transparent
======================

This extension makes the links to resources like images and CSS
files much cleaner for a site that is run by PivotX in multi-site mode.

NB! This extension is incompatible with Minify. If you enable
"Use Minify in Frontend", this extension will do nothing.

The standard link to an image `file.jpg` on a PivotX site 
(in multi-site mode) located at example.org is:

    http://example.org/pivotx/sites/example.org/images/file.jpg

After this extension is enabled the link becomes:

    http://example.org/images/file.jpg

The new link won't work unless you add the following lines to your 
virtual host definition:

    # Virtual host rules for transparent multi-site usage
    RewriteRule ^images/(.*)$ pivotx/sites/example.org/images/$1 [L]
    RewriteRule ^templates/(.*)$ pivotx/sites/example.org/templates/$1 [L] 

These rules are of course for the domain example.org - you should modify the
rules to fit your domain.

If you don't have access to the virtual host definition, you can use a
`.htaccess` file but requires a bit more code:

    # .htacces rules for transparent multi-site usage 
    RewriteCond %{HTTP_HOST} example.org
    RewriteRule ^images/(.*)$ pivotx/sites/example.org/images/$1 [L]
    RewriteCond %{HTTP_HOST} example.org
    RewriteRule ^templates/(.*)$ pivotx/sites/example.org/templates/$1 [L] 

Once again, these rules are of course for the domain example.org.
