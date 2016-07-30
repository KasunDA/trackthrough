<?php


/*
 * Created on April 17, 2009
 *
 * bispark software services
 * www.bispark.com
 */
require_once 'CommonRecord.php';
require_once 'BaseController.php';
require_once 'UserRecord.php';
require_once 'Util.php';
require_once 'Constants.php';
require_once 'BookmarkRecord.php';
require_once 'ProjectRecordPeer.php';
require_once 'TaskRecordPeer.php';
require_once 'MessageRecordPeer.php';
require_once 'UserPermission.php';
require_once 'IssuePeer.php';

class UserRecordPeer {
	public static function getAdminUserFirstName($db) {
		$where_cond = UserPermission :: PERMISSION_COL . "='" . Constants :: ADMINISTRATION . "' ";
		$permission_table = $db->getPrefix() . UserPermission :: TABLE_NAME;
		$results = CommonRecord :: getObjects($permission_table, $where_cond, '', '', 1, new UserPermission($db));
		if (count($results) > 0) {
			$table_name = $db->getPrefix() . UserRecord :: TABLE_NAME;
			$where_cond = UserRecord :: ID_COL . "= '" . $results[0]->getUserId() . "'";
			$records = CommonRecord :: getObjects($table_name, $where_cond, '', '', 1, new UserRecord($db), array (
				UserRecord :: FIRST_NAME_COL
			));
			if (is_array($records) && count($records) > 0) {
				return $records[0]->getFirstName();
			}
		}
		return '';
	}

	public static function getAdminUser($db) {
		$where_cond = UserPermission :: PERMISSION_COL . "='" . Constants :: ADMINISTRATION . "' ";
		$permission_table = $db->getPrefix() . UserPermission :: TABLE_NAME;
		$results = CommonRecord :: getObjects($permission_table, $where_cond, '', '', 1, new UserPermission($db));
		$user_record = null;
		if (count($results) > 0) {
			$user_record = self :: findByPK($db, $results[0]->getUserId());
		}
		return $user_record;
	}

	public static function getUsersCount($db, $exclude_ids = array()) {

		$table_name = $db->getPrefix() . UserRecord :: TABLE_NAME;
		$user_id_condn = " 1 ";
		if(!empty($exclude_ids)) {
			$user_ids_str = "'" . implode("','", $exclude_ids) . "'";
			$user_id_condn = "  ( " . UserRecord :: ID_COL . "  NOT IN ($user_ids_str ) ) ";
		}
		
		
		return $db->count(MessageBoardRecord :: ID_COL, $table_name, " $user_id_condn");

	}
	public static function getUsers($db, $offset = '', $limit = '', $exclude_ids = array()) {

		$table_name = $db->getPrefix() . UserRecord :: TABLE_NAME;
		
		$user_id_condn = " 1 ";
		if(!empty($exclude_ids)) {
			$user_ids_str = "'" . implode("','", $exclude_ids) . "'";
			$user_id_condn = "  ( " . UserRecord :: ID_COL . "  NOT IN ($user_ids_str ) ) ";
		}
		return CommonRecord :: getObjects($table_name, " $user_id_condn ", 'id desc', $offset, $limit, new UserRecord($db));

	}
	public static function getIconName($db, $user_id) {
		$table_name = $db->getPrefix() . UserRecord :: TABLE_NAME;
		$sql = "SELECT icon_name  FROM ". $table_name . " where id = '$user_id' ";
		$results = $db->query($sql);
		
		while($row = $db->dbObject($results)){
			if(isset($row->icon_name)) {
				return $row->icon_name;
			}
		}
		return '';
	}

	public static function findBySigninIdOrEmail($db, $signinId, $email = '') {

		$where_cond = UserRecord :: SIGNIN_ID_COL . "='$signinId' ";
		if ($email != '') {
			$where_cond .= ' or ' . UserRecord :: EMAIL_COL . "='$email' ";
		}
		$table_name = $db->getPrefix() . UserRecord :: TABLE_NAME;
		$records = CommonRecord :: getObjects($table_name, $where_cond, '', '', 1, new UserRecord($db));
		if (is_array($records) && count($records) > 0) {
			return $records[0];
		}
		return null;
	}

	public static function findBySigninId($db, $signinId) {

		$where_cond = UserRecord :: SIGNIN_ID_COL . "='$signinId' ";

		$table_name = $db->getPrefix() . UserRecord :: TABLE_NAME;
		$records = CommonRecord :: getObjects($table_name, $where_cond, '', '', 1, new UserRecord($db));

		if (is_array($records) && count($records) > 0) {
			return $records[0];
		}
		return null;
	}
	public static function findByPK($db, $userId) {

		$where_cond = UserRecord :: ID_COL . "='$userId' ";

		$table_name = $db->getPrefix() . UserRecord :: TABLE_NAME;

		$records = CommonRecord :: getObjects($table_name, $where_cond, '', '', 1, new UserRecord($db));

		if (is_array($records) && count($records) > 0) {
			return $records[0];
		}
		return null;
	}
	//user who can perform task and lead projects
	public static function getNonAdminUserIds($config, $exclude_user_ids = array ()) {
		$db = Db :: getInstance($config);
		$table_name = $db->getPrefix() . UserPermission :: TABLE_NAME;
		$where_cond = "  ( " . UserPermission :: PERMISSION_COL . " = '" . Constants :: LEAD_PROJECT . "' OR " . UserPermission :: PERMISSION_COL . " = '" . Constants :: CAN_PERFORM_TASK . "' ) ";

		$where_cond .= "   AND (" . UserPermission :: RECORD_TYPE_COL . " = '" . Constants :: USER . "') ";
		$user_id_condn = " 1 ";
		if (!empty ($exclude_user_ids)) {
			$user_ids_str = "'" . implode("','", $exclude_user_ids) . "'";
			$user_id_condn = "  ( " . UserPermission :: USER_ID_COL . "  NOT IN ($user_ids_str ) ) ";
		}

		$where_cond .= " AND $user_id_condn ";

		$records = CommonRecord :: getObjects($table_name, $where_cond, 'id desc', '', '', new UserPermission($db), array (
			UserPermission :: USER_ID_COL
		));
		$user_ids = array ();
		//to make distinct
		if (!empty ($records)) {
			foreach ($records as $record) {
				if (!in_array($record->getUserId(), $user_ids)) {
					$user_ids[] = $record->getUserId();
				}
			}
		}
		return $user_ids;
	}
	//do not use unless complete retrieval is required
	public static function getCompleteUserRecords($db, $user_ids_arr = array ()) {

		$table_name = $db->getPrefix() . UserRecord :: TABLE_NAME;
		$user_id_condn = " 1 ";
		if (!empty ($user_ids_arr)) {
			$user_ids_str = "'" . implode("','", $user_ids_arr) . "'";
			$user_id_condn = "  ( " . UserRecord :: ID_COL . "  NOT IN ($user_ids_str ) ) ";
		}

		return CommonRecord :: getObjects($table_name, $user_id_condn, ' signin_id asc  ', '', '', new UserRecord($db));

	}
	/* NOTE - when $user_ids_arr is EMPTY, this function returns all USERS!!!! */
	public static function getUserRecordsWithSigninId($db, $user_ids_arr = array ()) {

		$table_name = $db->getPrefix() . UserRecord :: TABLE_NAME;
		$user_id_condn = " 1 ";
		if (!empty ($user_ids_arr)) {
			$user_ids_str = "'" . implode("','", $user_ids_arr) . "'";
			$user_id_condn = "  ( " . UserRecord :: ID_COL . "   IN ($user_ids_str ) ) ";
		}
		
		

		return CommonRecord :: getObjects($table_name, $user_id_condn, ' signin_id asc  ', '', '', new UserRecord($db), array (
			UserRecord :: ID_COL,
			UserRecord :: SIGNIN_ID_COL
		));

	}

	public static function findByConfigAndPK($config, $userId) {

		$db = $db = Db :: getInstance($config);
		$where_cond = UserRecord :: ID_COL . "='$userId' ";

		$table_name = $db->getPrefix() . UserRecord :: TABLE_NAME;
		$records = CommonRecord :: getObjects($table_name, $where_cond, '', '', 1, new UserRecord($db));
		if (is_array($records) && count($records) > 0) {
			return $records[0];
		}
		return null;
	}

	public static function findByEmail($db, $email) {

		$where_cond = UserRecord :: EMAIL_COL . "='$email' ";

		$table_name = $db->getPrefix() . UserRecord :: TABLE_NAME;
		$records = CommonRecord :: getObjects($table_name, $where_cond, '', '', 1, new UserRecord($db));
		if (is_array($records) && count($records) > 0) {
			return $records[0];
		}
		return null;
	}

	public static function getUserIdSigninIdAssoc($db, $userIds = array ()) {
		$table_name = $db->getPrefix() . UserRecord :: TABLE_NAME;
		$cond = '';
		if (is_array($userIds) && count($userIds) > 0) {
			$userIds_with_comma = implode(",", $userIds);
			$cond = UserRecord :: ID_COL . " IN ($userIds_with_comma) ";
		}
		$where_cond = ($cond == '') ? '' : " where $cond ";
		$sql = "select " . UserRecord :: ID_COL . ", " . UserRecord :: SIGNIN_ID_COL . " from $table_name  $where_cond";

		$result = $db->query($sql);

		$assoc = array ();
		while ($row = $db->dbarray($result)) {
			$assoc[$row[UserRecord :: ID_COL]] = $row[UserRecord :: SIGNIN_ID_COL];

		}
		return $assoc;
	}

	public static function getUserIdFirstNameAssoc($db, $userIds = array ()) {
		$table_name = $db->getPrefix() . UserRecord :: TABLE_NAME;
		$cond = '';
		if (is_array($userIds) && count($userIds) > 0) {
			$userIds_with_comma = implode(",", $userIds);
			$cond = UserRecord :: ID_COL . " IN ($userIds_with_comma) ";
		}
		$where_cond = ($cond == '') ? '' : " where $cond ";
		$sql = "select " . UserRecord :: ID_COL . ", " . UserRecord :: FIRST_NAME_COL . " from $table_name  $where_cond ";

		$result = $db->query($sql);

		$assoc = array ();
		while ($row = $db->dbarray($result)) {
			$assoc[$row[UserRecord :: ID_COL]] = $row[UserRecord :: FIRST_NAME_COL];
		}
		return $assoc;
	}

	public static function getSigninId($db, $user_id) {
		$assoc = self :: getUserIdSigninIdAssoc($db, array (
			$user_id
		));
		if (isset ($assoc[$user_id])) {
			return $assoc[$user_id];
		}
		return null;
	}

	public static function createIfNotExist($db, $signinId, $password, $email, $firstName, $type) {
		$user = self :: findBySigninId($db, $signinId);
		if ($user == null) {
			$user = new UserRecord($db);
			$user->setSigninId($signinId);
			$user->setEmail($email);
			$user->setFirstName($firstName);
			$user->setLastName('');

			$iv = Util :: create_iv();

			$user->setPassword(Util :: encrypt($password, $signinId, $iv));
			$user->setIv($iv);
			$user->setType($type);
			$user->store();
		}
		return $user;
	}

	public static function search_text($db, $search_text, $user_id, $is_admin = false, $search_project_details = true, $search_task_details = true, $search_issue_details = true, $search_messages = true) {
		$project_table_name = $db->getPrefix() . ProjectRecord :: TABLE_NAME;
		$task_table_name = $db->getPrefix() . TaskRecord :: TABLE_NAME;
		$issue_table_name = $db->getPrefix() . Issue :: TABLE_NAME;
		$message_table_name = $db->getPrefix() . MessageRecord :: TABLE_NAME;
		$issue_table_name = $db->getPrefix() . Issue :: TABLE_NAME;
			$projects_result = $tasks_result = $messages_result = $issues_result = $issue_messages_result= false;
		
		$search_results = array ();
		$search_text = mysql_real_escape_string($search_text);
		if ($is_admin) {
			if ($search_project_details) {
				$query = "SELECT * FROM $project_table_name WHERE ( " . ProjectRecord :: NAME_COL . " LIKE '%$search_text%' OR " . ProjectRecord :: DESCRIPTION_COL . " LIKE '%$search_text%') ";
				$projects_result = $db->query($query);
			}
			//task search
			if ($search_task_details) {
				$query = "SELECT * FROM $task_table_name  WHERE (" . TaskRecord :: NAME_COL . " LIKE '%$search_text%' OR " . TaskRecord :: DESCRIPTION_COL . " LIKE '%$search_text%') ";
				$tasks_result = $db->query($query);
			}
			//issue search
			if ($search_issue_details) {
				$query = "SELECT * FROM $issue_table_name  WHERE (" . Issue :: TITLE_COL . " LIKE '%$search_text%' OR " . Issue :: DESCRIPTION_COL . " LIKE '%$search_text%') ";
				$issues_result = $db->query($query);
			}
			//message search
			if ($search_messages) {
				$query = "SELECT * FROM $message_table_name  WHERE (" . MessageRecord :: MESSAGE_COL . " LIKE '%$search_text%' OR " . MessageRecord :: SUBJECT_COL . " LIKE '%$search_text%')";

				$messages_result = $db->query($query);
			}
		} else {
			$project_ids = '';
			$task_ids = '';
			$issue_permitted_ids = array();
			$records = UserPermissionPeer :: findPermissionByUser($db, $user_id);
			if ($records) {
				for ($i = 0; $i < count($records); $i++) {
					if ($records[$i]->getRecordType() == Constants :: PROJECT) {
						if ($records[$i]->getPermission() == Constants :: ADD_ISSUE) {
							 /*if ($i != 0 && $issue_permitted_ids != '') {
								$issue_permitted_ids .= ", " . $records[$i]->getRecordId();
							} else {
								$issue_permitted_ids .= $records[$i]->getRecordId();
							}*/
							$issue_permitted_ids [] = $records[$i]->getRecordId();
							
						} else {
							if ($i != 0 && $project_ids != '') {
								$project_ids .= ", " . $records[$i]->getRecordId();
							} else {
								$project_ids .= $records[$i]->getRecordId();
							}
						}
					}
					if ($records[$i]->getRecordType() == Constants :: TASK) {
						if ($i != 0 && $task_ids != '') {
							$task_ids .= ", " . $records[$i]->getRecordId();
						} else {
							$task_ids .= $records[$i]->getRecordId();
						}
					}

				}
			}
		
			//team search
			if ($project_ids != '') {
				//project search
				if ($search_project_details) {
					$query = "SELECT * FROM $project_table_name WHERE (" . ProjectRecord :: ID_COL . " in ($project_ids) AND ( " . ProjectRecord :: NAME_COL . " LIKE '%$search_text%' OR " . ProjectRecord :: DESCRIPTION_COL . " LIKE '%$search_text%'))";
					$projects_result = $db->query($query);
				}
				//task search
				if ($search_task_details) {
					if ($task_ids != '') {
						$query = "SELECT * FROM $task_table_name WHERE (" . TaskRecord :: ID_COL . " in ($task_ids) AND ( " . TaskRecord :: NAME_COL . " LIKE '%$search_text%' OR " . TaskRecord :: DESCRIPTION_COL . " LIKE '%$search_text%'))";
						$tasks_result = $db->query($query);
					}
				}

				if ($search_issue_details) {
					if ($task_ids != '') {
						$query = "SELECT * FROM $issue_table_name WHERE (" . Issue :: PROJECT_ID_COL . " in ($project_ids) AND ( " . Issue :: TITLE_COL . " LIKE '%$search_text%' OR " . Issue :: DESCRIPTION_COL . " LIKE '%$search_text%'))";
						$issues_result = $db->query($query);
					}
				}

				if ($search_messages) {
					//message search
					$where_cond_1 = "( " . MessageRecord :: TYPE_COL . " =" . Constants :: PROJECT_MESSAGE . " AND " . MessageRecord :: TYPE_ID_COL . " in ($project_ids) ) ";
					$where_cond_2 = "( " . MessageRecord :: TYPE_COL . " =" . Constants :: TASK_MESSAGE . " AND " . MessageRecord :: TYPE_ID_COL . " in ($task_ids) ) ";
					$where_cond = "(" . $where_cond_1 . " OR $where_cond_2 )";

					$query = "SELECT * FROM $message_table_name WHERE (" . $where_cond . " AND ( " . MessageRecord :: MESSAGE_COL . " LIKE '%$search_text%'  OR  " . MessageRecord :: SUBJECT_COL . " LIKE '%$search_text%') AND (" . MessageRecord :: STATUS_COL . "<>'" . Constants :: DELETED_MESSAGE . "') )";

					$messages_result = $db->query($query);

					//issue message
					
					$issue_condn =  " 1 ";
					
					if(!empty($issue_permitted_ids)) {
						$issue_condn = "  i." . Issue :: PROJECT_ID_COL . " in ('".implode("','",$issue_permitted_ids)."') ";
						
					}
					
					$query = "SELECT m.id, m.type, m.cont, m.subject, m.type_id FROM $message_table_name m JOIN $issue_table_name i ON m." . MessageRecord :: TYPE_ID_COL . " = i." . Issue :: ID_COL . "  WHERE ( $issue_condn AND ( m." . MessageRecord :: MESSAGE_COL . " LIKE '%$search_text%' ) AND ( m." . MessageRecord :: STATUS_COL . "<>'" . Constants :: DELETED_MESSAGE . "') )";
					//var_dump($query);
				//	exit();
					$issue_messages_result = $db->query($query);
				}

			} else {
				return $search_results;
			}
		}

		$search_results['project_search'] = array ();
		$search_results['task_search'] = array ();
		$search_results['issue_search'] = array ();
		$search_results['message_search'] = array ();
		$row = null;

		if ($projects_result) {
			while ($row = mysql_fetch_object($projects_result)) {
				$search_results['project_search'][] = $row;
			}
		}

		if ($tasks_result) {

			while ($row = mysql_fetch_object($tasks_result)) {
				$search_results['task_search'][] = $row;
			}

		}

		if ($issues_result) {

			while ($row = mysql_fetch_object($issues_result)) {
				$search_results['issue_search'][] = $row;
			}
		}

		if ($messages_result) {
			while ($row = mysql_fetch_object($messages_result)) {
				$search_results['message_search'][] = $row;
			}
		}
		if (!$is_admin) {
			if ($issue_messages_result) {
				while ($row = mysql_fetch_object($issue_messages_result)) {
					$search_results['message_search'][] = $row;

				}
			}
		}

		return $search_results;
	}

}
?>