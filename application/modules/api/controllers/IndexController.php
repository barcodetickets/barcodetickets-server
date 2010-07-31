<?php
/**
 * IndexController for the API module
 *
 * @author	Frederick Ding
 * @version	$Id$
 */
class Api_IndexController extends Zend_Controller_Action
{
	public function init ()
	{
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->contextSwitch->initContext();
		if (is_null($this->_helper->contextSwitch->getCurrentContext())) {
			$this->_helper->contextSwitch->initContext('json');
		}
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
	public function testAction ()
	{
		$this->_helper->viewRenderer->setNoRender(false);
		$auth = new Api_Model_Authentication();
		$this->_response->setHeader('Content-Type', 'text/plain', true);
		$this->view->assign('timestamp', $auth->validateTimestamp($this->_getParam('timestamp')));
		$this->view->assign('signature', $auth->validateSignature($_SERVER['REQUEST_METHOD'], strtok($_SERVER['REQUEST_URI'], '?'), array(
			'sysName' => $this->_getParam('sysName') ,
			'signature' => $this->_getParam('signature') ,
			'timestamp' => $this->_getParam('timestamp'))));
	}
}

