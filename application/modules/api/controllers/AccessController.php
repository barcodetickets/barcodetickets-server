<?php
/**
 * Access (admin/user login) for the API module
 *
 * @author	Frederick Ding
 * @version $Id$
 */
require 'ControllerAbstract.php';
class Api_AccessController extends Api_Controller_Abstract
{
	public $contexts = array(
		'login' => true ,
		'test' => true);
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
		$sysName = $this->_getParam('sysName');
		$username = $this->_getParam('username');
		$password = $this->_getParam('password');
		if (! $this->_validateTimestamp() || ! $this->_validateSignature(array(
			'sysName' => $sysName ,
			'timestamp' => $this->_getParam('timestamp') ,
			'signature' => $this->_getParam('signature') ,
			'username' => $username ,
			'password' => $password))) {
			return;
		}
		$sessionId = $this->clientAuth->startSession($username, $password, $sysName);
		if ($sessionId == '') {
			// failed authentication
			$this->view->response = array(
				'statusCode' => 200 ,
				'statusText' => 'ACCESS_DENIED');
		}
		$this->view->response = array(
			'statusCode' => 200 ,
			'statusText' => 'OK' ,
			'data' => array(
				'sessionId' => $sessionId));
	}
}

