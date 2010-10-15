<?php

// Register 'orderform' as a smarty tag.
$PIVOTX['template']->register_function('orderform', 'smarty_orderform');

/**
 * Orderform
 *
 * @param array $params
 * @param object $smarty
 * @return unknown
 */
function smarty_orderform($params, &$smarty) {
		global $PIVOTX;

		$params = cleanParams($params);

		$vars = $smarty->get_template_vars();
		$pageuri = get_default($vars['page']['uri'], $vars['entry']['uri']);
		$pagelink = get_default($vars['entry']['link'], $vars['page']['link']);

		//debug("Doet nu orderform voor: REQUEST_URI:". $_SERVER["REQUEST_URI"] ." / link:". $pagelink ." / uri:".$pageuri);

		if(isset($params['showinweblog'])) {
			$formaction = $pagelink;
		} else {
			$formaction = $_SERVER["REQUEST_URI"];
		}

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

		$mail_config = array(
			'subject' => 'Order form -'.$PIVOTX['config']->data['sitename'],
			'recipient' => array(
				'email' => 'contactform@example.com',
				'name' => __('Order form')
			),
			'sender' => array(
				'email' => 'contactform@example.com',
				'name' => __('Order form')
			),
			'method' => 'mail' // mail | smtp
		);

		$username = get_default($PIVOTX['db']->entry['user'] , $PIVOTX['pages']->currentpage['user']);

		$user = $PIVOTX['users']->getUser($username);

		if(!empty($params['to']) && isemail($params['to'])) {
			$mail_config['recipient']['email'] = $params['to'];
			if(!empty($params['to_name']) && isemail($params['to_name'])) {
				$mail_config['recipient']['name'] = $params['to_name'];
			}
		} else {
			$mail_config['recipient']['email'] = $user['email'];
			$mail_config['recipient']['name'] = $user['nickname'];
		}
		if(!empty($params['from']) && isemail($params['from'])) {
			$mail_config['sender']['email'] = $params['from'];
			if(!empty($params['from_name']) && isemail($params['from_name'])) {
				$mail_config['sender']['name'] = $params['from_name'];
			}
		} else {
			$mail_config['sender']['email'] = $user['email'];
			$mail_config['sender']['name'] = $user['nickname'];
		}
		if(!empty($params['subject'])) {
			$mail_config['subject'] = $params['subject'];
		} else {
			$mail_config['subject'] = 'Orderform - '.$PIVOTX['config']->data['sitename'];
		}
		if(!empty($params['mailtemplate'])) {
			$mailtemplate = $params['mailtemplate'];
		} else {
			$mailtemplate = "orderform.mail.tpl.php";
		}
		if(!empty($params['confirmation'])) {
			$confirmation = $params['confirmation'];
		} else {
			$confirmation = "orderform.confirm.tpl.php";
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
		

		if(!empty($params['fields'])) {
			// cleanup fields into something usefull
			$fields = array();
			$rules = explode(';',$params['fields']);
			foreach($rules as $typerule) {
				$type = $labelval = $label = $rule = $key = false;
				list($type, $labelval) = explode(':', trim($typerule));
				list($label, $rule) = explode(',', trim($labelval));
				$key = preg_replace('/[^a-z0-9]/i','',trim(strtolower(strip_tags($label))));
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
					'requiredmessage' => sprintf(__("\"%s\" is a required field."), $label) . sprintf(__("Please enter a \"%s\""), $label),
					'error' => sprintf(__("Please enter a \"%s\""), $label)
				);
			}
		} else {
			$fields = array(
				'name' => array(
					'name' => 'name',
					'label' => __('Name'),
					'type' => 'text',
					'validation' => 'string',
					'requiredmessage' => sprintf(__("\"%s\" is a required field."), __('Name')) . sprintf(__("Please enter a \"%s\""), __('Name')),
					'error' => sprintf(__("Please enter a \"%s\""), __('Name'))
				),
				'email' => array(
					'name' => 'email',
					'label' => __('E-mail address'),
					'type' => 'text',
					'validation' => 'email',
					'requiredmessage' => sprintf(__("\"%s\" is a required field."), __('E-mail address')) . sprintf(__("Please enter a \"%s\""), __('E-mail address')),
					'error' => sprintf(__("Please enter a \"%s\""), __('E-mail address'))
				),
				'phone' => array(
					'name' => 'phone',
					'label' => __('Phone number'),
					'type' => 'text',
					'validation' => 'phonenumber',
					'requiredmessage' => sprintf(__("\"%s\" is a required field."), __('Phone number')) . sprintf(__("Please enter a \"%s\""), __('Phone number')),
					'error' => sprintf(__("Please enter a \"%s\""), __('Phone number'))
				),
				'address' => array(
					'name' => 'address',
					'label' => __('Street address'),
					'type' => 'text',
					'validation' => 'string',
					'requiredmessage' => sprintf(__("\"%s\" is a required field."), __('Street address')) . sprintf(__("Please enter a \"%s\""), __('Street address')),
					'error' => sprintf(__("Please enter a \"%s\""), __('Street address'))
				),
				'postcode' => array(
					'name' => 'postcode',
					'label' => __('Postal code'),
					'type' => 'text',
					'validation' => 'string',
					'requiredmessage' => sprintf(__("\"%s\" is a required field."), __('Postal code')) . sprintf(__("Please enter a \"%s\""), __('Postal code')),
					'error' => sprintf(__("Please enter a \"%s\""), __('Postal code'))
				),
				'city' => array(
					'name' => 'city',
					'label' => __('City'),
					'type' => 'text',
					'validation' => 'string',
					'requiredmessage' => sprintf(__("\"%s\" is a required field."), __('City')) . sprintf(__("Please enter a \"%s\""), __('City')),
					'error' => sprintf(__("Please enter a \"%s\""), __('City'))
				),
				'message' => array(
					'name' => 'message',
					'label' => __('Message'),
					'type' => 'textarea',
					'validation' => 'string',
					'requiredmessage' => sprintf(__("\"%s\" is a required field."), __('Message')) . sprintf(__("Please enter a \"%s\""), __('Message')),
					'error' => sprintf(__("Please enter a \"%s\""), __('Message'))
				)
			);
		}

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
					'value' => __('Send message')
				)
			);
		}


		$config = array(
			'id' => 'orderform-'.$pageuri,
			'name' => 'orderform',
			'action' => $formaction . '#orderform-' . $pageuri,
			'templates' => array(
				'confirmation' => $confirmation, // filename in form overrides path or html string
				'elements' => 'formclass_defaulthtml.php',
				'mailreply' => $mailtemplate // filename in form overrides path or html string
			),

			'fieldsets' => array(
				'sender-info' => array(
					'id' => 'personal',
					'label' => __('Personal info'),
					'fields' => array('name', 'email', 'phone')
				),
				'sender-address' => array(
					'id' => 'address',
					'label' => __('Address'),
					'fields' => array('address', 'postcode', 'city'),
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