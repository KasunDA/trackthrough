<?php
require_once 'BaseView.php';
class FW_ErrorView extends FW_BaseView {
	var $args;
	function FW_ErrorView($args) {
		
		$this->args = $args;
	}

	function display() {
		$message =  isset($this->args['error_message']) ? $this->args['error_message'] : '' ;
	
		print ("
		<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">
		<html><head>
		<title>FW Error</title>
		</head><body>
		<h1>FW Error</h1>
		     <p>There was an error in the server!. <br/>$message</p>
		<hr>
		<address>www.bispark.com</address>
		</body></html>");

	}
}
?>