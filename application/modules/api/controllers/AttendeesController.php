<?php
/**
 * Attendees API methods for the API module
 *
 * @author	Frederick Ding
 * @version	$Id$
 */
class Api_AttendeesController extends Zend_Controller_Action
{
	public $contexts = array(
		'create' => true ,
		'exists' => true ,
		'balance' => true);
	public function init ()
	{
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->contextSwitch->initContext();
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
			'response' => array(
				'statusCode' => 404 ,
				'statusText' => 'API_METHOD_NOT_FOUND' ,
				'debug' => array(
					'controller' => $this->getRequest()->getControllerName() ,
					'action' => $this->getRequest()->getActionName() ,
					'params' => $this->getRequest()->getParams())));
		$this->_helper->formatResponse($responseArray);
	}
	public function createAction ()
	{
		if (! $this->auth->validateTimestamp($this->_getParam('timestamp'))) {
			$this->_response->setHttpResponseCode(400);
			$this->view->response = array(
				'statusCode' => 400 ,
				'statusText' => 'BAD_TIMESTAMP');
		}
	}
	public function existsAction ()
	{
		if (! $this->auth->validateTimestamp($this->_getParam('timestamp'))) {
			$this->_response->setHttpResponseCode(400);
			$this->view->response = array(
				'statusCode' => 400 ,
				'statusText' => 'BAD_TIMESTAMP');
		}
	}
	public function balanceAction ()
	{
		if (! $this->auth->validateTimestamp($this->_getParam('timestamp'))) {
			$this->_response->setHttpResponseCode(400);
			$this->view->response = array(
				'statusCode' => 400 ,
				'statusText' => 'BAD_TIMESTAMP');
		}
	}
}
