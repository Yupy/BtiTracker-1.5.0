<?php 

const REQUIRED_PHP = 50300, REQUIRED_PHP_VERSION = '5.3.0';

if (PHP_VERSION_ID < REQUIRED_PHP)
	die('PHP '.REQUIRED_PHP_VERSION.' or higher is required.'); 

if (get_magic_quotes_gpc() || get_magic_quotes_runtime() || ini_get('magic_quotes_sybase'))
	die('PHP is configured incorrectly. Turn off magic quotes.');

if (ini_get('register_long_arrays') || ini_get('register_globals') || ini_get('safe_mode'))
	die('PHP is configured incorrectly. Turn off safe_mode, register_globals and register_long_arrays.'); 
	
if (!extension_loaded('memcache'))
{
    die('Memcache Extension is not loaded !');
}
