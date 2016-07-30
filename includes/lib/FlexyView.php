<?php
require_once 'HTML/Template/Flexy.php';
require_once 'BaseView.php';

class FlexyView extends FW_BaseView {
	var $template_file = 'error.html';
	var $masterTemplate = 'master.html';
	var $footer = 'footer.html';
	var $action = null;
	
	//var $blocks = array();
	function FlexyView($template_file, $action) {
		$this->template_file = $template_file;
		$this->action = $action;

		$this->action->java_script_start = "<script type=\"text/javascript\"> ";
		$this->action->java_script_end = " </script>";

		foreach (get_object_vars($this->action) as $k => $v) {
			$this-> $k = $v;
		}
		$config = $this->action->getConfig();
	
		$base_url = $config->getValue('FW', 'base_url');
		$resources_folder = $config->getValue('FlexyView', 'resources_dir');
		$style_sheets = $config->getValue('FlexyView', 'style_sheets');
		$java_scripts = $config->getValue('FlexyView', 'java_scripts');
		
		$style_files = explode(",", $style_sheets);
		
	
				
		$this->style_links = "";
		
		if (is_array($style_files) && count ($style_files) > 0) {
			foreach ($style_files as $style_file) {
				$style_file = trim($style_file);
					if ($style_file != '') {
						$style_file = $base_url . "/" . $resources_folder . "/" . $style_file;
						$this->style_links .= " <link rel=\"stylesheet\" type=\"text/css\" href=\"$style_file\" />";
					}
			}
		}
		
		$javascript_files = explode(",", $java_scripts);
		$this->javascript_links = "";
		
		if (is_array($javascript_files) && count ($javascript_files) > 0) {
			foreach ($javascript_files as $javascript_file) {
				$javascript_file = trim($javascript_file);
				if ($javascript_file != '') {
					$javascript_file = $base_url . "/" . $resources_folder . "/" . $javascript_file;
					$this->javascript_links .= " <script type=\"text/javascript\" src=\"$javascript_file\"></script> ";
				}
			}
		}
	}
	
	function aurl($arg) {
		return $this->action->getAbsoluteURL($arg);
	}
	
	function iurl($arg) {
		return $this->action->getAbsoluteImageURL($arg);
	}	
	
	function display() {
		if ($this->action->getIsXMLContent()) {
			$this->action->setShowMasterTemplate(false);
			$this->action->setShowFooter(false);
			header("Content-Type:text/xml");
		}
		
		if ($this->action->getShowMasterTemplate()) {
			$config = $this->action->getConfig();
			$master = new HTML_Template_Flexy($config->getValue('HTML_Template_Flexy'));
			$master->compile($this->masterTemplate);
			$master->outputObject($this);
			
		} else {
			$this->outputContent();
			$this->outputFooter();
		}

	}
	function outputContent() {
		$config = $this->action->getConfig();
		$body = new HTML_Template_Flexy($config->getValue('HTML_Template_Flexy'));
		$body->compile($this->template_file);
		$body->outputObject($this);
		
	}
	function outputBlock($block_file) {
		$config = $this->action->getConfig();
		$block = new HTML_Template_Flexy($config->getValue('HTML_Template_Flexy'));
		$block->compile($block_file);
		$block->outputObject($this);
	}
	function outputFooter() {
		if ($this->action->getShowFooter()) {
			$this->outputBlock($this->footer);
		}
		
	}

	function increment($cnt) {
		return $cnt +1;
	}
	function incrementBy($cnt, $n) {
		return $cnt + $n;
	}
	function even($cnt) {
		return ($cnt%2) == 0 ? true: false;
	}
	
	
	function includePage($url) {
		require_once 'Util.php';
		//$page = $this->action->getParameter($url);
		//if (!isset($page)) {
		$page = Util :: readURL($url);
		//$this->action->setParameter($url, $page);
		//}

		return $page;
	}
	/* Abhilash 6.1.15 */
	function includeIE11CSS($css_file){
		$config = $this->action->getConfig();
		$base_url = $config->getValue('FW', 'base_url');
		$resources_folder = $config->getValue('FlexyView', 'resources_dir');
		$css_url = $base_url . "/" . $resources_folder . "/images/" . $css_file;
		return '<!--[if lt IE 11]><link rel="stylesheet" type="text/css" href="'.$css_url.'" /><![endif]-->';
	}
	function includeiPhoneCSS($css_file) {		
		$config = $this->action->getConfig();
		$base_url = $config->getValue('FW', 'base_url');
		$resources_folder = $config->getValue('FlexyView', 'resources_dir');
		$css_url = $base_url . "/" . $resources_folder . "/images/mini/" . $css_file;
		return 
       '<!--[if !IE]>--><link media="only screen and (max-device-width: 480px)" rel="stylesheet" type="text/css" href="'.$css_url.'" /><!--<![endif]-->';
		
	}	
	
	function includeLandingPageCSS($css_file) {	
		if($this->isLanding){ 
			$config = $this->action->getConfig();
			$base_url = $config->getValue('FW', 'base_url');
			$resources_folder = $config->getValue('FlexyView', 'resources_dir');
			$css_url = $base_url . "/" . $resources_folder . "/images/landing_page/" . $css_file;
			return '<link rel="stylesheet" type="text/css" href="'.$css_url.'" />';
		}
		return;		
	}	
	
	
	function includeThemeCSS($css_file) {
		$config = $this->action->getConfig();
		$default_theme_color = $config->getValue('THEMES', 'default_theme');
		$theme_color = isset($this->theme_color) ? $this->theme_color : $default_theme_color;			
		$base_url = $config->getValue('FW', 'base_url');
		$resources_folder = $config->getValue('FlexyView', 'resources_dir');
		$css_url = $base_url . "/" . $resources_folder . "/images/$theme_color/" . $css_file;
		return '<link rel="stylesheet" type="text/css" href="'.$css_url.'" />';		
	}	
	
	function includeIE6JS($javascript_file) {
		$config = $this->action->getConfig();
		$base_url = $config->getValue('FW', 'base_url');
		$resources_folder = $config->getValue('FlexyView', 'resources_dir');
		$javascript_url = $base_url . "/" . $resources_folder . "/js/" . $javascript_file;
		return 
       '<!--[if lt IE 7]><script type="text/javascript" src="'.$javascript_url.'"></script><![endif]-->';
		
	}
	function includeIE7CSS($css_file){
		$config = $this->action->getConfig();
		$base_url = $config->getValue('FW', 'base_url');
		$resources_folder = $config->getValue('FlexyView', 'resources_dir');
		$css_url = $base_url . "/" . $resources_folder . "/images/" . $css_file;
		return '<!--[if IE 7]><link rel="stylesheet" type="text/css" href="'.$css_url.'" /><![endif]-->'; 
	}
	/*function htmlentities($arg1, $arg2 = '') {
		//return htmlentities($this->description, ENT_QUOTES,'UTF-8');
		return htmlentities($this->$arg1.$arg2, ENT_QUOTES,'UTF-8');
		isset($this->$arg1) && method_exists($this->$arg1, $arg2))) echo $this->$arg1->$arg2();?>
	}*/
	
	
}