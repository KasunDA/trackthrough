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

class ProjectRecord extends CommonRecord {	

	const LEAD_ID_COL = 'lead_id';
	const TEAM_ID_COL = 'team_id';  
	const TYPE_COL = 'type'; 

	const CREATED_AT_COL = 'created_at';
	const UPDATED_AT_COL = 'updated_at';
	
	const NAME_COL = 'name';
	const DESCRIPTION_COL = 'description';
	const ICON_NAME_COL = 'icon_name';
	const ATTACHMENT_NAME_COL = 'attachment_name';	
	
	const ENABLE_ISSUE_TRACKING_COL = 'enable_issue_tracking'; 
	
	const PROGRESS_COL = 'progress'; 

	const TABLE_NAME = 'project';

	private $name;
	private $description;
	private $iconName;
	private $attachmentName;
	
	private $leadId =0;
	private $leadFirstName;
	private $teamId = 0;
	private $type;
	private $enable_issue_tracking;
	private $progress;
	private $createdAt = '0000-00-00 00:00:00';
	private $updatedAt = '0000-00-00 00:00:00';
	
	function __construct($db) {
		parent :: __construct($db);
		$this->quantity = 0;
		$this->primaryKeywordCount = 0;
		$this->createdAt = date('Y-m-d H:i:s');
		$this->updatedAt = date('Y-m-d H:i:s');
		$this->enable_issue_tracking = 0;
		$this->progress = 0;
	}	

	public function getName() {
		return $this->name;
	}	

	public function getDescription() {		
		return $this->description;
		
	}	

	public function getAttachmentName() {
		return $this->attachmentName;
	}
	
	public function getIconName() {
		return $this->iconName;
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
	
	public function getEnableIssueTracking(){
		return $this->enable_issue_tracking;
	}
	public function getProgress(){
		return $this->progress;
	}
	function getTypeLabel() {
		return Constants :: getLabel($this->type);
	}
	
	public function isIssueTrackingEnabled(){
		return ($this->getEnableIssueTracking() == 1) ? true : false;
	}
	
	public function getCreatedAt() {
		return $this->createdAt;
	}
	public function getUpdatedAt() {
		return $this->updatedAt;
	}
	public function getCreatedAtFormatted() {
		return Util::getFormatedDate(strtotime($this->createdAt));
	}
		
	public function setName($name) {
		$this->name = $name;
	}
	public function setDescription($description) {
		$this->description = $description;
	}	
	
	public function setIconName($iconName) {
		$this->iconName = $iconName;
	}
	
	
	public function setAttachmentName($attachmentName) {
		$this->attachmentName = $attachmentName;
	}
	
	public function setLeadId($leadId) {
		$this->leadId = $leadId;
	}
	public function setLeadFirstName($leadFirstName) {
		$this->fromName = $leadFirstName;
	}
	public function setTeamId($teamId) {
		$this->teamId = $teamId;
	}
	public function setType($type) {
		$this->type = $type;
	}
	
	public function setEnableIssueTracking($enable_issue_tracking){
		$this->enable_issue_tracking = $enable_issue_tracking;
	}
	public function setProgress($progress){
		$this->progress = $progress;
	}
	
	public function setCreatedAt($createdAt) {
		$this->createdAt = $createdAt;
	}
	
	public function setUpdatedAt($updatedAt) {
		$this->updatedAt =  $updatedAt;
	}
	
	public function getHasProgress() {
		return ($this->progress > 0) ? true : false;
	}
		
	private $permission;
	public function setPermission($permission){
		$this->permission = $permission;
	}		
	public function getPermission(){
		return $this->permission;
	}
	
	
		
	public function getHasAttachment() {
		return ($this->attachmentName != '') ? true : false;
	}
		
	public function getHasTeam() {

		return ($this->teamId != '' && $this->teamId != 0) ? true : false;
	}	
	
	function getHasTeamId($teamId) {
		return ($this->teamId == $teamId) ? true : false;
	}

	function getTableName() {
		return self :: TABLE_NAME;
	}

	//utility functions
	private $team_signinid;
	private $lead_signinid;
	
	function setTeamSigninId($team_signinid) {
		$this->team_signinid = $team_signinid;
	}
	function setLeadSigninId($lead_signinid) {
		$this->lead_signinid = $lead_signinid;
	}
	function getTeamSigninId() {
		return $this->team_signinid;
	}
	function getLeadSigninId() {
		return $this->lead_signinid;
	}
	
	
	function isSelectedProject($project_record,$selected_project_id) {
		$project_id = $project_record->getId();
		return ($project_id == $selected_project_id) ? true : false;
	}
	
	//helper for flexy template
	function getHasId($id) {
		return $this->getId() == $id ? true : false;
	}
	
	
	function getNameValueAssoc() {

		return array (
			
			self :: NAME_COL => $this->name,
			self :: DESCRIPTION_COL => $this->description,
			self :: ICON_NAME_COL => $this->iconName,
			self :: ATTACHMENT_NAME_COL => $this->attachmentName,
            self :: LEAD_ID_COL => $this->leadId,
			self :: TEAM_ID_COL => $this->teamId,
			self :: TYPE_COL => $this->type,
			self :: ENABLE_ISSUE_TRACKING_COL => $this->enable_issue_tracking, 
			self :: PROGRESS_COL => $this->progress,
			self :: CREATED_AT_COL => $this->createdAt,
			self :: UPDATED_AT_COL => $this->updatedAt,
		);
	}
	
}
?>