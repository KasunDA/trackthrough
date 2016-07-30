<?php


/*
 * Created on May 14, 2009
 *
 * bispark software services
 * www.bispark.com
 */

require_once 'CommonRecord.php';
require_once 'Constants.php';
class ConfigRecord extends CommonRecord {

	const KEY_NAME_COL = 'key_name';
	const VALUE_COL = 'value';
	const TYPE_COL = 'type';
	const CREATED_AT_COL = 'created_at';

	const TABLE_NAME = 'config';

	private $keyName;
	private $value;
	private $type;


	function __construct($db) {
		parent :: __construct($db);
		$this->type = Constants::VALUE_TYPE;
	}

	public function getId() {
		return $this->id;
	}
	public function getKeyName() {
		return $this->keyName;
	}
	public function getValue() {
		return $this->value;
	}
	public function getType() {
		return $this->type;
	}
	
	public function setKeyName($keyName) {
		$this->keyName = $keyName;
	}

	public function setValue($value) {
	$this->value = $value;
	}
	public function setId($id) {
		$this->id =$id;
	}
	public function setType($type) {
		 $this->type = $type;
	}

function getIsBooleanType() {
	return ($this->type == Constants::BOOLEAN_TYPE ) ? true : false;
}

function getIsTrue() {
	return ($this->value == 1 ) ? true : false;
}
	function getTableName() {
		return self :: TABLE_NAME;
	}

	function getNameValueAssoc() {

		return array (
			self :: KEY_NAME_COL => $this->keyName,
			self :: VALUE_COL => $this->value,
			self::TYPE_COL => $this->type,
		);

	}

}
?>