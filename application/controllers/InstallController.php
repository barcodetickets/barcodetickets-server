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
		$this->view->tests = Bts_Model_Installer::testEnvironment();
	}
}
