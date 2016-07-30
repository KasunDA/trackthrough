<?php


/*
 * Created on April 22, 2009
 *
 * bispark software services
 * www.bispark.com
 */

require_once 'CommonRecord.php';
require_once 'Constants.php';
require_once 'Util.php';

class MessageRecord extends CommonRecord {

	const MESSAGE_COL = 'cont';
	const TYPE_COL = 'type';
	const ATTACHMENT_NAME_COL = 'attachment_name';
	const FROM_ID_COL = 'from_id';
	const TYPE_ID_COL = 'type_id';
	const SUBJECT_COL = 'subject';
	const STATUS_COL= 'status';
	const DATE_COL = 'date';
	const TABLE_NAME = 'message';	

	private $cont;
	private $subject;
	private $status;
	private $type;
	private $date = '0000-00-00 00:00:00';
	private $typeId = 0;
	private $fromId = 0;
	private $attachmentName;

	function __construct($db) {
		parent :: __construct($db);
		$this->quantity = 0;
		$this->primaryKeywordCount = 0;
		$this->date = date("Y-m-d H:i:s");
		$this->status = Constants::NONE;
	}

	public function getType() {
		return $this->type;
	}
	public function getCont() {
		return $this->cont;
		
	}
	public function getSubject() {
		return $this->subject;
	}
	public function getStatus() {
		return $this->status;
	}
	public function getDate() {
		return $this->date;
	}
	public function getProjectId() {
		return $this->projectId;
	}
	public function getTaskId() {
		return $this->taskId;
	}
	public function getDateFormatted() {
	return Util::getFormatedDate(strtotime($this->date));
		
	}
	public function getTypeId() {
		return $this->typeId;
	}
	
	public function getFromId() {
		return $this->fromId;
	}
	
	public function getAttachmentName() {
		return $this->attachmentName;
	}

	public function setCont($cont) {
		$this->cont = $cont;
	}
	public function setSubject($subject) {
		 $this->subject = $subject;
	}
	public function setStatus($status) {
		 $this->status = $status;
	}
	public function setDate($date) {
		$this->date = $date;
	}
	public function setType($type) {
		$this->type = $type;
	}
	public function setTypeId($typeId) {
		$this->typeId = $typeId;
	}
	
	public function setFromId($fromId) {
		$this->fromId = $fromId;
	}
	
	public function setAttachmentName($attachmentName) {
		$this->attachmentName = $attachmentName;
	}
	

	public function getHasAttachment() {
		return ($this->attachmentName != '') ? true : false;
	}
	/*
	public function getIsNew() {
		return  ($this->status == Constants::NEW_MESSAGE) ? true : false;
	}*/
	public function getIsDeleted() {
		return  ($this->status == Constants::DELETED_MESSAGE) ? true : false;
	}
	function getTableName() {
		return self :: TABLE_NAME;
	}

	//helper functions
	private $isUnRead;
	private $fromSigninId;
	private $fromSelf;
	private $toSelf;	
	private $fromName;
	private $isProjectMessage;
	private $isTaskMessage;
	private $isIssueMessage;
	
	public function getIsUnread() {
		return $this->isUnRead ;
	}
	public function getFromSigninId() {
		return $this->fromSigninId;
	}
	public function getToSigninId() {
		return $this->toSigninId;
	}
	public function getFromSelf() {
		return $this->fromSelf ;
	}
	public function getToSelf() {
		return $this->toSelf ;
	}
	public function setIsUnread($isUnRead) {
		$this->isUnRead = $isUnRead;
	}
	public function setFromSigninId($fromSigninId) {
		$this->fromSigninId = $fromSigninId;
	}
	
	public function setFromSelf($fromSelf) {
		$this->fromSelf = $fromSelf;
	}
	public function setToSelf($toSelf) {
		$this->toSelf = $toSelf;
	}
	
	public function getFromName() {
		return $this->fromName;
	}
	public function setProjectId($projectId) {
		$this->projectId = $projectId;
	}
	public function setTaskId($taskId) {
		$this->taskId = $taskId;
	}
	public function setFromName($fromName) {
		$this->fromName = $fromName;
	}
		
	public function setIsProjctMessage(){
		$this->isProjectMessage = true;
	}
	public function setIsTaskMessage(){
		$this->isTaskMessage = true;
	}
	
	public function setIsIssueMessage(){
		$this->isIssueMessage = true;
	}
	
	
	public function getIsProjctMessage(){
		return ($this->type == Constants :: PROJECT_MESSAGE) ? true : false;
	}
	
	public function getIsTaskMessage(){
		return ($this->type == Constants :: TASK_MESSAGE) ? true : false;
	}
	
	public function getIsIssueMessage(){
		return ($this->type == Constants :: ISSUE_MESSAGE) ? true : false;
	}
	
	function getNameValueAssoc() {
		return array (
			self :: MESSAGE_COL => $this->cont,
			self :: TYPE_COL => $this->type,
			self :: DATE_COL => $this->date,
			self :: ATTACHMENT_NAME_COL => $this->attachmentName,
			self :: FROM_ID_COL => $this->fromId,
			self :: TYPE_ID_COL => $this->typeId,
			self :: SUBJECT_COL => $this->subject,
			self :: STATUS_COL => $this->status
		);

	}

}
?>