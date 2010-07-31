<?php
/**
 * Events API methods for the API module
 *
 * @author	Frederick Ding
 * @version	$Id$
 */
require 'ControllerAbstract.php';
class Api_EventsController extends Api_Controller_Abstract
{
	public $contexts = array(
		'generate-tickets' => true ,
		'get-all' => true);
	private $clientAuth = null;
	public function init ()
	{
		parent::init();
		$this->clientAuth = new Api_Model_ClientAuthentication();
	}
	public function generateTicketsAction ()
	{
		$this->view->response = array();
		$this->_validateTimestamp();
	}
	public function getAllAction ()
	{
		$this->view->response = array();
		$this->_validateTimestamp();
	}
}
