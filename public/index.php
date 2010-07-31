<?php

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
	realpath(APPLICATION_PATH . '/../library') ,
	get_include_path())));

/** Zend_Application */
require_once 'Zend/Application.php';

$configs = array(
	'config' => array(
		APPLICATION_PATH . '/configs/application.ini'
));

// if the application is installed and a database configuration exists
if (file_exists(APPLICATION_PATH . '/configs/database.ini')) {
	$configs['config'][] = APPLICATION_PATH . '/configs/database.ini';
}

// Create application, bootstrap, and run
$application = new Zend_Application(APPLICATION_ENV, $configs);
$application->bootstrap()->run();