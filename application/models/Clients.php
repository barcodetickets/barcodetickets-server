<?php
/**
 * Methods for managing the clients of the system, used for authentication/
 * authorization and other system uses.
 *
 * @author	Frederick Ding
 * @version	$Id$
 * @package	Bts
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
	 *
	 * @param string $sysName
	 * @return string|false
	 */
	public function getApiKey ($sysName)
	{
		$query = $this->ClientsTable->select(false)
			->from($this->ClientsTable, 'api_key')
			->where('sys_name = ?', $sysName)
			->where('status = 1')
			->limit(1)
			->query()
			->fetchColumn();
		return $query;
	}
	/**
	 * Finds the client ID of a given client from the database.
	 *
	 * @param string $sysName
	 * @return string|false
	 */
	public function getClientId ($sysName)
	{
		$query = $this->ClientsTable->select(false)
			->from($this->ClientsTable, 'client_id')
			->where('sys_name = ?', $sysName)
			->limit(1)
			->query()
			->fetchColumn();
		return $query;
	}
	/**
	 * Finds the status of the given API client.
	 *
	 * @param string|int $client
	 * @return int|false
	 */
	public function getClientStatus ($client)
	{
		if (empty($client))
			return false;
		if (is_numeric($client)) {
			// treat as ID
			$query = $this->ClientsTable->select(false)
				->from($this->ClientsTable, 'status')
				->where('client_id = ?', $client)
				->limit(1)
				->query()
				->fetchColumn();
		} else {
			// treat as sysName
			$query = $this->ClientsTable->select(false)
				->from($this->ClientsTable, 'status')
				->where('sys_name = ?', $client)
				->limit(1)
				->query()
				->fetchColumn();
		}
		if ($query === false)
			return false;
		return (int) $query;
	}
	/**
	 * Gets the database adapter in use by the model.
	 *
	 * @return Zend_Db_Adapter_Abstract
	 */
	public function getDb ()
	{
		return $this->ClientsTable->getAdapter();
	}
	public function createClient ($sysName, $status = 1)
	{
		$BtsConfig = Zend_Registry::get('bts-config');
		$installationHash = $BtsConfig->get('secureHash', '');
		// perhaps ACL checks should exist in the future
		$newClient = $this->ClientsTable->createRow(
			array(
				'sys_name' => Zend_Filter::filterStatic($sysName, 'Alnum'),
				'api_key' => hash('sha384',
					$sysName . $installationHash . time()),
				'status' => (int) $status));
		try {
			$id = $newClient->save();
			return $id;
		} catch (Exception $e) {
			// most likely sys_name was duplicate
			return false;
		}
	}
}