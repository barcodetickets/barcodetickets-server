<?php

/**
 * @author Frederick
 *
 */
class Panel_Form_Generate extends Zend_Form
{

	private $_batchNumber = 1;

	public function __construct ($options = array())
	{
		$options['method'] = 'post';
		parent::__construct($options);
		$this->_batchNumber = $options['batchNumber'];
		$this->_initFields();
	}

	private function _initFields ()
	{
		$allowedCharsRegex = new Zend_Validate_Regex('/^[\d]*$/');
		$allowedCharsRegex->setMessage('Batch size must be an integer', 
				Zend_Validate_Regex::NOT_MATCH);
		$count = new Zend_Form_Element_Text(
				array(
						'label' => 'Size',
						'name' => 'count',
						'tabindex' => 1,
						'required' => true,
						'id' => 'input-count',
						'value' => '20',
						'validators' => array(
								$allowedCharsRegex
						)
				));
		$batchNumber = new Zend_Form_Element_Text(
				array(
						'label' => 'Batch #',
						'name' => 'batchnumber',
						'tabindex' => 2,
						'id' => 'input-batchnumber',
						'value' => '',
						'readonly' => true
				));
		if (isset($this->_batchNumber)) {
			$batchNumber->setValue($this->_batchNumber);
		}
		$submit = new Zend_Form_Element_Submit(
				array(
						'name' => 'submit',
						'tabindex' => 3,
						'label' => 'generate'
				));
		$this->addElement('hash', 'loginanticsrf', 
				array(
						'decorators' => array(
								'ViewHelper'
						)
				));
		$this->addElement($batchNumber, 'batchnumber')
			->addElement($count, 'count')
			->addElement($submit, 'submit');
	}
}