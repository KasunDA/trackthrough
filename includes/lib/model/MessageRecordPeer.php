<?php


/*
 * Created on April 23, 2009
 *
 * bispark software services
 * www.bispark.com
 */
require_once 'CommonRecord.php';
require_once 'MessageRecord.php';
require_once 'MessageBoardRecord.php';
require_once 'UserRecordPeer.php';

require_once 'Constants.php';

class MessageRecordPeer {
	public static function deleteProjectMessages($db, $project_id) {
		
		$table_name = $db->getPrefix() . MessageRecord :: TABLE_NAME;
		$where_cond = MessageRecord :: TYPE_COL ." = '" . Constants :: PROJECT_MESSAGE ."' AND ". MessageRecord :: TYPE_ID_COL ." = '" . $project_id . "'  " ;
		$sql = "DELETE FROM $table_name where ( $where_cond )";
		return $db->query($sql);
	}
	
	public static function deleteTaskMessages($db, $task_id) {
		/*
		$table_name = $db->getPrefix() . MessageRecord :: TABLE_NAME;
		CommonRecord :: delete($db, $table_name, MessageRecord :: TYPE_ID_COL, array (
			$task_id
		));*/
		$table_name = $db->getPrefix() . MessageRecord :: TABLE_NAME;
		$where_cond = MessageRecord :: TYPE_COL ." = '" . Constants :: TASK_MESSAGE ."' AND ". MessageRecord :: TYPE_ID_COL ." = '" . $task_id . "'  " ;
		$sql = "DELETE FROM $table_name where ( $where_cond )";
		return $db->query($sql);
	}
	
	
	
	public static function deleteIssueMessages($db, $issue_id) {
		
		/*$table_name = $db->getPrefix() . MessageRecord :: TABLE_NAME;
		CommonRecord :: delete($db, $table_name, MessageRecord :: TYPE_ID_COL, array (
			$issue_id
		));*/
		$table_name = $db->getPrefix() . MessageRecord :: TABLE_NAME;
		$where_cond = MessageRecord :: TYPE_COL ." = '" . Constants :: ISSUE_MESSAGE ."' AND ". MessageRecord :: TYPE_ID_COL ." = '" . $issue_id . "'  " ;
		$sql = "DELETE FROM $table_name where ( $where_cond )";
		return $db->query($sql);
	}
	public static function deleteMessage($db, $message_id) {
		$table_name = $db->getPrefix() . MessageRecord :: TABLE_NAME;
		CommonRecord :: delete($db, $table_name, MessageRecord :: ID_COL, array (
			$message_id
		));
	}
	
	
	
	
	public static function findByPK($db, $id) {
		$where_cond = MessageRecord :: ID_COL . "='$id' ";
		$table_name = $db->getPrefix() . MessageRecord :: TABLE_NAME;
		$records = CommonRecord :: getObjects($table_name, $where_cond, '', '', 1, new MessageRecord($db));
		if (is_array($records) && count($records) > 0) {
			return $records[0];
		}
		return null;
	}
	
	
	public static function getMessageRecords($db, $message_ids=null, $exclude = '', $message_type = '', $type_id = '', $offset = null,$limit = null, $orderby = null) {
		$table_name = $db->getPrefix() . MessageRecord :: TABLE_NAME;
		
		$where_cond = ' 1 ';
		
		if($message_type !=  '') {
			$where_cond .= " AND " . MessageRecord :: TYPE_COL ." = " . $message_type ;
		
		}
		if($type_id !=  '') {
			$where_cond .=  " AND " . MessageRecord :: TYPE_ID_COL  ." = ". $type_id ;
		
		}
		
		if($exclude != '') {
			$where_cond .= " AND " . MessageRecord :: STATUS_COL . "<>'" . $exclude . " '";
		}
		
		
		if($message_ids != null && !empty($message_ids)) {
			
			$message_ids_str = "('".implode("','",$message_ids)."')";
			$where_cond .= " AND " . MessageRecord :: ID_COL . " IN " . $message_ids_str . " ";
		}
		
		
		if($orderby != null){
			if($orderby == 'asc'){
				$orderby_cond = ' id asc ';
			}else{
				$orderby_cond = ' id desc ';
			}
		}else{
			$orderby_cond = ' id desc ';
		}
		
		
		//throw new Exception($where_cond);
		$records = CommonRecord :: getObjects($table_name, $where_cond, $orderby_cond, $offset, $limit, new MessageRecord($db));
		
		return $records;
	}
	
	public static function countTaskMessageRecords($db, $exclude = null,  $type_id = null) {
		$exclude_cond = '';
		$type_cond = '';
		$and = '';
	
		if ($exclude != null) { //non admin
			$exclude_cond = "(" . MessageRecord :: STATUS_COL . " <> '" . $exclude . "') ";
			$and = ' and ';
		}
		// blank for NON project messages
		if ($type_id != null) {
			$type_cond =" $and " . MessageRecord :: TYPE_ID_COL . " = '$type_id' ";
			
		}
		$message_type_cond = " AND " . MessageRecord :: TYPE_COL ." = '" . Constants :: TASK_MESSAGE ."' ";
		
		$where_cond = " $exclude_cond $type_cond  $message_type_cond";
		

		try {
			$table = $db->getPrefix() . MessageRecord :: TABLE_NAME;
			return $db->count(MessageRecord :: ID_COL, $table, $where_cond);
		} catch (Exception $exception) {
			throw new Exception('Error while retrieving # new messages; ' . $exception->getMessage());
		}
		return 0;
	
	}
	
	public static function getMessageRecordsWithUserName($db, $message_records, $self_user_id = '') { 
		$user_ids_assoc = array ();
		foreach ($message_records as $message_record) {
			$user_ids_assoc[$message_record->getFromId()] = '';
			
		}
		$user_ids = array_keys($user_ids_assoc);
		require_once 'UserRecordPeer.php';
		$admin_user_first_name = UserRecordPeer :: getAdminUserFirstName($db);
		$userid_first_name_assoc = UserRecordPeer :: getUserIdFirstNameAssoc($db, $user_ids);
		foreach ($message_records as $message_record) {
			if (isset ($userid_first_name_assoc[$message_record->getFromId()])) {
				$message_record->setFromName($userid_first_name_assoc[$message_record->getFromId()]);
			}
			
			if ($message_record->getFromId() == $self_user_id) {
				$message_record->setFromSelf(true);
			}
		
		}
		return $message_records;
	}
	

	
	public static function getMessageWithName($db,$message_record){
		$user_ids_assoc = array ();
		$user_ids_assoc[$message_record->getFromId()] = '';
		$user_ids = array_keys($user_ids_assoc);
		require_once 'UserRecordPeer.php';
		$admin_user_first_name = UserRecordPeer :: getAdminUserFirstName($db);
		$userid_first_name_assoc = UserRecordPeer :: getUserIdSigninIdAssoc($db, $user_ids);
			if (isset ($userid_first_name_assoc[$message_record->getFromId()])) {
				$message_record->setFromName($userid_first_name_assoc[$message_record->getFromId()]);
			}
			if($message_record->getSubject()!= ''){
				$subject = Util ::  truncate($message_record->getSubject(),70, '....');
				$message_record->setSubject(wordwrap($subject, 70, "\n", 1));
				
			}
		return $message_record;
	}
	
}
?>