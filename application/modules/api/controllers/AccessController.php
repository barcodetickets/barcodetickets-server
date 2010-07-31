<?php
/**
 * Access (admin/user login) for the API module
 *
 * @author	Frederick Ding
 * @version $Id$
 */
require 'PasswordHash.php';
require 'ControllerAbstract.php';
class Api_AccessController extends Api_Controller_Abstract
{
	public $contexts = array(
		'login' => true ,
		'test' => true);
	private $userAuth = null;
	public function init ()
	{
		parent::init();
		$this->userAuth = new Bts_PasswordHash();
	}
	public function loginAction ()
	{
		$this->view->response = array();
		$this->_validateTimestamp();
	}
}

