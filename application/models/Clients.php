<?php
/**
 * Methods for managing the clients of the system, used for authentication/
 * authorization and other system uses.
 *
 * @author	Frederick Ding
 * @version	$Id$
 */
class Bts_Model_Clients
{
	/**
	 * An instance of the Clients table.
	 * @var Bts_Model_DbTable_Clients
	 */
	private $ClientsTable = null;
	/**
	 * Constructs this Clients model.
	 */
	public function __construct ()
	{
		$this->ClientsTable = new Bts_Model_DbTable_Clients();
	}
	/**
	 * Fetches the API key of the given API client from the database.
	 * @param string $sysName
	 * @return string
	 */
	public function getApiKey ($sysName)
	{
		$query = $this->ClientsTable
			->select(false)
			->from($this->ClientsTable, 'api_key')
			->where('sys_name = ?', $sysName)
			->where('status = 1')
			->limit(1)
			->query()
			->fetchColumn();
		return $query;
	}
	public function getClientId ($sysName)
	{
		$query = $this->ClientsTable
			->select(false)
			->from($this->ClientsTable, 'client_id')
			->where('sys_name = ?', $sysName)
			->limit(1)
			->query()
			->fetchColumn();
		return $query;
	}
	public function getClientStatus ($client)
	{
		if (empty($client)) return false;
		if (is_numeric($client)) {
			// treat as ID
			$query = $this->ClientsTable
				->select(false)
				->from($this->ClientsTable, 'status')
				->where('client_id = ?', $client)
				->limit(1)
				->query()
				->fetchColumn();
		} else {
			// treat as sysName
			$query = $this->ClientsTable
				->select(false)
				->from($this->ClientsTable, 'status')
				->where('sys_name = ?', $client)
				->limit(1)
				->query()
				->fetchColumn();
		}
		if ($query === false) return false;
		return (int) $query;
	}
	/**
	 * @return Zend_Db_Adapter_Abstract
	 */
	public function getDb ()
	{
		return $this->ClientsTable
			->getAdapter();
	}
}