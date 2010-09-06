<?php
/**
 * Attendees model.
 *
 * Contains methods for creating and managing attendees.
 *
 * @author	Frederick Ding
 * @version $Id$
 * @package	Bts
 */
class Bts_Model_Attendees
{
	/**
	 * @var Bts_Model_DbTable_Attendees
	 */
	protected $AttendeesTable = null;
	protected $statuses = array(
		0 => 'inactive' ,  // no longer enabled; can be deleted
		1 => 'active' ,  // enabled
		2 => 'blocked' ,  // not permitted; blacklisted
		3 => 'indebted' ,  // owes money
		255 => 'invalidother'); // 255 = unknown status but nevertheless invalid -- must be last
	public function __construct ()
	{
		$this->AttendeesTable = new Bts_Model_DbTable_Attendees();
	}
	/**
	 * Creates a new BTS attendee and inserts this row into the database.
	 * @param string $firstName
	 * @param string $lastName
	 * @param string|int $uniqueId
	 * @param string|int|null $status (optional)
	 * @param array $params (optional)
	 * @throws Bts_Exception
	 * @return int
	 */
	public function create ($firstName, $lastName, $uniqueId, $status = 'active', array $params = array())
	{
		if (empty($firstName) || empty($lastName)) {
			throw new Bts_Exception(
				'Attendees must have names; none provided',
				Bts_Exception::ATTENDEES_PARAMS_BAD);
		}
		if (empty($uniqueId)) {
			throw new Bts_Exception(
				'Attendees must have unique IDs; none provided',
				Bts_Exception::ATTENDEES_PARAMS_BAD);
		}
		if (is_string($status)) {
			$status = $this->getStatusCode($status, 'active');
		} elseif (! in_array($status, $this->statuses)) {
			$status = $this->getStatusCode('active');
		}
		$newRow = $this->AttendeesTable
			->createRow(array(
			'first_name' => $firstName ,
			'last_name' => $lastName ,
			'unique_id' => $uniqueId ,
			'status' => $status));
		if (! empty($params['email'])) {
			// TODO: need e-mail field validation / filter in the future
			$newRow->email = $params['email'];
		}
		if (! empty($params['password'])) {
			$Password = new BtsX_PasswordHash(8, true);
			$newRow->password = $Password->hashPassword($params['password']);
		}
		if (! empty($params['balance']) && is_numeric($params['balance'])) {
			$newRow->balance = $params['balance'];
		}
		if (! empty($params['openid'])) {
			$newRow->openid = $params['openid'];
		}
		if (! empty($params['comments'])) {
			$newRow->comments = $params['comments'];
		}
		$attendee_id = - 1;
		try {
			$attendee_id = $newRow->save();
		} catch (Zend_Db_Exception $e) {
			// failed; probably unique ID wasn't unique
			return - 1;
		}
		return $attendee_id;
	}
	public function existsById ($uniqueId)
	{
		if (empty($uniqueId)) {
			throw new Bts_Exception('Unique ID must be given for find-by-ID',
				Bts_Exception::ATTENDEES_PARAMS_BAD);
		}
		$select = $this->AttendeesTable
			->select()
			->from($this->AttendeesTable, 'COUNT(*)')
			->where('unique_id = ?', $uniqueId)
			->query()
			->fetchColumn();
		return $select == 1;
	}
	public function existsByName ($firstName, $lastName)
	{
		if (empty($firstName) || empty($lastName)) {
			throw new Bts_Exception(
				'First and last name must be provided for search-by-name',
				Bts_Exception::ATTENDEES_PARAMS_BAD);
		}
		$select = $this->AttendeesTable
			->select()
			->from($this->AttendeesTable, 'COUNT(*)')
			->where('first_name = ?', $firstName)
			->where('last_name = ?', $lastName)
			->query()
			->fetchColumn();
		return $select == 1;
	}
	/**
	 * Finds an attendee by the unique ID (e.g. student number).
	 * @param string|int $uniqueId
	 * @throws Bts_Exception
	 * @return Zend_Db_Table_Row_Abstract|NULL
	 */
	public function findById ($uniqueId)
	{
		if (empty($uniqueId)) {
			throw new Bts_Exception('Unique ID must be given for find-by-ID',
				Bts_Exception::ATTENDEES_PARAMS_BAD);
		}
		$select = $this->AttendeesTable
			->select(true)
			->where('unique_id = ?', $uniqueId)
			->limit(1);
		$row = $this->AttendeesTable
			->fetchRow($select);
		if (is_null($row)) {
			return null;
		} else {
			// don't let things from outside this model modify these records
			$row->setReadOnly(true);
			$row->attendee_id = (int) $row->attendee_id;
			// TODO: determine if API clients should get # or text
			$row->status = $this->getStatusText($row->status);
			// don't reveal the password!
			unset($row->password);
			unset($row->openid);
			return $row;
		}
	}
	/**
	 * Finds attendees by first and last name.
	 * @param string $firstName
	 * @param string $lastName
	 * @throws Bts_Exception
	 * @return Zend_Db_Table_Rowset_Abstract
	 */
	public function findByName ($firstName, $lastName)
	{
		if (empty($firstName) || empty($lastName)) {
			throw new Bts_Exception(
				'First and last name must be provided for search-by-name',
				Bts_Exception::ATTENDEES_PARAMS_BAD);
		}
		$select = $this->AttendeesTable
			->select(true)
			->where('first_name LIKE ?', $firstName . '%')
			->where('last_name LIKE ?', $lastName . '%');
		$rows = $this->AttendeesTable
			->fetchAll($select);
		foreach ($rows as $row) {
			$row->setReadOnly(true);
			$row->attendee_id = (int) $row->attendee_id;
			$row->status = $this->getStatusText($row->status);
			unset($row->password);
			unset($row->openid);
		}
		return $rows;
	}
	public function getStatusText ($status)
	{
		if (isset($this->statuses[$status])) {
			return $this->statuses[$status];
		} else {
			return end($this->statuses);
		}
	}
	public function getStatusCode ($text, $default = 'invalidother')
	{
		$search = array_search($text, $this->statuses);
		if ($search === false) {
			// not found, return the 'other' status code
			return array_search($default, $this->statuses);
		} else {
			return $search;
		}
	}
}