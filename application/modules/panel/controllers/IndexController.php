<?php

/**
 * IndexController
 *
 * @author	Frederick Ding
 * @version	$Id$
 */
class Panel_IndexController extends Zend_Controller_Action
{

	/**
	 * The default action - show the home page
	 */
	public function indexAction ()
	{
		// send to homepage
		return $this->_helper->redirector->gotoSimpleAndExit('index', 'index', 
				'default');
	}
}
