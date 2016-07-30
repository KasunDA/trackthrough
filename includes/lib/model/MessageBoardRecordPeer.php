<?php
/*
 * Created on January 12, 2013
 *
 * bispark software services
 * www.bispark.com
 */
require_once 'CommonRecord.php';
require_once 'MessageBoardRecord.php';
require_once 'UserRecordPeer.php';

require_once 'Constants.php';

class MessageBoardRecordPeer {
	public static function getUnreadMessageList($db,$userId,$offset='', $limit='') {
		$where_cond = MessageBoardRecord :: USER_ID_COL . "='$userId' AND ".MessageBoardRecord :: STATUS_COL. "='".Constants::UNREAD_MESSAGE."'";
		try{ 
		
		$table_name = $db->getPrefix() . MessageBoardRecord :: TABLE_NAME;
		$records = CommonRecord :: getObjects($table_name, $where_cond, 'id desc', $offset, $limit, new MessageBoardRecord($db));
		}catch (Exception $exception) {
			throw new Exception('Error while retrieving new message; ' . $exception->getMessage());
		}
		return $records;
	}
	public static function setMessagesReadForUser($db, $message_id_arr, $user_id) {
		$table_name = $db->getPrefix() . MessageBoardRecord :: TABLE_NAME;
		$message_id_arr_str = "('".implode("','", $message_id_arr)."')";
		$where_cond = MessageBoardRecord :: STATUS_COL . "='".Constants::UNREAD_MESSAGE."' AND ".MessageBoardRecord :: USER_ID_COL . " = '$user_id' AND ". MessageBoardRecord :: MESSAGE_ID_COL. " IN $message_id_arr_str  ";
		
		$sql = "update " . $table_name . " set " . MessageBoardRecord :: STATUS_COL . "='".Constants :: NONE."' where $where_cond " ; 
		
		$db->query($sql);
	}
	public static function getUnreadCountsForUser($config, $user_id) {
		$where_cond = MessageBoardRecord :: USER_ID_COL . "='$user_id'  AND ".MessageBoardRecord :: STATUS_COL. "='".Constants::UNREAD_MESSAGE."'";
		try{ 
			$db = Db :: getInstance($config);
			$table_name = $db->getPrefix() . MessageBoardRecord :: TABLE_NAME;
		return $db->count(MessageBoardRecord :: ID_COL, $table_name, $where_cond);
		}catch (Exception $exception) {
			throw new Exception('Error while retrieving new message; ' . $exception->getMessage());
		}
		return 0;
	}
	public static function addMessageToMessageBoard($db, $message_id,$user_id) {
			$message_board = null;
			try {
			/*$table_name = $db->getPrefix() . MessageBoardRecord :: TABLE_NAME; 
			$sql = "insert into  " . $table_name . " values(0, $message_id, $user_id)"; 
			return $db->query($sql);*/
			$message_board = new MessageBoardRecord($db);
			$message_board->setMessageId($message_id);
			$message_board->setUserId($user_id);
			$message_board->store();
			
		 }catch (Exception $exception) {
			throw new Exception('Error while adding new message; ' . $exception->getMessage());
		 }
		  return $message_board;
	}
	public static function deleteUserMessageBoardRecord($config, $message_record='',$user_id=''){
		$db = Db :: getInstance($config);
		$message_id = $message_record->getId();
		$message_id_cond = '';
		$user_id_cond = '';
				
		try{
			$message_id_cond = MessageBoardRecord :: MESSAGE_ID_COL . "= '$message_id'";
			if($user_id != ''){ 
				$user_id_cond = MessageBoardRecord :: USER_ID_COL . "= '$user_id'";
				$where_cond = " $message_id_cond and $user_id_cond";
			}
			else{// condition to delete message board record when the project or task has been deleted.
				$where_cond = " $message_id_cond";
			}
	   
			$table_name = $db->getPrefix() . MessageBoardRecord :: TABLE_NAME;
			$records = CommonRecord :: getObjects($table_name, $where_cond, '', '', '', new MessageBoardRecord($db));
			
			$ids = array();
			for($cnt = 0; $cnt<count($records); $cnt++){
				$ids[] =  $records[$cnt]->getId();
			}
			self :: deleteMessageBoardRecords($db, $ids);
		
		}catch (Exception $exception) {
			throw new Exception('Error while retrieving new message; ' . $exception->getMessage());
		}
		return;
   }
   public  static function deleteMessageBoardRecords($db,$ids) {
		
		$table_name = $db->getPrefix() . MessageBoardRecord :: TABLE_NAME;
		CommonRecord :: delete($db, $table_name, MessageBoardRecord :: ID_COL, $ids);
	}
	
	private static function getUserMessageCriteria($db, $to_user_id, $inbox_status = '', $exclude_deleted = true) {
		$message_board_table_name = $db->getPrefix() . MessageBoardRecord :: TABLE_NAME;
		$message_table_name = $db->getPrefix() . MessageRecord :: TABLE_NAME;
		
		$where_cond = " 1 ";
		if ($inbox_status && $inbox_status != '') {
			$where_cond .= " AND ".$message_board_table_name. ".".MessageBoardRecord :: STATUS_COL . "= '$inbox_status'  ";
		}
		
		$where_cond .= " AND ".$message_board_table_name. ".".MessageBoardRecord :: MESSAGE_ID_COL . "= $message_table_name.".MessageRecord :: ID_COL;
		$where_cond .= " AND ".$message_board_table_name. ".".MessageBoardRecord :: USER_ID_COL . "= '$to_user_id' ";
		if($exclude_deleted) {
			
			$where_cond .= " AND ".$message_table_name. ".".MessageRecord :: STATUS_COL . "<> '".Constants::DELETED_MESSAGE."' ";
		
		}
		return $where_cond;
	}
	public static function  getUserMessageCount($db, $to_user_id, $inbox_status = '', $exclude_deleted = true) {
		$message_board_table_name = $db->getPrefix() . MessageBoardRecord :: TABLE_NAME;
		$message_table_name = $db->getPrefix() . MessageRecord :: TABLE_NAME;
	
		$where_cond = self:: getUserMessageCriteria($db, $to_user_id, $inbox_status , $exclude_deleted);
		$sql = "SELECT COUNT($message_board_table_name.id)  FROM  $message_board_table_name, $message_table_name ". " WHERE " . $where_cond ;
		
		$result = $db->query($sql);
		if ($result) {
				return mysql_result($result, 0);
		}
		return 0;
	}
	public static function getUserMessageBoardRecords($db, $to_user_id, $inbox_status = '', $exclude_deleted = true, $offset='',$limit='', $orderby = '') {
		$message_board_table_name = $db->getPrefix() . MessageBoardRecord :: TABLE_NAME;
		$message_table_name = $db->getPrefix() . MessageRecord :: TABLE_NAME;
		
		/*$where_cond = " 1 ";
		if ($inbox_status && $inbox_status != '') {
			$where_cond .= " AND ".$message_board_table_name. ".".MessageBoardRecord :: STATUS_COL . "= '$inbox_status'  ";
		}
		
		$where_cond .= " AND ".$message_board_table_name. ".".MessageBoardRecord :: MESSAGE_ID_COL . "= $message_table_name.".MessageRecord :: ID_COL;
		$where_cond .= " AND ".$message_board_table_name. ".".MessageBoardRecord :: USER_ID_COL . "= '$to_user_id' ";
		if($exclude_deleted) {
			
			$where_cond .= " AND ".$message_table_name. ".".MessageRecord :: STATUS_COL . "<> '".Constants::DELETED_MESSAGE."' ";
		
		}*/
		
		$where_cond = self:: getUserMessageCriteria($db, $to_user_id, $inbox_status , $exclude_deleted);
		
		if(!$orderby || $orderby = '') {
			$orderby = $message_board_table_name. "." . MessageBoardRecord :: ID_COL . "  desc ";
		
		}
		$limit_condn =  "";
		if($limit && $limit != '') {
			$limit_condn = "  LIMIT $limit offset $offset";
		}
		
		//only message board entries are selected
		$sql = "SELECT $message_board_table_name.*  FROM  $message_board_table_name, $message_table_name ". " WHERE " . $where_cond . " ORDER BY  $orderby $limit_condn ";

		
		$results = $db->query($sql);
		
		$records = array ();

		while ($row = $db->dbObject($results)) {
			$object = new MessageBoardRecord($db);
			$object->setId($row->id); //message board id

			$object->setUserId($row->user_id);
			$object->setMessageId($row->message_id);
			$object->setStatus($row->status);

			$records[] = $object;
		}
		return $records; 
		
		
	}
}
?>