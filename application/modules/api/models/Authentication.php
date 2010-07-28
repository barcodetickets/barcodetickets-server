<?php
class Api_Model_Authentication
{
	private $db = null;
	public function __construct ()
	{
		// get the database resource that was defined from Zend_Application
		if (Zend_Registry::isRegistered('db')) {
			$this->db = Zend_Registry::get('db')->getDbAdapter();
		}
		if (is_null($this->db)) {
			throw new Zend_Db_Adapter_Exception(
				'Database adapter could not be built');
		}
		$this->db->getConnection();
	}
	private function getApiKey ($sysName)
	{
		$query = $this->db->select()->from('bts_clients', 'api_key')
		->where('sys_name = ?', $sysName)
		->where('status = 1')
		->limit(1)
		->query()->fetchColumn();
		return $query;
	}
	public function validateTimestamp($timestamp = 0)
	{
		// valid timestamps are less than 15 minutes from current GMT time
		// ABS(TIMESTAMPDIFF(MINUTE, ********* , NOW())) < 15
		if(!is_numeric($timestamp))
			return false;
		$query = $this->db->select()->from('',
			new Zend_Db_Expr('ABS(TIMESTAMPDIFF(MINUTE, ' . $timestamp . ' , UTC_TIMESTAMP())) < 15'))
		->query()->fetchColumn();
		return ($query == 1);
	}
	public function generateSignature($httpVerb, $uri, array $params, $apiKey = null)
	{
		// validate the HTTP verb
		$httpVerb = trim(strtoupper($httpVerb));
		if(!in_array($httpVerb, array('GET', 'POST')))
			throw new Zend_Exception('Invalid HTTP verb in generateSignature()');

		// create a message to sign with HMAC
		$stringToSign = $httpVerb . "\n";
		$stringToSign .= $uri . "\n";

		// parameters in key => value format must be in alpha order
		unset($params['signature']);
		ksort($params);

		// use the sysName in the params to get the API key if it is not specified
		if (is_null($apiKey)) {
			$apiKey = $this->getApiKey($params['sysName']);
			if ($apiKey === false) {
				throw new Zend_Exception(
					'Invalid sysName in generateSignature()');
			}
		}

		// concatenate the parameters to the HMAC message
		reset($params);
		while(list($key, $val) = each($params)) {
			$stringToSign .= $key . '=' . urlencode($val) . "\n";
		}

		// generate a HMAC hash using the API key as key, encoded using base64
		$digest = base64_encode(hash_hmac('sha1', $stringToSign, $apiKey, true));
		return $digest;
	}
	public function validateSignature($httpVerb, $uri, array $params)
	{
		try {
			$generated = $this->generateSignature($httpVerb, $uri, $params);
		} catch(Zend_Exception $e) {
			return false;
		}
		return ($generated == $params['signature']);
	}
}