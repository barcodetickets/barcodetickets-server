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
	/**
	 * An instance of the Barcodes model.
	 *
	 * @var Bts_Model_Barcodes
	 */
	private $Barcodes = null;
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
	}
	protected function _emptyBarcode ()
	{
		$this->_response
			->setHttpResponseCode(400);
		$this->view->response = array(
			'statusCode' => 400 ,
			'statusText' => 'BARCODE_EMPTY');
	}
	protected function _invalidBarcode ()
	{
		$this->_response
			->setHttpResponseCode(404);
		$this->view->response = array(
			'statusCode' => 404 ,
			'statusText' => 'BAD_BARCODE');
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
		// TODO: process the decoded barcode
		if ($decoded === false) {
			return $this->_invalidBarcode();
		}
			// now $decoded should be { event, batch, ticket }
	}
	public function validateAction ()
	{
		$this->view->response = array();
		$this->_validateTimestamp();
	}
	public function validateBarcodeAction ()
	{
		$this->view->response = array();
		$barcodeString = $this->_getParam('barcode');
		if (! $this->_validateTimestamp() || ! $this->_validateSignature(array(
			'barcode' => $barcodeString ,
			'token' => $this->_getParam('token'))) || ! $this->_validateSession()) {
			return;
		}
		if (empty($barcodeString)) {
			return $this->_emptyBarcode();
		}
		$decoded = $this->Barcodes
			->decryptBarcode($barcodeString);
		// TODO: process the decoded barcode
		if ($decoded === false) {
			return $this->_invalidBarcode();
		}
		// now $decoded should be { event, batch, ticket }
		$this->view->response = $decoded;
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
