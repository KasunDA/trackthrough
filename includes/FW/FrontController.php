<?php
class FW_FrontController {
	var $router = null;
	var $action_controller = null;
	var $config = null;
	
	function FW_FrontController($config, $router) {
		$this->config = $config;
		$this->router = $router;
	}
	//static 
	function GET_FW_VERSION() {
		return  "1.0.5";
	}
	function GET_FW_RELEASE_DATE() {
		return  "20 Jan 2011";
	}
	//Not static
	function isValidFWVersion() {
		return  $this->config->getValue('FW', 'version') == FW_FrontController :: GET_FW_VERSION() ? true : false;
		
	}
	
	function getView() {
		require_once ('ErrorView.php');
		$args = $this->router->getArgs();
		$this->_load_action_controller();
		
		if (!$this->_isAuthorizationExempted($this->router->getModuleName())) {
			if (!$this->action_controller->getIsAuthorised()) {
				//$args['error_message'] = "You are not authorized to access this module.";
				//return new FW_ErrorView($args);
				$this->action_controller->callModuleMethod($this->router->getDefaultModule(), 'index');
			}
		}
		$view = null;
		$method_name = $this->router->getMethodName();
		$method_name = str_replace("-", "_", $method_name);
		
		//$r = new ReflectionClass($this->action_controller);
		//if ($method = $r->getMethod($method_name)) {
		//$view = $method->invoke($this->action_controller, $args);

		//}
		if (method_exists($this->action_controller, $method_name) ) {
			$view = call_user_func(array ($this->action_controller,$method_name), $args);
		} else
		if($this->_load_error_controller ()) {
		  $error_method_name = "index";
		  $this->args['error_message'] = "No such method by name $method_name";
		  $view = call_user_func(array ($this->action_controller,$error_method_name), $this->args);
		}
		else {
			
			$this->args['error_message'] = "No such method by name $method_name";
			$view = new FW_ErrorView($args);
			
		}
		return $view;
	}
	//does no authorization required
	function _isAuthorizationExempted($module) {
		$non_authorized_modules = $this->config->getValue('FW', 'non_authorized_modules');

	//	if (in_array($module, split(",", $non_authorized_modules))) {
			if (in_array($module, explode(",", $non_authorized_modules))) {
			return true;
		}
		return false;
	}
	function _load_action_controller() {
		require_once ($this->router->getClassFile());
		$class_name = $this->router->getClassName();
		
		$this->action_controller = new $class_name;
		
		if (isset ($this->action_controller)) {
			$this->action_controller->setConfig($this->config);
		}
	}
	function _load_error_controller() {
		$error_module = $this->config->getValue('FW', 'error_module');
		$error_controller =  $this->config->getValue('FW', 'error_controller');
		
		
		$error_class_file = $this->config->getValue('FW', 'module_dir') . DIRECTORY_SEPARATOR . $error_module . DIRECTORY_SEPARATOR . $error_controller.'.php';
		if (file_exists($error_class_file)) {
			require_once ($error_class_file);
			$error_class_name = $error_controller;
			$this->action_controller = new $error_class_name;
		
			if (isset ($this->action_controller)) {
				$this->action_controller->setConfig($this->config);
				return true;
			}
			
		}
		
		
		return false;
		
	}

}
?>
