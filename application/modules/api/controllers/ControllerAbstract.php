<?php
/**
 * A specific kind of Zend_Controller_Action which contains methods used for
 * the BTS API.
 *
 * @author	Frederick Ding
 * @version	$Id$
 * @see		Zend_Controller_Action
 */
abstract class Api_Controller_Abstract extends Zend_Controller_Action
{
	/**
	 * An associative array indicating which actions support which contexts.
	 * @var array
	 */
	public $contexts = array();
	/**
	 * An instance of the client authentication model.
	 * @var Api_Model_ClientAuthentication
	 */
	protected $clientAuth = null;
	/**
	 * Sets up the controller per our needs, including activation of the Context
	 * Switch helper and instantiation of a client authentication model.
	 */
	public function init ()
	{
		$this->_helper->viewRenderer
			->setNoRender();
		$this->_helper->contextSwitch
			->initContext();
		if (is_null($this->_helper->contextSwitch
			->getCurrentContext())) {
			$this->_helper->contextSwitch
				->initContext('json');
		}
		$this->clientAuth = new Api_Model_ClientAuthentication();
	}
	/**
	 * Validates the timestamp provided in the request using the client
	 * authentication model; upon fail, sends an error response.
	 *
	 * All API methods requiring a valid timestamp must call this method.
	 */
	protected function _validateTimestamp ()
	{
		if (! $this->clientAuth
			->validateTimestamp($this->_getParam('timestamp', 0))) {
			$this->_response
				->setHttpResponseCode(400);
			$this->view->response = array(
				'statusCode' => 400 ,
				'statusText' => 'BAD_TIMESTAMP');
			return false;
		}
		return true;
	}
	/**
	 * Validates the signature provided in the request using the client
	 * authentication model; upon fail, sends an error response appropriate
	 * to the specific case of failure.
	 *
	 * All API methods requiring a valid signature must call this method.
	 * @param array $params Parameters to be included in the HMAC message
	 */
	protected function _validateSignature (array $params)
	{
		$validation = $this->clientAuth
			->validateSignature($_SERVER['REQUEST_METHOD'], $_SERVER['SERVER_NAME'], strtok($_SERVER['REQUEST_URI'], '?'), $params);
		if (! $validation) {
			$this->_response
				->setHttpResponseCode(403);
			$status = $this->clientAuth
				->clientStatus($this->_getParam('sysName'));
			if ($status === 1) {
				$this->view->response = array(
					'statusCode' => 403 ,
					'statusText' => 'BAD_SIGNATURE');
			} else if ($status === 0) {
				$this->view->response = array(
					'statusCode' => 403 ,
					'statusText' => 'CLIENT_NOT_ACTIVE');
			} else {
				$this->view->response = array(
					'statusCode' => 403 ,
					'statusText' => 'BAD_SYSNAME');
			}
			return false;
		}
		return true;
	}
	/**
	 * The default action on a non-existent action - sends a 404
	 * unless we know it has a JSON/XML extension signifying format.
	 * @param string $methodName
	 * @param array $args
	 */
	public function __call ($methodName, $args)
	{
		$this->_response
			->setHttpResponseCode(404);
		$responseArray = array(
			'statusCode' => 404 ,
			'statusText' => 'API_METHOD_NOT_FOUND' ,
			'debug' => array(
				'controller' => $this->getRequest()
					->getControllerName() ,
				'action' => $this->getRequest()
					->getActionName() ,
				'params' => $this->getRequest()
					->getParams()));
		$this->_helper
			->formatResponse($responseArray);
	}
}