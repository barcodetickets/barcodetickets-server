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

	public function hashAction ()
	{
		$Installer = new Bts_Model_Installer();
		$hash = $Installer->generateHash();
		$this->view->hash = $hash;
		try {
			$config = $Installer->saveHash($hash);
		} catch (Bts_Exception $e) {
			if ($e->getCode() == Bts_Exception::INSTALLER_CONFIG_EXISTS) {
				$this->view->error = 'exists';
			} elseif ($e->getCode() == Bts_Exception::INSTALLER_CONFIG_WRITE_FAILURE) {
				$this->view->error = 'write';
			}
			$this->view->exception = $e;
			// generate the config string for manual user installation
			$config = $Installer->saveHash($hash, false, true);
			if ($config instanceof Zend_Config_Writer) {
				$this->view->config = $config->render();
			}
		}
	}
}
