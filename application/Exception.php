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
}