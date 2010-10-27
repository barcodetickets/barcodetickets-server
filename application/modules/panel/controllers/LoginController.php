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
	}
	public function indexAction ()
	{
		$_redir = $this->_getParam('redirect', '');
		if ($this->AuthSession->loggedIn) {
			// no need
			if (! empty($_redir) && $_redir[0] == '/') {
				$this->_redirect($this->view->serverUrl() . $_redir);
			} else {
				// send them to the dashboard
				$this->_redirect(
				$this->view->serverUrl() . str_replace($this->view->baseUrl(),
				$this->view->serverPublicPath,
				$this->view->url(
				array(
					'module' => 'panel',
					'controller' => 'index',
					'action' => 'index'))));
			}
		}
		$this->view->username = $_username = preg_replace('/[^a-zA-Z0-9\s]/',
		'', $this->_getParam('username'));
		if (! empty($_redir) && $_redir[0] == '/')
			$this->view->assign('redirect', $_redir);
		else
			$this->view->assign('redirect', '');
		if ($this->_getParam('failed', false)) {
			$this->view->assign('loginError', true);
		} else {
			unset($this->view->loginError);
		}
	}
	public function logoutAction ()
	{
		$this->AuthSession->loggedIn = false;
		unset($this->AuthSession->userId);
	}
	public function processAction ()
	{
		$this->_helper->viewRenderer->setNoRender();
		$Auth = new Panel_Model_UserAuthentication();
		$_username = preg_replace('/[^a-zA-Z0-9\s]/', '',
		$this->_getParam('username'));
		$_password = $this->_getParam('password');
		$_redir = $this->_getParam('redirect');
		// validate username/password
		$Auth->setIdentity($_username)->setCredential($_password);
		try {
			$authResult = $Auth->authenticate();
		} catch (Zend_Auth_Adapter_Exception $e) {
			$this->AuthSession->loggedIn = false;
			if (! empty($_redir) && $_redir[0] == '/') {
				$this->_redirect(
				$this->view->serverUrl() . str_replace($this->view->baseUrl(),
				$this->view->serverPublicPath,
				$this->view->url(
				array(
					'module' => 'panel',
					'controller' => 'login',
					'action' => 'index')) . '?failed=1&username=' . $_username .
				 '&redirect=' . $_redir));
			} else {
				$this->_redirect(
				$this->view->serverUrl() . str_replace($this->view->baseUrl(),
				$this->view->serverPublicPath,
				$this->view->url(
				array(
					'module' => 'panel',
					'controller' => 'login',
					'action' => 'index')) . '?failed=1&username=' . $_username));
			}
		}
		if ($authResult->isValid()) {
			// SUCCESSFUL
			$this->AuthSession->loggedIn = true;
			$this->AuthSession->userId = $Auth->getResultRowObject()->user_id;
			if (! empty($_redir) && $_redir[0] == '/') {
				$this->_redirect($this->view->serverUrl() . $_redir);
			} else {
				// send them to the dashboard
				$this->_redirect(
				$this->view->serverUrl() . str_replace($this->view->baseUrl(),
				$this->view->serverPublicPath,
				$this->view->url(
				array(
					'module' => 'panel',
					'controller' => 'index',
					'action' => 'index'))));
			}
		} else {
			$this->AuthSession->loggedIn = false;
			if (! empty($_redir) && $_redir[0] == '/') {
				$this->_redirect(
				$this->view->serverUrl() . str_replace($this->view->baseUrl(),
				$this->view->serverPublicPath,
				$this->view->url(
				array(
					'module' => 'panel',
					'controller' => 'login',
					'action' => 'index')) . '?failed=1&username=' . $_username .
				 '&redirect=' . $_redir));
			} else {
				$this->_redirect(
				$this->view->serverUrl() . str_replace($this->view->baseUrl(),
				$this->view->serverPublicPath,
				$this->view->url(
				array(
					'module' => 'panel',
					'controller' => 'login',
					'action' => 'index')) . '?failed=1&username=' . $_username));
			}
		}
	}
}