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
		$this->_helper->viewRenderer->setNoRender();
		$this->view->assign('pageTitle', 'BTS &raquo; Installer');
		$this->view->headTitle('Installer');
	}
}
