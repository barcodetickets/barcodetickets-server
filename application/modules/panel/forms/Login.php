<?php

/**
 * @author Frederick
 *
 */
class Panel_Form_Login extends Zend_Form
{

	private $_defaultInputDecorators = array(
			'ViewHelper',
			'HtmlTag'
	);

	public function __construct ($options = array())
	{
		$options['method'] = 'post';
		parent::__construct($options);
		$this->_initFields();
		$this->removeDecorator('htmltag');
		$this->addDecorator(
				new Zend_Form_Decorator_FormErrors(
						array(
								'markupListStart' => '',
								'markupListEnd' => '',
								'markupListItemStart' => '<ul class="error grid_5">',
								'markupListItemEnd' => '</ul>'
						)));
	}

	private function _initFields ()
	{
		$allowedCharsRegex = new Zend_Validate_Regex('/^[\w\.-]*$/');
		$allowedCharsRegex->setMessage(
				'Only alphanumeric characters, underscores, hyphens and periods may be used', 
				Zend_Validate_Regex::NOT_MATCH);
		$startEndRegex = new Zend_Validate_Regex('/^[\w].*[\w]$/');
		$startEndRegex->setMessage(
				'Usernames must start and end with alphanumeric characters', 
				Zend_Validate_Regex::NOT_MATCH);
		$username = new Zend_Form_Element_Text(
				array(
						'name' => 'username',
						'tabindex' => 1,
						'required' => true,
						'id' => 'input-username',
						'placeholder' => 'username',
						'validators' => array(
								new Zend_Validate_StringLength(
										array(
												'min' => 3,
												'max' => 45
										)),
								$allowedCharsRegex,
								$startEndRegex
						)
				));
		$password = new Zend_Form_Element_Password(
				array(
						'name' => 'password',
						'tabindex' => 2,
						'required' => true,
						'id' => 'input-password',
						'placeholder' => 'password'
				));
		$redir = new Zend_Form_Element_Hidden(
				array(
						'name' => 'redir',
						'id' => 'redir-to',
						'decorators' => array(
								'ViewHelper'
						)
				));
		$submit = new Zend_Form_Element_Submit(
				array(
						'name' => 'submit',
						'tabindex' => 3,
						'label' => 'login',
						'decorators' => array(
								'ViewHelper'
						)
				));
		$this->addElement('hash', 'loginanticsrf');
		$this->addElement($username, 'username');
		$this->addElement($password, 'password');
		$this->addElement($submit, 'submit');
		$this->addElement($redir, 'redir');
		$this->setElementDecorators($this->_defaultInputDecorators);
	}
}
