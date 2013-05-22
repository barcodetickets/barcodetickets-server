<?php

/**
 * @author Frederick
 */
class Panel_LoginController extends Zend_Controller_Action
{

	public function init ()
	{
		Zend_Session::start();
		$this->AuthSession = new Zend_Session_Namespace('bts-auth');
		$this->view->assign('serverPublicPath', 
				Zend_Registry::get('bts-config')->get('serverPublicPath'));
		$this->_helper->layout()->setLayout('login');
	}

	public function indexAction ()
	{
		if (isset($this->AuthSession->loggedIn) && $this->AuthSession->loggedIn) {
			return $this->_helper->redirector('index', 'index', 'default');
		}
		$LoginForm = new Panel_Form_Login(
				array(
						'action' => $this->_helper->url->url()
				));
		if ($_SERVER['REQUEST_METHOD'] == 'POST' && $LoginForm->isValid($_POST)) {
			$formValues = $LoginForm->getValues();
			$Auth = new Panel_Model_UserAuthentication();
			$Auth->setIdentity($formValues['username'])->setCredential(
					$formValues['password']);
			try {
				$authResult = $Auth->authenticate();
				if ($authResult->isValid()) {
					return $this->_helper->redirector('index', 'index', 
							'default');
				} else {
					$LoginForm->addError(
							'Username/password authentication failed.');
				}
			} catch (Zend_Auth_Adapter_Exception $e) {
				$LoginForm->addError($e->getMessage());
			}
		}
		$this->view->assign('formObject', $LoginForm);
	}

	public function logoutAction ()
	{
		if (isset($this->AuthSession->loggedIn)) {
			$this->AuthSession->loggedIn = false;
		}
		if (isset($this->AuthSession->username)) {
			unset($this->AuthSession->username);
		}
		if (isset($this->AuthSession->userObject)) {
			unset($this->AuthSession->userObject);
		}
	}
}