<?php
/**
 * Backend data manipulation for dealing with events.
 *
 * @author	Frederick Ding
 * @version	$Id$
 * @package	Bts
 */
class Bts_Model_Events
{
	/**
	 * An instance of the events database table class.
	 * @var Bts_Model_DbTable_Events
	 */
	protected $EventsTable = null;
	/**
	 * A Zend_Config object to read from the bts.ini configuration file.
	 * @var Zend_Config
	 */
	protected $BtsConfig = null;
	protected $statuses = array(
		0 => 'inactive',  // draft
		1 => 'active',  // valid event
		2 => 'activerestricted',  // no further sales
		3 => 'occurred',
		4 => 'cancelled',
		255 => 'other'); // 255 = unknown statusmust be last
	public function __construct ()
	{
		$this->EventsTable = new Bts_Model_DbTable_Events();
		try {
			$this->BtsConfig = Zend_Registry::get('bts-config');
		} catch (Zend_Exception $e) {
			$this->BtsConfig = new Zend_Config(array());
		}
	}
	public function createEvent ($name, $time, $user, $slug = null, $status = 1)
	{
		if (empty($name) || empty($time) || empty($user)) {
			throw new Bts_Exception('Parameters missing',
				Bts_Exception::EVENTS_PARAMS_BAD);
		}
		$installationHash = $this->BtsConfig->get('secureHash', '');
		$newEvent = $this->EventsTable->insert(
			array(
				'name' => $name,
				'event_time' => $time,
				'owner' => (int) $user,
				'secure_hash' => new Zend_Db_Expr(
					'UNHEX("' . hash('sha256',
						$name . $time . time() . $installationHash) . '")'),
				'status' => (int) $status,
				'creation_time' => new Zend_Db_Expr('UTC_TIMESTAMP()'),
				'slug' => (empty($slug)) ? Zend_Filter::filterStatic($name,
					'Alnum') : $slug));
		return $newEvent;
	}
	/**
	 *
	 *
	 * @param int $user
	 * @return Zend_Db_Table_Rowset
	 * @throws Bts_Exception
	 */
	public function getEventsForUser ($user)
	{
		if (empty($user)) {
			throw new Bts_Exception('Parameters missing',
				Bts_Exception::EVENTS_PARAMS_BAD);
		}
		$selectQuery = $this->EventsTable->select()
			->from($this->EventsTable, '*')
			->joinInner('bts_users', 'bts_events.owner = bts_users.user_id',
			array())
			->where('bts_users.user_id = ?', $user);
		return $this->EventsTable->fetchAll($selectQuery);
	}
	public function getEvent ($id)
	{
		if (empty($id)) {
			throw new Bts_Exception('Parameter missing',
				Bts_Exception::EVENTS_PARAMS_BAD);
		}
		$select = $this->EventsTable->select(true)
			->where('event_id = ?', (int) $id)
			->query()
			->fetchObject();
		return ($select) ? $select : null;
	}
	public function generateBatch ($event, $batchSize, $user,
		Bts_Model_Tickets $Tickets)
	{
		// check that this is for a valid event
		$select = $this->EventsTable->select(true)
			->where('event_id = ?', $event)
			->limit(1);
		$eventRow = $this->EventsTable->fetchRow($select);
		if (is_null($eventRow)) {
			return false;
		}
		// determine an appropriate batch #
		$batch = $Tickets->getMaxBatch($event) + 1;
		// iterate and make tickets!
		$ticketIds = array();
		for ($i = 0; $i < $batchSize; $i ++) {
			$ticketIds[] = $Tickets->insert($batch, $event);
		}
		// get ticket strings & label numbers
		$tickets = array();
		$Barcodes = Bts_Model_Barcodes::getInstance();
		foreach ($ticketIds as $ticket) {
			$thisTicket = new stdClass();
			$thisTicket->id = $ticket;
			$thisTicket->barcode = $Barcodes->encryptBarcode($event, $batch,
				$ticket);
			$thisTicket->label = $Barcodes->encodeLabel($event, $batch, $ticket);
			$tickets[] = $thisTicket;
		}
		return $tickets;
	}
	public function getStatusText ($status)
	{
		if (isset($this->statuses[$status])) {
			return $this->statuses[$status];
		} else {
			return end($this->statuses);
		}
	}
	public function getStatusCode ($text)
	{
		$search = array_search($text, $this->statuses);
		if ($search === false) {
			// not found, return the 'other' status code
			return array_search('invalidother', $this->statuses);
		} else {
			return $search;
		}
	}
}