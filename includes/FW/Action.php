<?php

/*
 * Created on May 21, 2007
 *
 * bispark software services
 * www.bispark.com
 */
require_once 'BaseController.php';
require_once 'ErrorView.php';

class Action extends FW_BaseController {

	function index($args) {
		
		return new FW_ErrorView($args);
	}
}


?>