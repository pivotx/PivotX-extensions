# Orderform quicktag

The `[[orderform]]` tag makes a simple orderform that looks like the following:

<img src="/pivotx/extensions/formbuilder/docs/simpleorderform.png" alt="custom orderform">

This form will only be shown in the full page view of an entry or page - even if you put it in the introduction. If a user submits the form it will be sent to the e-mail address of the author of the page and the user will see a confirmation on screen.

If you want the form to be visible in a weblog listing you may use the showinweblog parameter.

## Extra options

Both those tags have optional values where you can override the defaults.

to="email@example.com"
:   An email address where you want the mail to be sent, other than the author of the entry or page.

from="email@example.com"
:   An email address where you want the mailoriginate from, other than the author of the entry or page.

subject="A nice subject"
:   Overides the default subject which would be "Contactform" or "Orderform".

confirmation="You message has been sent"
:   The name of a template file in the `./pivotx/extensions/formbuilder/templates/` directory or a HTML text that you want the user to see when the form is submitted successfully. See the [template options][7] for more info.

mailtemplate="contactform.mail.tpl.php"
:   Similar to the confirmation message, but with the text or template that will be put into the mail. See the [template options][7] for more info.

submit="Send mail"
:   The text on the submit button

fields
:   If you want to customize the fields in the form you can use the fields parameter. The configuration is done via a long structured string that defines the fields in the form. The each field is defined by the following string `type:Label,validation|validation;`. Valid types are `text`, `textarea`, `checkbox` and `markup`. The label is the name of the field. The validation may be the following validation types: `required` for required fields, `email` for email fields, `zipcodenl` for postal codes / zipcodes, `phonenumber` for phone numbers and `ifany` for optional fields. You may combine the validation types separated by a `|`. The type `markup` is not available in the php templates and has no validation.

showinweblog=1
:   With this parameter forms will be shown in weblogs listings. Use only when you are sure you know what you're doing. You must set the recipient for the form when using this parameter.
