<?php
/**
 * DebugController
 *
 * @author	Frederick Ding
 * @version $Id$
 */
class DebugController extends Zend_Controller_Action
{
	/**
	 * An instance of the Barcodes model.
	 * @var Bts_Model_Barcodes
	 */
	protected $barcodes = null;
	public function init ()
	{
		$this->_helper->viewRenderer
			->setNoRender();
		$this->barcodes = Bts_Model_Barcodes::getInstance();
	}
	/**
	 * The default action - show the home page
	 */
	public function indexAction ()
	{}
	public function testDecryptionAction ()
	{
		$barcode = $this->_getParam('barcode');
		var_dump($this->barcodes
			->decryptBarcode($barcode));
	}
	public function testEncryptionAction ()
	{
		$batch = $this->_getParam('batch');
		$ticket = $this->_getParam('ticket');
		try {
			$this->_response
				->setBody($this->barcodes
				->encryptBarcode(1, $batch, $ticket));
		} catch (Bts_Exception $e) {
			switch ($e->getCode()) {
				case Bts_Exception::BARCODES_EVENT_BAD:
					$this->_response
						->setBody('{ bad event ID }');
					break;
				case Bts_Exception::BARCODES_PARAMS_BAD:
					$this->_response
						->setBody('{ invalid params }');
					break;
			}
		}
	}
	public function testEncodeLabelAction ()
	{
		$batch = $this->_getParam('batch');
		$ticket = $this->_getParam('ticket');
		$this->_response
			->setBody($this->barcodes
			->encodeLabel(1, $batch, $ticket));
	}
	public function testDecodeLabelAction ()
	{
		$label = $this->_getParam('label');
		var_dump($this->barcodes
			->decodeLabel($label));
	}
}
