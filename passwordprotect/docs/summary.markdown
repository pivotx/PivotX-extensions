
Password Protect Extension
==========================

This is an extension that adds Password Protection to certain pages or entries.
If enabled for a certain page or entry, the visitor will have to give the
password, otherwise he/she will not see the page. Passwords are case-sensitive,
but the *used username is irrelevant*. You can also select to allow access
(without giving the password) to users that are already logged into PivotX.

If you use this extension, it's good practice to let the visitor know that they
will be asked for a password, when they click a link. Inside your `[[subweblog]]`
you can do the following:

    [[if $entry.extrafields.passwordprotect!=""]]
        <strong>Note:</strong> This entry is password protected.
    [[/if]]
 
**Note:** Do not use this extension for really secret stuff. The contents of the
page or entry is still stored in plain text in the database, so a very dedicated
cracker might still find a way to get the information. Compare it to an ordinary
bikelock: It will prevent people from taking your bike, but a professional will
have the tools to break it open, and steal the bike anyways.
