<?php

/**
 * InstallController
 * 
 * @author
 * @version 
 */
class InstallController extends Zend_Controller_Action
{

	/**
	 * The default action - show the installer
	 */
	public function indexAction ()
	{
		// TODO
		$this->_helper->layout->setLayout('installer');
	}
}
