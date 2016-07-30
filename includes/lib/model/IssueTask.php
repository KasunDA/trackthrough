<?php


/*
 * Created on April 12, 2009
 *
 * bispark software services
 * www.bispark.com
 */

require_once 'CommonRecord.php';
require_once 'Constants.php';

class IssueTask extends CommonRecord {

	const ISSUE_ID_COL = 'issue_id';
	const TASK_ID_COL = 'task_id';
	
	const TABLE_NAME = 'issue_task';

	private $issueId;
	private $taskId;
		

	function __construct($db) {
		parent :: __construct($db);
		$this->quantity = 0;
		$this->primaryKeywordCount = 0;
	}
	
	public function getIssueId() {
		return $this->issueId;
	}	
	public function getTaskId() {
		return $this->taskId;
	}
	

	public function setIssueId($issueId) {
		 $this->issueId = $issueId;
	}	
	
	public function setTaskId($taskId) {
		 $this->taskId = $taskId;
	}	
		
	function getTableName() {
		return self :: TABLE_NAME;
	}

	function getNameValueAssoc() {
		return array (			
			self :: ISSUE_ID_COL => $this->issueId,
			self :: TASK_ID_COL => $this->taskId,
			
		);
	}
}
?>