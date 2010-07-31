<?php
/**
 * Tickets API methods for the API module
 *
 * @author	Frederick Ding
 * @version	$Id$
 */
require 'ControllerAbstract.php';
class Api_TicketsController extends Api_Controller_Abstract
{
	public $contexts = array(
		'activate' => true ,
		'activate-barcode' => true ,
		'validate' => true ,
		'validate-barcode' => true ,
		'invalidate' => true ,
		'invalidate-barcode' => true ,
		'check-in' => true ,
		'check-in-barcode' => true);
	private $clientAuth = null;
	public function init ()
	{
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->contextSwitch->initContext();
		if (is_null($this->_helper->contextSwitch->getCurrentContext())) {
			$this->_helper->contextSwitch->initContext('json');
		}
		$this->clientAuth = new Api_Model_ClientAuthentication();
	}
	public function activateAction ()
	{
		$this->view->response = array();
		$this->_validateTimestamp();
	}
	public function activateBarcodeAction ()
	{
		$this->view->response = array();
		$this->_validateTimestamp();
	}
	public function validateAction ()
	{
		$this->view->response = array();
		$this->_validateTimestamp();
	}
	public function validateBarcodeAction ()
	{
		$this->view->response = array();
		$this->_validateTimestamp();
	}
	public function invalidateAction ()
	{
		$this->view->response = array();
		$this->_validateTimestamp();
	}
	public function invalidateBarcodeAction ()
	{
		$this->view->response = array();
		$this->_validateTimestamp();
	}
	public function checkInAction ()
	{
		$this->view->response = array();
		$this->_validateTimestamp();
	}
	public function checkInBarcodeAction ()
	{
		$this->view->response = array();
		$this->_validateTimestamp();
	}
}
