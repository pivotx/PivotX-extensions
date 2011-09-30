<?php
// - Extension: Pivotmail
// - Version: 0.03
// - Author: Two Kings // Marcel Wouters // Lodewijk Evers
// - Email: marcel@twokings.nl // lodewijk@twokings.nl
// - Site: http://twokings.nl/
// - Updatecheck: none
// - Description: Pivotx mail extension
// - Date: 2011-05-17
// - Identifier: pivotmail

class pivotmail {
    protected static $templates = false;
    protected static $from_name = false;
    protected static $from_email = false;
    protected static $headers = '';

    protected static function init() {
        global $PIVOTX;

        if (self::$from_name === false) {
            if ($PIVOTX['config']->get('pivotmail_fromname') != '') {
                self::$from_name = $PIVOTX['config']->get('pivotmail_fromname');
            }
            else {
                self::$from_name = $PIVOTX['config']->get('sitename');
            }
        }
        if (self::$from_email === false) {
            if ($PIVOTX['config']->get('pivotmail_fromemail') != '') {
                self::$from_email = $PIVOTX['config']->get('pivotmail_fromemail');
            }
            else {
                $domain = str_replace(array('http://','https://'),'',$PIVOTX['paths']['canonical_host']);
                if (substr($domain,0,4) == 'www.') {
                    $domain = substr($domain,4);
                }

                self::$from_email = 'info@'.$domain;
            }
        }
    }

    public static function set_sender($name, $email) {
        self::init();
        self::$from_email = $email;
        self::$from_name = $name;
    }
    
    public static function add_header($header) {
        self::init();
        self::$headers .= $header ."\n";
    }

    public static function read_mail_template($name) {
        self::init();

        if (self::$templates === false) {
            self::$templates = array();
        }

        if (!isset(self::$templates[$name]) && !file_exists($name)) {
            $fname = dirname(dirname(dirname(__FILE__))).'/templates/mailtemplates/' . $name;
            if(file_exists($fname)) {
                self::$templates[$name] = file_get_contents($fname);
            } else {
                // it died! the mess is horrible
                debug('something went wrong while reading the mail template, the requested template ('.$name.') seems to be missing.');
                self::$templates[$name] = $name;
            }
        } else {
            self::$templates[$name] = file_get_contents($name);
        }

        return self::$templates[$name];
    }

    /**
     * Send a SMTP mail
     */
    public static function mail($to_name, $to_email, $template, $macros=false, $bccself=false) {
        self::init();

        if (($bccself === true) && ($to_email === false)) {
            $to_name  = self::$from_name;
            $to_email = self::$from_email;

            $bccself = false;
        }

        if (is_array($macros)) {
            $template = strtr($template, $macros);
        }

        if (preg_match('|(.+)====+(.+)|s',$template,$match)) {
            $header   = $match[1];
            $template = trim($match[2]);

            if (preg_match_all('|([^:]+):([^\r\n]+)|',$header,$matches)) {
                for($i=0; $i < count($matches[0]); $i++) {
                    $hdr = strtolower(trim($matches[1][$i]));
                    $txt = trim($matches[2][$i]);

                    switch ($hdr) {
                        case 'subject':
                            $subject = $txt;
                            break;
                    }
                }
            }
        }

        $headers  = self::$headers;
        $headers .= 'From: '.self::$from_name.' <'.self::$from_email.'>'."\n";
        $recipient = $to_name.' <'.$to_email.'>';

        $debug_output = 'Mail to: ' . htmlspecialchars($to_email) . '<br/>'."\n";
        $debug_output .= 'Subject: ' . htmlspecialchars($subject) . '<br/>'."\n";
        $debug_output .= 'Headers:<br/>'."\n";
        $debug_output .= '<pre>' . trim(htmlspecialchars($headers)) . '</pre>'."\n";
        $debug_output .= 'Message:<br/>'."\n";
        $debug_output .= '<pre>' . htmlspecialchars($template) . '</pre>'."\n";
       // debug($debug_output);

        if(!empty($to_email) && !empty($template) && !empty($subject)) {
            $ret = mail($recipient, $subject, $template, $headers);

            if ($bccself) {
                mail(self::$from_email,'Copy: '.$subject,$template,$headers);
            }

            return $ret;
        }
        if(empty($to_email)){
            debug('mail recipient not set');
        }
        if(empty($template)){
            debug('mail content not set');
        }
        if(empty($subject)) {
            debug('mail subject not set');
        }
        debug('something went wrong while sending mail, check if all required fields are set');
        return false;
    }
}
