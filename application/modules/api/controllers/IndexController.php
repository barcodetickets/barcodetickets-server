<?php
/**
 * IndexController for the API module
 *
 * @author
 * @version
 */
class Api_IndexController extends Zend_Controller_Action
{
	public function init ()
	{
		$this->_helper->viewRenderer->setNoRender();
	}
	/**
	 * The default action - show the home page
	 */
	public function indexAction ()
	{

	}
}

