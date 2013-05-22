<?php

class Panel_Model_UserAuthentication extends Zend_Auth_Adapter_DbTable
{

	/**
	 *
	 * @var BtsX_PasswordHash
	 */
	protected $Hash = null;

	/**
	 *
	 * @var Zend_Session_Namespace
	 */
	protected $Session = null;

	/**
	 * Builds a new instance of the authentication adapter.
	 * 
	 * @param Zend_Db_Adapter_Abstract $zendDb        	
	 * @param string $tableName        	
	 * @param string $identityColumn        	
	 * @param string $credentialColumn        	
	 */
	public function __construct (Zend_Db_Adapter_Abstract $zendDb = null, 
			$tableName = null, $identityColumn = null, $credentialColumn = null)
	{
		// use the adapter stored in the registry by default
		$zendDb = ! is_null($zendDb) ? $zendDb : Zend_Registry::get('db');
		$tableName = ! empty($tableName) ? $tableName : 'bts_users';
		$identityColumn = ! empty($identityColumn) ? $identityColumn : 'username';
		$credentialColumn = ! empty($credentialColumn) ? $credentialColumn : 'password';
		parent::__construct($zendDb, $tableName, $identityColumn, 
				$credentialColumn);
		$this->Hash = new BtsX_PasswordHash(8, true);
		$this->Session = new Zend_Session_Namespace('bts-auth');
	}

	protected function _authenticateCreateSelect ()
	{
		// get select
		$dbSelect = clone $this->getDbSelect();
		$dbSelect->from($this->_tableName, array(
				'*'
		))->where(
				$this->_zendDb->quoteIdentifier($this->_identityColumn, true) .
						 ' = ?', $this->_identity);
		// BTS specific!
		$dbSelect->where('status = 1');
		return $dbSelect;
	}

	protected function _authenticateValidateResult ($resultIdentity)
	{
		$valid = $this->Hash->checkPassword($this->_credential, 
				$resultIdentity[$this->_credentialColumn]);
		if (! $valid) {
			$this->_authenticateResultInfo['code'] = Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID;
			$this->_authenticateResultInfo['messages'][] = 'Supplied credential is invalid.';
			return $this->_authenticateCreateAuthResult();
		}
		$this->_resultRow = $resultIdentity;
		$this->_authenticateResultInfo['code'] = Zend_Auth_Result::SUCCESS;
		$this->_authenticateResultInfo['messages'][] = 'Authentication successful.';
		// set the session
		$this->Session->loggedIn = true;
		$this->Session->username = $this->_identity;
		$this->Session->userRow = $resultIdentity;
		return $this->_authenticateCreateAuthResult();
	}

	public function getResultRowObject ($returnColumns = null, $omitColumns = null)
	{
		if ($returnColumns || $omitColumns) {
			return parent::getResultRowObject($returnColumns, $omitColumns);
		} else {
			$omitColumns = array(
					$this->_credentialColumn
			);
			return parent::getResultRowObject($returnColumns, $omitColumns);
		}
	}
}