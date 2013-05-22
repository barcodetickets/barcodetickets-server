<?php
require 'ControllerAbstract.php';

/**
 * Events API methods for the API module
 *
 * @author Frederick Ding
 * @version $Id$
 * @package Bts
 */
class Api_EventsController extends Api_Controller_Abstract
{

	public $contexts = array(
			'create' => true,
			'generate-tickets' => true,
			'get-all' => true
	);

	public function createAction ()
	{
		$this->view->response = array();
		$eventName = $this->_getParam('name');
		$token = $this->_getParam('token');
		$time = $this->_getParam('time');
		$sysName = $this->_getParam('sysName');
		// carry out authentication
		if (! $this->_validateTimestamp() || ! $this->_validateSignature(
				array(
						'event' => $eventName,
						'time' => $time,
						'token' => $token
				)) || ! $this->_validateSession()) {
			return;
		}
		if (empty($eventName)) {
			return $this->_simpleErrorResponse(400, 'EVENT_EMPTY');
		} else 
			if (empty($time)) {
				return $this->_simpleErrorResponse(400, 'TIME_EMPTY');
			}
		// determine who's doing the action
		$user = $this->clientAuth->getSessionUser($token, $sysName);
		$Events = new Bts_Model_Events();
		try {
			$eventId = $Events->createEvent($eventName, $time, $user);
		} catch (Bts_Exception $e) {
			return $this->_simpleErrorResponse(400, 'FAILED_CREATION');
		}
		$this->view->responseJson = array(
				'statusCode' => 200,
				'statusText' => 'OK',
				'data' => array(
						'event' => array(
								'name' => $eventName,
								'time' => $time,
								'id' => $eventId
						)
				)
		);
		$this->view->responseXml = array(
				'statusCode' => 200,
				'statusText' => 'OK',
				'data' => array(
						'event' => array(
								'_attributes' => array(
										'name' => $eventName,
										'time' => $time,
										'id' => $eventId
								)
						)
				)
		);
	}

	public function generateTicketsAction ()
	{
		$this->view->response = array();
		$eventId = $this->_getParam('event');
		$batchSize = $this->_getParam('batchSize');
		$token = $this->_getParam('token');
		$sysName = $this->_getParam('sysName');
		// carry out authentication
		if (! $this->_validateTimestamp() || ! $this->_validateSignature(
				array(
						'event' => $eventId,
						'batchSize' => $batchSize,
						'token' => $token
				)) || ! $this->_validateSession()) {
			return;
		}
		if (empty($eventId)) {
			return $this->_simpleErrorResponse(400, 'EVENT_EMPTY');
		} else 
			if (empty($batchSize)) {
				return $this->_simpleErrorResponse(400, 'BATCH_SIZE_EMPTY');
			}
		if ($batchSize > 100 || $batchSize < 1) {
			// only allow batches of 100 or smaller
			return $this->_simpleErrorResponse(400, 'BAD_BATCH_SIZE');
		}
		// determine who's doing the action
		$user = $this->clientAuth->getSessionUser($token, $sysName);
		$Events = new Bts_Model_Events();
		$result = $Events->generateBatch($eventId, $batchSize, $user, 
				new Bts_Model_Tickets());
		if ($result === false) {
			// failed
			return $this->_simpleErrorResponse(404, 'FAILED_EVENT_NOT_FOUND');
		} else 
			if (is_array($result)) {
				$this->view->responseJson = array(
						'statusCode' => 200,
						'statusText' => 'OK',
						'data' => array(
								'tickets' => $result
						)
				);
				$this->view->responseXml = array(
						'statusCode' => 200,
						'statusText' => 'OK',
						'data' => array(
								'ticket' => array()
						)
				);
				foreach ($result as $row) {
					$this->view->responseXml['data']['ticket'][]['_attributes'] = (array) $row;
				}
			}
	}

	public function getAllAction ()
	{
		$this->view->response = array();
		$this->_validateTimestamp();
	}
}
