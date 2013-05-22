<?php
require 'ControllerAbstract.php';

/**
 * Attendees API methods for the API module
 *
 * @author Frederick Ding
 * @version $Id$
 * @package Bts
 */
class Api_AttendeesController extends Api_Controller_Abstract
{

	/**
	 * An instance of the Attendees model.
	 * 
	 * @var Bts_Model_Attendees
	 */
	private $Attendees = null;

	public $contexts = array(
			'create' => true,
			'exists' => true,
			'find' => true,
			'balance' => true
	);

	public function init ()
	{
		parent::init();
		$this->Attendees = new Bts_Model_Attendees();
	}

	/**
	 * Creates a new attendee row in the database.
	 *
	 * BTS API authentication and a valid user session are required.
	 *
	 * If the uniqueId already exists in the database, the request will fail
	 * with
	 * a response code of 200 and the status text FAILED_NOT_UNIQUE.
	 *
	 * This API method accepts 6 request parameters:
	 * - firstName
	 * - lastName
	 * - signature
	 * - sysName
	 * - token
	 * - uniqueId
	 */
	public function createAction ()
	{
		$this->view->response = array();
		$firstName = $this->_getParam('firstName');
		$lastName = $this->_getParam('lastName');
		$uniqueId = $this->_getParam('uniqueId');
		$token = $this->_getParam('token');
		// carry out authentication
		if (! $this->_validateTimestamp() || ! $this->_validateSignature(
				array(
						'firstName' => $firstName,
						'lastName' => $lastName,
						'uniqueId' => $uniqueId,
						'token' => $token
				)) || ! $this->_validateSession()) {
			return;
		}
		try {
			$id = $this->Attendees->create($firstName, $lastName, $uniqueId);
		} catch (Bts_Exception $e) {
			// failed!
			return $this->_simpleErrorResponse(400, 'PARAMS_EMPTY');
		}
		if ($id == - 1) {
			// remember, 200 does not always been successful
			$this->view->response['statusCode'] = 200;
			$this->view->response['statusText'] = 'FAILED_NOT_UNIQUE';
			return;
		} else {
			// everything worked out!
			$this->view->response['statusCode'] = 200;
			$this->view->response['statusText'] = 'OK_CREATED';
			$this->view->response['data'] = array(
					'attendeeId' => $id
			);
		}
	}

	/**
	 * Searches for an attendee by name or by unique ID against the database
	 * (the attendees table).
	 *
	 * BTS API authentication and a valid user session are required.
	 *
	 * This API method accepts 6 request parameters:
	 * - uniqueId OR (firstName and lastName); if all 3 are supplied, signature
	 * will not validate
	 * - signature
	 * - sysName
	 * - token
	 */
	public function existsAction ()
	{
		$this->view->response = array();
		$firstName = $this->_getParam('firstName');
		$lastName = $this->_getParam('lastName');
		$uniqueId = $this->_getParam('uniqueId');
		$token = $this->_getParam('token');
		if (! $this->_validateTimestamp()) {
			return;
		}
		// demand ONLY uniqueId OR (firstName & lastName); this code
		// will not validate signatures that have all 3 encoded
		if (is_null($uniqueId)) {
			if (! $this->_validateSignature(
					array(
							'firstName' => $firstName,
							'lastName' => $lastName,
							'token' => $token
					)) || ! $this->_validateSession()) {
				return;
			}
		} elseif (! $this->_validateSignature(
				array(
						'uniqueId' => $uniqueId,
						'token' => $token
				)) || ! $this->_validateSession()) {
			return;
		}
		// non-existent until proven otherwise
		$exists = false;
		if (! empty($uniqueId)) {
			$exists = $this->Attendees->existsById($uniqueId);
		} else {
			try {
				$exists = $this->Attendees->existsByName($firstName, $lastName);
			} catch (Bts_Exception $e) {
				return $this->_simpleErrorResponse(400, 'NAME_EMPTY');
			}
		}
		// you either exist or you don't. there is no middle ground.
		if ($exists) {
			$this->view->response['statusCode'] = 200;
			$this->view->response['statusText'] = 'OK_EXISTS';
		} else
			return $this->_simpleErrorResponse(404, 'OK_NOT_FOUND');
	}

	public function findAction ()
	{
		$this->view->response = array();
		$firstName = $this->_getParam('firstName');
		$lastName = $this->_getParam('lastName');
		$uniqueId = $this->_getParam('uniqueId');
		$token = $this->_getParam('token');
		if (! $this->_validateTimestamp()) {
			return;
		}
		// demand ONLY uniqueId OR (firstName & lastName); this code
		// will not validate signatures that have all 3 encoded
		if (is_null($uniqueId)) {
			if (! $this->_validateSignature(
					array(
							'firstName' => $firstName,
							'lastName' => $lastName,
							'token' => $token
					)) || ! $this->_validateSession()) {
				return;
			}
		} elseif (! $this->_validateSignature(
				array(
						'uniqueId' => $uniqueId,
						'token' => $token
				)) || ! $this->_validateSession()) {
			return;
		}
		if (! empty($uniqueId)) {
			// search by unique ID
			$row = $this->Attendees->findById($this->_getParam('uniqueId'));
			if (! is_null($row)) {
				// we have to target JSON and XML separately
				$this->view->responseXml = array(
						'statusCode' => 200,
						'statusText' => 'OK_FOUND',
						'data' => array(
								'attendee' => array()
						)
				);
				$tmp = array(
						'_attributes' => $row->toArray()
				);
				$Tickets = new Bts_Model_Tickets();
				$tickets = $Tickets->getByAttendee($row->attendee_id);
				foreach ($tickets as $t) {
					$tmp['ticket'][]['_attributes'] = $t->toArray();
				}
				$this->view->responseXml['data']['attendee'][] = $tmp;
				$this->view->responseJson = array(
						'statusCode' => 200,
						'statusText' => 'OK_FOUND',
						'data' => array(
								$row->attendee_id => $row->toArray()
						)
				);
			} else {
				$this->_response->setHttpResponseCode(404);
				$this->view->response['statusCode'] = 404;
				$this->view->response['statusText'] = 'OK_NOT_FOUND';
			}
		} else {
			// search by name
			try {
				$row = $this->Attendees->findByName($firstName, $lastName);
			} catch (Bts_Exception $e) {
				return $this->_simpleErrorResponse(400, 'NAME_EMPTY');
			}
			if ($row instanceof Zend_Db_Table_Rowset_Abstract) {
				if ($row->count() > 0) {
					// again, we need to tailor the response to the format;
					// XML gets <attendee /> tags with attributes; JSON gets
					// array entries
					$this->view->responseXml = array(
							'statusCode' => 200,
							'statusText' => 'OK_FOUND',
							'data' => array(
									'attendee' => array()
							)
					);
					$this->view->responseJson = array(
							'statusCode' => 200,
							'statusText' => 'OK_FOUND',
							'data' => array()
					);
					foreach ($row as $r) {
						$this->view->responseJson['data'][$r->attendee_id] = $r->toArray();
						// only in XML provide tickets too
						$tmp = array(
								'_attributes' => $r->toArray()
						);
						$Tickets = new Bts_Model_Tickets();
						$tickets = $Tickets->getByAttendee($r->attendee_id);
						foreach ($tickets as $t) {
							$tmp['ticket'][]['_attributes'] = $t->toArray();
						}
						$this->view->responseXml['data']['attendee'][] = $tmp;
					}
				} else {
					$this->_response->setHttpResponseCode(404);
					$this->view->response['statusCode'] = 404;
					$this->view->response['statusText'] = 'OK_NOT_FOUND';
				}
			}
		}
	}

	public function balanceAction ()
	{
		$this->view->response = array();
		$this->_validateTimestamp();
	}
}
