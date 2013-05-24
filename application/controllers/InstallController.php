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
		/*
		 * testReadable is a multidimensional array that maps tests to their
		 * human-readable name, text to show if passed, and text to show if
		 * failed
		 */
		$this->view->testReadable = array(
				'php-version' => array(
						'PHP version',
						PHP_VERSION,
						PHP_VERSION
				),
				'php-safe' => array(
						'PHP safe mode',
						'off',
						'on'
				),
				'php-pear' => array(
						'PEAR',
						'installed',
						'not installed'
				),
				'ext-hash' => array(
						'Hash functions',
						'supported',
						'not supported'
				),
				'ext-mcrypt' => array(
						'mcrypt functions',
						'supported',
						'not supported'
				),
				'ext-pdo' => array(
						'PHP Data Objects',
						'supported',
						'not supported'
				),
				'ext-pdomysql' => array(
						'PDO MySQL',
						'supported',
						'not supported'
				),
				'ext-mysqli' => array(
						'MySQLi extension',
						'supported',
						'not supported'
				),
				'files-btsdist' => array(
						'bts.ini.dist file',
						'exists',
						'not found'
				),
				'files-dbdist' => array(
						'database.ini.dist',
						'exists',
						'not found'
				),
				'files-writable' => array(
						'Configuration files',
						'writable',
						'not writable'
				)
		);
	}
}
