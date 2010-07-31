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
