<?php
/**
 * Barcodes model.
 *
 * Contains methods for producing and interpreting ticket barcodes. See the
 * wiki on this project's Trac to determine the barcode specifications.
 *
 * @author	Frederick Ding
 * @version $Id$
 * @package	Bts
 */
class Bts_Model_Barcodes
{
	const CIPHER = MCRYPT_RIJNDAEL_256;
	const MODE = MCRYPT_MODE_ECB;
	protected static $_instance = null;
	/**
	 * An instance of the bts_events DbTable.
	 * @var Bts_Model_DbTable_Events
	 */
	protected $EventsTable = null;
	/**
	 * Constructs this model.
	 */
	protected function __construct ()
	{
		$this->EventsTable = new Bts_Model_DbTable_Events();
	}
	/**
	 * Retrieves the secure_hash of a given event from the database.
	 *
	 * @param int $eventId
	 * @param boolean $binary (optional)
	 * @return string|false secure_hash as a binary string or false if not found
	 */
	protected function _retrieveEventHash ($eventId, $binary = true)
	{
		$eventId = (int) $eventId;
		$hash = $this->EventsTable
			->select(false)
			->from($this->EventsTable, 'secure_hash')
			->where('event_id = ?', $eventId)
			->limit(1)
			->query()
			->fetch(Zend_Db::FETCH_COLUMN);
		if ($binary) return $hash;
		else return bin2hex($hash);
	}
	/**
	 * Retrieves an instance of this class (implements singleton pattern).
	 *
	 * @return Bts_Model_Barcodes
	 */
	public static function getInstance ()
	{
		if (is_null(self::$_instance)) {
			self::$_instance = new Bts_Model_Barcodes();
		}
		return self::$_instance;
	}
	/**
	 * Decrypts the provided data as a BTS barcode string and returns an array
	 * containing all the data extracted from it.
	 *
	 * @param string $barcodeString
	 * @return array|false Array with the event, batch and ticket, or false on failure
	 */
	public function decryptBarcode ($barcodeString)
	{
		$parts = array();
		if (! preg_match('/^([0-9]+);([0-9]+);([0-9a-zA-Z\+\/=]+)$/', $barcodeString, $parts)) {
			// did not fit pattern; false signifies failure
			return false;
		}
		/*
		 * at this point $parts will contain:
		 * [0] => full $barcodeString
		 * [1] => eventId
		 * [2] => batchId
		 * [3] => encrypted portion
		 */
		$eventHash = $this->_retrieveEventHash($parts[1]);
		// if an eventHash cannot be found, we can't proceed with decryption
		if ($eventHash === false || empty($eventHash)) return false;
		// DECRYPT
		$decryptedString = trim(mcrypt_decrypt(self::CIPHER, $eventHash, base64_decode($parts[3]), self::MODE));
		$result = array();
		if (! preg_match('/^([0-9]+)-([0-9]+)-([0-9]+)$/', $decryptedString, $result)) {
			// decryption did not fit pattern; probably junk -- failure
			return false;
		}
		if ($result[1] != $parts[1] || $result[2] != $parts[2]) {
			// results of decryption produce different data from the plaintext wrapper text
			// = failure
			return false;
		}
		/*
		 * $result currently contains
		 * [0] => $decryptedString
		 * [1] => eventId
		 * [2] => batchId
		 * [3] => ticketId
		 */
		return array(
			'event' => $result[1] ,
			'batch' => $result[2] ,
			'ticket' => $result[3]);
	}
	/**
	 * Encrypts the provided data and outputs a string containing the data to go
	 * in the barcode according to BTS specifications.
	 *
	 * @param int $eventId
	 * @param int $batchId
	 * @param int $ticketId
	 * @param string $eventHash (optional)
	 * @throws Bts_Exception
	 * @return string
	 */
	public function encryptBarcode ($eventId, $batchId, $ticketId, $eventHash = null)
	{
		// first validate arguments and standardize them to ints
		if (empty($eventId) || empty($batchId) || empty($ticketId)) throw new Bts_Exception(
			'Invalid arguments to Bts_Model_Barcodes::encryptBarcode()',
			Bts_Exception::BARCODES_PARAMS_BAD);
		$eventId = (int) $eventId;
		$batchId = (int) $batchId;
		$ticketId = (int) $ticketId;
		// we can't continue without an eventHash
		if (is_null($eventHash)) $eventHash = $this->_retrieveEventHash($eventId);
		if ($eventHash === false) throw new Bts_Exception(
			'Invalid eventId in Bts_Model_Barcodes::encryptBarcode()',
			Bts_Exception::BARCODES_EVENT_BAD);
		$decryptedString = $eventId . '-' . $batchId . '-' . $ticketId;
		$encryptedString = base64_encode(mcrypt_encrypt(self::CIPHER, $eventHash, $decryptedString, self::MODE));
		return $eventId . ';' . $batchId . ';' . $encryptedString;
	}
	/**
	 * Decodes the provided human-readable label and returns an array containing
	 * its data.
	 *
	 * @param string $labelString
	 * @return array|boolean Array containing event, batch, ticket and checksum, or false on failure
	 */
	public function decodeLabel ($labelString)
	{
		$labelString = strtolower($labelString);
		$parts = array();
		if (! preg_match('/^([0-9]+)-([0-9]+)-([0-9]+)-([0-9a-f]{2})$/', $labelString, $parts)) {
			// did not fit pattern; empty array signifies failure
			return false;
		}
		// at this point $parts will contain:
		// [0] => full $barcodeString
		// [1] => eventId
		// [2] => batchId
		// [3] => ticketId
		// [4] => checksum
		// now check validity (not database validity, only format validity)
		$eventHash = $this->_retrieveEventHash($parts[1]);
		if ($eventHash === false) return false;
		$encryptedString = bin2hex(mcrypt_encrypt(self::CIPHER, $eventHash, $parts[1] . '-' . $parts[2] . '-' . $parts[3], self::MODE));
		if ($parts[4] != $encryptedString[0] . $encryptedString[strlen($encryptedString) - 1]) return false;
		return array(
			'event' => $parts[1] ,
			'batch' => $parts[2] ,
			'ticket' => $parts[3] ,
			'checksum' => $parts[4]);
	}
	/**
	 * Encodes the provided ticket details into a human-readable label formatted
	 * according to BTS specifications.
	 *
	 * @param int $eventId
	 * @param int $batchId
	 * @param int $ticketId
	 * @param string $eventHash (optional)
	 * @throws Bts_Exception
	 * @return string
	 */
	public function encodeLabel ($eventId, $batchId, $ticketId, $eventHash = null)
	{
		// first validate arguments and standardize them to ints
		if (empty($eventId) || empty($batchId) || empty($ticketId)) throw new Bts_Exception(
			'Invalid arguments to Bts_Model_Barcodes::encodeLabel()',
			Bts_Exception::BARCODES_PARAMS_BAD);
		$eventId = (int) $eventId;
		$batchId = (int) $batchId;
		$ticketId = (int) $ticketId;
		// we can't continue without an eventHash
		if (is_null($eventHash)) $eventHash = $this->_retrieveEventHash($eventId);
		if ($eventHash === false) throw new Bts_Exception(
			'Invalid eventId in Bts_Model_Barcodes::encodeLabel()',
			Bts_Exception::BARCODES_EVENT_BAD);
		$baseString = $eventId . '-' . $batchId . '-' . $ticketId;
		// create our "checksum" from the first and last chars of the hex-encoded encrypted string
		$encryptedString = bin2hex(mcrypt_encrypt(self::CIPHER, $eventHash, $baseString, self::MODE));
		return $baseString . '-' . $encryptedString[0] . $encryptedString[strlen($encryptedString) - 1];
	}
}