<?php
require 'ControllerAbstract.php';
/**
 * Tickets API methods for the API module
 *
 * @author	Frederick Ding
 * @version	$Id$
 * @package	Bts
 */
class Api_TicketsController extends Api_Controller_Abstract
{
	/**
	 * An instance of the Barcodes model.
	 *
	 * @var Bts_Model_Barcodes
	 */
	private $Barcodes = null;
	/**
	 * An instance of the Tickets model.
	 *
	 * @var Bts_Model_Tickets
	 */
	private $Tickets = null;
	public $contexts = array(
		'activate' => true,
		'activate-barcode' => true,
		'validate' => true,
		'validate-barcode' => true,
		'invalidate' => true,
		'invalidate-barcode' => true,
		'check-in' => true,
		'check-in-barcode' => true);
	public function init ()
	{
		parent::init();
		$this->Barcodes = Bts_Model_Barcodes::getInstance();
		$this->Tickets = new Bts_Model_Tickets();
	}
	public function activateAction ()
	{
		$this->view->response = array();
		// collect all the parameters we need
		$ticket = $this->_getParam('ticket');
		$batch = $this->_getParam('batch');
		$event = $this->_getParam('event');
		$checksum = $this->_getParam('checksum');
		$attendeeId = $this->_getParam('attendee');
		$token = $this->_getParam('token');
		// carry out authentication
		if (! $this->_validateTimestamp() || ! $this->_validateSignature(
		array(
			'ticket' => $ticket,
			'event' => $event,
			'batch' => $batch,
			'checksum' => $checksum,
			'attendee' => $attendeeId,
			'token' => $token)) || ! $this->_validateSession()) {
			return;
		}
		// verify the checksum
		$validChecksum = false;
		try {
			// first strtolower
			$checksum = strtolower($checksum);
			$validChecksum = $this->Barcodes->verifyChecksum($event, $batch,
			$ticket, $checksum);
		} catch (Bts_Exception $e) {
			if ($e->getCode() == Bts_Exception::BARCODES_EVENT_BAD)
				return $this->_simpleErrorResponse(404, 'BAD_EVENT');
			else
				return $this->_simpleErrorResponse(404, 'BAD_CHECKSUM');
		}
		if (! $validChecksum)
			return $this->_simpleErrorResponse(404, 'BAD_CHECKSUM');
			// TODO: add check for user permissions
	// TODO
	}
	public function activateBarcodeAction ()
	{
		$this->view->response = array();
		$barcodeString = $this->_getParam('barcode');
		$attendeeId = $this->_getParam('attendee');
		$token = $this->_getParam('token');
		if (! $this->_validateTimestamp() || ! $this->_validateSignature(
		array(
			'barcode' => $barcodeString,
			'attendee' => $attendeeId,
			'token' => $token)) || ! $this->_validateSession()) {
			return;
		}
		if (is_null($barcodeString)) {
			return $this->_simpleErrorResponse(400, 'BARCODE_EMPTY');
		}
		if (is_null($attendeeId)) {
			return $this->_simpleErrorResponse(400, 'ATTENDEE_EMPTY');
		}
		$decoded = $this->Barcodes->decryptBarcode($barcodeString);
		// now $decoded should be { event, batch, ticket }
		if ($decoded === false) {
			return $this->_simpleErrorResponse(404, 'BAD_BARCODE');
		}
		// TODO: check user ACL
		$activation = $this->Tickets->activate($decoded['event'],
		$decoded['ticket'], $attendeeId,
		$this->clientAuth->getSessionUser($token, $this->_getParam('sysName')));
		switch ($activation) {
			case $this->Tickets->getStatusCode('active'):
				$this->view->response = array(
					'statusCode' => 200,
					'statusText' => 'OK_ACTIVATED');
				break;
			case - 1:
				return $this->_simpleErrorResponse(404, 'FAILED_NOT_FOUND');
				break;
			case - 2:
				// API clients CANNOT rely on status code 200 to indicate success;
				// it only indicates the request is valid
				// the status message is very important!
				return $this->_simpleErrorResponse(
				200, 'FAILED_ALREADY_ACTIVE');
				break;
			case - 3:
				return $this->_simpleErrorResponse(404, 'BAD_ATTENDEE');
				break;
			default:
				$this->view->response = array(
					'statusCode' => 200,
					'statusText' => 'FAILED_STATUS_CHECK',
					'data' => array(
						'ticketStatusCode' => $activation,
						'ticketStatusText' => $this->Tickets->getStatusText(
						$activation)));
		}
	}
	/**
	 * Validates a given BTS ticket by checking provided variables against the
	 * database (the tickets table).
	 *
	 * BTS API authentication and a valid user session are required.
	 *
	 * This API method accepts 6 request parameters:
	 * - event
	 * - signature
	 * - sysName
	 * - ticket
	 * - timestamp
	 * - token
	 */
	public function validateAction ()
	{
		$this->view->response = array();
		$event = $this->_getParam('event');
		$ticket = $this->_getParam('ticket');
		$batch = $this->_getParam('batch');
		$checksum = $this->_getParam('checksum');
		// do our secure authentication scheme stuff
		if (! $this->_validateTimestamp() || ! $this->_validateSignature(
		array(
			'event' => $event,
			'batch' => $batch,
			'ticket' => $ticket,
			'checksum' => $checksum,
			'token' => $this->_getParam('token'))) || ! $this->_validateSession()) {
			return;
		}
		// make sure we have valid params
		if (empty($event)) {
			return $this->_simpleErrorResponse(400, 'EVENT_EMPTY');
		} elseif (empty($ticket)) {
			return $this->_simpleErrorResponse(400, 'TICKET_EMPTY');
		} elseif (empty($batch)) {
			return $this->_simpleErrorResponse(400, 'BATCH_EMPTY');
		} elseif (empty($batch)) {
			return $this->_simpleErrorResponse(400, 'CHECKSUM_EMPTY');
		}
		// run what we have through Bts_Model_Tickets::validate()
		$params = array(
			'event' => $event,
			'batch' => $batch,
			'ticket' => $ticket);
		// first strtolower
		$checksum = strtolower($checksum);
		$checksumValid = $this->Barcodes->verifyChecksum($event, $batch,
		$ticket, $checksum);
		if (! $checksumValid) {
			return $this->_simpleErrorResponse(404, 'BAD_CHECKSUM');
		}
		$validation = $this->Tickets->validate($params);
		if ($validation !== false) {
			$this->view->response = array(
				'statusCode' => 200,
				'statusText' => 'OK_VALID',
				'data' => array(
					'ticketStatusCode' => $validation->status,
					'ticketStatusMessage' => $this->Tickets->getStatusText(
					$validation->status)));
		} else {
			return $this->_simpleErrorResponse(404, 'FAILED_NOT_FOUND');
		}
	}
	/**
	 * Validates a given BTS ticket barcode by checking its format and by
	 * checking the actual data against the database (the tickets table).
	 *
	 * BTS API authentication and a valid user session are required.
	 *
	 * This API method accepts 5 request parameters:
	 * - barcode
	 * - signature
	 * - sysName
	 * - timestamp
	 * - token
	 */
	public function validateBarcodeAction ()
	{
		$this->view->response = array();
		$barcodeString = $this->_getParam('barcode');
		// everything related to authentication
		if (! $this->_validateTimestamp() || ! $this->_validateSignature(
		array(
			'barcode' => $barcodeString,
			'token' => $this->_getParam('token'))) || ! $this->_validateSession()) {
			return;
		}
		if (empty($barcodeString)) {
			return $this->_simpleErrorResponse(400, 'BARCODE_EMPTY');
		}
		// decrypt the barcode string
		$decoded = $this->Barcodes->decryptBarcode($barcodeString);
		if ($decoded === false) {
			return $this->_simpleErrorResponse(404, 'BAD_BARCODE');
		}
		// now $decoded should be { event, batch, ticket }
		// try running it through the Bts_Model_Tickets::validate() method
		$validation = $this->Tickets->validate($decoded);
		if ($validation !== false) {
			$this->view->response = array(
				'statusCode' => 200,
				'statusText' => 'OK_VALID',
				'data' => array(
					'ticketStatusCode' => $validation->status,
					'ticketStatusMessage' => $this->Tickets->getStatusText(
					$validation->status)));
		} else {
			return $this->_simpleErrorResponse(404, 'FAILED_NOT_FOUND');
		}
	}
	public function invalidateAction ()
	{
		$this->view->response = array();
		$token = $this->_getParam('token');
		$sysName = $this->_getParam('sysName');
		$event = $this->_getParam('event');
		$ticket = $this->_getParam('ticket');
		$reasonCode = $this->_getParam('reasonCode');
		// everything related to authentication
		if (! $this->_validateTimestamp() || ! $this->_validateSignature(
		array(
			'event' => $event,
			'ticket' => $ticket,
			'reasonCode' => $reasonCode,
			'token' => $token)) || ! $this->_validateSession()) {
			return;
		}
		$userId = $this->clientAuth->getSessionUser($token, $sysName);
			// TODO: check user permissions
	}
	public function invalidateBarcodeAction ()
	{
		$this->view->response = array();
		$this->_validateTimestamp();
			// TODO
	}
	public function checkInAction ()
	{
		$this->view->response = array();
		$event = $this->_getParam('event');
		$ticket = $this->_getParam('ticket');
		$batch = $this->_getParam('batch');
		$checksum = $this->_getParam('checksum');
		// do our secure authentication scheme stuff
		if (! $this->_validateTimestamp() || ! $this->_validateSignature(
		array(
			'event' => $event,
			'batch' => $batch,
			'ticket' => $ticket,
			'checksum' => $checksum,
			'token' => $this->_getParam('token'))) || ! $this->_validateSession()) {
			return;
		}
		// make sure we have valid params
		if (empty($event)) {
			return $this->_simpleErrorResponse(400, 'EVENT_EMPTY');
		} elseif (empty($ticket)) {
			return $this->_simpleErrorResponse(400, 'TICKET_EMPTY');
		} elseif (empty($batch)) {
			return $this->_simpleErrorResponse(400, 'BATCH_EMPTY');
		} elseif (empty($batch)) {
			return $this->_simpleErrorResponse(400, 'CHECKSUM_EMPTY');
		}
		// run what we have through Bts_Model_Tickets::checkIn()
		$params = array(
			'event' => $event,
			'batch' => $batch,
			'ticket' => $ticket);
		// first strtolower
		$checksum = strtolower($checksum);
		$checksumValid = $this->Barcodes->verifyChecksum($event, $batch,
		$ticket, $checksum);
		if (! $checksumValid) {
			return $this->_simpleErrorResponse(404, 'BAD_CHECKSUM');
		}
		$validation = $this->Tickets->checkIn($params);
		if ($validation !== false) {
			if (is_array($validation)) {
				// failed for some reason
				$this->view->responseXml = $this->view->responseJson = array(
					'statusCode' => 200,
					'statusText' => 'FAILED_CHECK_IN',
					'data' => array());
				// still give the <ticket> and <attendee> objects
				$this->view->responseJson['data']['ticket'] = $validation[0]->toArray();
				$this->view->responseXml['data']['ticket']['_attributes'] = $validation[0]->toArray();
				if (! empty($validation[0]->attendee_id)) {
					$Attendees = new Bts_Model_Attendees();
					$attendee = $Attendees->getById($validation[0]->attendee_id);
					$this->view->responseJson['data']['attendee'] = $attendee->toArray();
					$this->view->responseXml['data']['attendee']['_attributes'] = $attendee->toArray();
				}
			} else {
				// successful
				$this->view->responseXml = $this->view->responseJson = array(
					'statusCode' => 200,
					'statusText' => 'OK_CHECKED_IN',
					'data' => array());
				$this->view->responseJson['data']['ticket'] = $validation->toArray();
				$this->view->responseXml['data']['ticket']['_attributes'] = $validation->toArray();
				$Attendees = new Bts_Model_Attendees();
				$attendee = $Attendees->getById($validation->attendee_id);
				$this->view->responseJson['data']['attendee'] = $attendee->toArray();
				$this->view->responseXml['data']['attendee']['_attributes'] = $attendee->toArray();
			}
		} else {
			return $this->_simpleErrorResponse(404, 'FAILED_NOT_FOUND');
		}
	}
	public function checkInBarcodeAction ()
	{
		$this->view->response = array();
		$barcodeString = $this->_getParam('barcode');
		// everything related to authentication
		if (! $this->_validateTimestamp() || ! $this->_validateSignature(
		array(
			'barcode' => $barcodeString,
			'token' => $this->_getParam('token'))) || ! $this->_validateSession()) {
			return;
		}
		if (empty($barcodeString)) {
			return $this->_simpleErrorResponse(400, 'BARCODE_EMPTY');
		}
		// decrypt the barcode string
		$decoded = $this->Barcodes->decryptBarcode($barcodeString);
		if ($decoded === false) {
			return $this->_simpleErrorResponse(404, 'BAD_BARCODE');
		}
		// now $decoded should be { event, batch, ticket }
		// try running it through the Bts_Model_Tickets::validate() method
		$validation = $this->Tickets->checkIn($decoded);
		if ($validation !== false) {
			if (is_array($validation)) {
				// failed for some reason
				$this->view->responseXml = $this->view->responseJson = array(
					'statusCode' => 200,
					'statusText' => 'FAILED_CHECK_IN',
					'data' => array());
				// still give the <ticket> and <attendee> objects
				$this->view->responseJson['data']['ticket'] = $validation[0]->toArray();
				$this->view->responseXml['data']['ticket']['_attributes'] = $validation[0]->toArray();
				if (! empty($validation[0]->attendee_id)) {
					$Attendees = new Bts_Model_Attendees();
					$attendee = $Attendees->getById($validation[0]->attendee_id);
					$this->view->responseJson['data']['attendee'] = $attendee->toArray();
					$this->view->responseXml['data']['attendee']['_attributes'] = $attendee->toArray();
				}
			} else {
				// successful
				$this->view->responseXml = $this->view->responseJson = array(
					'statusCode' => 200,
					'statusText' => 'OK_CHECKED_IN',
					'data' => array());
				$this->view->responseJson['data']['ticket'] = $validation->toArray();
				$this->view->responseXml['data']['ticket']['_attributes'] = $validation->toArray();
				$Attendees = new Bts_Model_Attendees();
				$attendee = $Attendees->getById($validation->attendee_id);
				$this->view->responseJson['data']['attendee'] = $attendee->toArray();
				$this->view->responseXml['data']['attendee']['_attributes'] = $attendee->toArray();
			}
		} else {
			return $this->_simpleErrorResponse(404, 'FAILED_NOT_FOUND');
		}
	}
}
