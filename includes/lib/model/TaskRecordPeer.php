<?php


/*
 * Created on April 12, 2009
 *
 * bispark software services
 * www.bispark.com
 */
require_once 'CommonRecord.php';
require_once 'ProjectRecord.php';
require_once 'TaskRecord.php';
require_once 'MessageRecordPeer.php';
require_once 'UserRecordPeer.php';
require_once 'AppLogPeer.php';
require_once 'Constants.php';

class TaskRecordPeer {

	public static function deleteTask($db, $task_id) {

		$table_name = $db->getPrefix() . TaskRecord :: TABLE_NAME;
		CommonRecord :: delete($db, $table_name, CommonRecord :: ID_COL, array (
			$task_id
		));

		MessageRecordPeer :: deleteTaskMessages($db, $task_id);
	}
	/*
	public static function getProjectIdsByTaskIds($db, $task_ids) {
		$table_name = $db->getPrefix() . TaskRecord :: TABLE_NAME;
		$task_ids_arr_str = "'".implode("','",$task_ids). "'";
		$where_cond = TaskRecord :: ID_COL . " IN ($task_ids_arr_str)  ";
		$task_records =  CommonRecord :: getObjects($table_name, $where_cond, ' id desc ', '', '', new TaskRecord($db),  array(TaskRecord :: PARENT_PROJECT_ID_COL));
		$project_ids = array();
		if(!empty($task_records)) {
			foreach ($task_records as $t) {
				if(!in_array($t->getParentProjectId(), $project_ids)) {
					$project_ids[] = $t->getParentProjectId();
				}
				
			}
			
		}
		
		return $project_ids;
	}*/

	public static function getProjectTasks($db, $project_id) {
		$where_cond = TaskRecord :: PARENT_PROJECT_ID_COL . "= '$project_id'  ";
		$table_name = $db->getPrefix() . TaskRecord :: TABLE_NAME;
		return CommonRecord :: getObjects($table_name, $where_cond, ' id desc ', '', '', new TaskRecord($db));
	}
	public static function getProjectTaskIds($db, $project_id) {
		$task_ids = array ();
		$where_cond = TaskRecord :: PARENT_PROJECT_ID_COL . "= '$project_id'  ";
		$table_name = $db->getPrefix() . TaskRecord :: TABLE_NAME;
		$task_records = CommonRecord :: getObjects($table_name, $where_cond, ' id desc ', '', '', new TaskRecord($db), array (
			'id'
		));
		if (!empty ($task_records)) {
			foreach ($task_records as $task_records) {

				$task_ids[] = $task_records->getId();
			}

		}
		return $task_ids;

	}

	public static function getProjectTasksWithProgressCol($db, $project_id) {
		$where_cond = TaskRecord :: PARENT_PROJECT_ID_COL . "= '$project_id'  ";
		$table_name = $db->getPrefix() . TaskRecord :: TABLE_NAME;
		return CommonRecord :: getObjects($table_name, $where_cond, ' id desc ', '', '', new TaskRecord($db), array (
			TaskRecord :: PROGRESS_COL
		));
	}

	//function to filter project task in projects page
	public static function filterProjectTasks($db, $project_id, $access_all_tasks, $user_id = '', $task_status = '', $priority='', $order='desc') {

		$task_table_name = $db->getPrefix() . TaskRecord :: TABLE_NAME;
		$permission_table_name = $db->getPrefix() . UserPermission :: TABLE_NAME;
		if ($access_all_tasks == true && $task_status != '') {
			$where_cond = TaskRecord :: PARENT_PROJECT_ID_COL . "= '$project_id'  ";
			$where_cond = $where_cond . " AND (" . TaskRecord :: STATUS_COL . " = '$task_status' ) " ;
			if($priority != '') {
				$where_cond = $where_cond . " AND (" . TaskRecord :: PRIORITY_COL . " =  '$priority' ) ";
			}
			
			
			$table_name = $db->getPrefix() . TaskRecord :: TABLE_NAME;
			$results = CommonRecord :: getObjects($table_name, $where_cond, " updated_at $order ", '', '', new TaskRecord($db));
			
			

		}
		elseif ($access_all_tasks == true) {
			$where_cond = TaskRecord :: PARENT_PROJECT_ID_COL . "= '$project_id'  ";
			if($priority != '') {
				$where_cond = $where_cond . " AND (" . TaskRecord :: PRIORITY_COL . " =  '$priority') ";
			}
			
			$table_name = $db->getPrefix() . TaskRecord :: TABLE_NAME;
			$results = CommonRecord :: getObjects($table_name, $where_cond, " updated_at $order ", '', '', new TaskRecord($db));

		}
		if ($access_all_tasks == false && $task_status != '') {

			$where_cond = "( task." . TaskRecord :: PARENT_PROJECT_ID_COL . " = '" . $project_id . "' )";
			$where_cond = $where_cond . " AND ( permission." . UserPermission :: RECORD_TYPE_COL . " = " . Constants :: TASK . " )";
			$where_cond = $where_cond . " AND  ( permission." . UserPermission :: USER_ID_COL . " = " . $user_id . ")";
			$where_cond = $where_cond . " AND ( task." . TaskRecord :: STATUS_COL . "= '$task_status' ) ";
			if($priority != '') {
				$where_cond = $where_cond . " AND ( task." . TaskRecord :: PRIORITY_COL . "= '$priority' ) ";
			}
			
		
			$sql = "SELECT * FROM " . $task_table_name . " task JOIN " . $permission_table_name . " permission ON permission." . UserPermission :: RECORD_ID_COL . " = task." . TaskRecord :: ID_COL . " WHERE " . $where_cond . " order by task." . TaskRecord :: UPDATED_AT_COL . " $order";

			$records = $db->query($sql);
			$results = self :: getTaskObjectRecords($db, $records);
		}
		elseif ($access_all_tasks == false) {
			$where_cond = "( permission." . UserPermission :: RECORD_TYPE_COL . " = " . Constants :: TASK . " )";
			$where_cond = $where_cond . " AND ( task." . TaskRecord :: PARENT_PROJECT_ID_COL . " = '" . $project_id . "' )";
			if($priority != '') {
				$where_cond = $where_cond . " AND ( task." . TaskRecord :: PRIORITY_COL . "= '$priority' ) ";
			}
			
			$where_cond = $where_cond . " AND  ( permission." . UserPermission :: USER_ID_COL . " = " . $user_id . ")";
			
			
			
			$sql = "SELECT * FROM " . $task_table_name . " task JOIN " . $permission_table_name . " permission ON permission." . UserPermission :: RECORD_ID_COL . " = task." . TaskRecord :: ID_COL . " WHERE " . $where_cond . " order by task." . TaskRecord :: UPDATED_AT_COL . " $order";
			$records = $db->query($sql);
			$results = self :: getTaskObjectRecords($db, $records);
		}
		return $results;
	}

	public static function getUserTaskCountHavingPermissions($db, $project_id_arr, $user_id, $permission_types = array ()) {

		$task_table_name = $db->getPrefix() . TaskRecord :: TABLE_NAME;
		$permission_table_name = $db->getPrefix() . UserPermission :: TABLE_NAME;

		$project_id_cond = " 1 ";
		if (!empty ($project_id_arr)) {
			$project_id_str = "('" . implode("','", $project_id_arr) . "')";
			$project_id_cond = "  task." . TaskRecord :: PARENT_PROJECT_ID_COL . " IN " . $project_id_str . " ";
		}

		$where_cond = "( permission." . UserPermission :: RECORD_TYPE_COL . " = " . Constants :: TASK . " )";
		$where_cond .= " AND ( $project_id_cond )";
		$where_cond .= " AND  ( permission." . UserPermission :: USER_ID_COL . " = '" . $user_id . "')";
		if (!empty ($permission_types)) {
			$permission_str = "'" . implode("','", $permission_types) . "'";
			$where_cond .= " AND  ( permission." . UserPermission :: PERMISSION_COL . " IN  ($permission_str) )";
		}

		$sql = "SELECT count(task.id) as n_tasks  FROM " . $task_table_name . " task JOIN " . $permission_table_name . " permission ON permission." . UserPermission :: RECORD_ID_COL . " = task." . TaskRecord :: ID_COL . " WHERE " . $where_cond;

		$results = $db->query($sql);
		$row = $db->dbObject($results);

		return $row->n_tasks;
	}

	public static function findByPK($db, $taskId) {
		$where_cond = CommonRecord :: ID_COL . "='$taskId'   ";
		$table_name = $db->getPrefix() . TaskRecord :: TABLE_NAME;
		$records = CommonRecord :: getObjects($table_name, $where_cond, '', '', 1, new TaskRecord($db));
		if (is_array($records) && count($records) > 0) {
			return $records[0];
		}
		return null;
	}

	public static function getTaskRecordsWithSigninId($db, $task_records) {
		$user_ids_assoc = array ();

		foreach ($task_records as $task_record) {
			if ($task_record->getLeadId() && $task_record->getLeadId() != '') {
				$user_ids_assoc[$task_record->getLeadId()] = '';
			}
		}
		$user_ids = array_keys($user_ids_assoc);

		$userid_signinid_assoc = UserRecordPeer :: getUserIdSigninIdAssoc($db, $user_ids);
		foreach ($task_records as $task_record) {
			if (isset ($userid_signinid_assoc[$task_record->getLeadId()])) {
				$task_record->setLeadSigninId($userid_signinid_assoc[$task_record->getLeadId()]);
			} else {
				$task_record->setLeadSigninId('None');
			}
			$team_ids = UserPermissionPeer :: getTaskAnyTeam($db, $task_record->getId());
			
			

			
			if (!empty ($team_ids)) {
				$team_id_name_assoc = array(); //to maintain user id order
				foreach ($team_ids as $t_id) {
					$team_id_name_assoc[$t_id] = "";
				}
				$team_records = UserRecordPeer :: getUserRecordsWithSigninId($db, $team_ids);
				foreach ($team_records as $team_record) {
				//	$team_signin_ids[] = $team_record->getSigninId();
				$team_id_name_assoc[$team_record->getId()] = $team_record->getSigninId();

				}
				$team_signin_ids = array_values($team_id_name_assoc);

				$task_record->setTeamSigninId(implode(", ", $team_signin_ids));
				$task_record->setAssignedUids(implode(", ", $team_ids));
			} else {
				$task_record->setTeamSigninId('None');
				$task_record->setAssignedUids('');
			}

		}
		return $task_records;
	}

	//todo - verify - may be wrong ?
	function getNumberOfAssignedTasks($db, $project_id) {
		try {
			$table_name = $db->getPrefix() . TaskRecord :: TABLE_NAME;
			$userpermission_table_name = $db->getPrefix() . UserPermission :: TABLE_NAME;

			$sql = "SELECT count(task.id) FROM " . $table_name . " task JOIN " . $userpermission_table_name . " permission ON permission." . UserPermission :: RECORD_ID_COL . " = task." . TaskRecord :: ID_COL . "  WHERE task." . TaskRecord :: PARENT_PROJECT_ID_COL . " = $project_id  AND permission." . UserPermission :: RECORD_TYPE_COL . " = " . Constants :: TASK . " AND ( permission." . UserPermission :: PERMISSION_COL . " = '" . Constants :: CAN_PERFORM_TASK . "' OR permission." . UserPermission :: PERMISSION_COL . " = '" . Constants :: CAN_VIEW_TASK . "' )";

			$result = $db->query($sql);
			if ($result) {
				return mysql_result($result, 0);
			}
		} catch (Exception $exception) {
			throw new Exception('Error while retrieving # assigned tasks; ' . $exception->getMessage());
		}
		return 0;
	}

	public static function updateTaskName($db, $name, $task_id) {
		$table_name = $db->getPrefix() . TaskRecord :: TABLE_NAME;
		$sql = "update  " . $table_name . " set " . TaskRecord :: NAME_COL . "='$name' where id='" . $task_id . "'";
		//throw new Exception(" $sql");
		$db->query($sql);
	}
	//todo what ??????
	/*public static function getTaskProgress($task_records) {
		$task_progress = 0;
		if ($task_records != null) {
			foreach ($task_records as $task_record) {
				$task_progress = $task_record->getProgressValue($task_record);
				return $task_progress;
			}
		}
	}*/

	public static function updateTaskRecord($db, $project_id, $task_id) {
		$table_name = $db->getPrefix() . TaskRecord :: TABLE_NAME;
		$sql = "update " . $table_name . " set " . TaskRecord :: PARENT_PROJECT_ID_COL . "='$project_id' where " . TaskRecord :: ID_COL . " = $task_id ";
		$db->query($sql);

	}

	private static function getTaskObjectRecords($db, $results) {
		$records = array ();

		while ($row = $db->dbObject($results)) {
			$object = new TaskRecord($db);
			if (isset ($row->record_id)) {
				$object->setId($row->record_id);
			} else {
				$object->setId($row->id);
			}

			$object->setLeadId($row->lead_id);
			$object->setType($row->type);
			$object->setName($row->name);
			$object->setDescription($row->description);
			$object->setAttachmentName($row->attachment_name);
			$object->setProgress($row->progress);
			$object->setParentProjectId($row->parent_project_id);
						
			if (isset ($row->permission)) {
				$object->setPermission($row->permission);
			}
			$object->setStatus($row->status);
			$object->setPriority($row->priority);
			
			$object->setCreatedAt($row->created_at);
			$object->setUpdatedAt($row->updated_at); //should be after setStatus (seems dates get changed inside!)
			
			

			$records[] = $object;
		}
		return $records;

	}

	public static function getUserOpenTasks($db, $by_user_id, $for_project_id = false, $count_only = false, $priority='', $from_date_ymd='', $to_date_ymd='') {
		$lead_project_ids = ProjectRecordPeer :: getLeadProjectIds($db, $by_user_id);

		return self :: filterTask($db, 0, array (
			Constants :: TASK_OPEN
		), $for_project_id, $lead_project_ids,false,'','',false,false,$count_only,$priority,$from_date_ymd,$to_date_ymd);
	}

	public static function getTasksAssignedToMe($db, $by_user_id, $me_user_id, $for_project_id = false, $status_arr = array (), $offset = '', $limit = '', $sort_col = false, $sort_order = false, $count_only = false,$priority='', $from_date_ymd='', $to_date_ymd='') {

		$exclude_unassigned = true;
		$lead_project_ids = $by_user_id ? ProjectRecordPeer :: getLeadProjectIds($db, $by_user_id) : false;

		return self :: filterTask($db, $me_user_id, $status_arr, $for_project_id, $lead_project_ids, $exclude_unassigned, $offset, $limit, $sort_col, $sort_order,$count_only,$priority,$from_date_ymd,$to_date_ymd);

	}
	public static function getTasksAssignedToOthers($db, $by_user_id, $for_user_id, $for_project_id = false, $status_arr = array (), $offset = '', $limit = '', $sort_col = false, $sort_order = false, $count_only = false,$priority='', $from_date_ymd='', $to_date_ymd='') {
		$lead_project_ids = false;

		if ($by_user_id) {

			$lead_project_ids = ProjectRecordPeer :: getLeadProjectIds($db, $by_user_id);

		}

		$exclude_unassigned = true;
		$tasks = array ();
		if (!empty ($lead_project_ids)) {
			$tasks = self :: filterTask($db, $for_user_id, $status_arr, $for_project_id, $lead_project_ids, $exclude_unassigned, $offset, $limit, $sort_col, $sort_order,$count_only,$priority,$from_date_ymd,$to_date_ymd);
		}
		if($count_only && !is_numeric($tasks)) { //megha 4.12.14
			return 0;
		}
		return $tasks;

	}

	// abhilash	
	//this function would fail if $in_project_ids is EMPTY -
	private static function filterTask($db, $team_id, $status_arr = array (), $project_id = 0, $in_project_ids = false, $exclude_unassigned = false, $offset = '', $limit = '', $sort_col = false, $sort_order = false, $count_only = false, $priority='', $from_date_ymd='', $to_date_ymd='') {
		$task_table_name = $db->getPrefix() . TaskRecord :: TABLE_NAME;
		$permission_table_name = $db->getPrefix() . UserPermission :: TABLE_NAME;
		$project_table_name = $db->getPrefix() . ProjectRecord :: TABLE_NAME;

		$lead_project_id_condn = " 1 "; //any lead 
		if (!empty ($in_project_ids)) {
			$lead_project_ids_str = "'" . implode("','", $in_project_ids) . "'";
			$lead_project_id_condn = "  ( $task_table_name." . TaskRecord :: PARENT_PROJECT_ID_COL . "  IN ($lead_project_ids_str ) ) ";
		} else
			if (is_array($in_project_ids) && empty ($in_project_ids)) {
				$lead_project_id_condn = " 0 "; // required lead has NO TASKS and  no projects
			}

		$status_str = false;
		if (!empty ($status_arr)) {
			$status_str = "'" . implode("','", $status_arr) . "'";

		}

		$results = null;
		if ($team_id == 0) {
			$where_cond = "";
			$cond_arr = array();
			if ($status_str) {
				$cond_arr[] = TaskRecord :: STATUS_COL . " IN  ($status_str)  ";
			} //commented to consider view only assignments
			/*else if ( !$status_str && $exclude_unassigned) {
				$where_cond = TaskRecord :: STATUS_COL . " != '".Constants::TASK_OPEN."'  ";
			}*/
			
			if($from_date_ymd && $to_date_ymd) {
				$cond1 =  "  ( DATE($task_table_name." . TaskRecord :: UPDATED_AT_COL . ") = '$from_date_ymd' ) ";
				$cond2 =   "  ( DATE($task_table_name." . TaskRecord :: UPDATED_AT_COL . ") = '$to_date_ymd' ) ";
				
				$cond3 =   "  ( DATE($task_table_name." . TaskRecord :: UPDATED_AT_COL . ") > '$from_date_ymd' ) ";
				$cond4 =   "   ( DATE($task_table_name." . TaskRecord :: UPDATED_AT_COL . ") < '$to_date_ymd' )  ";
				
				$cond_arr[] = " ( $cond1 OR $cond2 OR ($cond3 AND $cond4)) ";
				
			}
			if ($priority) {
				$cond_arr[] = "  ( $task_table_name." . TaskRecord :: PRIORITY_COL . "= '$priority' ) ";
			}
			
			if(!empty($cond_arr)) {
				$where_cond = implode(' AND ', $cond_arr);
			}
			

			if ($project_id != 0) {
				$project_id_cond = "  ( " . TaskRecord :: PARENT_PROJECT_ID_COL . "= '$project_id' ) ";
				if ($where_cond != "") {
					$where_cond = $where_cond . " AND $project_id_cond ";
				} else {
					$where_cond = $project_id_cond;
				}

			}

			if ($where_cond != "") {
				$where_cond .= " AND $lead_project_id_condn ";

			} else {
				$where_cond = $lead_project_id_condn;
			}

			$table_name = $db->getPrefix() . TaskRecord :: TABLE_NAME;
			$sort_col = $sort_col ? $sort_col : TaskRecord :: UPDATED_AT_COL;
			$sort_order = $sort_order ? $sort_order : 'desc';

			$sort_criteria = " $sort_col $sort_order ";
			
			if ($count_only) {
				
				
				$sql = "SELECT count(*) as cnt FROM  $table_name WHERE  $where_cond ";
			
				$result = $db->query($sql);
				
				while ($row = $db->dbarray($result)) {				
					return isset($row['cnt']) ? $row['cnt'] : 0;
				}
				return 0;
			}

			$results = CommonRecord :: getObjects($table_name, $where_cond, " $sort_criteria ", $offset, $limit, new TaskRecord($db));

		} else {
			$where_cond = "( $permission_table_name." . UserPermission :: RECORD_TYPE_COL . " = " . Constants :: TASK . " )";
			if ($status_str) {
				$where_cond = $where_cond . " AND ( $task_table_name." . TaskRecord :: STATUS_COL . " IN ($status_str) ) ";
			}
			if($from_date_ymd && $to_date_ymd) {
				//$where_cond = $where_cond . " AND ( DATE($task_table_name." . TaskRecord :: UPDATED_AT_COL . ") >= '$from_date_ymd' ) ";
				//$where_cond = $where_cond . " AND ( DATE($task_table_name." . TaskRecord :: UPDATED_AT_COL . ") <= '$to_date_ymd' ) ";
				
				$cond1 =  "  ( DATE($task_table_name." . TaskRecord :: UPDATED_AT_COL . ") = '$from_date_ymd' ) ";
				$cond2 =   "  ( DATE($task_table_name." . TaskRecord :: UPDATED_AT_COL . ") = '$to_date_ymd' ) ";
				
				$cond3 =   "  ( DATE($task_table_name." . TaskRecord :: UPDATED_AT_COL . ") > '$from_date_ymd' ) ";
				$cond4 =   "   ( DATE($task_table_name." . TaskRecord :: UPDATED_AT_COL . ") < '$to_date_ymd' )  ";
				
				$where_cond = $where_cond . " AND ( $cond1 OR $cond2 OR ($cond3 AND $cond4)) ";
			}
			if ($priority) {
				$where_cond = $where_cond . " AND ( $task_table_name." . TaskRecord :: PRIORITY_COL . "= '$priority' ) ";
			}
			
			//commented to consider view only assignments
			/*if (!$status_str  && $exclude_unassigned) {
				$where_cond = $where_cond . " AND ( $task_table_name.".TaskRecord :: STATUS_COL . " != '".Constants::TASK_OPEN."'  ) ";
			}*/

			$where_cond = ($project_id != 0) ? $where_cond . " AND ( $task_table_name." . TaskRecord :: PARENT_PROJECT_ID_COL . "= '$project_id' ) " : $where_cond;

			$where_cond = $where_cond . " AND  ( $permission_table_name." . UserPermission :: USER_ID_COL . " = " . $team_id . ")";

			$where_cond = $where_cond . "AND ( ($permission_table_name." . UserPermission :: PERMISSION_COL . " = " . Constants :: CAN_PERFORM_TASK . ") OR ($permission_table_name." . UserPermission :: PERMISSION_COL . " = " . Constants :: CAN_VIEW_TASK . "))";

			$where_cond .= " AND $lead_project_id_condn ";
			$limit_constraint = '';
			if ($limit && $limit != '') {
				$offset = ($offset && $offset != '') ? $offset : 0;
				$limit_constraint = " LIMIT $offset, $limit ";

			}

			$sort_col2 = TaskRecord :: UPDATED_AT_COL;
			$sort_order2 = "desc";
			$sort_criteria = $sort_col ? " $task_table_name.$sort_col $sort_order " : " $task_table_name.$sort_col2 $sort_order2 ";

			$sql = "SELECT * FROM " . " $task_table_name JOIN " . " $permission_table_name ON $permission_table_name." . UserPermission :: RECORD_ID_COL . " = $task_table_name." . TaskRecord :: ID_COL . " WHERE " . $where_cond . " ORDER BY $sort_criteria $limit_constraint";

			if ($count_only) {
				$sql = "SELECT count(*) as cnt FROM " . " $task_table_name JOIN " . " $permission_table_name ON $permission_table_name." . UserPermission :: RECORD_ID_COL . " = $task_table_name." . TaskRecord :: ID_COL . " WHERE " . $where_cond . " ORDER BY $sort_criteria $limit_constraint";
				$result = $db->query($sql);
				while ($row = $db->dbarray($result)) {
					return isset($row['cnt']) ? $row['cnt'] : 0;
				}
				return 0;
			}

		//	var_dump($where_cond);
				//exit();
			$records = $db->query($sql);
			$results = self :: getTaskObjectRecords($db, $records);
		}

		return $results;
	}

	public static function getTaskReportForAdmin($db, $user_id, $project_id = false, $status = false,$priority='', $from_date_ymd='', $to_date_ymd='') {
		$task_table_name = $db->getPrefix() . TaskRecord :: TABLE_NAME;
		$permission_table_name = $db->getPrefix() . UserPermission :: TABLE_NAME;
		$project_table_name = $db->getPrefix() . ProjectRecord :: TABLE_NAME;

		$lead_project_ids = ProjectRecordPeer :: getLeadProjectIds($db, $user_id);

		$lead_project_id_condn = " 0 ";
		if (!empty ($lead_project_ids)) {
			$lead_project_ids_str = "'" . implode("','", $lead_project_ids) . "'";
			$lead_project_id_condn = "  ( $task_table_name." . TaskRecord :: PARENT_PROJECT_ID_COL . "  IN ($lead_project_ids_str ) ) ";
		}
		/////////////////////////assigned BY this user

		$all_results = new StdClass;
		$where_cond = " 1 ";
		if ($status != 0 && $status != '') {
			$where_cond = TaskRecord :: STATUS_COL . "= '$status'  ";
		}
		if ($project_id != 0) {
			$where_cond = $where_cond . " AND ( " . TaskRecord :: PARENT_PROJECT_ID_COL . "= '$project_id' ) ";
		}
		$where_cond .= " AND $lead_project_id_condn ";

		$table_name = $db->getPrefix() . TaskRecord :: TABLE_NAME;
		$all_results->assignedToOthers = CommonRecord :: getObjects($table_name, $where_cond, ' id desc ', '', '', new TaskRecord($db));

		///////////////////////////////assigned to this user 
		$where_cond = "( $permission_table_name." . UserPermission :: RECORD_TYPE_COL . " = " . Constants :: TASK . " )";
		if ($status != 0 && $status != '') {
			$where_cond = $where_cond . " AND ( $task_table_name." . TaskRecord :: STATUS_COL . "= '$status' ) ";
		}
		$where_cond = ($project_id != 0) ? $where_cond . " AND ( $task_table_name." . TaskRecord :: PARENT_PROJECT_ID_COL . "= '$project_id' ) " : $where_cond;
	
		
		if($from_date_ymd && $to_date_ymd) {
				//$where_cond = $where_cond . " AND ( DATE($task_table_name." . TaskRecord :: UPDATED_AT_COL . ") >= '$from_date_ymd' ) ";
				//$where_cond = $where_cond . " AND ( DATE($task_table_name." . TaskRecord :: UPDATED_AT_COL . ") <= '$to_date_ymd' ) ";
				
				
				$cond1 =  "  ( DATE($task_table_name." . TaskRecord :: UPDATED_AT_COL . ") = '$from_date_ymd' ) ";
				$cond2 =   "  ( DATE($task_table_name." . TaskRecord :: UPDATED_AT_COL . ") = '$to_date_ymd' ) ";
				
				$cond3 =   "  ( DATE($task_table_name." . TaskRecord :: UPDATED_AT_COL . ") > '$from_date_ymd' ) ";
				$cond4 =   "   ( DATE($task_table_name." . TaskRecord :: UPDATED_AT_COL . ") < '$to_date_ymd' )  ";
				
				$where_cond = $where_cond . " AND ( $cond1 OR $cond2 OR ($cond3 AND $cond4)) ";
				
			}
			if ($priority) {
				$where_cond = $where_cond . " AND ( $task_table_name." . TaskRecord :: PRIORITY_COL . "= '$priority' ) ";
			}
		
	
		$where_cond = $where_cond . " AND  ( $permission_table_name." . UserPermission :: USER_ID_COL . " = " . $user_id . ")";
		$where_cond = $where_cond . "AND ( ($permission_table_name." . UserPermission :: PERMISSION_COL . " = " . Constants :: CAN_PERFORM_TASK . ") OR  ($permission_table_name." . UserPermission :: PERMISSION_COL . " = " . Constants :: CAN_VIEW_TASK . "))";

		$sql = "SELECT * FROM " . " $task_table_name JOIN " . " $permission_table_name ON $permission_table_name." . UserPermission :: RECORD_ID_COL . " = $task_table_name." . TaskRecord :: ID_COL . " WHERE " . $where_cond . " ORDER BY $task_table_name." . TaskRecord :: UPDATED_AT_COL . "  desc ";

		$records = $db->query($sql);
		$all_results->assignedToThisUser = self :: getTaskObjectRecords($db, $records);

		return $all_results;
	}
	//todo needs further changes to avoid join

}
?>