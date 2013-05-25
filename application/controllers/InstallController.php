<?php

/**
 * InstallController
 * 
 * @author
 * @version 
 */
class InstallController extends Zend_Controller_Action
{

	public function init ()
	{
		$this->_helper->layout->setLayout('installer');
	}

	/**
	 * The default action - show the installer
	 */
	public function indexAction ()
	{}

	public function testAction ()
	{
		$Installer = new Bts_Model_Installer();
		$this->view->tests = $Installer->testEnvironment();
		/*
		 * testReadable is a multidimensional array that maps tests to their
		 * human-readable name, text to show for pass/fail
		 */
		$this->view->testReadable = $Installer->tests;
	}
}
