<?php


/*
 * Created on April 17, 2009
 *
 * bispark software services
 * www.bispark.com
 */
require_once 'CommonRecord.php';
require_once 'UserRecord.php';
require_once 'Util.php';
require_once 'Constants.php';
require_once 'BookmarkRecord.php';
require_once 'ProjectRecordPeer.php';
require_once 'TaskRecord.php';
require_once 'TaskRecordPeer.php';
require_once 'MessageRecordPeer.php';
require_once 'UserPermission.php';
require_once 'UserRecordPeer.php';

class UserPermissionPeer {

	public static function canCreateProject($db, $user_id) {
		$permission_record = self :: findByPermissionType($db, $user_id, Constants :: LEAD_PROJECT, Constants :: USER, '0');
		return ($permission_record != null) ? true : false;
	}
	public static function canPerformTask($db, $user_id) {
		$permission_record = self :: findByPermissionType($db, $user_id, Constants :: CAN_PERFORM_TASK, Constants :: USER, '0');
		return ($permission_record != null) ? true : false;
	}

	public static function canLeadProject($db, $user_id, $project_id) {
		$permission_record = self :: findByPermissionType($db, $user_id, Constants :: LEAD_PROJECT, Constants :: PROJECT, $project_id);
		return ($permission_record != null) ? true : false;
	}
	public static function canExecProject($db, $user_id, $project_id) {
		$permission_record = self :: findByPermissionType($db, $user_id, Constants :: CAN_PERFORM_TASK, Constants :: PROJECT, $project_id);
		return ($permission_record != null) ? true : false;
	}
	public static function canLeadTask($db, $user_id, $task_id) {
		$permission_record = self :: findByPermissionType($db, $user_id, Constants :: LEAD_TASK, Constants :: TASK, $task_id);
		return ($permission_record != null) ? true : false;
	}
	public static function canExecTask($db, $user_id, $task_id) {
		$permission_record = self :: findByPermissionType($db, $user_id, Constants :: CAN_PERFORM_TASK, Constants :: TASK, $task_id);
		return ($permission_record != null) ? true : false;
	}
	public static function canViewTask($db, $user_id, $task_id) {
		$permission_record = self :: findByPermissionType($db, $user_id, Constants :: CAN_VIEW_TASK, Constants :: TASK, $task_id);
		return ($permission_record != null) ? true : false;
	}

	public static function canAddIssue($db, $user_id, $project_id) {
		$permission_record = self :: findByPermissionType($db, $user_id, Constants :: ADD_ISSUE, Constants :: PROJECT, $project_id);
		return ($permission_record != null) ? true : false;
	}

	public static function unsetCreateProjectPermission($db, $user_id) {
		self :: unsetContentPermission($db, $user_id, Constants :: LEAD_PROJECT, Constants :: USER, '0');

	}
	public static function setCreateProjectPermission($db, $user_id) {
		self :: createPermissionRecord($db, $user_id, Constants :: LEAD_PROJECT, Constants :: USER, '0');

	}
	
	//added - 20.10.2013
	public static function unsetProjectLeadPermission($db, $user_id, $project_id) {
		self :: unsetContentPermission($db, $user_id, Constants :: LEAD_PROJECT, Constants :: PROJECT, $project_id);

	}
	
	
	public static function unsetPerformTaskPermission($db, $user_id) {
		self :: unsetContentPermission($db, $user_id, Constants :: CAN_PERFORM_TASK, Constants :: USER, '0');

	}
	public static function setPerformTaskPermission($db, $user_id) {
		self :: createPermissionRecord($db, $user_id, Constants :: CAN_PERFORM_TASK, Constants :: USER, '0');

	}
	
	public static function unsetProjectExecPermission($db, $user_id, $project_id) {
		self :: unsetContentPermission($db, $user_id, Constants :: CAN_PERFORM_TASK, Constants :: PROJECT, $project_id);

	}
	public static function setProjectExecPermission($db, $user_id, $project_id) {
		self :: createPermissionRecord($db, $user_id, Constants :: CAN_PERFORM_TASK, Constants :: PROJECT, $project_id);

	}
	

	public static function setLeadTaskPermission($db, $user_id, $task_id) {
		self :: createPermissionRecord($db, $user_id, Constants :: LEAD_TASK, Constants :: TASK, $task_id);

	}
	public static function unsetLeadTaskPermission($db, $user_id, $task_id) {
		self :: unsetContentPermission($db, $user_id, Constants :: LEAD_TASK, Constants :: TASK, $task_id);

	}

	public static function unsetTeamPermission($db, $user_id,  $task_id) {
		self :: unsetContentPermission($db, $user_id, Constants :: CAN_PERFORM_TASK, Constants :: TASK, $task_id);
		self :: unsetContentPermission($db, $user_id, Constants :: CAN_VIEW_TASK, Constants :: TASK, $task_id);
						
	}
	public static function setTeamExecPermission($db, $user_id, $project_id, $task_id) {
		self :: setProjectExecPermission($db, $user_id, $project_id);
		self :: createPermissionRecord($db, $user_id, Constants :: CAN_PERFORM_TASK, Constants :: TASK, $task_id);
	}
	public static function setTeamViewPermission($db, $user_id, $project_id, $task_id) {
		self :: createPermissionRecord($db, $user_id, Constants :: CAN_PERFORM_TASK, Constants :: PROJECT, $project_id);
		self :: createPermissionRecord($db, $user_id, Constants :: CAN_VIEW_TASK, Constants :: TASK, $task_id);
	}

	public static function unsetAddIssuePermission($db, $user_id, $project_id) {
		self :: unsetContentPermission($db, $user_id, Constants :: ADD_ISSUE, Constants :: PROJECT, $project_id);

	}
	public static function setAddIssuePermission($db, $user_id, $project_id) {
		self :: createPermissionRecord($db, $user_id, Constants :: ADD_ISSUE, Constants :: PROJECT, $project_id);
	}

	public static function unsetProjectPermissions($db, $project_id) {
		self :: unsetContentPermission($db, '', '', Constants :: PROJECT, $project_id);

	}
	public static function unsetTaskPermissions($db, $task_id) {
		self :: unsetContentPermission($db, '', '', Constants :: TASK, $task_id);

	}
	public static function unsetIssuePermissions($db, $issue_id) {
		self :: unsetContentPermission($db, '', '', Constants :: ISSUE, $issue_id);

	}

	public static function getIssueTrackingUserIds($db, $project_id) {
		$permission_table_name = $db->getPrefix() . UserPermission :: TABLE_NAME;
		$where_cond = UserPermission :: RECORD_TYPE_COL . " = " . Constants :: PROJECT . " AND " . UserPermission :: RECORD_ID_COL . " = " . $project_id . " AND " . UserPermission :: PERMISSION_COL . " = " . Constants :: ADD_ISSUE;
		$records = CommonRecord :: getObjects($permission_table_name, $where_cond, '', '', '', new UserPermission($db), array (
			UserPermission :: USER_ID_COL
		));
		$user_ids = array ();
		if (!empty ($records)) {
			foreach ($records as $record) {
				$user_ids[] = $record->getUserId();
			}
		}
		return $user_ids;
	}
	

	public static function unsetContentPermission($db, $user_id = '', $permission_type = '', $record_type = '', $record_id = '') {
		$table_name = $db->getPrefix() . UserPermission :: TABLE_NAME;
		$where_cond = " 1 ";
		$dangerous = true;
		if ($user_id != '') {
			$where_cond .= " AND " . UserPermission :: USER_ID_COL . " = '" . $user_id . "'";
			$dangerous = false;

		}
		if ($permission_type != '') {
			$where_cond .= " AND " . UserPermission :: PERMISSION_COL . " = '" . $permission_type . "'";
			$dangerous = false;
		}
		if ($record_type != '' && $record_id != '') {
			$where_cond .= " AND " . UserPermission :: RECORD_TYPE_COL . " = '" . $record_type . "' AND " . UserPermission :: RECORD_ID_COL . " = '" . $record_id . "'";
			$dangerous = false;

		}

		if (!$dangerous) {
			$sql = "DELETE FROM $table_name where $where_cond";
			
			return $db->query($sql);
		}

	}
	public static function getUserPermissionTypes($db, $user_id) {
		$records = self :: listByRecordType($db, $user_id, Constants :: USER, '0');
		$permission_types = array ();
		if (!empty ($records)) {
			foreach ($records as $record) {
				$permission_types[] = $record->getPermission();
			}
		}
		return $permission_types;
	}
	public static function getUserRecordsWithPermissions($db, $user_records) {
		foreach ($user_records as $user_record) {
			$user_record->can_create_project = self :: canCreateProject($db, $user_record->getId());
			$user_record->can_perform_task = self :: canPerformTask($db, $user_record->getId());
		}
		return $user_records;
	}
	public static function getLeadUserIdsForProject($db, $project_id) {
		$table_name = $db->getPrefix() . UserPermission :: TABLE_NAME;
		$where_cond = UserPermission :: RECORD_TYPE_COL . " = " . Constants :: PROJECT;
		$where_cond .= " AND " . UserPermission :: PERMISSION_COL . "= '" . Constants :: LEAD_PROJECT . "' ";
		$where_cond .= " AND " . UserPermission :: RECORD_ID_COL . "= '$project_id' ";

		$records = CommonRecord :: getObjects($table_name, $where_cond, 'id desc', '', '', new UserPermission($db), array (
			'user_id'
		));
		$user_ids = array ();
		if (!empty ($records)) {
			foreach ($records as $record) {
				$user_ids[] = $record->getUserId();
			}
		}
		return $user_ids;
	}
	//only single lead for current version
	/* not now
	public static function getProjectLeadId($db,$project_id) {
		$lead_ids = self::getLeadUserIdsForProject($db,$project_id);
		if(!empty ($lead_ids)) {
			return $lead_ids[0];
		}
		return false;
	}
	*/

	//rename to setContet
	public static function createPermissionRecord($db, $user_id, $permission_type, $record_type, $record_id = 0) {
		$permission_record = self :: findByPermissionType($db, $user_id, $permission_type, $record_type, $record_id);
		if ($permission_record == null) {
			$permission_record = new UserPermission($db);
			$permission_record->setUserId($user_id);
			$permission_record->setPermission($permission_type);
			$permission_record->setRecordType($record_type);
			$permission_record->setRecordId($record_id);
			$permission_record->store();
		}

	}
	
	
	//$record_id is ZERO for USER type records
	private static function findByPermissionType($db, $user_id, $permission_type, $record_type, $record_id) {

		$table_name = $db->getPrefix() . UserPermission :: TABLE_NAME;

		$where_cond = UserPermission :: USER_ID_COL . " = '" . $user_id . "' AND  " . UserPermission :: RECORD_TYPE_COL . " = '" . $record_type . "' AND " . UserPermission :: RECORD_ID_COL . " = '" . $record_id . "' AND " . UserPermission :: PERMISSION_COL . " = '" . $permission_type . "'";

		$records = CommonRecord :: getObjects($table_name, $where_cond, '', '', 1, new UserPermission($db));
		if (is_array($records) && count($records) > 0) {
			return true;
		}

		return false;
	}
	private static function listByRecordType($db, $user_id, $record_type, $record_id) {

		$table_name = $db->getPrefix() . UserPermission :: TABLE_NAME;

		$where_cond = UserPermission :: USER_ID_COL . " = '" . $user_id . "' AND  " . UserPermission :: RECORD_TYPE_COL . " = '" . $record_type . "' AND " . UserPermission :: RECORD_ID_COL . " = '" . $record_id . "'  ";

		return CommonRecord :: getObjects($table_name, $where_cond, '', '', '', new UserPermission($db));

	}

	//////////////////////

	public static function findContentPermission($db, $user_id, $record_type, $record_id) {

		$table_name = $db->getPrefix() . UserPermission :: TABLE_NAME;

		$where_cond = UserPermission :: USER_ID_COL . " = '" . $user_id . "' AND  " . UserPermission :: RECORD_TYPE_COL . " = '" . $record_type . "' AND " . UserPermission :: RECORD_ID_COL . " = '" . $record_id . "'";

		$record = CommonRecord :: getObjects($table_name, $where_cond, '', '', 1, new UserPermission($db));
		if (is_array($record) && count($record) > 0) {
			return $record[0];
		}

		return null;
	}

	public static function setPermission($action_object, $user_id, $permission, $record_type, $record_id) {
		$key = $user_id . "_" . $record_type . "_" . $record_id;
		return $action_object->setParameter($key, $permission);
	}

	public static function findPermissionByUser($db, $user_id) {
		$table_name = $db->getPrefix() . UserPermission :: TABLE_NAME;
		$where_cond = UserPermission :: USER_ID_COL . " = '" . $user_id . "'";
		$records = CommonRecord :: getObjects($table_name, $where_cond, '', '', '', new UserPermission($db));
		if (is_array($records) && count($records) > 0) {
			return $records;
		}
		return null;
	}

	public static function findContentPermittedUserIds($db, $record_type, $record_id, $user_id = '') {
		$table_name = $db->getPrefix() . UserPermission :: TABLE_NAME;
		$where_cond = UserPermission :: RECORD_TYPE_COL . " = '" . $record_type . "' AND " . UserPermission :: RECORD_ID_COL . " = '" . $record_id . "'";
		$where_cond = ($user_id != '') ? $where_cond . " AND " . UserPermission :: USER_ID_COL . " = '" . $user_id . "'" : $where_cond;
		$records = CommonRecord :: getObjects($table_name, $where_cond, '', '', '', new UserPermission($db), array (
			UserPermission :: USER_ID_COL
		));
		$user_ids = array ();
		if (!empty ($records)) {
			foreach ($records as $record) {
				$user_ids[] = $record->getUserId();
			}
		}

		return $user_ids;
	}
	public static  function listByUserIdPermissionTypes($db, $user_id, $permission_types, $record_type) {
		
		$table_name = $db->getPrefix() . UserPermission :: TABLE_NAME;

		$permission_arr_str = "'".implode("','",$permission_types). "'";
		$where_cond = UserPermission :: USER_ID_COL . " = '" . $user_id . "' AND  " . UserPermission :: PERMISSION_COL . " IN  ($permission_arr_str) AND  " . UserPermission :: RECORD_TYPE_COL . " = '" . $record_type . "'";

		$records = CommonRecord :: getObjects($table_name, $where_cond, '', '', '', new UserPermission($db), array(UserPermission :: RECORD_ID_COL));
	
		return $records;
	}
	
	public  static function listUserIdsHavingPermission($db,  $permission_type, $record_type) {

		$table_name = $db->getPrefix() . UserPermission :: TABLE_NAME;

		$where_cond =  UserPermission :: RECORD_TYPE_COL . " = '" . $record_type . "' AND  " . UserPermission :: PERMISSION_COL . " = '" . $permission_type . "'";

		$records = CommonRecord :: getObjects($table_name, $where_cond, '', '', '', new UserPermission($db));
		$user_ids = array ();
		if (!empty ($records)) {
			foreach ($records as $record) {
				$user_ids[] = $record->getUserId();
			}
		}

		return $user_ids;
	}
	public static function getIsViewOnlyTask($db,$task_record){
		 
		 if($task_record ->getIsOpen()) {
		 	$team_ids = self::getTaskExecTeam($db, $task_record->getId(), 1);
		 	if(empty($team_ids)) {
		 		
		 		$view_team_id = self::getTaskTeam($db, array(Constants :: CAN_VIEW_TASK), $task_record->getId(), 1);
		 		if(!empty($view_team_id)) {
		 			return true;
		 		}
		 	}
		 	
		 }
		 return false;
	}
	public static function getProjectTeam($db, $project_ids, $permission_arr,  $limit='') {
	
		$table_name = $db->getPrefix() . UserPermission :: TABLE_NAME;
		$project_ids_str = "'".implode("','",$project_ids). "'";
		
		$permission_str = "'".implode("','",$permission_arr). "'";
		
		$where_cond = UserPermission :: PERMISSION_COL . " IN ($permission_str) AND " . UserPermission :: RECORD_TYPE_COL . "= '" . Constants::PROJECT . "' AND " . UserPermission :: RECORD_ID_COL . " IN ($project_ids_str)";
	
		$records = CommonRecord :: getObjects($table_name, $where_cond, 'id asc', '', $limit, new UserPermission($db), array(UserPermission :: USER_ID_COL));
		$user_ids = array();
		if (is_array($records) && count($records) > 0) {
			foreach ($records as $record) {
				if(!in_array($record->getUserId(), $user_ids)) {
				$user_ids[] = $record->getUserId();
				}
			}
		}

		return $user_ids;
	}
	public static function getTaskExecTeam($db, $task_id, $limit = '') {
		return self::getTaskTeam($db, array(Constants :: CAN_PERFORM_TASK), $task_id, $limit);
	}
	public static function getTaskAnyTeam($db, $task_id, $limit = '') { 
		return self::getTaskTeam($db, array(Constants :: CAN_PERFORM_TASK, Constants :: CAN_VIEW_TASK), $task_id, $limit);
	}
	private static function getTaskTeam($db, $permission_arr, $task_id, $limit = '') {
	
		$table_name = $db->getPrefix() . UserPermission :: TABLE_NAME;
		
		$permission_str = "'".implode("','",$permission_arr). "'";
		
		$where_cond = UserPermission :: PERMISSION_COL . " IN ($permission_str) AND " . UserPermission :: RECORD_TYPE_COL . "= '" . Constants::TASK . "' AND " . UserPermission :: RECORD_ID_COL . "= '" . $task_id . "'";
	
	   // $order_by = "id asc";
	    
	    $order_by = UserPermission :: PERMISSION_COL. " asc "; //will not work?
	  
	     
		$records = CommonRecord :: getObjects($table_name, $where_cond, $order_by, '', $limit, new UserPermission($db),array(UserPermission :: USER_ID_COL));
		$user_ids = array();
		/*if($first_must_id && !empty($records)) {
				foreach ($records as $record) {
			if($record->getUserId() == $first_must_id) {
				$user_ids[] = $first_must_id;
				break;
			}
				}
		}*/
		if (is_array($records) && count($records) > 0) {
			foreach ($records as $record) {
				if(!in_array($record->getUserId(), $user_ids)) {
				$user_ids[] = $record->getUserId();
				}
			}
		}
		

		return $user_ids;
	}
	

}
?>