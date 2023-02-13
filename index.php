<?php

header('content-type:text/html;charset=utf-8');

ini_set('display_errors', 'On');
error_reporting(E_ALL ^ E_NOTICE);
spl_autoload_register('autoload');
#set_exception_handler('exception_handler');

set_error_handler('error_handler');
session_start();
define('ROOT', __DIR__);

/**
 *
 *
 * @var array
 */
$dirs = [
	get_include_path(),

	ROOT . '/controller',

];

set_include_path(implode(PATH_SEPARATOR, $dirs));

$url = $_SERVER["REQUEST_URI"];

//$url_params = str_replace('/index.php/', '', $url);

$url_params = substr($url, 1);

list($class, $method) = explode('/', $url_params);

/**
 *
 * nginx 伪静态配置
 * if (!-e $request_filename) {

rewrite ^/(.*)$ /index.php?$1 last;

}
 *
 *
 *
 */

if ($class && $method) {

	try {

		$reflection_method = new ReflectionMethod($class, $method);

	} catch (Exception $e) {
		//非法请求
		include "_tpl/404.html";

		exit;
	}

	$argv = [];
	$reflection_params = $reflection_method->getParameters();
	foreach ($reflection_params as $p) {
		$name = $p->getName();
		if (isset($params[$name])) {
			$argv[] = ($p->isArray() && !is_array($params[$name])) ? json_decode($params[$name], true) : $params[$name];
		} else if ($p->isDefaultValueAvailable()) {
			$argv[] = $p->getDefaultValue();
		} else {
			//非法请求!
			exit();

		}
	}

	$reflection_method->invokeArgs(new $class, $argv);
} else {
	header('location:/login/login');
}

function autoload($className) {
	@include_once "$className.php";
}

function error_handler($errno, $errstr, $errfile, $errline) {
	if ($errno == E_NOTICE || $errno == E_DEPRECATED) {
		return true;
	}
	$errorDir = ROOT . '/log/error/';
	if (!is_dir($errorDir)) {
		mkdir($errorDir);
	}
	$msg = '[' . date('Y-m-d H:i:s') . '] ' . "$errfile line $errline: $errstr\n";
	$file = $errorDir . date('Y-m') . '_single.log';
	@file_put_contents($file, $msg, FILE_APPEND);
	return false;
}
