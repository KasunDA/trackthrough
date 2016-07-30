<?php


/*
 * Created on April 12, 2009
 *
 * bispark software services
 * www.bispark.com
 */

require_once 'CommonRecord.php';
require_once 'Constants.php';

class PreferenceRecord extends CommonRecord {	

	const USER_ID_COL = 'user_id';  
	const SETTINGS_COL = 'settings';
	
	const TABLE_NAME = 'preference';

	
	private $userId = 0;
	private $settings =0;
	
	function __construct($db) {
		parent :: __construct($db);
	}	

	
	public function getUserId() {
		return $this->userId;
	}
	
	public function setUserId($userId) {
		$this->userId = $userId;
	}
	
	public function setSettings($settings) {
		$this->settings = $settings;
	}
	public function getSettings() {
		return $this->settings;
	}
	
	public function isVisible() {
		return ($this->visibility == '1') ? true : false;
	}
	
	function getTableName() {
		return self :: TABLE_NAME;
	}
	
	
	
	//utility functions
	
	function getNameValueAssoc() {

		return array (
			self :: USER_ID_COL => $this->userId,
			self :: SETTINGS_COL => $this->settings,
		);
	}
}

?>