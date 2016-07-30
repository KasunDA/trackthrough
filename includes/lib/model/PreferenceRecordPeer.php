<?php


/*
 * Created on April 17, 2009
 *
 * bispark software services
 * www.bispark.com
 */
require_once 'CommonRecord.php';
require_once 'UserRecord.php';
require_once 'ProjectRecord.php';
require_once 'TaskRecord.php';
require_once 'PreferenceRecord.php';
require_once 'ConfigRecordPeer.php';
require_once 'Util.php';
require_once 'Constants.php';

class PreferenceRecordPeer {
	const PREFERENCE_CACHE = 'PREFERENCE_CACHE';
	
	
	public static function makeProjectAndIssuesVisible($action_obj,$user_id, $project_id) {
		self::setProjectFilter($action_obj, $user_id, $project_id);
				self::setProjectVisible($action_obj, $user_id,$project_id,true );
				self::setIssueVisible($action_obj, $user_id,$project_id,true );
				self::setIssueStatusFilter($action_obj, $user_id, $project_id, 0); //for all
			
	}
	public static function makeProjectAndTasksVisible($action_obj,$user_id, $project_id) {
		self::setProjectFilter($action_obj, $user_id, $project_id);
				self::setProjectVisible($action_obj, $user_id,$project_id,true );
				self::setTaskVisible($action_obj, $user_id,$project_id,true );
				self::setTaskStatusFilter($action_obj, $user_id, $project_id, 0); //for all
			
	}
	
	
	public static function setProjectFilter($action_obj, $user_id, $project_id) {
		$key = 'project_filter';
		
		self::setValues($action_obj, $user_id, array($key=>$project_id));
	}
	public static function getProjectFilter($action_obj,$user_id ) {
		$key = 'project_filter';
		
		$value = self::getValue($action_obj, $user_id, $key);
		if($value == '' ) {
			$value = 0;
		}
		return $value;
		
	}
	
	
	
	public static function setTaskPriorityFilter($action_obj, $user_id, $project_id, $status) {
		$key = 'project_task_priority_'.$project_id;
		
		self::setValues($action_obj, $user_id, array($key=>$status));
		
	}
	public static function getTaskPriorityFilter($action_obj,$user_id, $project_id ) {
		$key = 'project_task_priority_'.$project_id;
		
		
		$value = self::getValue($action_obj, $user_id, $key);
		if($value == '' ) {
			$value = 0;
		}
		return $value;
		
	}
	
	public static function setIssuePriorityFilter($action_obj, $user_id, $project_id, $status) {
		$key = 'project_issue_priority_'.$project_id;
		
		self::setValues($action_obj, $user_id, array($key=>$status));
		
	}
	public static function getIssuePriorityFilter($action_obj,$user_id, $project_id ) {
		$key = 'project_issue_priority_'.$project_id;
		
		
		$value = self::getValue($action_obj, $user_id, $key);
		if($value == '' ) {
			$value = 0;
		}
		return $value;
		
	}
	
	
	public static function setTaskStatusFilter($action_obj, $user_id, $project_id, $status) {
		$key = 'project_task_status_'.$project_id;
		
		self::setValues($action_obj, $user_id, array($key=>$status));
		
	}
	public static function getTaskStatusFilter($action_obj,$user_id, $project_id ) {
		$key = 'project_task_status_'.$project_id;
		
		
		$value = self::getValue($action_obj, $user_id, $key);
		if($value == '' ) {
			$value = 0;
		}
		return $value;
		
	}
	
	public static function setTaskSortOrder($action_obj, $user_id, $project_id, $sort_order='asc') {
		$key = 'project_task_sort_order_'.$project_id;
		
		self::setValues($action_obj, $user_id, array($key=>$sort_order));
		
	}
	public static function getTaskSortOrder($action_obj,$user_id, $project_id ) {
		$key = 'project_task_sort_order_'.$project_id;
		
		
		$value = self::getValue($action_obj, $user_id, $key);
		if($value == '' ) {
			$value = 'asc';
		}
		return $value;
		
	}
	
	public static function setIssueSortOrder($action_obj, $user_id, $project_id, $sort_order='asc') {
		$key = 'project_issue_sort_order_'.$project_id;
		
		self::setValues($action_obj, $user_id, array($key=>$sort_order));
		
	}
	public static function getIssueSortOrder($action_obj,$user_id, $project_id ) {
		$key = 'project_issue_sort_order_'.$project_id;
		
		
		$value = self::getValue($action_obj, $user_id, $key);
		if($value == '' ) {
			$value = 'asc';
		}
		return $value;
		
	}
	
	public static function setIssueStatusFilter($action_obj, $user_id, $project_id, $status) {
		$key = 'project_issue_status_'.$project_id;
		self::setValues($action_obj, $user_id, array($key=>$status));
		
	}
	public static function getIssueStatusFilter($action_obj, $user_id, $project_id ) {
		$key = 'project_issue_status_'.$project_id;
		
		
		$value = self::getValue($action_obj, $user_id, $key);
		if($value == '' ) {
			$value = 0;
		}
		return $value;
		
	}
	
	public static function setProjectVisible($action_obj, $user_id, $project_id, $visible = true) {
		$key = 'project_visible_'.$project_id;
		self::setValues($action_obj, $user_id, array($key=>$visible));
		
	}
	public static function getIsProjectVisible($action_obj, $user_id, $project_id ) {
		$key = 'project_visible_'.$project_id;
		
		$value = self::getValue($action_obj, $user_id, $key);
		if($value == '' ) {
			$value = false;
		}
		return $value;
		
	}
	public static function setTaskVisible($action_obj, $user_id, $project_id, $visible = true) {
		$key = 'task_visible_'.$project_id;
	
		self::setValues($action_obj, $user_id, array($key=>$visible));
	}
	public static function getIsTaskVisible($action_obj,$user_id, $project_id ) {
		$key = 'task_visible_'.$project_id;
		
		$value = self::getValue($action_obj, $user_id, $key);
		if($value == '' ) {
			$value = false;
		}
		return $value;
		
	}
	public static function setIssueVisible($action_obj, $user_id, $project_id, $visible = true) {
		$key = 'issue_visible_'.$project_id;
		
		self::setValues($action_obj, $user_id, array($key=>$visible));
	}
	public static function getIsIssueVisible($action_obj, $user_id, $project_id ) {
		$key = 'issue_visible_'.$project_id;
		
		$value = self::getValue($action_obj, $user_id, $key);
		if($value == '' ) {
			$value = false;
		}
		return $value;
		
	}
	
	/* Abhilash 17.12.14 */
	public static function setComposeProjectDescVisible($action_obj, $user_id, $project_id, $visible = true) {
		$key = 'project_desc_compose_'.$project_id;
	
		self::setValues($action_obj, $user_id, array($key=>$visible));
	}
	public static function getIsComposeProjectDescVisible($action_obj,$user_id, $project_id ) {
		$key = 'project_desc_compose_'.$project_id;
	
		$value = self::getValue($action_obj, $user_id, $key);
		if($value == '' ) {
			$value = false;
		}
		return $value;
	
	}
	
	/* Abhilash 3.1.15 */
	public static function setComposeTaskDescVisible($action_obj, $user_id, $task_id, $visible = true) {
		$key = 'task_desc_compose_'.$task_id;

		self::setValues($action_obj, $user_id, array($key=>$visible));
	}
	public static function getIsComposeTaskDescVisible($action_obj,$user_id, $task_id ) {
		$key = 'task_desc_compose_'.$task_id;
		
		$value = self::getValue($action_obj, $user_id, $key);
		if($value == '' ) {
			$value = false;
		}
		return $value;
	
	}
	
	
	/* Abhilash 3.1.15 */
	public static function setComposeIssueDescVisible($action_obj, $user_id, $issue_id, $visible = true) {
		$key = 'issue_desc_compose_'.$issue_id;
	
		self::setValues($action_obj, $user_id, array($key=>$visible));
	}
	public static function getIsComposeIssueDescVisible($action_obj,$user_id, $issue_id ) {
		$key = 'issue_desc_compose_'.$issue_id;
	
		$value = self::getValue($action_obj, $user_id, $key);
		if($value == '' ) {
			$value = false;
		}
		return $value;
	
	}
	
	
	
	public static function setIssueReportPreferences($action_obj, $user_id,  $status, $issue_priority, $project_id, $from_date, $to_date) {
		$key = 'issue_report_settings';
		$values = array (
			"status" => $status,
			"issue_priority" => $issue_priority,
			"project_id" => $project_id,
			"issue_from_date" => $from_date,
			"issue_to_date" => $to_date,
		);
		
		
		self::setValues($action_obj, $user_id, array($key=>$values));
		
	}
	public static function getIssueReportPreferences($action_obj, $user_id ) {
		$key = 'issue_report_settings';
		
		$value = self::getValue($action_obj, $user_id, $key);
		if($value == '' ) {
			$value = array();
		}
		return $value;
		
	}
	
	public static function setMyTaskReportTab($action_obj, $user_id, $tab_name) {
		$key = 'task_report_tab';
		
		self::setValues($action_obj, $user_id, array($key=>$tab_name));
	}
	public static function getMyTaskReportTab($action_obj,$user_id ) {
		$key = 'task_report_tab';
		
		$value = self::getValue($action_obj, $user_id, $key);
		if($value == '' ) {
			$value = '';
		}
		return $value;
		
	}
	
	
	public static function setTaskReportPreferences($action_obj, $user_id, $report_type, $user_filter_id, $status, $task_priority, $project_id, $from_date, $to_date) {
		$key = 'task_report_settings_' . $report_type;
		$values = array (
			"report_type" => $report_type,
			"user_filter_id" => $user_filter_id,
			"status" => $status,
			"project_id" => $project_id,
			"task_priority" => $task_priority,
			"task_from_date" => $from_date,
			"task_to_date" => $to_date
		);
		
		
		self::setValues($action_obj, $user_id, array($key=>$values));
		
	}
	public static function getTaskReportPreferences($action_obj, $user_id, $report_type ) {
		$key = 'task_report_settings_' . $report_type;
		
		$value = self::getValue($action_obj, $user_id, $key);
		if($value == '' ) {
			$value = array();
		}
		return $value;
		
	}
	


	public static function setUserTheme($action_obj, $user_id, $theme) {
		$key = 'theme_color';
		
		self::setValues($action_obj, $user_id, array($key=>$theme));
		
	}
	public static function getUserTheme($action_obj,$user_id ) {
		$key = 'theme_color';
		/*$settings = array();
		if($preference_record && !is_null($preference_record)) {
			$settings = unserialize($preference_record->getSettings());
			
		}
		return isset($settings[$key]) ?  $settings[$key] : null;*/
		$value = self::getValue($action_obj, $user_id, $key);
		//	var_dump($value);
		if($value == '' ) {
			$value = null;
		}
	
		return $value;
		
	}
	
	
	public static function getHiddenProjectIds($action_obj, $user_id) {
		
		return self::getValue($action_obj, $user_id, Constants::HIDDEN_PROJECT_IDS, "");
		
		
	}
	
	public static function getDashboardShowUnreadMessages($action_obj, $user_id) {
		
		$value = self::getValue($action_obj, $user_id, Constants::SHOW_DASHBOARD_UNREAD_MESSAGES, TRUE);
		if($value == ''  || $value == 0 ) {
			$value = false;
		}
		return $value;
	}
	public static function getDashboardShowOthersTasks($action_obj, $user_id) {
		
		$value = self::getValue($action_obj, $user_id, Constants::SHOW_DASHBOARD_OTHERS_TASKS, TRUE);
		if($value == ''  || $value == 0 ) {
			$value = false;
		}
		return $value;
	}
	public static function getDashboardShowMyTasks($action_obj, $user_id) {
		
		$value = self::getValue($action_obj, $user_id, Constants::SHOW_DASHBOARD_MY_TASKS, TRUE);
		if($value == ''  || $value == 0 ) {
			$value = false;
		}
		return $value;
	}
	
	
	public static function getSearchProjectDetails($action_obj, $user_id) {
		
		$value = self::getValue($action_obj, $user_id, Constants::SEARCH_PROJECT_DETAILS, TRUE);
		if($value == ''  || $value == 0 ) {
			$value = false;
		}
		return $value;
	}
	public static function getSearchTaskDetails($action_obj, $user_id) {
		
		$value = self::getValue($action_obj, $user_id, Constants::SEARCH_TASK_DETAILS, TRUE);
		if($value == ''  || $value == 0 ) {
			$value = false;
		}
		return $value;
	}
	public static function getSearchIssueDetails($action_obj, $user_id) {
		
		$value = self::getValue($action_obj, $user_id, Constants::SEARCH_ISSUE_DETAILS, TRUE);
		if($value == ''  || $value == 0 ) {
			$value = false;
		}
		return $value;
	}
	public static function getSearchMessages($action_obj, $user_id) {
		
		$value = self::getValue($action_obj, $user_id, Constants::SEARCH_MESSAGESS, TRUE);
		if($value == ''  || $value == 0 ) {
			$value = false;
		}
		return $value;
	}
	
	
 
 	public static function getMaxUserTableRows($action_obj, $user_id) {
		
		$value = self::getValue($action_obj, $user_id, Constants::MAX_USER_TABLE_ROWS);
		if($value == '') {
			$value = 20;
		}
		return $value;
	}
	public static function getCloseTaskWhenIssueClosed($action_obj, $user_id) {
		
		$value = self::getValue($action_obj, $user_id, Constants::CLOSE_TASK_WHEN_ISSUE_CLOSED);
		if($value == ''  || $value == 0 ) {
			$value = false;
		}
		return $value;
	}
	public static function getCloseIssueWhenTaskClosed($action_obj, $user_id) {
		
		$value = self::getValue($action_obj, $user_id, Constants::CLOSE_ISSUE_WHEN_TASK_CLOSED);
		if($value == ''  || $value == 0 ) {
			$value = false;
		}
		return $value;
	}
 	public static function getShowAllCommentsOpenIssues($action_obj, $user_id) {
		
		$value = self::getValue($action_obj, $user_id, Constants::SHOW_ALL_COMMENTS_OF_OPEN_ISSUES);
		if($value == ''  || $value == 0 ) {
			$value = false;
		}
		return $value;
	}
	public static function getShowClosedCommentsClosedIssues($action_obj, $user_id) {
		
		$value = self::getValue($action_obj, $user_id, Constants::SHOW_CLOSED_COMMENTS_OF_CLOSED_ISSUES);
		if($value == ''  || $value == 0 ) {
			$value = false;
		}
		return $value;
	}
	public static function getShowImageWithIssues($action_obj, $user_id) {
		
		$value = self::getValue($action_obj, $user_id, Constants::SHOW_ATTACHED_IMAGE_WITH_COMMENTS);
		if($value == '' || $value == 0 ) {
			$value = false;
		}
		return $value;
	}
 	
 	
 	public static function getMaxMessagesPerPage($action_obj, $user_id) {
		
		$value = self::getValue($action_obj, $user_id, Constants::MAX_MESSAGES_PER_PAGE);
		if($value == '') {
			$value = 5;
		}
		return $value;
	}
	public static function getMaxDashboardItemsPerPage($action_obj, $user_id) {
		
		$value = self::getValue($action_obj, $user_id, Constants::MAX_DASHBOARD_BLOCK_ITEMS);
		if($value == '') {
			$value = 5;
		}
		return $value;
	}

	public static function getProjectsPerPage($action_obj, $user_id) {
		
		$value = self::getValue($action_obj, $user_id, Constants::PROJECTS_PER_PAGE);
		if($value == '') {
			$value =  25; //ConfigRecordPeer::getProjectsPerPage($action_obj);
		}
		return $value;
	}
	public static function getTaskCommentsPerPage($action_obj, $user_id) {
		
		$value = self::getValue($action_obj, $user_id, Constants::TASK_COMMENTS_PER_PAGE);
		if($value == '') {
			$value =  25; //ConfigRecordPeer::getTaskCommentsPerPage($action_obj);
		}
		return $value;
		
	}
	//PLEASE DO NOT USE THIS, ONLY MEANT FOR user module
	public static function setValues($action_obj, $user_id, $key_value_assoc) {
		$action_obj->setParameter(self::PREFERENCE_CACHE, null);
		$db = Db :: getInstance($action_obj->getConfig());
		$preference_record = self::findByUserId($db, $user_id);
		$settings = null;
		if($preference_record && !is_null($preference_record)) {
					$settings = unserialize($preference_record->getSettings());
		}
		else {
			$preference_record = new PreferenceRecord($db);
			$settings = array();
			
		}
		
		foreach ($key_value_assoc as $key=>$value) {
			$settings[$key] = $value;
		}
		
		$preference_record->setUserId($user_id);
		$preference_record->setSettings(serialize($settings));
		$preference_record->store();
		
		//store for the future
		$action_obj->setParameter(self::PREFERENCE_CACHE, $settings);
		
	}
	private  static function getValue($action_obj, $user_id, $key, $default_value=false) {
		$settings_cache = $action_obj->getParameter(self::PREFERENCE_CACHE);
		
		$settings_cache = (!$settings_cache || !is_array($settings_cache)) ? array() : $settings_cache;
	
		if( !isset($settings_cache[$key])) {
				$db = Db :: getInstance($action_obj->getConfig());
				$preference_record = self::findByUserId($db, $user_id);
				if($preference_record && !is_null($preference_record)) {
					$settings_cache = unserialize($preference_record->getSettings());
				}
				if(!isset($settings_cache[$key])) {
					$settings_cache[$key] = $default_value;
				}
				
				//store for the future
				$action_obj->setParameter(self::PREFERENCE_CACHE, $settings_cache);
		}
		
		return  isset($settings_cache[$key]) ? $settings_cache[$key] : '';
	}
	private static function findByUserId($db, $user_id) {
		$where_cond = PreferenceRecord :: USER_ID_COL . "='$user_id' ";
		$table_name = $db->getPrefix() . PreferenceRecord :: TABLE_NAME;
		$records = CommonRecord :: getObjects($table_name, $where_cond, '', '', 1, new preferenceRecord($db));
		if (is_array($records) && count($records) > 0) {
			return $records[0];
		}
		return null;
	}
	/*
	private static function updatePreferences($db, $user_id, $key, $values) {
		$preference_record = self :: findByUserId($db, $user_id);
		$settings = null;
		if ($preference_record == null) {
			$preference_record = new PreferenceRecord($db);
			$settings = array();

		} else {
			$settings = unserialize($preference_record->getSettings());
		}
		$settings[$key] = $values;
		$preference_record->setUserId($user_id);
		$preference_record->setSettings(serialize($settings));
		$preference_record->store();
		
	}*/
}
?>