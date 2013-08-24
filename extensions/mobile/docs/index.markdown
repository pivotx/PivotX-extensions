
Mobile Extension
================

This extension enables you to show a mobile version of your website to your
visitors.

This extension works best in combination with the default 'mobile' templates,
or derivatives thereof. As such, you should also activate the Image Tools
extension.

Preparation
-----------

For optimal results you have to make sure that you have a 'subdomain' set up for
use as the mobile version. So, if your domain name is `example.org` you should
have a `mobile.example.org` or `m.example.org` set up, that shows the same
content on both URLs.

NB! The extension does work without using a subdomain. Just make sure that you
set "Mobile Domain name" in the configuration to your normal domain name.

How to do this might differ from server to server. Some ways to do this are:

  * Try it. Go to `m.example.org` (substitute your domain name, of course). Your
    domain might be set up with a wildcard, so it might already work.
  * Add `m.example.org` as an 'alias' or 'domain pointer' to your
    website/webserver's configuration. Some servers allow you to set this up
    yourself.
  * Add a subdomain for `m.example.org` to your site. Make sure that the
    subdomain points to the same location as the 'main' website.

If these options aren't available, or you can't get it to work, you should ask
your Hosting provider.


Usage
-----

After you've set up the subdomain, enable the extension in the Extension pages
of PivotX and configure it.
   
Selecting the option of using the folder name of the active weblog enables you
to display matching mobile layout templates with each of your weblogs.
If the template name does not exist in that weblog folder the extension automatically
switches to the templates you defined in the configuration of the extension itself.

Options
-------

You might want to give your visitors the option to switch between the mobile and
full versions of the website. To do this, you'll need links to the other version
than the current version of the site, with a special flag that disables the
automatic detection or redirection for the browser.
If you use your normal domain also as a mobile domain this link only works for mobile
visitors.

On the 'normal' site, use this link:

    [[mobilelink to=mobile text="View the mobile version of this site."]]

On the mobile site, use this link:

    [[mobilelink to=full text="View the full version of this site."]]

If you wish to show these links in another way, feel free to add hard coded links
to your templates. The only thing you need is the `mobilecookie=1` and
`mobilecookie=0` flags on the appropriate links.
