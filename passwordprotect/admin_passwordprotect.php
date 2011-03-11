<?php
// - Extension: Password Protect
// - Version: 1.1.1
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: An extension that makes it possible to protect entries, pages or the complete site with a password. 
// - Date: 2011-03-10
// - Identifier: passwordprotect

global $passwordprotect_config;

$passwordprotect_config = array(
    'passwordprotect' => false,
    'passwordprotect_loggedin_access' => false,
    'passwordprotect_default' => "password",
    'passwordprotect_text' => __("This page requires a password to view. Please give the password."),
    'passwordprotect_noaccesstemplate' => "skinny/page_template.html",
    'passwordprotect_noaccesstitle' => __("You don't have access to this page"),
    'passwordprotect_noaccesstext' => __("You didn't provide the correct password to access the requested entry. If you've made a typo, go back, and try again. <br /><br />If you don't know the password, you could ask the owner of the website to get access to the entry."),
    'passwordprotect_userlist' => __("# Enter each user with password on a new line.\n# Use a hash to add comments or disable a user\n# Example: password|username\n"),
);


/**
 * Adds the hook for passwordprotectAdmin()
 *
 * @see passwordprotectAdmin()
 */
$this->addHook(
    'configuration_add',
    'passwordprotect',
    array("passwordprotectAdmin", "Password Protection")
);



/**
 * The configuration screen for Password protect
 *
 * @param unknown_type $form_html
 */
function passwordprotectAdmin(&$form_html) {
    global $PIVOTX, $passwordprotect_config;


    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_ADMIN);

    // When running for the first time, set the default options, if they are not available in config..
    foreach ($passwordprotect_config as $key => $value) {
        if ($PIVOTX['config']->get($key)==="") {
            $PIVOTX['config']->set($key, $value);
        }
    }
    

    $form = $PIVOTX['extensions']->getAdminForm('passwordprotect');

    $choices = array(
        __('No'), 
        __('Yes, but only pages and entries'),
        __('Yes, for the complete site')
    );

    $form->add( array(
        'name' => 'passwordprotect',
        'type' => 'select',
        'options' => $choices,
        'label' => __("Enable password protection"),
    ));

    $form->add( array(
        'type' => 'text',
        'size' => 20,
        'name' => 'passwordprotect_default',
        'label' => __("Default password"),
        'text' => __("This is the password that's requested when no other password is given."),
        'isrequired' => 1,
        'validation' => 'string|minlen=3|maxlen=20'        
    ));

    $form->add( array(
        'type' => 'text',
        'size' => 80,
        'name' => 'passwordprotect_text',
        'label' => __("Password question"),
        'text' => __("This is the text that people see, when they're being asked for the password."),
        'isrequired' => 1,
        'validation' => 'string|minlen=5|maxlen=80'        
    ));
    
    $form->add( array(
        'type' => 'checkbox',
        'name' => 'passwordprotect_loggedin_access',
        'label' => __("Access for logged in users"),
        'text' => makeJtip(__("Access for logged in users"),
            __("Yes, don't require a password from a user that is logged in."))
    ));
    
    $form->add( array(
       'type' => 'custom',
       'text' => "<tr><td colspan='3'><hr size='1' noshade='1' /></td></tr>"
        
    ));
    
    $form->add( array(
        'type' => 'text',
        'name' => 'passwordprotect_noaccesstemplate',
        'label' => __("'No access' template"),
        'value' => '',
        'error' => __("When the user is not granted access, show this template."),
        'text' => "",
        'size' => 40,
        'isrequired' => 1,
        'validation' => 'ifany|string|minlen=5|maxlen=80'
    ));    


    $form->add( array(
        'type' => 'text',
        'size' => 80,
        'name' => 'passwordprotect_noaccesstitle',
        'label' => __("'No access' title"),
        'text' => __("When the user is not granted access, show this title."),
        'isrequired' => 1,
        'validation' => 'string|minlen=2|maxlen=80'        
    ));

    $form->add( array(
        'type' => 'textarea',
        'size' => 80,
        'name' => 'passwordprotect_noaccesstext',
        'label' => __("'No access' text"),
        'text' => "<br />" . __("When the user is not granted access, show this text."),
        'isrequired' => 1,
        'validation' => 'string|minlen=5'        
    ));

    $form->add( array(
        'type' => 'text',
        'size' => 80,
        'name' => 'passwordprotect_allaccessip',
        'label' => __("All access ipnumbers"),
        'text' => __("These ipnumbers can access everything without password."),
        'isrequired' => 0,
        'validation' => 'string'        
    ));

    $form->add( array(
        'type' => 'textarea',
        'size' => 80,
        'name' => 'passwordprotect_userlist',
        'label' => __("Users"),
        'text' => '',
        'isrequired' => 0,
        'validation' => 'string'        
    ));

    $form->use_javascript(true);

    /**
     * Add the form to our (referenced) $form_html. Make sure you use the same key
     * as the first parameter to $PIVOTX['extensions']->getAdminForm
     */
    $form_html['passwordprotect'] = $PIVOTX['extensions']->getAdminFormHtml($form, $passwordprotect_config);

}


$this->addHook(
    'after_parse',
    'callback',
    "passwordprotectHook"
    );


/**
 * main: If enabled, ask for the password..
 */
function passwordprotectHook() {
    global $PIVOTX;

    $modifier = $PIVOTX['parser']->modifier;

    // Check if we need to test..
    if ( defined('PIVOTX_INWEBLOG') && ($PIVOTX['config']->get('passwordprotect') > 0) ) {

        // Abort here if the user is logged in and access should be given.
        if ($PIVOTX['config']->get('passwordprotect_loggedin_access') == 1) {
            if ($PIVOTX['session']->isLoggedIn()) {
                return;
            }
        }

        // Abort here if we only password protect pages/entries and we aren't viewing one 
        if (($PIVOTX['config']->get('passwordprotect') == 1) && 
                ($modifier['pagetype'] != "entry") && ($modifier['pagetype'] != "page")) {
            return;
        }

        $password_protected = false;

        if ($PIVOTX['config']->get('passwordprotect') == 2) {
            $password_protected = true;
            $page = array();
        } else if ($modifier['pagetype'] == "entry" || $modifier['pagetype'] == "page") {
            if ($modifier['pagetype'] == "entry") {
                $page = $PIVOTX['db']->read_entry($modifier['uri'], $modifier['date']);
            } else {
                $page = $PIVOTX['pages']->getPageByUri($modifier['uri']);
            }
            if ($page['extrafields']['passwordprotect'] == 1) {
                $password_protected = true;
            }
        }
        
        // If the page/entry has passwordprotect enabled..
        if ($password_protected) {
            // Display an errorpage if we're not allowed to view the page/entry..
            if (passwordcheck_login($page) == false) {
                Header("WWW-Authenticate: Basic realm=\"PivotX Protected Page\"");
                Header("HTTP/1.0 401 Unauthorized");
        
                // Make a fake page to show (with all values, but two, empty).
                foreach ($page as $key=>$value) {
                    $page[$key] = '';
                }
                $page['title'] = $PIVOTX['config']->get('passwordprotect_noaccesstitle');
                $page['introduction'] = $PIVOTX['config']->get('passwordprotect_noaccesstext');
        
                // Set the page in $smarty as an array, as well as separate variables.
                $PIVOTX['template']->assign('page', $page);
                foreach ($page as $key=>$value) {
                    $PIVOTX['template']->assign($key, $value);
                }
               
                $template = $PIVOTX['config']->get('passwordprotect_noaccesstemplate');
                
                // If the template isn't set, or doesn't exist..
                if ( ($template == "") || (!file_exists($PIVOTX['paths']['templates_path'].$template)) ) {
                    // .. we guesstimate a template, and show that..
                    $template = templateGuess('page');
                }
                                       
                // Render and show the template.
                echo $PIVOTX['template']->fetch($template);
                
                exit;
            }
            
        }
        
    }
    
}

/**
 * Helper function, checks for login..
 *
 */
function passwordcheck_login($page) {
    global $PIVOTX;

    if (trim($PIVOTX['config']->get('passwordprotect_allaccessip')) != '') {
        $allaccessip = trim($PIVOTX['config']->get('passwordprotect_allaccessip'));
        $ipnumbers = explode(',',$allaccessip);

        $granted = false;
        foreach($ipnumbers as $ipnumber) {
            $ipnumber = trim($ipnumber);

            if (substr($ipnumber,-1) == '.') {
                // simple pattern
                if (strpos($_SERVER['REMOTE_ADDR'],$ipnumber) !== false) {
                    $granted = true;
                }
            }
            else if ($ipnumber == $_SERVER['REMOTE_ADDR']) {
                $granted = true;
            }
        }

        if ($granted === true) {
            return true;
        }
    }

    $user = $_SERVER['PHP_AUTH_USER'];
    $passed_password = $_SERVER['PHP_AUTH_PW'];

    $default_password = $PIVOTX['config']->get('passwordprotect_default');

    if (trim($PIVOTX['config']->get('passwordprotect_userlist')) != '') {
        $lines = explode("\n",trim($PIVOTX['config']->get('passwordprotect_userlist')));
        foreach($lines as $line) {
            $line = trim($line);

            if ((substr($line,0,1) != '#') && (strpos($line,'|') !== false)) {
                list($pwd,$usr) = explode('|',$line,2);
                $pwd = trim($pwd);
                $usr = trim($usr);

                if ($user == $usr) {
                    $default_password = $pwd;
                    break;
                }
            }
        }
    }

    if (is_array($page) && (isset($page['extrafields'])) && (isset($page['extrafields']['password']))) {
        $password = getDefault($page['extrafields']['password'], $default_password);
    }
    else {
        $password = $default_password;
    }

    if (($password == '') || ($password == 'password')) {
        // it's not possible to leave the password blank or use the password 'password'
        return false;
    }

    // debug('check for pass: ' . $passed_password . " = ". $password);
  
    if ($passed_password == $password) {
        return true;
    } else {
        return false;        
    }
}



/**
 * Extra fields to edit the password..
 */
$this->addHook(
    'in_pivotx_template',
    'entry-bottom',
    array('callback' => 'extraPasswordFields' )
    );

/**
 * Extra fields to edit the password..
 */
$this->addHook(
    'in_pivotx_template',
    'page-bottom',
    array('callback' => 'extraPasswordFields' )
    );



function extraPasswordFields($page) {

    $output = <<< EOM
    <table class="formclass" border="0" cellspacing="0" width="650">
        <tbody>
            <tr>
            <td colspan="3"><hr noshade="noshade" size="1" /></td></tr>
            
            <tr>
                <td width="150">
                    <label><strong>%header1%:</strong></label>
                </td>
                <td width="50">
                    <input type='checkbox' value='1' name='extrafields[passwordprotect]' %passwordprotect-checked% /> 
                </td>
                <td width="450">
                    %confirm% 
                </td>
            </tr>
            <tr>
                <td>
                    <label><strong>%header2%:</strong></label>
                </td>
                <td colspan="2">
                    <input id="extrafield-password" name="extrafields[password]" value="%password%" type="text" /><br />
                    <small>%desc%</small>
                </td>
            </tr>
        
        </tbody>
    </table>
EOM;

    // Substitute some labels..
    $output = str_replace("%header1%", __("Password Protect"), $output);
    $output = str_replace("%confirm%", __("Yes, protect this page with a password."), $output);
    $output = str_replace("%header2%", __("Password"), $output);
    $output = str_replace("%desc%", __("Specify the required password, or leave blank to use the default password."), $output);

    // For ease of use, just try to replace everything in $page here:
    foreach($page as $key=>$value) {
        if (is_array($value)) {
            // Currently ignore
        } else {
            $output = str_replace("%".$key."%", $value, $output);
        }
    }
    if (isset($page['extrafields'])) {
        foreach($page['extrafields'] as $key=>$value) {
            $output = str_replace("%".$key."%", $value, $output);
        }

        if (!empty($page['extrafields']['passwordprotect'])) {
            $output = str_replace('%passwordprotect-checked%', 'checked="checked"', $output);
        }
    }

    // Don't keep any %whatever%'s hanging around..
    $output = preg_replace("/%([a-z0-9_-]+)%/i", "", $output);

    return $output;    
    
}

?>
