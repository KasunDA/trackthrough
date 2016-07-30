<?php
/*
 * Created on April 12, 2009
 *
 * bispark software services
 * www.bispark.com
 */
require_once 'CommonRecord.php';
require_once 'ProjectRecord.php';
require_once 'MessageRecordPeer.php';
require_once 'AppLogPeer.php';
require_once 'Constants.php';
require_once 'PreferenceRecord.php';
require_once 'PreferenceRecordPeer.php';
require_once 'TaskRecordPeer.php'; 
require_once 'UserPermissionPeer.php';
require_once 'TaskRecordPeer.php';

class ProjectRecordPeer {

	public static function deleteProject($db, $project_id) {
		$table_name = $db->getPrefix() . ProjectRecord :: TABLE_NAME;
		CommonRecord :: delete($db, $table_name, CommonRecord :: ID_COL, array (
			$project_id
		));

		MessageRecordPeer :: deleteProjectMessages($db, $project_id);
	}
	
	public static function findByPK($db, $projectId) {
		$where_cond = CommonRecord :: ID_COL . " = '$projectId'   ";
		
		$table_name = $db->getPrefix() . ProjectRecord :: TABLE_NAME;
		$join_table_name = $db->getPrefix(). TaskRecord :: TABLE_NAME;
		$records = CommonRecord :: getObjects($table_name, $where_cond, '', '', 1, new ProjectRecord($db));
		if (is_array($records) && count($records) > 0) {
			return $records[0];
		}
		return null;
	}
	
	public static function getCountTotalProjects($db, $leadId= ''){
		$where_cond =  ($leadId == '') ? '' : ProjectRecord :: LEAD_ID_COL . "='$leadId' ";
		$table_name = $db->getPrefix() . ProjectRecord :: TABLE_NAME;
		return $db->count(ProjectRecord :: ID_COL, $table_name, $where_cond);
	}
	
	
	public static function getProjectRecordsWithSigninId($db, $project_records) {
		$user_ids_assoc = array ();
		
		foreach ($project_records as $project_record) {
			if ($project_record->getLeadId() && $project_record->getLeadId() != '') {
				$user_ids_assoc[$project_record->getLeadId()] = '';
			}
		}
		$user_ids = array_keys($user_ids_assoc); 
		require_once 'UserRecordPeer.php';
		$userid_signinid_assoc = UserRecordPeer :: getUserIdSigninIdAssoc($db, $user_ids);
		
		foreach ($project_records as $project_record) {
			if (isset ($userid_signinid_assoc[$project_record->getLeadId()])) {
				$project_record->setLeadSigninId($userid_signinid_assoc[$project_record->getLeadId()]);
			} else {
				$project_record->setLeadSigninId('None');
			}
		} 	
		return $project_records;
	}
	
	
	public static function getProjectProgress($db, $project_id) {
		
		$task_table_name = $db->getPrefix() . TaskRecord :: TABLE_NAME;
		$sql = "SELECT avg(".TaskRecord :: PROGRESS_COL.") as project_progress FROM ". $task_table_name . " where parent_project_id = '$project_id' ";
		$results = $db->query($sql);
		while($row = $db->dbObject($results)){
			if(isset($row->project_progress)) {
				return $row->project_progress;
			}
		}
		return 0;
		
	}
	public static function getIconName($db, $project_id) {
		$table_name = $db->getPrefix() . ProjectRecord :: TABLE_NAME;
		$sql = "SELECT icon_name  FROM ". $table_name . " where id = '$project_id' ";
		$results = $db->query($sql);
		
		while($row = $db->dbObject($results)){
			if(isset($row->icon_name)) {
				return $row->icon_name;
			}
		}
		return '';
	}
	
	public static function getLeadProjectIds($db,$user_id){
		$project_ids = array();
			$table_name = $db->getPrefix() . ProjectRecord :: TABLE_NAME;
			$where_cond = ProjectRecord :: LEAD_ID_COL . "='$user_id' ";
			
			$project_records = CommonRecord :: getObjects($table_name, $where_cond, ' name asc  ','','', new ProjectRecord($db), array('id'));
			if(!empty($project_records)) {
				foreach ($project_records as $project_record) {
					
					$project_ids[] = $project_record->getId();
				}
				
				
			
		}
		
		return $project_ids;
	}
	//to be used for Admin only
	public static function getAllProjectIdAndProjectName($db,$exclude_project_id=false){

			$table_name = $db->getPrefix() . ProjectRecord :: TABLE_NAME;
			$where_cond = " 1 ";
			if($exclude_project_id) {
				$where_cond .= " AND ". ProjectRecord :: ID_COL . "!='$exclude_project_id' ";
			}
			return CommonRecord :: getObjects($table_name, $where_cond, ' name asc  ','','', new ProjectRecord($db), array('id','name'));	
		
	}
	
	//returns all project where user is  lead
	public static function getLeadProjectIdAndProjectName($db,$user_id, $hide_project_ids = array()){

			$table_name = $db->getPrefix() . ProjectRecord :: TABLE_NAME;
			$where_cond = ProjectRecord :: LEAD_ID_COL . "=' $user_id' ";
			if($hide_project_ids && !empty($hide_project_ids)) { 
				
			
				$hide_project_ids_str = "'".implode("','",$hide_project_ids). "'";				
					
				//	$where_cond .= " AND ". ProjectRecord :: ID_COL . "!='$exclude_project_id' ";
				$where_cond .= " AND ". ProjectRecord :: ID_COL . " NOT IN ($hide_project_ids_str) ";
			}
			return CommonRecord :: getObjects($table_name, $where_cond, ' name asc  ','','', new ProjectRecord($db), array('id','name'));	
		
	}
	//returns all project where user is NOT lead
	public static function getNonLeadProjectIdAndProjectName($db,$user_id, $hide_project_ids = array()){
		
	
		$project_ids = array();
		$permission_records = UserPermissionPeer::listByUserIdPermissionTypes($db, $user_id, array(Constants :: CAN_PERFORM_TASK), Constants :: PROJECT);
		if(!empty($permission_records)) {
			foreach ($permission_records as $pr) {
				if(!empty($hide_project_ids)) {
					if(in_array($pr->getRecordId(), $hide_project_ids)) {
						continue;
					}
				}
				$project_ids[] = $pr->getRecordId();
			}
		}
		
		
		$table_name = $db->getPrefix() . ProjectRecord :: TABLE_NAME;
		$projectids_arr_str = "'".implode("','",$project_ids). "'";
		$where_cond = ProjectRecord :: ID_COL . " IN ($projectids_arr_str)  ";
		return CommonRecord :: getObjects($table_name, $where_cond, ' name asc  ','','', new ProjectRecord($db), array('id','name'));	
		
	
	}
	//hide project ids is not used for now
	public static function getUserProjectRecords($db,$offset = '',$limit = '', $access_all_projects= '', $user_id = '', $project_permission_types=array(), $sort_col=ProjectRecord :: UPDATED_AT_COL, $sort_order='desc', $hide_project_ids = array()){
		$project_table_name = $db->getPrefix() . ProjectRecord :: TABLE_NAME;
		$permission_table_name = $db->getPrefix() . UserPermission :: TABLE_NAME;
		
		$where_cond_hide = "";
		if(!empty($hide_project_ids)) {
						$hide_project_ids_str = "'".implode("','",$hide_project_ids). "'";
						$where_cond_hide = "   $project_table_name.".ProjectRecord :: ID_COL. " NOT IN ($hide_project_ids_str) ";
					}
		
		$offset = ($offset == '') ? '' : " offset $offset ";
		$limit = ($limit == '') ? '' : " limit $limit ";
		 //ProjectRecord :: NAME_COL ." asc "; //ProjectRecord :: UPDATED_AT_COL ." desc "
		 $order_by =  "$sort_col $sort_order";
		if($access_all_projects){
			$cond_str = "";
			if($where_cond_hide) {
				$cond_str =  " where $where_cond_hide";
			}
			$sql = "SELECT * FROM ". $project_table_name . " $cond_str order by ".$order_by. " ". $limit .$offset ;
		}
		else{ 
			$select_cols = array(
			'project.*',
			'permission.'.UserPermission :: PERMISSION_COL,
			
			);
			//project. is the prefix during select, it is NOT project table name, but alias we are going to use  below
			$select_col_str = implode(", ",$select_cols);
			
			$where_cond = "permission.". UserPermission :: RECORD_TYPE_COL ." = ". Constants :: PROJECT;
			
			
			if($where_cond_hide) {
				$where_cond .=  " AND $where_cond_hide ";
			}
			
			if($user_id != '') {
					$where_cond .= " AND  permission.".UserPermission :: USER_ID_COL. " = ". $user_id ;
					if(!empty($project_permission_types)) {
						$permission_types_str = "'".implode("','",$project_permission_types). "'";
						$where_cond .= " AND  permission.".UserPermission :: PERMISSION_COL. " IN ($permission_types_str) ";
					}
		
			}
			$order_by = "project.".$order_by;
			$sql = "SELECT $select_col_str FROM ". $project_table_name . " project JOIN ". $permission_table_name ." permission ON permission." .UserPermission :: RECORD_ID_COL ." = project." .ProjectRecord :: ID_COL ." WHERE ". $where_cond . " GROUP BY  permission.".UserPermission :: RECORD_ID_COL." ORDER BY $order_by " . $limit  .$offset;
	//	var_dump($sql);
		}
		$results = $db->query($sql);
		
		return self :: getProjectObjectRecords($db,$results);
		
		
	}
	
	
	private static function getProjectObjectRecords($db,$results){
		
 		
 		$records = array();
		$record_ids = array();
		while($row = $db->dbObject($results)){
			$project_id = false;
			
			if(!in_array($row->id, $record_ids)){
			
			$object = new ProjectRecord($db);
			$object->setId($row->id);
			
			$object->setLeadId($row->lead_id);
			$object->setType($row->type);
			$object->setName($row->name);
			$object->setDescription($row->description);
			$object->setIconName($row->icon_name);
			$object->setEnableIssueTracking($row->enable_issue_tracking);
			$object->setProgress($row->progress);
			$object->setAttachmentName($row->attachment_name);
			$object->setCreatedAt($row->created_at);
			if(isset($row->permission)){ 
				$object->setPermission($row->permission);
			}
			
			$records[] = $object;
			$record_ids[] = $object->getId();
			}
 		}
 		
 		return $records;
	}
	
	public static function countUserProjectRecords($db, $access_all_projects= '', $user_id = '', $hide_project_ids = array()){
		$project_table_name = $db->getPrefix() . ProjectRecord :: TABLE_NAME;
		$permission_table_name = $db->getPrefix() . UserPermission :: TABLE_NAME;
		
		$where_cond_hide = "";
		if(!empty($hide_project_ids)) {
						$hide_project_ids_str = "'".implode("','",$hide_project_ids). "'";
						$where_cond_hide = "   $project_table_name.".ProjectRecord :: ID_COL. " NOT IN ($hide_project_ids_str) ";
					}
		if($access_all_projects){
			$sql = "SELECT count(*) FROM ". $project_table_name;
			if($where_cond_hide) {
				$sql = $sql. " WHERE $where_cond_hide ";
			}
			
		}
		else{ 
			$where_cond = "permission.". UserPermission :: RECORD_TYPE_COL ." = ". Constants :: PROJECT;
			$where_cond = ($user_id != '')? $where_cond ." AND  permission.".UserPermission :: USER_ID_COL. " = ". $user_id : $where_cond;
			
			if($where_cond_hide) {
				$where_cond  = $where_cond. " AND ($where_cond_hide) ";
				
			}
			
			
			$sql = "SELECT count(DISTINCT permission.record_id )  FROM ". $permission_table_name . " permission JOIN ". $project_table_name ."  project ON project." .ProjectRecord :: ID_COL . " = permission." .UserPermission :: RECORD_ID_COL ." WHERE ". $where_cond;
		}
		
		$result = $db->query($sql);
		if($result){ 
			return mysql_result($result, 0);
		}
		return 0;	
		
	}
	
	public static function updateProjectProgress($db, $project_id) {
		
		$project_record = self :: findByPK($db, $project_id);
				
				if ($project_record == null) {
					throw new Exception(' error while retrieving project record!');
				}	
				$project_record->setProgress(self::getProjectProgress($db, $project_id));
				$project_record->setUpdatedAt(date('Y-m-d H:i:s'));
			   	$project_record->store();
		
		return $project_record;
	}
	
}
?>