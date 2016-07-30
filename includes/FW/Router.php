<?php
class FW_Router {
	var $uri_string = '';
	var $segments = array ();
	var $module = '';
	var $method = 'index';
	var $class_name = "Action";
	var $class_file = 'Action.php';
	var $config = NULL;
	var $args = array ();

	function FW_Router($config) {
		/*array_walk($_GET, 'FW_Router::clean');
		array_walk($_POST, 'FW_Router::clean');
		array_walk($_COOKIE, 'FW_Router::clean');
		extract($_GET, EXTR_PREFIX_ALL, 'get');
		extract($_POST, EXTR_PREFIX_ALL, 'post');
		extract($_COOKIE, EXTR_PREFIX_ALL, 'cookie');*/

		//add $_POST and $_GET key, values.
		$this->args = array_merge($this->args, $_POST);
		$this->args = array_merge($this->args, $_GET);
		if (is_array($this->args) && (count($this->args) > 0)) {
			$cleaned_args = array ();
			foreach ($this->args as $key => $arg) {
				if (is_array($arg) && (count($arg) > 0)) {
					foreach ($arg as $arg_key => $arg_value) {
						$this->cleaned_args[$key][$arg_key] = $this->clean($arg_value);
					}
				} else {
					$this->cleaned_args[$key] = $this->clean($arg);
				}

			}
			$this->args = $this->cleaned_args;
		}

		$this->config = $config;
		$this->_extract_action_comps();
		$this->_validate_action_comps();

	}
	function _validate_action_comps() {
		//$this->args['error_message'] = ''; do not set it blank, it may have an error message set already.
		$module_folder = $this->config->getValue('FW', 'module_dir') . DIRECTORY_SEPARATOR . $this->module;
		if (!file_exists($module_folder)) {
			$this->args['error_message'] = 'Error: Could not find module by name ' . $this->module;
			$this->module = 'error';
			$this->method = 'index';

		}
		$this->class_file = $this->config->getValue('FW', 'module_dir') . DIRECTORY_SEPARATOR . $this->module . DIRECTORY_SEPARATOR . $this->class_name . ".php";
		//API.php
		if (!file_exists($this->class_file)) {
			$this->class_name = "API";
			$this->class_file = $this->config->getValue('FW', 'module_dir') . DIRECTORY_SEPARATOR . $this->module . DIRECTORY_SEPARATOR . $this->class_name . ".php";

		}

		if (!file_exists($this->class_file)) {
			$this->args['error_message'] = 'Error: Could not find the action class for module ' . $this->module;
			$this->class_file = 'Action.php';
			$this->method = 'index';

		}

	}
	function _extract_action_comps() {

		$this->uri_string = $this->_get_uri_string();

		if ($this->uri_string == '/') {
			$this->uri_string = '';
		}

		// Is there a URI string? If not, the default controller specified in the "routes" file will be shown.
		if ($this->uri_string == '') {

			//$this->set_class($this->default_controller);
			$this->module = $this->getDefaultModule();
			$this->method = 'index';
			return;
		}
		foreach (explode("/", preg_replace("|/*(.+?)/*$|", "\\1", $this->uri_string)) as $val) {
			// Filter segments for security
			$val = trim($this->_filter_uri($val));

			if ($val != '')
				$this->segments[] = $val;
		}
		$this->_extract_module_method_args();

	}
	function _extract_module_method_args() {
		$cnt = count($this->segments);
		//if ($cnt == 0) {
		//	$this->module = $this->getDefaultModule();

		//}
		if ($cnt >= 1) {
			$this->module = $this->segments[0];
		}
		if ($cnt >= 2) {

			$this->method = $this->segments[1];
		} else {
			$this->method = "index";
		}
		if ($cnt >= 3) {
			for ($i = 2; $i < $cnt; $i++) {

				$even = ($i % 2) == 0;
				if ($even) {
					$this->args[$this->segments[$i]] = '';
				} else {
					$this->args[$this->segments[$i -1]] = $this->segments[$i];
				}
			}
		}

	}
	function _get_uri_string() {
		 //moved this block to end  21/03/2015
		/*if (is_array($_GET) AND count($_GET) == 1) {

			$keys = array_keys($_GET);
			return current($keys);
		}*/

		$path = (isset ($_SERVER['PATH_INFO'])) ? $_SERVER['PATH_INFO'] : @ getenv('PATH_INFO');
		if ($path != '' AND $path != "/" . SELF) {

			//return ($last_char == "/") ? $path : $path . "/";
			return $path;
		}

		$path = (isset ($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : @ getenv('QUERY_STRING');
		if ($path != '') {
			return $path;
		}

		$path = (isset ($_SERVER['ORIG_PATH_INFO'])) ? $_SERVER['ORIG_PATH_INFO'] : @ getenv('ORIG_PATH_INFO');
		if ($path != '' AND $path != "/" . SELF) {
			return $path;
		}
		
		if (is_array($_GET) AND count($_GET) == 1) {

			$keys = array_keys($_GET);
			return current($keys);
		}

		return '';
	}

	function _filter_uri($str) {
		/*if ($this->config->getValue('FW', 'permitted_uri_chars') != '') {
			if (!preg_match("|^[" . preg_quote($this->config->getValue('FW', 'permitted_uri_chars')) . "]+$|i", $str)) {
				exit ('The URI you submitted has disallowed characters.');
			}
		}*/
		return $str;
	}

	function clean($value) {
		if (get_magic_quotes_gpc())
			$value = stripslashes($value);
		$value = trim($value);
		/*if (!is_numeric($value))
		$value = mysql_real_escape_string($value);*/
		return $value;
	}
	function getModuleName() {
		return $this->module;
	}
	function getDefaultModule() {
		return $this->config->getValue('FW', 'default_module');
	}
	function getMethodName() {
		return $this->method;
	}
	function getClassName() {
		return $this->class_name;
	}
	function getClassFile() {
		return $this->class_file;
	}
	function getArgs() {
		return $this->args;
	}

}
?>
