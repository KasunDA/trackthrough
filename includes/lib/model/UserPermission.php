<?php
/*
 * Created on July 13, 2012
 *
 * bispark software services
 * www.bispark.com
 */

require_once 'CommonRecord.php';
require_once 'Util.php';
require_once 'Constants.php';
class UserPermission extends CommonRecord{
	const ID_COL='id'; 
	const USER_ID_COL = 'user_id';
	const RECORD_TYPE_COL = 'record_type';
	const PERMISSION_COL = 'permission';
	const RECORD_ID_COL = 'record_id';
	const TABLE_NAME = 'user_permission';
	
	protected $id;
	private $userId;
	private $recordType;
	private $permission;
	private $recordId;
	
	
	function __construct($db) {
		parent::__construct($db);
	}	
	
	public function getId() {
		return $this->id;
	}
	public function getUserId() {
		return $this->userId;
	}
	public function getPermission() {
		return $this->permission;
	}
	public function getRecordType() {
		return $this->recordType;
	}
	public function getRecordId() {
		return $this->recordId;
	}
		
	public function setUserId($userId) {
		 $this->userId = $userId;
	}
	public function setPermission($permission) {
		 $this->permission = $permission;
	}
	public function setRecordType($recordType) {
		 $this->recordType = $recordType;
	}
	public function setRecordId($recordId) {
		$this->recordId = $recordId;
	}
	
	function getTableName()  {
		return self::TABLE_NAME;
	}
		
	function getNameValueAssoc() {
		return array (
			self::USER_ID_COL => $this->userId,
			self::PERMISSION_COL => $this->permission,
			self::RECORD_TYPE_COL => $this->recordType,
			self::RECORD_ID_COL => $this->recordId,
		);
	}
 
}
?>