# Advanced options (PHP knowledge required)

There are two ways to configure the e-mail function of the form. The simple one is using the normal mail function and the complicated but more reliable one is using SMTP.

The normal mail function should "Just Work™"

The configuration for SMTP needs the username, the password and the servername of the e-mail account you will use.

*   [Configuration and customization options](#customization)
*   [Basics](#basics)
*   [Mail configuration](#mail_config)
*   [Fields definition](#fields)
*   [Extra markup options](#fmarkup)

<a id="customization"></a>
## Configuration and customization options

The forms you can make are controlled by a huge hierarchical array.

The configuration is one big array that has the following structure:

    $config = array(
      'id' => 'formid',
      'name' => 'formname',
      'action' => $_SERVER["REQUEST_URI"],
      'templates' => array(
        'confirmation' => 'form.confirm.tpl.php', // filename in formpath or html string
        'elements' => 'formclass_defaulthtml.php',
        'mailreply' => 'form.mail.tpl.php' // filename in formpath or html string
      ),
      'method' => 'post', // get | post
      'encoding' => '', // multipart/form-data | empty
      'buttons' => array(
        'verzenden' => array(
          'type' => 'submit',
          'label' => '',
          'value' => 'Send form'
        )
      ),
      'fields' => array(
        'fieldname1' => array(
          'name' => 'fieldname1',
          'label' => 'Name 1',
          'type' => 'text',
          'isrequired' => true,
          'validation' => 'required',
          'requiredmessage' => '"Name 1" is required',
          'error' => 'Please check "Name 1"'
        ),
        'fieldname2' => array(
          'name' => 'fieldname2',
          'label' => 'Name 2',
          'type' => 'text',
          'isrequired' => true,
          'validation' => 'required',
          'requiredmessage' => '"Name 2" is required',
          'error' => 'Please check "Name 2"'
        ),
        'fieldname3' => array(
          'name' => 'fieldname3',
          'label' => 'Name 3',
          'type' => 'text',
          'isrequired' => true,
          'validation' => 'required',
          'requiredmessage' => '"Name 3" is required',
          'error' => 'Please check "Name 3"'
        ),
        'fieldname3' => array(
          'name' => 'fieldname3',
          'label' => 'Name 4',
          'type' => 'text',
          'isrequired' => true,
          'validation' => 'required',
          'requiredmessage' => '"Name 4" is required',
          'error' => 'Please check "Name 4"'
        )
      ),
      'mail_config' => array(
        'subject' => 'E-mail subject',
        'recipient' => array(
          'email' => 'formtomail@example.com',
          'name' => 'Formtomail'
        ),
        'sender' => array(
          'email' => 'formtomail@example.com',
          'name' => 'Formtomail'
        ),
        'method' => 'mail', // mail | smtp
        'smtp' => array(
          'login' => 'formtomail@example.com',
          'password' => 'password',
          'server' => 'mail.example.com'
        )
      ),
      'fieldsets' => array(
        'fieldset1' => array(
          'label' => 'fieldset title 1',
          'fields' => array('fieldname1', 'fieldname2')
        ),
        'fieldset2' => array(
          'label' => 'fieldset title 2',
          'fields' => array('fieldname3', 'fieldname4')
        )
      )
    );


If that looks terrible to you, you're right. Let go through all the options.

<a id="basics"></a>
## Basics

__id__
:   Simple string, with the html id attribute of the form.

__name__
:   Simple string, with the html name attribute of the form.

__action__
:   The url for the action attribute of the form. `$_SERVER["REQUEST_URI"]` is usually the best, it's the same as the page you're on when you load the form.

__templates__
:   Filenames of the default templates for the form_html, the [e-mail message][7] and the [confirmation page][7].

__method__
:   The type of form you're creating. Possible values are `post` and `get`.

__encoding__
:   Only used for file uploads (`multipart/form-data`), otherwise empty.

__[mail_config](#mail_config)__
:   Configuration variables for the e-mail function.

__buttons__
:   Used for the submit buttons of the form. Please use only one submit button for now.

__[fields](#fields)__
:   The definition of all form fields in a nested array.

__fieldsets__
:   Links to the fields grouped and ordered in fieldsets. Fields not in fieldsets will be appended at the end of the form.

<a id="mail_config"></a>
### Mail configuration

__mail_config__
:   Configuration variables for the e-mail function.

__subject__
:   Simple string with the subject of the email

__recipient__
:   Named array of `email` and `name` for the recipient, both keys are required. If the optional keys `formfield_email` and `formfield_name` exist they must contain the identifying key for an e-mail and a name field, and will be added as an extra recipient.

__sender__
:   Named array of `email` and `name` for the sender.

__bcc__
:   Named array of `email` and `name` for the bcc field.

__cc__
:   Named array of `email` and `name` for the cc field. If `email` and `name` are false the keys `formfield_email` and `formfield_name` are required and must contain the identifying key for an e-mail and a name field

__method__
:   E-mail send method. `mail` or `smtp`

__smtp__
:   Only used with smtp method. Named array with `login`, `password` and SMTP `server` values.

    $config['mail_config'] = array(
      'subject' => 'E-mail subject',
      'recipient' => array(
        'email' => 'formtomail@example.com',
        'name' => 'Formtomail'
      ),
      'sender' => array(
        'email' => 'formtomail@example.com',
        'name' => 'Formtomail'
      ),
      'bcc' => array(
        'email' => 'lodewijk@twokings.nl',
        'name' => 'Meerdanbeheer inschrijvingen - BCC'
    	),
      'cc' => array(
        'formfield_email' => 'email',
        'formfield_name' => 'name',
        'email' => false,
        'name' => false
    	),
      'method' => 'mail', // mail | smtp
      'smtp' => array(
        'login' => 'formtomail@example.com',
        'password' => 'password',
        'server' => 'mail.example.com'
      )
    );

<a id="fields"></a>
## Fields definition

Each field is a named array with all the options for the field that is defined

### name

The internal name of the field, will be used for the id, and should beunique in the form. In this example we'll use `examplefield`

### field types

__text__
:   default text input fields

__text_readonly__
:   readonly text input fields, make sure to set a default value

__textarea__
:   default textarea input fields

__select__
:   select box items, an array of options is required

__radios__
:   radio input fields, an array of options is required

__checkbox__
:   checkbox input fields

### label

The visible label of the field, will be placed next to the input field itself.

### requiredmessage

A message you want to display if the field is required and not entered.

### errormessage

A message you want to display if the entered value is incorrect.

### value

Yes you can enter a defaultvalue if you want to.

### validation

__required__
:   use this for required values

__ifany__
:   use this in combination with string for not-required (optional) string values

__string__
:   check if the input is a valid string, which means no unsafe html or scripts are included, this is the default for all fields

__integer__
:   check if the input is a valid number

__email__
:   check if the input is a valid e-mail address

_/regexppattern/_
:   you can use a regular expression if you want to test the input directly ft(not implemented, will be added later)__

__phonenumber__
:   at the moment this just checks if the string contains a valid numeric string with spaces, dashes or + signs

__zipcodenl__
:   checks for valid dutch zipcodes *9999 XX* or *9999XX*

__datetime__
:   checks for a datetime value *YYYY-MM-DD HH:MM:SS*


### listentoget

If you include `'listentoget'=>true` the form will use the get value for the field name `$_GET['examplefield']`

<a id="fmarkup"></a>
## Extra markup options

Every field, fieldset and the form itself can have a `pre_html` and/or a `post_html` keys where you can insert extra custom text before or after those elements.

These parameters also work for the smarty shortcuts.

    [[ contactform
    pre_html="<p>this will be shown before the form</p>"
    pre_html="<p>this will be shown after the form</p>"
    submit="Send message" ]]