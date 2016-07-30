<?php
define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));
define('FCPATH', __FILE__);
ini_set("display_errors", false);


require_once 'config.inc.php';
require_once 'Config.php';
require_once 'Router.php';
require_once 'FrontController.php';
require_once 'UserRecord.php';
require_once 'Util.php';

$config = Config :: getIniConfig("config.ini");

if (is_null($config->getValue('FW'))) {
	header("Location: ".get_base_url().'/install.php'); 
	exit();
}

$router = new FW_Router($config);
$util = new Util($config);

$args = $router->getArgs();
if ($config->getValue('FW', 'multi_session') == true) {
	if (isset ($args['session_name'])) {
		session_name($args['session_name']);
	} else {
		session_name($util->getNewSession());
	}
}
session_start();

$fc = new FW_FrontController($config, $router);

$view = $fc->getView();
if (isset ($view)) {
	$view->display();
}
session_write_close();

function get_base_url() {
		$protocol = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on' ? 'https' : 'http';
		$base_url = $protocol.'://'.  $_SERVER['HTTP_HOST'];  
		$base_url .= str_replace("/".basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);  
		return $base_url;
}
?>
