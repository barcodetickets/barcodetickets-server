<?php
/**
 * An exception type used by the BTS application.
 *
 * @author	Frederick Ding
 * @version	$Id$
 * @see		Zend_Exception
 */
class Bts_Exception extends Zend_Exception
{
	const AUTH_SESSION_FAILURE = - 301;
	const AUTH_SYSNAME_MISSING = - 302;
	const AUTH_SYSNAME_BAD = - 303;
	const BARCODES_PARAMS_BAD = -330;
	const BARCODES_EVENT_BAD = -331;
	const TICKETS_PARAMS_BAD = -340;
	const TICKETS_UNAUTHORIZED = -341;
}