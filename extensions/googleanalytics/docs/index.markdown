
Google Analytics Extension
==========================

This extension adds the Google Analytics tracking code to your PivotX pages with full control over tracking code customization.  

You can also add a small block on the dashboard that shows statistics from Analytics.  
(This doesn't work at the moment because the new API as not yet been incorporated -- any one?)

With advanced customization options that allow you to track subdomains within one profile, setup your custom campaigns, change the session timeout limit, and use GoogleAnalytics in combination with Urchin. All these settings can be managed from the extension configuration page.

As a pre-requisite you need a GoogleAnalytics account.


Usage
-----

Enable the Google Analytics hook extension in the Extension section of PivotX.
Goto the configuration page for the Google Analytics extension and enter your Web Property id, this is also known as the UA tracking code and looks like UA-xxxxxx-y.
Optional: if you want the Google Analytics statistics to show on the PivotX dashboard then you have to provide your Google Account credentials
(see remark above about the new API).

The Google Analytics asynchronous tracking code will now be added to every page or entry that PivotX generates for your weblog. The tracking code is placed in the head section of your pages in line with Google Analytics recommendations.

There are advanced configuration options available from the Google Analytics extension configuration page. If you know what you are doing these are great to track custom campaigns and the like. Please refer to the Google Analytics documentation for additional information on these advanced options.



