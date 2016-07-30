<?php
/*
 * Created on June 22, 2013
 *
 * bispark software services
 * www.bispark.com
 */
$localhost = "localhost";
$user_name = "root";
$password = "root";
$database_name = "ttdb";
$prefix = ""; 

$connect=mysql_connect($localhost,$user_name,$password) or die(mysql_error());
mysql_select_db($database_name) or die(mysql_error());

//Call all functions
addPriorityColumn($prefix);
upgradeConfigTable($prefix);
upgradeUserTable($prefix);




function addPriorityColumn($prefix) {
	$table_name1 = $prefix.'task' ;
	$query = "ALTER TABLE  $table_name1 ADD `priority` TINYINT UNSIGNED NOT NULL DEFAULT  '113' AFTER  `status`;";
	mysql_query($query);
	
	$table_name2 = $prefix.'issue';
	$query = "ALTER TABLE  $table_name2 ADD `priority` TINYINT UNSIGNED  NOT NULL DEFAULT  '113' AFTER  `status`;";
	mysql_query($query);
}	
	
function upgradeConfigTable($prefix) {
	$table_name =  $prefix.'config';
	$query = "ALTER TABLE  $table_name CHANGE  `value`  `value` VARCHAR( 1024 ) ; ";
	mysql_query($query);
	
}
function upgradeUserTable($prefix) {
	$table_name = $prefix.'user' ;
	$query = "ALTER TABLE  $table_name ADD `icon_name`  VARCHAR( 256 ) DEFAULT  '';";
	mysql_query($query);
	
	
}	



?>