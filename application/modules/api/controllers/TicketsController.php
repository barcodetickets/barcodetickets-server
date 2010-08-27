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
		'activate' => true ,
		'activate-barcode' => true ,
		'validate' => true ,
		'validate-barcode' => true ,
		'invalidate' => true ,
		'invalidate-barcode' => true ,
		'check-in' => true ,
		'check-in-barcode' => true);
	public function init ()
	{
		parent::init();
		$this->Barcodes = Bts_Model_Barcodes::getInstance();
		$this->Tickets = new Bts_Model_Tickets();
	}
	protected function _emptyBarcode ()
	{
		return $this->_simpleErrorResponse(400, 'BARCODE_EMPTY');
	}
	protected function _invalidBarcode ()
	{
		return $this->_simpleErrorResponse(404, 'BAD_BARCODE');
	}
	protected function _missingParam ($text)
	{
		return $this->_simpleErrorResponse(400, $text);
	}
	public function activateAction ()
	{
		$this->view->response = array();
		$this->_validateTimestamp();
	}
	public function activateBarcodeAction ()
	{
		$this->view->response = array();
		$barcodeString = $this->_getParam('barcode');
		if (! $this->_validateTimestamp() || ! $this->_validateSignature(array(
			'barcode' => $barcodeString ,
			'token' => $this->_getParam('token'))) || ! $this->_validateSession()) {
			return;
		}
		if (is_null($barcodeString)) {
			return $this->_emptyBarcode();
		}
		$decoded = $this->Barcodes
			->decryptBarcode($barcodeString);
		// now $decoded should be { event, batch, ticket }
		if ($decoded === false) {
			return $this->_invalidBarcode();
		}
			// TODO: process the decoded barcode
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
		// do our secure authentication scheme stuff
		if (! $this->_validateTimestamp() || ! $this->_validateSignature(array(
			'event' => $event ,
			'ticket' => $ticket ,
			'token' => $this->_getParam('token'))) || ! $this->_validateSession()) {
			return;
		}
		// make sure we have valid params
		if (empty($event)) {
			return $this->_missingParam('EVENT_EMPTY');
		} elseif (empty($ticket)) {
			return $this->_missingParam('TICKET_EMPTY');
		}
		// run what we have through Bts_Model_Tickets::validate()
		$params = array(
			'event' => $event ,
			'ticket' => $ticket);
		$validation = $this->Tickets
			->validate($params);
		if ($validation !== false) {
			$this->view->response = array(
				'statusCode' => 200 ,
				'statusText' => 'OK_VALID' ,
				'data' => array(
					'ticketStatusCode' => $validation->status ,
					'ticketStatusMessage' => $this->Tickets
						->getStatusText($validation->status)));
		} else {
			return $this->_simpleErrorResponse(404, 'OK_NOTFOUND');
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
		if (! $this->_validateTimestamp() || ! $this->_validateSignature(array(
			'barcode' => $barcodeString ,
			'token' => $this->_getParam('token'))) || ! $this->_validateSession()) {
			return;
		}
		if (empty($barcodeString)) {
			return $this->_emptyBarcode();
		}
		// decrypt the barcode string
		$decoded = $this->Barcodes
			->decryptBarcode($barcodeString);
		if ($decoded === false) {
			return $this->_invalidBarcode();
		}
		// now $decoded should be { event, batch, ticket }
		// try running it through the Bts_Model_Tickets::validate() method
		$validation = $this->Tickets
			->validate($decoded);
		if ($validation !== false) {
			$this->view->response = array(
				'statusCode' => 200 ,
				'statusText' => 'OK_VALID' ,
				'data' => array(
					'ticketStatusCode' => $validation->status ,
					'ticketStatusMessage' => $this->Tickets
						->getStatusText($validation->status)));
		} else {
			return $this->_simpleErrorResponse(404, 'OK_NOTFOUND');
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
		if (! $this->_validateTimestamp() || ! $this->_validateSignature(array(
			'event' => $event ,
			'ticket' => $ticket ,
			'reasonCode' => $reasonCode ,
			'token' => $token)) || ! $this->_validateSession()) {
			return;
		}
		$userId = $this->clientAuth
			->getSessionUser($token, $sysName);
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
