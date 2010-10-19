<?php
/**
 * IndexController for the API module
 *
 * @author	Frederick Ding
 * @version	$Id$
 * @package	Bts
 */
require 'ControllerAbstract.php';
class Api_IndexController extends Api_Controller_Abstract
{
	public function testAction ()
	{
		$this->_helper->viewRenderer->setNoRender(false);
		$this->_response->setHeader('Content-Type', 'text/plain', true);
		$this->view->assign('timestamp', 
			$this->clientAuth->validateTimestamp($this->_getParam('timestamp')));
		try {
			$message = $this->clientAuth->generateSignature(
				$_SERVER['REQUEST_METHOD'], $_SERVER['SERVER_NAME'], 
				strtok($_SERVER['REQUEST_URI'], '?'), 
				array(
					'sysName' => $this->_getParam('sysName'), 
					'signature' => $this->_getParam('signature'), 
					'timestamp' => $this->_getParam('timestamp')), null, true);
		} catch (Bts_Exception $e) {
			$message = 'Something went wrong: ' . $e->getMessage();
		}
		$this->view->assign('message', $message);
		try {
			$signature = $this->clientAuth->generateSignature(
				$_SERVER['REQUEST_METHOD'], $_SERVER['SERVER_NAME'], 
				strtok($_SERVER['REQUEST_URI'], '?'), 
				array(
					'sysName' => $this->_getParam('sysName'), 
					'signature' => $this->_getParam('signature'), 
					'timestamp' => $this->_getParam('timestamp')));
		} catch (Bts_Exception $e) {
			$signature = 'Something went wrong: ' . $e->getMessage();
		}
		$this->view->assign('signature', $signature);
		$this->view->assign('signature_match', 
			$this->clientAuth->validateSignature($_SERVER['REQUEST_METHOD'], 
				$_SERVER['SERVER_NAME'], strtok($_SERVER['REQUEST_URI'], '?'), 
				array(
					'sysName' => $this->_getParam('sysName'), 
					'signature' => $this->_getParam('signature'), 
					'timestamp' => $this->_getParam('timestamp'))));
	}
}

