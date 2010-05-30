<?php

// Register 'sendtofriend' as a smarty tag.
$PIVOTX['template']->register_function('sendtofriend', 'smarty_sendtofriend');

/**
 * Contactform
 *
 * @param array $params
 * @param object $smarty
 * @return unknown
 */
function smarty_sendtofriend($params, &$smarty) {
		global $PIVOTX;

		$vars = $smarty->get_template_vars();
		$pageuri = get_default($vars['page']['uri'], $vars['entry']['uri']);
		$pagelink = get_default($vars['entry']['link'], $vars['page']['link']);
		
		$sendtofriendlink = $PIVOTX['config']->get('canonical_host').get_default($pagelink, $pageuri);

		//debug("Doet nu contactform voor: REQUEST_URI:". $_SERVER["REQUEST_URI"] ." / link:". $pagelink ." / uri:".$pageuri);

		if(isset($params['showinweblog'])) {
			$formaction = $pagelink;
		} else {
			$formaction = $_SERVER["REQUEST_URI"];
		}
		//debug_printbacktrace();

		if(!in_array($PIVOTX['parser']->modifier['pagetype'], array('page', 'entry')) && !isset($params['showinweblog'])) {
			return;
		} elseif(!in_array($PIVOTX['parser']->modifier['pagetype'], array('page', 'entry')) && isset($params['showinweblog'])) {
			if(empty($params['to']) || !isemail($params['to'])) {
				return 'Formbuilder ERROR: the recipient must be set for this form';
			}
		}

		$username = get_default($PIVOTX['db']->entry['user'] , $PIVOTX['pages']->currentpage['user']);

		$user = $PIVOTX['users']->getUser($username);

		$mail_config = array(
		  'subject' => 'Send to friend - '.$PIVOTX['config']->data['sitename'],
		  'recipient' => array(
		    'formfield_email' => 'email',
		    'formfield_name' => 'name',
		    'email' => false,
		    'name' => false
		  ),
		  'sender' => array(
		    'formfield_email' => 'emailsender',
		    'formfield_name' => 'namesender',
		    'email' => false,
		    'name' => false

		  ),
		  'method' => 'mail'
		);

		if(!empty($params['cc']) && isemail($params['cc'])) {
			$mail_config['cc']['email'] = $params['cc'];
		}

		if(!empty($params['bcc']) && isemail($params['bcc'])) {
			$mail_config['bcc']['email'] = $params['bcc'];
		}

		if(!empty($params['subject'])) {
			$mail_config['subject'] = $params['subject'];
		}
		if(!empty($params['mailtemplate'])) {
			$mailtemplate = $params['mailtemplate'];
		} else {
			$mailtemplate = "sendtofriend.mail.tpl.php";
		}
		if(!empty($params['confirmation'])) {
			$confirmation = $params['confirmation'];
		} else {
			$confirmation = "sendtofriend.confirm.tpl.php";
		}
		if(!empty($params['redirect'])) {
			$redirect = $params['redirect'];
		} else {
			$redirect = false;
		}

		$fields = array(

			'name' => array(
				'name' => 'name',
				'label' => 'Naam ontvanger',
				'type' => 'text',
				'isrequired' => true,
				'requiredmessage' => 'Vul uw naam in',
				'validation' => 'string',
				'class' => 'default-value',
				'error' => 'Please enter a name'
			),
			'email' => array(
				'name' => 'email',
				'label' => 'Emailadres ontvanger',
				'type' => 'text',
				'isrequired' => true,
				'requiredmessage' => 'Vul een E-mailadres in',
				'validation' => 'email',
				'class' => 'default-value',
				'error' => 'Vul een E-mailadres in',
			),
			'namesender' => array(
				'name' => 'namesender',
				'label' => 'Uw eigen naam',
				'type' => 'text',
				'isrequired' => true,
				'requiredmessage' => 'Vul uw E-mailadres in',
				'validation' => 'string',
				'class' => 'default-value',
				'error' => 'Vul uw E-mailadres in',
			),
			'emailsender' => array(
				'name' => 'emailsender',
				'label' => 'Uw eigen email',
				'type' => 'text',
				'isrequired' => true,
				'requiredmessage' => 'Vul uw E-mailadres in',
				'validation' => 'email',
				'class' => 'default-value',
				'error' => 'Please enter a correct e-mail address',
			),
			'message' => array(
				'name' => 'message',
				'label' => 'Het bericht',
				'value' => 'Ik kwam deze pagina tegen, misschien is dit wel iets voor jou.'."\n\n".$sendtofriendlink,
				'type' => 'textarea',
				'isrequired' => true,
				'requiredmessage' => '&quot;Message&quot; is a required field. Please enter a message',
				'validation' => 'string',
				'error' => 'Please enter a message'
			),
		);
		

		if(!empty($params['defaultmessage'])) {
			$fields['message']['value'] = $params['defaultmessage'];
		}

		if(!empty($params['submit'])) {
			$submit = array(
				'verzenden' => array(
					'type' => 'submit',
					'label' => '',
					'value' => $params['submit']
				)
			);
		} else {
			$submit = array(
				'verzenden' => array(
					'type' => 'submit',
					'label' => '',
					'value' => 'Send message'
				)
			);
		}

		$config = array(
			'id' => 'sendtofriend-'.$pageuri,
			'name' => 'sendtofriend',
			'action' => $formaction,
			'templates' => array(
				'confirmation' => $confirmation, // filename in form overrides path or html string
				'elements' => 'formclass_defaulthtml.php',
				'mailreply' => $mailtemplate // filename in form overrides path or html string
			),

			'fieldsets' => array(
				'left' => array(
					'label' => 'Recipient',
					'fields' => array('name','email')
				),
				'right' => array(
					'label' => 'Your message',
					'fields' => array('namesender','emailsender','message'),
				),
			),
			'redirect' => $redirect, // url for redirect after successfull submission of form
			'method' => 'post', // get | post
			'encoding' => '', // multipart/form-data | empty
			'buttons' => $submit,
			'fields' => $fields,
			'mail_config' => $mail_config,
		);

		$form = new FormBuilder($config);
		$form->execute_form();
}