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

		$params = cleanParams($params);

		$vars = $smarty->get_template_vars();
		
		if(!isset($params['link'])) {
				//debug('sendtofriend tries to determine the default link');
				
				$pageuri = get_default($vars['page']['uri'], $vars['entry']['uri']);
				$pagelink = get_default($vars['entry']['link'], $vars['page']['link']);
				
				$sendtofriendlink = $PIVOTX['config']->get('canonical_host').get_default($pagelink, $pageuri);
		} else {
				//debug('sendtofriend knows the default link by param');
				$pageuri = '';
				$pagelink = '';
				$sendtofriendlink = $params['link'];
		}

		//debug("Doet nu contactform voor: REQUEST_URI:". $_SERVER["REQUEST_URI"] ." / link:". $pagelink ." / uri:".$pageuri);

		if(isset($params['showinweblog'])) {
			$formaction = $pagelink;
		} else {
			$formaction = $_SERVER["REQUEST_URI"];
		}
		//debug_printbacktrace();

		if(in_array($PIVOTX['parser']->modifier['pagetype'], array('weblog')) && !isset($params['showinweblog'])) {
			debug("You might want to set a showinweblog parameter.");
			return;
		} elseif(!in_array($PIVOTX['parser']->modifier['pagetype'], array('page', 'entry')) && isset($params['showinweblog'])) {
			if(empty($params['to']) || !isemail($params['to'])) {
				return 'Formbuilder ERROR: the recipient must be set for this form';
			}
		} elseif(!in_array($PIVOTX['parser']->modifier['pagetype'], array('page', 'entry'))) {
			// does not do anything
			//debug("Continue at your own peril - you're not in page-and-entryland anymore");
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
				
		if(!empty($params['enable_logging'])&&$params['enable_logging']!='false') {
				debug('logging on');
			$enable_logging = true;
		} else {
			$enable_logging = false;
		}
				

		$fields = array(

			'name' => array(
				'name' => 'name',
				'label' => __('Name'),
				'type' => 'text',
				'isrequired' => true,
				'validation' => 'string',
				'class' => 'default-value',
				'requiredmessage' => sprintf(__("\"%s\" is a required field."), __('Name')) . sprintf(__("Please enter a \"%s\""), __('Name')),
				'error' => sprintf(__("Please enter a \"%s\""), __('Name'))
			),
			'email' => array(
				'name' => 'email',
				'label' => __('E-mail address'),
				'type' => 'text',
				'isrequired' => true,
				'validation' => 'email',
				'class' => 'default-value',
				'requiredmessage' => sprintf(__("\"%s\" is a required field."), __('E-mail address')) . sprintf(__("Please enter a \"%s\""), __('E-mail address')),
				'error' => sprintf(__("Please enter a \"%s\""), __('E-mail address'))
			),
			'namesender' => array(
				'name' => 'namesender',
				'label' => __('Name'),
				'type' => 'text',
				'isrequired' => true,
				'validation' => 'string',
				'class' => 'default-value',
				'requiredmessage' => sprintf(__("\"%s\" is a required field."), __('Name')) . sprintf(__("Please enter a \"%s\""), __('Name')),
				'error' => sprintf(__("Please enter a \"%s\""), __('Name'))
			),
			'emailsender' => array(
				'name' => 'emailsender',
				'label' => __('E-mail address'),
				'type' => 'text',
				'isrequired' => true,
				'validation' => 'email',
				'class' => 'default-value',
				'requiredmessage' => sprintf(__("\"%s\" is a required field."), __('E-mail address')) . sprintf(__("Please enter a \"%s\""), __('E-mail address')),
				'error' => sprintf(__("Please enter a \"%s\""), __('E-mail address'))
			),
			'message' => array(
				'name' => 'message',
				'label' => __('Message'),
				'value' => 'Ik kwam deze pagina tegen, misschien is dit wel iets voor jou.'."\n\n".$sendtofriendlink,
				'type' => 'hidden',
				'isrequired' => true,
				'validation' => 'string',
				'requiredmessage' => sprintf(__("\"%s\" is a required field."), __('Message')) . sprintf(__("Please enter a \"%s\""), __('Message')),
				'error' => sprintf(__("Please enter a \"%s\""), __('Message'))
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
					'class' => 'button',
					'label' => '',
					'value' => __('Send message')
				)
			);
		}

		$config = array(
			'id' => 'tellafriend-form' . $pageuri,
			'name' => 'tellafriend-form',
			'action' => $formaction . '#tellafriend-form' . $pageuri,
			'templates' => array(
				'confirmation' => $confirmation, // filename in form overrides path or html string
				'elements' => 'formclass_defaulthtml.php',
				'mailreply' => $mailtemplate // filename in form overrides path or html string
			),

			'fieldsets' => array(
				'sender-info' => array(
					'label' => __('Sender'),
					'fields' => array('namesender', 'emailsender')
				),
				'recipient-info' => array(
					'label' => __('Recipient'),
					'fields' => array('name', 'email'),
				),
			),
			'redirect' => $redirect, // url for redirect after successfull submission of form
			'method' => 'post', // get | post
			'encoding' => '', // multipart/form-data | empty
			'buttons' => $submit,
			'fields' => $fields,
			'mail_config' => $mail_config,
			'enable_logging'=> $enable_logging,
		);

		if(!empty($params['pre_html'])) {
			$config['pre_html'] = $params['pre_html'];
		}
		if(!empty($params['post_html'])) {
			$config['post_html'] = $params['post_html'];
		}


		$form = new FormBuilder($config);
		$form->execute_form();
}
