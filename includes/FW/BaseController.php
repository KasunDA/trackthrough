<?php
class FW_BaseController {
	var $config = null;
	var $ISAUTHORISED_KEY = "qweqwrwe223ew409764";
	var $js_back_href = "javascript:history.back()";

	var $CURRENT_HREF_KEY = 'CURRENT_HREF';
	var $BACK_HREF_KEY = 'BACK_HREF';
	
	
	var $MESSAGE_KEY = "flash_message";
	
		

	var $showMasterTemplate = true;
	var $showFooter = true;
	var $isXMLContent = false;

	var $has_error = false;
	var $error_message = '';
	var $has_message = false;
	var $message = '';
	
	function getShowMasterTemplate() {
		return $this->showMasterTemplate;

	}
	function getShowFooter() {
		return $this->showFooter;

	}
	function getIsXMLContent() {
		return $this->isXMLContent;
	}
	function setShowMasterTemplate($showMasterTemplate) {
		$this->showMasterTemplate = $showMasterTemplate;
	}
	function setIsXMLContent($isXMLContent) {
		$this->isXMLContent = $isXMLContent;
	}
	function setShowFooter($showFooter) {
		$this->showFooter = $showFooter;
	}
	function setIsAuthorised($isAuthorised) {
		$this->setParameter($this->ISAUTHORISED_KEY, $isAuthorised);
	}
	function getIsAuthorised() {
		$isAuthorised = $this->getParameter($this->ISAUTHORISED_KEY);
		if (isset ($isAuthorised) && $isAuthorised == true) {
			return true;
		}
		return false;
	}

	
	function setConfig($config) {
		$this->config = $config;
	}
	function getConfig() {
		return $this->config;
	}
	function getAbsoluteURLWithoutSession($arg) {
		$base_url = $this->config->getValue('FW', 'base_url');
		if (isset ($base_url)) {
			return    $base_url . $arg;
		}
		return    "." . $arg;
	}
	
	function getAbsoluteURL($arg) {
		$base_url = $this->config->getValue('FW', 'base_url');
		$multi_session = $this->config->getValue('FW', 'multi_session') == true;
		if (isset ($base_url)) {
			return  $multi_session ? $base_url . $arg . "/session_name/" . session_name() :  $base_url . $arg;
		}
		return   $multi_session ? "." . $arg . "/session_name/" . session_name() : "." . $arg;
	}

	function getAbsoluteImageURL($arg) {
		$base_url = $this->config->getValue('FW', 'base_url');
		$image_dir = $this->config->getValue('FW', 'image_dir');
		if (isset ($base_url)) {
			return "$base_url/$image_dir/$arg";
		}
		return "./$image_dir/$arg";
	}
	
	function callMethod($action) {
		/* Redirect to a different page in the current directory that was requested */
		$host = $_SERVER['HTTP_HOST'];
		$uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
		$protocol = "http";
		if (isset ($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != '') {
			$protocol = "https";
		}
		//if ($this->config->getValue('FW', 'https') == true) {
		//	$protocol = "https";
		//}
		header("Location: $protocol://$host$uri/$action");
		exit;
	}
	function callModuleMethod($module, $action, $new_args = array()) {
		$extended_url_str = "";
		foreach ($new_args as $key => $val) {
			$extended_url_str .= "/$key/$val";
		}
		
		if ($this->config->getValue('FW', 'multi_session') == true && $this->getIsAuthorised()) {
			$extended_url_str .= "/session_name/" . session_name();
		}
		$base_url = $this->config->getValue('FW', 'base_url');
		header("Location: $base_url/$module/$action" . $extended_url_str);
		exit;
	}
	
	function callSaveASContent($file_name, $content, $type = 'pdf') {
		header('Content-type: application/' . $type);

		// It will be called downloaded.pdf
		header("Content-Disposition: attachment; filename=\"$file_name\"");
		echo "$content";
		exit ();
	}

	function setParameter($key, $value) {
		$_SESSION[$key] = $value;
	}
	function getParameter($key) {
		if (empty ($_SESSION[$key])) {
			return;
		}
		return $_SESSION[$key];
	}
	function unsetParameter($key) {
		if (isset ($key)) {
			unset ($_SESSION[$key]);
		}
	}
	function setCurrentHREF($current_href) {

		$old_current_href = $this->getParameter($this->CURRENT_HREF_KEY);
		if (!isset ($old_current_href)) {
			$old_current_href = $this->js_back_href;
		}
		if ($old_current_href == $current_href) {
			$old_current_href = $this->js_back_href;
		}
		//make old href the back 
		$this->setParameter($this->BACK_HREF_KEY, $old_current_href);
		//store the new current href
		$this->setParameter($this->CURRENT_HREF_KEY, $current_href);
	}
	function getBackHREF() {

		$back_href = $this->getParameter($this->BACK_HREF_KEY);
		if (!isset ($back_href)) {
			return $this->js_back_href;
		}
		return $back_href;
	}



	
	function getFlashMessageObj() {
		$message_obj = $this->getParameter($this->MESSAGE_KEY);
		$this->setParameter($this->MESSAGE_KEY, null);
		return $message_obj;
	}
	
	function setFlashMessage($message, $error=false) {
		$message_obj = new StdClass;
		$message_obj->message = $message;
		$message_obj->error_type =  $error ? true : false;
		$this->setParameter($this->MESSAGE_KEY, $message_obj);
	}
	
	
	function appendMessage($message) {
		$this->has_message = true;

		$this->message .= $message;
	}
	function appendErrorMessage($message) {
		$this->has_error = true;
		$this->error_message .= $message;
	}
	
	

	
	
	
	function getCurrentURL(){
		$current_path = isset ($_SERVER['PATH_INFO'])? $_SERVER['PATH_INFO'] : '';
	
		
		//$base_url = self :: getAbsoluteURL();
		//$current_url = $path;
		//return $current_url;
		$path_array = explode('/',$current_path);
		$path = false;
		if(is_array($path_array) && count($path_array) > 0){ 
			if(count($path_array) >= 2){
				
				for($cnt =1; $cnt < (count($path_array)); $cnt++){
					$path  .= $path_array[$cnt];
					if($cnt != (count($path_array) - 1)){
						$path .= '-';
					}
				}
			}
			else if(count($path_array) < 2){
				$path = str_replace('/','',$path);
			}
		}
		
		return $path;
	}
	/*
	function generateSignature($uri, $secret, array $args) {
		$config = $this->getConfig();
		$method = $_SERVER['REQUEST_METHOD'];
		$base_url = $config->getValue('FW', 'base_url');
		$host= str_replace(array("http://", "https://"), '',$base_url);
		$query = array();
		if (!empty ($args)) {
			ksort($args);
			foreach ($args as $k => $v) {
					$k = strtolower($k);
					$k = str_replace('%7E', '~', rawurlencode($k));
					$v = str_replace('%7E', '~', rawurlencode($v));
					$query[] = $k . '=' . $v;
			}
		
		}
		$query_str = implode('&', $query);
		
		$data = $method . "\n" . $host . "\n" . $uri . "\n" . $query_str;
		return  base64_encode(hash_hmac('sha256', $data, $secret, true));
	
	}
	function  getSignableParams($args, $key_arr) {
		if (!in_array('public_key', $key_arr)) {
			$key_arr[] = 'public_key';
		}

		$key_vals = array ();
		foreach ($key_arr as $key) {
			if (isset ($args[$key])) {
				$key_vals[$key] = $args[$key];
			}

		}
		return $key_vals;
	}
	*/		
}
?>
