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
require_once 'AppLogPeer.php';
require_once 'Constants.php';
require_once 'Issue.php';
require_once 'IssueTask.php';

class IssuePeer {
    public static function getProjectIssues($db,$project_ids,$status='',$priority='',$order_by = false,$order='desc', $offset = null,$limit = null, $from_ymd = '', $to_ymd = ''){
    	$table_name = $db->getPrefix() . Issue :: TABLE_NAME;
    	$where_cond = " 1 ";
    	if(!empty($project_ids)) {
			
			$project_ids_str = "('".implode("','",$project_ids)."')";
			$where_cond .= " AND " . Issue :: PROJECT_ID_COL . " IN " . $project_ids_str . " ";
		}
    	if($status != '' && $status != 0){  //all
    		$where_cond = $where_cond ." AND ( " .Issue :: STATUS_COL ." = '$status' ) ";
    	}
    	if($priority ){  //all
    		$where_cond = $where_cond ." AND ( " .Issue :: PRIORITY_COL ." = '$priority' ) ";
    	}
    	
    	if($from_ymd && $to_ymd) {
			//	$where_cond = $where_cond . " AND ( DATE(". Issue :: UPDATED_AT_COL . ") >= '$from_ymd' ) ";
			//	$where_cond = $where_cond . " AND ( DATE( " . Issue :: UPDATED_AT_COL . ") <= '$to_ymd' ) ";
				
				$cond1 =  "  ( DATE($table_name." . Issue :: UPDATED_AT_COL . ") = '$from_ymd' ) ";
				$cond2 =   "  ( DATE($table_name." . Issue :: UPDATED_AT_COL . ") = '$to_ymd' ) ";
				
				$cond3 =   "  ( DATE($table_name." . Issue :: UPDATED_AT_COL . ") > '$from_ymd' ) ";
				$cond4 =   "   ( DATE($table_name." . Issue :: UPDATED_AT_COL . ") < '$to_ymd' )  ";
				
				$where_cond = $where_cond . " AND ( $cond1 OR $cond2 OR ($cond3 AND $cond4)) ";
			//	var_dump($where_cond);
			}
    	
    	
    	if(!$order_by) {
    		$order_by = Issue :: UPDATED_AT_COL ." $order ";
    	}
    	
    	$records = CommonRecord :: getObjects($table_name, $where_cond, $order_by , $offset,$limit, new Issue($db));
    	if(is_array($records) && count($records) > 0){
    		return $records;
    	}
    	return null;
    }
    
    public static function findByPK($db, $id){
    	$table_name = $db->getPrefix() . Issue :: TABLE_NAME;
    	$where_cond = Issue :: ID_COL ." = ". $id;
    	$records = CommonRecord :: getObjects($table_name, $where_cond, '' , '','', new Issue($db));
    	if(is_array($records) && count($records) > 0){
    		return $records[0];
    	}
    	return null;
    }
	
	public static function deleteIssue($db, $issue_id) {
		$table_name = $db->getPrefix() . Issue :: TABLE_NAME;
		CommonRecord :: delete($db, $table_name, CommonRecord::ID_COL, array (
			$issue_id
		));
		
		MessageRecordPeer::deleteIssueMessages($db,$issue_id);
	}

	
	public static function getIssueWithUserName($db,$issue_recods){
		$user_ids_assoc = array ();
		if(count($issue_recods) > 0){ 
			foreach ($issue_recods as $issue_recod) {
				if ($issue_recod->getUserId() && $issue_recod->getUserId() != '') {
					$user_ids_assoc[$issue_recod->getUserId()] = '';
				}
			} 
			$user_ids = array_keys($user_ids_assoc); 
			require_once 'UserRecordPeer.php';
			$userid_signinid_assoc = UserRecordPeer :: getUserIdSigninIdAssoc($db, $user_ids);	
			foreach ($issue_recods as $issue_recod) {
				if (isset ($userid_signinid_assoc[$issue_recod->getUserId()])) {
					$issue_recod->setUserSigninId($userid_signinid_assoc[$issue_recod->getUserId()]);					
				} else {
					$issue_recod->setUserSigninId('None');
				}			
			}
		}
				
		return $issue_recods;
	}
	
	public static function isIssuePromotedAsTask($db, $record){
		$table_name =  $db->getPrefix() . IssueTask :: TABLE_NAME;
		$where_cond = "";
		
			if($record->getType() == Constants :: TASK) {
				$where_cond = IssueTask :: TASK_ID_COL ." = " .$record->getId() ;
			}
			else if($record->getType() == Constants :: ISSUE) {
				$where_cond = IssueTask :: ISSUE_ID_COL ." = " .$record->getId() ;
			}
		
		$records = CommonRecord :: getObjects($table_name, $where_cond,'','','1', new IssueTask($db));
		if(is_array($records) && count($records) > 0){
			return $records[0];
		}
		return null;
	}
	public static function getIssuesPostedByUserId($db, $by_user_id='', $status_arr = array (), $offset = '', $limit = '', $sort_col = false, $sort_order = false, $count_only = false) {

$table_name = $db->getPrefix() . Issue :: TABLE_NAME;
    	$where_cond = " 1 ";
    	if($by_user_id && $by_user_id !=''){ 
    		
    		$where_cond = $where_cond ." AND " .Issue :: USER_ID_COL ." =  '$by_user_id'  " ;
    	}
    	if(!empty($status_arr)){  //all
    		$status_str = "'" . implode("','", $status_arr) . "'";
    		$where_cond = $where_cond ." AND " .Issue :: STATUS_COL ." IN  ($status_str)  " ;
    	}
    	if ($count_only) {
				$sql = "SELECT count(*) as cnt FROM  $table_name WHERE  $where_cond ";
				$result = $db->query($sql);
				while ($row = $db->dbarray($result)) {
					return ($row['cnt']);
				}
				return 0;
		}
    	$order_by = false;
    	if($sort_order && $sort_col) {
    		$order_by = Issue :: UPDATED_AT_COL ." $sort_col $sort_order ";
    	}
    	
    	if(!$order_by) {
    		$order_by = Issue :: UPDATED_AT_COL ." desc ";
    	}
    	
    	$records = CommonRecord :: getObjects($table_name, $where_cond, $order_by , $offset,$limit, new Issue($db));
    	if(is_array($records) && count($records) > 0){
    		return $records;
    	}
    	return null;


	
	}
}
?>