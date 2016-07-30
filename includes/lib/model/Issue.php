<?php


/*
 * Created on April 12, 2009
 *
 * bispark software services
 * www.bispark.com
 */

require_once 'CommonRecord.php';
require_once 'Constants.php';
require_once 'Util.php';

class Issue extends CommonRecord {

	const PROJECT_ID_COL = 'project_id';
	const USER_ID_COL = 'user_id';
	const TYPE_COL = 'type';
	const STATUS_COL = 'status';	
	const PRIORITY_COL = 'priority';	
	const CREATED_AT_COL = 'created_at';
	const UPDATED_AT_COL = 'updated_at';	
	const TITLE_COL = 'title';
	const DESCRIPTION_COL = 'description';
	const ATTACHMENT_NAME_COL = 'attachment_name';
	const TABLE_NAME = 'issue';

	private $projectId;
	private $title;
	private $description;
	private $status;
	private $attachmentName;
	private $userId;
	private $userFirstName;
	private $issueId = 0;
	private $priority = 0;
	private $type;
	private $createdAt = '0000-00-00 00:00:00';
	private $updatedAt = '0000-00-00 00:00:00';	
		

	function __construct($db) {
		parent :: __construct($db);
		$this->quantity = 0;
		$this->primaryKeywordCount = 0;
		$this->priority = Constants::NORMAL_PRIORITY;
		$this->createdAt = date('Y-m-d H:i:s');
		$this->updatedAt = date('Y-m-d H:i:s');
	}
	
	public function getProjectId() {
		return $this->projectId;
	}	
	public function getTitle() {
		return $this->title;
	}	
	public function getDescription() {
		return $this->description;
	}
	
	public function getAttachmentName() {
		return $this->attachmentName;
	}
	public function getUserId() {
		return $this->userId;
	}
	public function getUserFirstName() {
		return $this->userFirstName;
	}
	
	public function getType() {
		return $this->type;
	}
	
	function getTypeLabel() {
		return Constants :: getLabel($this->type);
	}

	public function getStatus() {
		return $this->status;
	}
	public function getPriority() {
		return $this->priority;
	}
	
	function getStatusLabel() {
		return Constants :: getLabel($this->status);
	}

	public function getCreatedAt() {
			return $this->createdAt;
	}
		
	public function getCreatedAtFormatted() {
		return Util::getFormatedDate(strtotime($this->createdAt));
	}
	
	public function getUpadtedAt() {
		return $this->updatedAt;
	}	
	

	public function setProjectId($projectId) {
		 $this->projectId = $projectId;
	}	
	public function setTitle($title) {
		$this->title = $title;
	}
	public function setDescription($description) {
		$this->description = $description;
	}
	
	public function setAttachmentName($attachmentName) {
		$this->attachmentName = $attachmentName;
	}

	public function setUserId($userId) {
		$this->userId = $userId;
	}
	
	public function setType($type) {
		$this->type = $type;
	}
	public function setPriority($priority) {
		$this->priority = $priority;
	
	}
	public function setStatus($status) {
		$this->status = $status;
				$this->updatedAt =  date('Y-m-d H:i:s');				
		 
	}
	public function setCreatedAt($createdAt) {
		$this->createdAt = $createdAt;
	}	
	
	public function setUpdatedAt($updatedAt) {
		$this->updatedAt =  $updatedAt;
	}
		
	public function getHasAttachment() {
		return ($this->attachmentName != '') ? true : false;
	}		
	
	public function getIsOpen() {
		return ($this->status == Constants :: ISSUE_OPEN) ? true : false;
	}
	public function getIsClosed() {
		return ($this->status == Constants :: ISSUE_CLOSED) ? true : false;
	}	
	
	function getHasUserId($userId) {
		return ($this->userId == $userId) ? true : false;
	}
		
	function getStatusDate() {
		
		return $this->updatedAt ;
	}
	public function getStatusDateFormatted() {
	//	return date('d-m-Y g:i a', strtotime($this->getStatusDate()));
		return  Util::getFormatedDate(strtotime($this->getStatusDate()));
	}
	
	
	function getTableName() {
		return self :: TABLE_NAME;
	}

	//utility functions
	private $user_signinid;
	function setUserSigninId($user_signinid) {
		$this->user_signinid = $user_signinid;
	}
		
	function getUserSigninId() {
		return $this->user_signinid;
	}
	
	function isOpen(){
		return ($this->getStatus() == Constants :: ISSUE_OPEN)? true : false;
	}
	
	function isClosed(){
		return ($this->getStatus() == Constants :: ISSUE_CLOSED)? true : false;
	}
	
	private $permission;
	public function setPermission($permission){
		$this->permission = $permission;
	}		
	public function getPermission(){
		return $this->permission;
	}
	
	
	
	public function isParentProject($project_record,$task_record){
		if($project_record->getId() ==  $task_record->getParentProjectId()){
			return true;
		}else{
			return false;
		}
	}
	
	public function getIsLowPriority(){
		return $this->priority == Constants::LOW_PRIORITY;
	}
	public function getIsHighPriority(){
		return $this->priority == Constants::HIGH_PRIORITY;
	}
	public function getIsNormalPriority(){
		return  /*!$this->priority ||*/ $this->priority == Constants::NORMAL_PRIORITY;
	}
	
	public function getPriorityLabel(){
		$str = "";
		switch ($this->priority) {
			case Constants::LOW_PRIORITY:
			$str="low";
			break;
			case Constants::HIGH_PRIORITY:
			$str="high";
			break;
				case Constants::NORMAL_PRIORITY:
			$str="medium";
			break;
			
			default:
			break;
		}
		return $str;
	}
	public function getPriorityLabelFormatted(){
		
		return ucfirst($this->getPriorityLabel());
	}
	
	
	
	
	function getNameValueAssoc() {
		return array (			
			self :: PROJECT_ID_COL => $this->projectId,
			self :: TITLE_COL => $this->title,
			self :: DESCRIPTION_COL => $this->description,
			self :: ATTACHMENT_NAME_COL => $this->attachmentName,
			self :: USER_ID_COL => $this->userId,
			self :: TYPE_COL => $this->type,
			self :: STATUS_COL => $this->status,
			self :: PRIORITY_COL => $this->priority,
			self :: CREATED_AT_COL => $this->createdAt,
			self :: UPDATED_AT_COL => $this->updatedAt,
		);
	}
}
?>