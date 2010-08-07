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
		if (! $this->_validateTimestamp() || ! $this->_validateSignature(array(
			'sysName' => $this->_getParam('sysName') ,
			'timestamp' => $this->_getParam('timestamp') ,
			'signature' => $this->_getParam('signature')))) {
			return;
		}
		$this->view->response = array();
	}
}

