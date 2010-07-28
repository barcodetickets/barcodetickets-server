<?php
/**
 * Tickets API methods for the API module
 *
 * @author	Frederick Ding
 * @version	$Id$
 */
class Api_TicketsController extends Zend_Controller_Action
{
	public $contexts = array(
		'activate' => array(
			'xml' ,
			'json') ,
		'activate-barcode' => array(
			'xml' ,
			'json') ,
		'validate' => array(
			'xml' ,
			'json') ,
		'validate-barcode' => array(
			'xml' ,
			'json') ,
		'invalidate' => array(
			'xml' ,
			'json') ,
		'invalidate-barcode' => array(
			'xml' ,
			'json') ,
		'check-in' => array(
			'xml' ,
			'json') ,
		'check-in-barcode' => array(
			'xml' ,
			'json'));
	public function init ()
	{
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->contextSwitch()->addHeaders('json', array(
			'Cache-Control' => 'private,no-cache' ,
			'Expires' => 'Sat, 26 Jul 1997 05:00:00 GMT'))->addHeaders('xml', array(
			'Cache-Control' => 'private,no-cache' ,
			'Expires' => 'Sat, 26 Jul 1997 05:00:00 GMT'))->setCallback('xml', 'post', array(
			$this->_helper->formatResponse ,
			'xmlContext'))->setCallback('json', 'post', array(
			$this->_helper->formatResponse ,
			'jsonContext'))->initContext();
		if (is_null($this->_helper->contextSwitch->getCurrentContext())) {
			$this->_helper->contextSwitch->initContext('json');
		}
		$this->auth = new Api_Model_Authentication();
	}
	/**
	 * The default action on a non-existent action - send a 404
	 * unless we know it has a JSON/XML extension signifying format
	 */
	public function __call ($methodName, $args)
	{
		$this->_response->setHttpResponseCode(404);
		$responseArray = array(
			'statusCode' => 404 ,
			'statusText' => 'API_METHOD_NOT_FOUND' ,
			'debug' => array(
				'controller' => $this->getRequest()->getControllerName() ,
				'action' => $this->getRequest()->getActionName() ,
				'params' => $this->getRequest()->getParams()));
		$this->_helper->formatResponse($responseArray);
	}
	public function activateAction ()
	{
		if (! $this->auth->validateTimestamp($this->_getParam('timestamp'))) {
			$this->_response->setHttpResponseCode(400);
			$this->view->response = array(
				'statusCode' => 400 ,
				'statusText' => 'BAD_TIMESTAMP');
		}
	}
	public function activateBarcodeAction ()
	{
		if (! $this->auth->validateTimestamp($this->_getParam('timestamp'))) {
			$this->_response->setHttpResponseCode(400);
			$this->view->response = array(
				'statusCode' => 400 ,
				'statusText' => 'BAD_TIMESTAMP');
		}
	}
	public function validateAction ()
	{
		if (! $this->auth->validateTimestamp($this->_getParam('timestamp'))) {
			$this->_response->setHttpResponseCode(400);
			$this->view->response = array(
				'statusCode' => 400 ,
				'statusText' => 'BAD_TIMESTAMP');
		}
	}
	public function validateBarcodeAction ()
	{
		if (! $this->auth->validateTimestamp($this->_getParam('timestamp'))) {
			$this->_response->setHttpResponseCode(400);
			$this->view->response = array(
				'statusCode' => 400 ,
				'statusText' => 'BAD_TIMESTAMP');
		}
	}
	public function invalidateAction ()
	{
		if (! $this->auth->validateTimestamp($this->_getParam('timestamp'))) {
			$this->_response->setHttpResponseCode(400);
			$this->view->response = array(
				'statusCode' => 400 ,
				'statusText' => 'BAD_TIMESTAMP');
		}
	}
	public function invalidateBarcodeAction ()
	{
		if (! $this->auth->validateTimestamp($this->_getParam('timestamp'))) {
			$this->_response->setHttpResponseCode(400);
			$this->view->response = array(
				'statusCode' => 400 ,
				'statusText' => 'BAD_TIMESTAMP');
		}
	}
	public function checkInAction ()
	{
		if (! $this->auth->validateTimestamp($this->_getParam('timestamp'))) {
			$this->_response->setHttpResponseCode(400);
			$this->view->response = array(
				'statusCode' => 400 ,
				'statusText' => 'BAD_TIMESTAMP');
		}
	}
	public function checkInBarcodeAction ()
	{
		if (! $this->auth->validateTimestamp($this->_getParam('timestamp'))) {
			$this->_response->setHttpResponseCode(400);
			$this->view->response = array(
				'statusCode' => 400 ,
				'statusText' => 'BAD_TIMESTAMP');
		}
	}
}
