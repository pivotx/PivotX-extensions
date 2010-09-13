# Template options

Each form can have two extra templates for the confirmation. These templates may contain replacement keys for the submitted values.

## Location of the templates

The default installation has a few templates in the `./pivotx/extensions/formclass/templates/` directory. These templates are the default templates for the `[[ contactform ]]` and the `[[ orderform ]]` tags.

You may place your own email and confirmation templates in any pivotx theme directory. This will help if you need to override the default email and confirmation templates and want to keep them apart from the extension.

The order in which te templates are selected is:

*   Your templates directory: if you select `skinny/form.confirm.html` it will search in `./pivotx/templates/skinny/form.confirm.html`
*   If you use the `[[ contactform ]]` or the `[[ orderform ]]` tags you may use the templates directory of the extension and use `form.confirm.html` to use `./pivotx/templates/{your theme name}/form.confirm.html` where {your theme name} will obviously be replaced with the theme name of the selected theme for your site.
*   If a file with the selected name exists in the `./pivotx/extensions/formclass/templates/` directory that file will be used.
*   If no file is found, the filename will be displayed. This means you may use a message instead of the filename. Eg. '*Thank you for your submission, we will get back to you shortly.*'

## The mail template file

The mail template is a simple textfile with the contents of the e-mail that will be sent when the form is validated and submitted. You may use *%replacement%* tags to personalize the e-mail message.

## The confirmation template file

The confirmation template is a HTML snippet with the message you want to show on your site when a user successfully submits a form. You may use *%replacement%* tags to personalize the confirmation message.

## *%replacement%* tags

Replacement tags will be replaced with the content of the field they represent. For example if the form contains an e-mail field named `user_email` and a message named `site-comment` - the replacement tags will be `%user_email%` and `%site-comment%`.

If you use custom fields in the `[[ contactform ]]` and the `[[ orderform ]]` tags the replacement tag will be generated from the type of your input field, an underscore and the label of your input field without spaces or punctuation marks. So an input field like `checkbox:Yes, I want to subscribe to the newsletter` will become `%checkbox_yesiwantosubscribetothenewsletter%` and `text:Your name,required` will be `%text_yourname%`.

## Special *%replacement%* tags

There are some special replacement tags that will do some nifty stuff for you.

### *%uniqid%*

The uniqid tag will be replaced by a unique identifier (something like: 05d96806e3) that you can use to distinguish between submissions of a form.

### *%posted_content%*

The posted_content replacement tag is usefull if you don't want to bother with manyally defining all fields in your form. It will list all field names and the submitted form values.
