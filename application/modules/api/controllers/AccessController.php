<?php
require 'ControllerAbstract.php';
/**
 * Access (admin/user login) for the API module
 *
 * @author	Frederick Ding
 * @version $Id$
 * @todo	phpdoc this class
 * @package	Bts
 */
class Api_AccessController extends Api_Controller_Abstract
{
	public $contexts = array(
		'login' => true, 
		'logout' => true);
	/**
	 *
	 * @var Bts_Model_Users
	 */
	private $userAuth = null;
	public function init ()
	{
		parent::init();
		$this->userAuth = new Bts_Model_Users();
	}
	public function loginAction ()
	{
		$this->view->response = array();
		$sysName = $this->_getParam('sysName');
		$username = $this->_getParam('username');
		$password = $this->_getParam('password');
		if (! $this->_validateTimestamp() || ! $this->_validateSignature(
			array(
				'username' => $username, 
				'password' => $password))) {
			return;
		}
		if (empty($username) || empty($password)) {
			$this->_response->setHttpResponseCode(400);
			$this->view->response = array(
				'statusCode' => 400, 
				'statusText' => 'USERPASS_EMPTY');
			return;
		}
		$sessionId = $this->clientAuth->startSession($username, $password, 
			$sysName, $this->userAuth);
		if ($sessionId == '') {
			// failed authentication
			$this->_response->setHttpResponseCode(401);
			$this->view->response = array(
				'statusCode' => 401, 
				'statusText' => 'ACCESS_DENIED');
			return;
		}
		$this->view->response = array(
			'statusCode' => 200, 
			'statusText' => 'OK', 
			'data' => array(
				'token' => $sessionId));
	}
	public function logoutAction ()
	{
		$this->view->response = array();
		$sysName = $this->_getParam('sysName');
		$token = $this->_getParam('token');
		if (! $this->_validateTimestamp() || ! $this->_validateSignature(
			array(
				'token' => $token))) {
			return;
		}
		if (empty($token)) {
			$this->_response->setHttpResponseCode(400);
			$this->view->response = array(
				'statusCode' => 400, 
				'statusText' => 'BAD_TOKEN');
			return;
		}
		$result = $this->clientAuth->destroySession($token, $sysName);
		if ($result == false) {
			$this->_response->setHttpResponseCode(400);
			$this->view->response = array(
				'statusCode' => 400, 
				'statusText' => 'BAD_TOKEN');
			return;
		}
		$this->view->response = array(
			'statusCode' => 200, 
			'statusText' => 'OK_LOGGED_OUT');
	}
}

