<?php
/**
 * Attendees API methods for the API module
 *
 * @author	Frederick Ding
 * @version	$Id$
 */
require 'ControllerAbstract.php';
class Api_AttendeesController extends Api_Controller_Abstract
{
	public $contexts = array(
		'create' => true ,
		'exists' => true ,
		'balance' => true);
	private $clientAuth = null;
	public function init ()
	{
		parent::init();
		$this->clientAuth = new Api_Model_ClientAuthentication();
	}
	public function createAction ()
	{
		$this->view->response = array();
		$this->_validateTimestamp();
	}
	public function existsAction ()
	{
		$this->view->response = array();
		$this->_validateTimestamp();
	}
	public function balanceAction ()
	{
		$this->view->response = array();
		$this->_validateTimestamp();
	}
}
