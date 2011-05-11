<?php
/**
 * @author Frederick
 *
 */
class Panel_Form_CreateEvent extends Zend_Form
{
	public function __construct ($options = array())
	{
		$options['method'] = 'post';
		parent::__construct($options);
		$this->_initFields();
	}
	private function _initFields ()
	{
		$name = new Zend_Form_Element_Text(
			array(
				'label' => 'Name',
				'name' => 'name',
				'tabindex' => 1,
				'required' => true,
				'id' => 'input-name',
				'value' => ''));
		$time = new Zend_Form_Element_Text(
			array(
				'label' => 'Time',
				'name' => 'time',
				'tabindex' => 2,
				'id' => 'input-time',
				'required' => true));
		$status = new Zend_Form_Element_Select(
			array(
				'label' => 'Status',
				'name' => 'status',
				'tabindex' => 3,
				'required' => true,
				'id' => 'input-status'));
		$status->addMultiOptions(
			array(
				0 => 'Inactive',
				1 => 'Active'));
		$submit = new Zend_Form_Element_Submit(
			array(
				'name' => 'submit',
				'tabindex' => 3,
				'label' => 'generate'));
		$this->addElement('hash', 'loginanticsrf',
			array(
				'decorators' => array(
					'ViewHelper')));
		$this->addElement($name, 'name')
			->addElement($time, 'time')
			->addElement($status, 'status')
			->addElement($submit, 'submit');
	}
}