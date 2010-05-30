<?php

// Register 'contactform' as a smarty tag.
$PIVOTX['template']->register_function('contactform', 'smarty_contactform');

/**
 * Contactform
 *
 * @param array $params
 * @param object $smarty
 * @return unknown
 */
function smarty_contactform($params, &$smarty) {
		global $PIVOTX;

		$vars = $smarty->get_template_vars();
		$pageuri = get_default($vars['page']['uri'], $vars['entry']['uri']);
		$pagelink = get_default($vars['entry']['link'], $vars['page']['link']);

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

		$mail_config = array(
			'subject' => 'Contact form -'.$PIVOTX['config']->data['sitename'],
			'recipient' => array(
				'email' => 'contactform@example.com',
				'name' => 'Contact form'
			),
			'sender' => array(
				'email' => 'contactform@example.com',
				'name' => 'Contact form'
			),
			'method' => 'mail' // mail | smtp
		);

		$username = get_default($PIVOTX['db']->entry['user'] , $PIVOTX['pages']->currentpage['user']);
		//var_dump($username);

		$user = $PIVOTX['users']->getUser($username);

		//var_dump($user);

		if(!empty($params['to']) && isemail($params['to'])) {
			$mail_config['recipient']['email'] = $params['to'];
			debug('normal email recipient '.$mail_config['recipient']['email'] . " - " . $mail_config['recipient']['name']);
		} else {
			$mail_config['recipient']['email'] = $user['email'];
			$mail_config['recipient']['name'] = $user['nickname'];
			debug('automatic email recipient from pivot entry or page '.$mail_config['recipient']['email'] . " - " . $mail_config['recipient']['name']);
		}
		if(!empty($params['from']) && isemail($params['from'])) {
			$mail_config['sender']['email'] = $params['from'];
		} elseif($user['email']) {
			$mail_config['sender']['email'] = $user['email'];
			$mail_config['sender']['name'] = $user['nickname'];
		} else {
			$mail_config['sender']['email'] = $mail_config['recipient']['email'];
			$mail_config['sender']['name'] = $mail_config['recipient']['name'] . ' (contactform default)';
		}
		if(!empty($params['subject'])) {
			$mail_config['subject'] = $params['subject'];
		} else {
			$mail_config['subject'] = 'Contact form - '.$PIVOTX['config']->data['sitename'];
		}
		if(!empty($params['mailtemplate'])) {
			$mailtemplate = $params['mailtemplate'];
		} else {
			$mailtemplate = "contactform.mail.tpl.php";
		}
		if(!empty($params['confirmation'])) {
			$confirmation = $params['confirmation'];
		} else {
			$confirmation = "contactform.confirm.tpl.php";
		}
		if(!empty($params['redirect'])) {
			$redirect = $params['redirect'];
		} else {
			$redirect = false;
		}

		if(!empty($params['fields'])) {
			//debug('running custom form configuration for contactform-'.$pageuri);
			// cleanup fields into something usefull
			$fields = array();
			$rules = explode(';',$params['fields']);
			foreach($rules as $typerule) {
				$type = $labelval = $label = $rule = $key = false;
				list($type, $labelval) = explode(':', trim($typerule));
				list($label, $rule) = explode(',', trim($labelval));
				$key = $type ."_". preg_replace('/[^a-z0-9]/i','',trim(strtolower(strip_tags($label))));
				$required = stristr($rule, 'required')?true:false;
				if(!$required && !empty($rule) && !stristr($rule, 'ifany')) {
					$rule = 'ifany|'.$rule;
				}
				$fields[$key] = array(
					'name' => $key,
					'type' => $type,
					'label' => $label,
					'required' => $required,
					'validation' => $rule,
					'requiredmessage' => $label.' is a required field. Please enter a '.$label,
					'error' => 'Please enter a '.$label
				);
			}
		} else {
			$fields = array(
				'name' => array(
					'name' => 'text_name',
					'label' => 'Name',
					'type' => 'text',
					'requiredmessage' => 'This is a required field. Please enter a name',
					'validation' => 'string',
					'error' => 'Please enter a name'
				),
				'email' => array(
					'name' => 'text_email',
					'label' => 'E-mail address',
					'type' => 'text',
					'requiredmessage' => 'This is a required field. Please enter a valid e-mail address',
					'validation' => 'email',
					'error' => 'Please enter a correct e-mail address'
				),
				'message' => array(
					'name' => 'textarea_message',
					'label' => 'Message',
					'type' => 'textarea',
					'requiredmessage' => 'This is a required field. Please enter a message',
					'validation' => 'string',
					'error' => 'Please enter a message'
				)
			);
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
			'id' => 'contactform-'.$pageuri,
			'name' => 'contactform',
			'action' => $formaction,
			'templates' => array(
				'confirmation' => $confirmation, // filename in form overrides path or html string
				'elements' => 'formclass_defaulthtml.php',
				'mailreply' => $mailtemplate // filename in form overrides path or html string
			),
			'redirect' => $redirect, // url for redirect after successfull submission of form
			'method' => 'post', // get | post
			'encoding' => '', // multipart/form-data | empty
			'buttons' => $submit,
			'fields' => $fields,
			'mail_config' => $mail_config,
		);
		//var_dump($config);
		$form = new FormBuilder($config);
		$form->execute_form();
}