<?php
$instance = array ();
class Config {
	//megha
	public static function getIniConfig($iniFile, $baseConfig = NULL) {
		if (!isset ($instance[$iniFile]) || $instance[$iniFile] == NULL) {
			$instance[$iniFile] = new Config($iniFile, $baseConfig);
		}
		return $instance[$iniFile];
	}
	function __clone() {
		//todo  throw exception
	}
	function getValue($section, $attrib = NULL) {
		if (isset ($attrib) && $attrib != NULL) {
			if (isset ($this->config[$section][$attrib])) {
				return $this->config[$section][$attrib];
			} else {
				return $this->getBaseConfigValue($section, $attrib);
			}
		}
		return $this->config[$section] ? $this->config[$section] : $this->getBaseConfigValue($section, $attrib);

	}
	function getBaseConfigValue($section, $attrib = NULL) {
		if(!is_null($this->baseConfig)) {
			$val = $this->baseConfig->getValue($section, $attrib);
			if(!is_null($val) && $val) {
				return $val;
			}
			
		}
	}

	function Config($iniFile, $baseConfig = NULL) {
		if(file_exists($iniFile)){ 
		$this->config = parse_ini_file($iniFile, TRUE);
		}
		$this->baseConfig = $baseConfig;

	}
	var $config;
	var $baseConfig;
}
?>
