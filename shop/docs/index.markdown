Shop 0.2-40
===========
An extension to sell products on your website.

Installation
------------
To install, use the following steps:

  * Extract and copy this extension to a subdirectory of your pivotx/extensions directory.
  * Create a category called "shop", you may also use another category if you want to.
  * Enable the extension and go to the extension configuration for the basic settings.
  * Enter the category you want to use for your shop in the extension configuration.
  * Modify your templates to enable the shop, see the rest of this file for more info.

Usage
-----
To make items into products just select the category you chose for your shop and the fields for price, stock item id and availability will appear. Items that are available will get an "add to cart" button, items that are unavailable will get an "out of stock" message.
  
Templates
---------
You can copy `checkout.tpl` and `shop.tpl` from the extension templates directory, or you can follow these steps:

  * Create a template called `checkout.tpl` in the theme you want to use, it should be a copy of `page.tpl`
  * Create a default template for the shop weblog pages. This template is based on `frontpage.tpl`. You can use the shop template in a weblog that shows the default shop category. It should include an `[[addtocart]]` and `[[pricedisplay]]` snippet for each entry if the shop is not configured to automatically add those.
  * Add the `[[addtocart entry=$entry]]` snippet to your weblog and entry template files where you want to have products enabled.
  * Add the `[[pricedisplay entry=$entry]]` snippet to your weblog and entry template files to show a price.
  * Add the `[[shoppingcart]]` snippet to your sidebar.

Template options
----------------

  * You may use the showqty to add an textfield for the amount `[[addtocart entry=$entry showqty=1]]`
  * You may use the showlabels see the labels for all visible fields `[[addtocart entry=$entry showlabels=1]]`
  * The `[[pricedisplay]]` and `[[addtocart]]` snippets also work with only the entry id like `[[addtocart entryid=$entry.uid]]`
  * The default cart is the compact version, you can choose another one by adding `[[shoppingcart type=full]]` or `[[shoppingcart type=small]]`
  * The `[[shoppingcart hidempty=1]]` snippet will only show a cart when you're have one.

Automatically add snippets
--------------------------
If you switch on the "Append snippets to default templates" checkbox in the extension configuration you do not need to add the `[[addtocart]]` and `[[shoppingcart]]` snippets, because the add to cart button will be appended to the entry introduction and the shoppingcart will be automatically added to the `[[widgets]]` snippet.

Automatically add CSS
---------------------
If you don't like the CSS the shop gives you, you can disable 'Use builtin CSS' in the shop configuration. See the contents of`../shop/css/pivotx.shop.css` for example style rules.

Payment with Mollie.nl
----------------------
The default payment provider for this shop is <a href="http://mollie.nl">mollie</a>. You need an account for that. After setting up an account you need to set up a profile for iDEAL. Your partner key and your profile key need to be set in the Shop configuration in PivotX. Mollie.nl requires that your payments are in Euros.

For testing purposes you have to make sure your iDEAL account is set to testmode both in the mollie.nl configuration and in the pivotx shop configuration. Transactions will then redirect to the big mollie testbank and no money will be required. Final testing in real transaction mode could be done with a product that only costs &euro;1.18 to see if all transactions come through.

Payment with OGONE
------------------
Ogone works for <a href="https://i-kassa.rabobank.nl/">Rabobank internetkassa (RIK)</a>, <a href="https://internetkassa.abnamro.nl/">ABN-Amro internetkassa</a> and generic dutch <a href="http://www.ogone.com/">Ogone</a>. The ogone configuration is a lot more complicated than the mollie configuration.

  * First set up a testkassa account, when you have your `PSPID` and password you can enter the technical details.
  * Setup the default operation to 'Verkoop' (Direct sales) and the transaction handling to 'Online maar overschakelen naar offline wanneer het online systeem van de aquirer niet beschikbaar is'.
  * It is recommended to use `SHA512` as hashing algorithm, you must also set this to the corresponding value in your pivotx shop configuration.
  * You also need to setup a `SHA-1-IN` and `SHA-1-OUT` key in the ogone control panel.

Further there are three kind of URL's you need to fill in. The source URL report URL and the return URL.

  * The source URL looks like `http://www.example.com/index.php?action=payment` The ogone control panel states that more URL's may be entered by separating them with ";" but the PivotX shop does not need that.
  * The report URL looks like `http://www.example.com/index.php?action=report&status={status}` In these links the report `{status}` must be one of the following: unsure, rejected, update. Use these links for the post-payment and status-update pages
  * The return URL looks like `http://www.example.com/index.php?action=return&status={status}` In these links the return `{status}` must be one of the following: back, accept, decline, exception, cancel. Use these links for the Back button, Accepturl, Declineurl, Exceptionurl and Cancelurl.
  
The domain *www.example.com* is obviously your own domain.

*Please note:* Your site may only have one url, so use a no-www `.htaccess` rule. You will also need a separate account for each site. 

After you have tested the payments you can upgrade the test account to a production account.

*Please note:* Sometimes your testaccount will be inacessible after upgrading, you may need a separate test account for your testsites. If you have multiple e-commerce sites you will also need a separate bank account for each site.

Emails
------
There are some email templates that are automatically configured. One for the default order and others for orders with each payment provider.
You can customize these templates. Make sure that the templates are in the correct location and available.

The first line of the template will be used as subject for the email. The text after the second line is an html template which will be used for the body of the email. The keywords enclosed by "[[" "]]" will be replaced by the information from the order.

The `email_order_debug.tpl` template is an example with all available variables.

Translation
-----------
The messages are automaticaly translated if a translation is available.

Ther is no multiple language support yet.

You can also manually translate most of it with the following:
To enable another translation, make sure that the translation file exists in `shop/translations`. 
The name for a translation file should be {language code}.php where {language code} is something like en, de, fr, nl or the name of your shop.

To enable a translation, set the language code in the shop configuration

If you want to customize a default translation - copy the translation file to `shop/translations/{new language code}.php` and replace the translated texts with your own. The rest will happen automatically.

Translation is simple, add the english text and the translation on a line in the array like `"{english text}"=>"{custom translation}"`.
  
Help! It's not working
----------------------
If there is an obvious error in the configuration the shop will disable itself. You will see a message in the dashboard. The shopping cart, price display and add to cart buttons will disappear. This is because if the configuration is broken your customers will not be able to receive confirmation mails or even order from your site anyway.

If your shop is disabled, you will have to correct the configuration and manually turn the shop back on.