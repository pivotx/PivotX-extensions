# Database logging

Database logging is only available for PivotX installations that are using the MySQL database.

You can enable the database log by adding the parmeter `logging='true'` to the `[[ contactform ]]`, the `[[ orderform ]]` and the `[[ sendtofriend ]]` tags.

In the PHP version of the configuration you can also add the parameter `'enable_logging'=>true` to the configuration array.

When you enable logging for the first form, a MysQL table will be created. In this table all forms will log the following fields:

* formbuilderlog_uid - a unique id, auto incrementing
* form_id - the form id from the form configuration
* submission_id - the random generated form submission id
* last_updated - when the form was submitted
* user_email - the email of the sender if available
* user_name - the name of the sender if available
* user_ip - the ip address of the user submitting the form
* user_hostname - the hostname
* user_browser - the browser user agent string
* form_fields - a serialized array of all fieldnames in the form
* form_values - a serialized array of all submitted values
* response - currently not used
* status - defaults to 'new', useful to distinguish if the entry had been modified