<?php
class IndexController extends Zend_Controller_Action
{
	/**
	 * @var Zend_Session_Namespace
	 */
	private $AuthSession = null;
	public function init ()
	{
		$this->AuthSession = new Zend_Session_Namespace('bts-auth');
		if (isset($this->AuthSession->loggedIn) && $this->AuthSession->loggedIn) {
			$this->view->assign('authSession', $this->AuthSession)->assign(
				'isLoggedIn', true);
		}
	}
	public function indexAction ()
	{
		// action body
	}
}

