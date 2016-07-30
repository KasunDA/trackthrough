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

class TaskRecord extends CommonRecord {

	const PARENT_PROJECT_ID_COL = 'parent_project_id';
	const LEAD_ID_COL = 'lead_id';
	const TEAM_ID_COL = 'team_id';
	const TYPE_COL = 'type';
	const STATUS_COL = 'status';	
	const PRIORITY_COL = 'priority';	
	const CREATED_AT_COL = 'created_at';
	const ASSIGNED_AT_COL = 'assigned_at';	
	const UPDATED_AT_COL = 'updated_at';	
	const NAME_COL = 'name';
	const DESCRIPTION_COL = 'description';
	const ATTACHMENT_NAME_COL = 'attachment_name';
	const PROGRESS_COL = 'progress';
	const TABLE_NAME = 'task';

	private $parentProjectId;
	private $name;
	private $topic;
	private $description;
	private $status;
	private $attachmentName;
	private $leadId;
	private $leadFirstName;
	private $teamId = 0;
	private $taskId = 0;
	private $type;
	private $progress = 0;
	private $priority;
	
	private $createdAt = '0000-00-00 00:00:00';
	private $assignedAt = '0000-00-00 00:00:00';
	private $updatedAt = '0000-00-00 00:00:00';	
		

	function __construct($db) {
		parent :: __construct($db);
		$this->quantity = 0;
		$this->primaryKeywordCount = 0;
		$this->priority = Constants::NORMAL_PRIORITY;
		$this->createdAt = date('Y-m-d H:i:s');
		$this->updatedAt = date('Y-m-d H:i:s');
	}
	
	public function getParentProjectId() {
		return $this->parentProjectId;
	}	
	public function getName() {
		return $this->name;
	}	
	public function getDescription() {
		return $this->description;
	}
	public function getTopic() {
		return $this->topic;
	}
	public function getAttachmentName() {
		return $this->attachmentName;
	}
	public function getLeadId() {
		return $this->leadId;
	}
	public function getLeadFirstName() {
		return $this->leadFirstName;
	}
	public function getTeamId() {
		return $this->teamId;
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
	public function getProgress() { 
		return $this->progress;
	}	
	
	
	function getStatusLabel() {
		return Constants :: getLabel($this->status);
	}
	public function getCreatedAt() {
		return $this->createdAt;
	}	
	public function getAssignedAt() {
		return $this->assignedAt;
	}
	public function getUpadtedAt() {
		return $this->updatedAt;
	}	
	

	public function setParentProjectId($parentProjectId) {
		 $this->parentProjectId = $parentProjectId;
	}	
	public function setName($name) {
		$this->name = $name;
	}
	public function setDescription($description) {
		$this->description = $description;
	}
	public function setTopic($topic) {
		$this->topic = $topic;
	}
	public function setAttachmentName($attachmentName) {
		$this->attachmentName = $attachmentName;
	}

	public function setLeadId($leadId) {
		$this->leadId = $leadId;
	}
	public function setTeamId($teamId) {
		$this->teamId = $teamId;
	}
	public function setType($type) {
		$this->type = $type;
	}
	public function setStatus($status) {
		$this->status = $status;
		$this->updatedAt =  date('Y-m-d H:i:s');
		
	}
	public function setPriority($priority) {
		$this->priority = $priority;
	
	}
	public function setCreatedAt($createdAt) {
		$this->createdAt = $createdAt;
	}	
	
	public function getCreatedAtFormatted() {
		return Util::getFormatedDate(strtotime($this->createdAt));
	}
	
	public function setAssignedAt($assignedAt) {
		$this->assignedAt = $assignedAt;
	}
	public function setUpdatedAt($updatedAt) {
	
		
		$this->updatedAt = $updatedAt;
		}
	public function setProgress($progress) {
		$this->progress = $progress;
	}
	
	public function getHasAttachment() {
		return ($this->attachmentName != '') ? true : false;
	}		
	public function getHasTeam() { 
		return ($this->teamId != '' && $this->teamId != 0) ? true : false;
	}
	public function getIsOpen() {
		return ($this->status == Constants :: TASK_OPEN) ? true : false;
	}
	public function getIsInProgress() {
		return ($this->status == Constants :: TASK_INPROGRESS) ? true : false;
	}	
	public function getIsInReviewStatus() {
		return ($this->status == Constants :: TASK_REVIEW_PENDING) ? true : false;
	}
	public function getIsClosed() {
		return ($this->status == Constants :: TASK_CLOSED) ? true : false;
	}	
	function getHasTeamId($teamId) {
		return ($this->teamId == $teamId) ? true : false;
	}
	function getIsAssignedTeam($team) {
		return ($this->teamId == $team->getId()) ? true : false;
	}	
	function getStatusDate() {
		return  $this->updatedAt;
		
		
	}
	public function getStatusDateFormatted() {
				return Util::getFormatedDate(strtotime($this->getStatusDate()));
	}
	
	public function getHasProgress() {
		return ($this->progress > 0) ? true : false;
	}
	//for flexy
	public function getHasProgressValue($val) {
		return ($this->progress == $val)? true: false;
	}

	function getTableName() {
		return self :: TABLE_NAME;
	}

	//utility functions
	private $team_signinid;
	function setTeamSigninId($team_signinid) {
		$this->team_signinid = $team_signinid;
	}
	private $assignedUids;
	function setAssignedUids($assignedUids) {
		$this->assignedUids = $assignedUids;
	}
	
	private $lead_signinid;
	function setLeadSigninId($lead_signinid) {
		$this->lead_signinid = $lead_signinid;
	}	
	function getTeamSigninId() {
		return $this->team_signinid;
	}
	function getLeadSigninId() {
		return $this->lead_signinid;
	}	
	function getAssignedUids() {
		return $this->assignedUids;
	}
	
	private $permission;
	public function setPermission($permission){
		$this->permission = $permission;
	}		
	public function getPermission(){
		return $this->permission;
	}
	
	
	private $isViewOnly;
	public function setIsViewOnly($isViewOnly){
		$this->isViewOnly = $isViewOnly;
	}		
	public function getIsViewOnly(){
		return $this->isViewOnly;
	}
	
	public function isParentProject($project_record,$task_record){
		if($project_record->getId() ==  $task_record->getParentProjectId()){
			return true;
		}else{
			return false;
		}
	}
	
	function getSingleTeamName(){
		$team_names = $this->getTeamSigninId();
		$team_names = @explode(", ", $team_names);
		return (is_array($team_names) && count($team_names) > 0)? $team_names[0] : '';
	}
	
	function hasMultipleTeam(){
		$team_names = $this->getTeamSigninId();
		$team_names = @explode(", ", $team_names);
		return (count($team_names) > 1)? true : false;
	}
	public function getIsLowPriority(){
		return $this->priority == Constants::LOW_PRIORITY;
	}
	public function getIsHighPriority(){
		return $this->priority == Constants::HIGH_PRIORITY;
	}
	public function getIsNormalPriority(){
		return /*!$this->priority || */ $this->priority == Constants::NORMAL_PRIORITY;
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
			self :: PARENT_PROJECT_ID_COL => $this->parentProjectId,
			self :: NAME_COL => $this->name,
			self :: DESCRIPTION_COL => $this->description,
			self :: ATTACHMENT_NAME_COL => $this->attachmentName,
			self :: LEAD_ID_COL => $this->leadId,
			self :: TEAM_ID_COL => $this->teamId,
			self :: TYPE_COL => $this->type,
			self :: STATUS_COL => $this->status,
			self :: PRIORITY_COL => $this->priority,
			self :: CREATED_AT_COL => $this->createdAt,
			self :: ASSIGNED_AT_COL => $this->assignedAt,
			self :: UPDATED_AT_COL => $this->updatedAt,
			self :: PROGRESS_COL => $this->progress,
		);
	}
}
?>