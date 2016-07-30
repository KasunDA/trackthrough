<?php


/*
 * Created on April 12, 2009
 *
 * bispark software services
 * www.bispark.com
 */

require_once 'CommonRecord.php';
require_once 'Constants.php';

class BookmarkRecord extends CommonRecord {	

	const USER_ID_COL = 'user_id';  
	const CATEGORY_COL = 'category';
	const CATEGORY_ID_COL = 'category_id';
	
	
	const TABLE_NAME = 'bookmark';

	
	private $userId = 0;
	private $category;
	private $categoryId = 0;
	private $label;
	private $categoryLabel;
	private $projectId;
	private $taskId; 
	private $issueId; 
	private $bookmark_icon;
	
	function __construct($db) {
		parent :: __construct($db);
	}	

	
	public function getUserId() {
		return $this->userId;
	}
	
	public function setUserId($userId) {
		$this->userId = $userId;
	}
	
	public function setCategory($category) {
		$this->category = $category;
	}
	public function getCategory() {
		return $this->category;
	}
	
	public function setCategoryId($category_id) {
		$this->categoryId = $category_id;
	}
	public function getCategoryId() {
		return $this->categoryId;
	}
	
	public function setBookmarkDescription($label){
		$this->label = $label;
	}
	
	public function getBookmarkDescription(){
		return $this->label;
	}
	
	public function setBookmarkCategoryLabel($label){
		$this->categoryLabel = $label;
	}
	
	public function getBookmarkCategoryLabel(){
		return $this->categoryLabel;
	}
	
	function getTableName() {
		return self :: TABLE_NAME;
	}
	
	function getIsProjectBookmark(){
		return ($this->category == Constants :: PROJECT) ? true : false;
	}
	function getIsTaskBookmark(){
		return ($this->category == Constants :: TASK) ? true : false;
	}
	
	function getIsProjectMessageBookmark(){
		return ($this->category == Constants :: PROJECT_MESSAGE) ? true : false;
	}
	function getIsTaskMessageBookmark(){
		return ($this->category == Constants :: TASK_MESSAGE) ? true : false;
	}
	
	
	function getIsIssueBookmark(){
		return ($this->category == Constants :: ISSUE) ? true : false;
	}
		
	function getIsIssueMessageBookmark(){
		return ($this->category == Constants :: ISSUE_MESSAGE) ? true : false;
	}
	
	
	
	
	function setMessageProjectId($project_id){
		$this->projectId = $project_id;
	}
	function getMessageProjectId(){
		return $this->projectId;
	}
	
	function setMessageTaskId($task_id){
		$this->taskId = $task_id;
	}
	function getMessageTaskId(){
		return $this->taskId;
	}
	
	function setMessageIssueId($issue_id){
		$this->issueId = $issue_id;
	}
	function getMessageIssueId(){
		return $this->issueId;
	}
	
	
	function setBookmarkIcon($icon){
		$this->icon = $icon;
	}
	
	function getBookmarkIcon(){
		return $this->icon;
	}
	
	
	
	
	//utility functions
	
	function getNameValueAssoc() {

		return array (
			self :: USER_ID_COL => $this->userId,
			self :: CATEGORY_COL => $this->category,
			self :: CATEGORY_ID_COL => $this->categoryId,
		);
	}
}


?>