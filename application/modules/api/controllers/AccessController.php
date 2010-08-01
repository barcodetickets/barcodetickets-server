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
		$this->view->response = array();
		$this->_validateTimestamp();
	}
}

