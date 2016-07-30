<?php


/*
 * Created on February 03, 2013
 *
 * bispark software services
 * www.bispark.com
 */

require_once 'Util.php';
require_once 'UserPermissionPeer.php';

class ActionHelper {
	//not used for now
	public static function getThemePallette($action_obj, $selected_theme = '') {
		$config = $action_obj->getConfig();

		$theme_default = $config->getValue('THEMES', 'default_theme');
		$themes = explode(",", $config->getValue('THEMES', 'names'));
		$pallette = array ();
		$names = array ();
		$default = ($selected_theme != '') ? $selected_theme : $theme_default;
		$names[] = $default;
		for ($cnt = 0; $cnt < count($themes); $cnt++) {
			if ($themes[$cnt] != $default) {
				$names[] = $themes[$cnt];
			}
		}
		$cells_per_row = 3;
		$n_names = count($names);
		//array_unshift($names, $default);
		$n_rows = 1 + (int) ($n_names / $cells_per_row);
		for ($row = 0; $row < $n_rows; $row++) {
			$pallette[$row] = array ();
			for ($col = 0; $col < $cells_per_row; $col++) {
				$cnt = $col + ($cells_per_row * $row);
				if ($cnt >= $n_names) {
					break;
				}
				$obj = new StdClass;
				$obj->name = $names[$cnt];
				$obj->alt = ucfirst($names[$cnt]);
				$obj->icon_url = $action_obj->getAbsoluteImageURL('theme_' . $obj->name . '.png');
				$obj->click_url = $action_obj->getAbsoluteURL('/user/setTheme/name/' . $obj->name . '/path/' . $action_obj->getCurrentURL());
				$obj->start_first_row = ($cnt == 0);
				$obj->start_2nd_row = (!$obj->start_first_row && $cnt == $cells_per_row);
				$obj->start_other_row = (!$obj->start_2nd_row && $cnt % $cells_per_row == 0);

				$obj->end_first_row = ($cnt == ($cells_per_row -1));
				$obj->end_any_row = (!$obj->end_first_row && ($col == ($cells_per_row -1)));
				$obj->end_last_row = (!$obj->end_any_row && ($cnt == ($n_names -1)));
				$pallette[$row][] = $obj;
			}

		}

		return $pallette;
	}
	public static function getProjectIcon($action_obj, $project_record) {
		$project_icon_name = 'default.png';
		if ($project_record && $project_record->getIconName() != null) {
			$config = $action_obj->getConfig();
			$project_icons_folder = Util :: getAttachmentFolderName($config, $project_record->getType());

			$project_icon_name = $project_record->getIconName();
			$project_icon = $config->getValue('FW', 'base_url') . '/' . $project_icons_folder . $project_record->getId() . '/' . $project_icon_name;
		} else {

			$project_icon = $action_obj->getAbsoluteImageURL('') . '/' . $project_icon_name;
		}
		return $project_icon;
	}

	public static function getTaskIcon($action_obj, $task_record) {
		$task_icon = '';
		if ($task_record != null) {
			if ($task_record->getStatus() == Constants :: TASK_OPEN) {
				$task_icon = $action_obj->getAbsoluteImageURL('open_status.png');
			} else
				if ($task_record->getStatus() == Constants :: TASK_INPROGRESS) {
					$task_icon = $action_obj->getAbsoluteImageURL('inprogress_status.png');
				} else
					if ($task_record->getStatus() == Constants :: TASK_CLOSED) {
						$task_icon = $action_obj->getAbsoluteImageURL('closed_status.png');
					} else
						if ($task_record->getStatus() == Constants :: TASK_REVIEW_PENDING) {
							$task_icon = $action_obj->getAbsoluteImageURL('pending_status.png');
						}
		}
		return $task_icon;
	}

	public static function getIssueIcon($action_obj, $issue_record) {
		if ($issue_record != null) {
			if ($issue_record->getStatus() == Constants :: ISSUE_OPEN) {
				$issue_icon = $action_obj->getAbsoluteImageURL('open_status.png');
			}
			if ($issue_record->getStatus() == Constants :: ISSUE_CLOSED) {
				$issue_icon = $action_obj->getAbsoluteImageURL('closed_status.png');
			}
			return $issue_icon;
		}
	}

	public static function sendProjectMessage($action_obj, $to_user_ids, $project_record, $message_record) {

		$db = Db :: getInstance($action_obj->getConfig());
		$already_sent = array ();
		if (!empty ($to_user_ids)) {
			foreach ($to_user_ids as $to_user_id) {
				$to_user = UserRecordPeer :: findByPK($db, $to_user_id);
				if (in_array($to_user_id, $already_sent)) {
					continue;
				}
				$already_sent[] = $to_user_id;
				if ($to_user_id != $action_obj->user->getId()) {

					MessageBoardRecordPeer :: addMessageToMessageBoard($db, $message_record->getId(), $to_user->getId());
				}
				self :: fireProjectMessageMail($action_obj, $action_obj->user, $to_user, $message_record, $project_record, '');
			}
		}
		$admin = UserRecordPeer :: getAdminUser($db);
		MessageBoardRecordPeer :: addMessageToMessageBoard($db, $message_record->getId(), $admin->getId());

		if (!$action_obj->isAdmin && ConfigRecordPeer :: getCopyMailsToAdmin($action_obj)) {

			$admin_email = $admin->getEmail();
			self :: fireProjectMessageMail($action_obj, $action_obj->user, $admin, $message_record, $project_record, '');
		}
	}

	public static function isAuthorizedProject($action_obj, $project_record) {
		$db = Db :: getInstance($action_obj->getConfig());
		$action_obj->lead_project = false;
		$action_obj->can_access_tasks = false;
		$action_obj->can_add_task = false;
		$action_obj->can_delete_project_message = false;

		$can_lead_project = UserPermissionPeer :: canLeadProject($db, $action_obj->user->getId(), $project_record->getId());

		if ($can_lead_project) {
			$action_obj->lead_project = true;
			$action_obj->can_access_tasks = true;
			$action_obj->can_add_task = true;
			$action_obj->can_delete_project_message = true;
			return true;
		}
		if ($action_obj->isAdmin) {
			$action_obj->can_delete_project_message = true;
		}

		$can_exec_project = UserPermissionPeer :: canExecProject($db, $action_obj->user->getId(), $project_record->getId());
		if ($action_obj->isAdmin || $can_exec_project) {
			$action_obj->can_access_tasks = true;

			return true;
		}
		$can_add_issue = UserPermissionPeer :: canAddIssue($db, $action_obj->user->getId(), $project_record->getId());
		if ($can_add_issue) {
			return true;
		}
		return false;
	}

	public static function isAuthorizedTask($action_obj, $task_record) {
		$db = Db :: getInstance($action_obj->getConfig());
		$action_obj->lead_task = false;
		$action_obj->can_post_comments = false;
		$action_obj->can_assign_task = false;
		$action_obj->can_copy_task = false;

		if ($action_obj->isAdmin) {
			$action_obj->can_assign_task = true;
			$action_obj->can_copy_task = true;
			$action_obj->can_post_comments = true;
			return true;
		}
		$action_obj->lead_task = UserPermissionPeer :: canLeadTask($db, $action_obj->user->getId(), $task_record->getId());

		if ($action_obj->lead_task) {
			$action_obj->can_assign_task = true;
			$action_obj->can_copy_task = true;
			$action_obj->can_post_comments = true;
			return true;
		}

		// check whether has team permission

		$action_obj->can_exec_task = UserPermissionPeer :: canExecTask($db, $action_obj->user->getId(), $task_record->getId());
		if ($action_obj->can_exec_task) {
			$action_obj->can_post_comments = true;
			return true;
		}

		$action_obj->can_view_task = UserPermissionPeer :: canViewTask($db, $action_obj->user->getId(), $task_record->getId());

		return $action_obj->can_view_task;

	}
	public static function isAuthorizedIssue($action_obj, $project_record) {
		$db = Db :: getInstance($action_obj->getConfig());
		$action_obj->leadProject = false;
		if ($action_obj->isAdmin) {
			return true;
		}
		$can_lead_project = UserPermissionPeer :: canLeadProject($db, $action_obj->user->getId(), $project_record->getId());

		if ($can_lead_project) {
			$action_obj->leadProject = true;
			return true;
		}
		return UserPermissionPeer :: canAddIssue($db, $action_obj->user->getId(), $project_record->getId());

	}
	public static function updateProjectLinkDetails($action_obj, $message_record, $short_name_len = 90) {
		$project_record = null;
		$config = $action_obj->getConfig();
		$db = Db :: getInstance($action_obj->getConfig());
		switch ($message_record->getType()) {
			case Constants :: PROJECT_MESSAGE :
				$project_record = ProjectRecordPeer :: findByPK($db, $message_record->getTypeId());
				break;

			case Constants :: TASK_MESSAGE :
				$task_record = TaskRecordPeer :: findByPK($db, $message_record->getTypeId());
				if (!is_null($task_record)) {
					$project_record = ProjectRecordPeer :: findByPK($db, $task_record->getParentProjectId());
				}
				break;

			case Constants :: ISSUE_MESSAGE :
				$issue_record = IssuePeer :: findByPK($db, $message_record->getTypeId());
				if (!is_null($issue_record)) {
					$project_record = ProjectRecordPeer :: findByPK($db, $issue_record->getProjectId());
				}
				break;

			default :
				//general message
				break;
		}

		$message_record->project_link = null;
		if ($project_record != null) {

			//update link details for the message project
			$view_url = $config->getValue('FW', 'base_url') . '/project/view/id/' . $project_record->getId();
			$name_more_link = '<a href="' . $view_url . '" class="more_link">...</a>';
			$more_link = ' <a href="' . $view_url . '" class="more_link">More...</a>';
			$project_record->short_description = Util :: truncate($project_record->getDescription(), 108, $more_link);
			$project_record->project_short_name = Util :: truncate($project_record->getName(), $short_name_len, $name_more_link);
			$project_record->project_id = $project_record->getId();
			$project_icons_folder = Util :: getAttachmentFolderName($config, $project_record->getType());
			if ($project_record->getIconName() != null) {
				$project_icon_name = $project_record->getIconName();
				$project_record->project_icon = $config->getValue('FW', 'base_url') . '/' . $project_icons_folder . $project_record->getId() . '/' . $project_icon_name;
			} else {
				$project_icon_name = 'default.png';
				$project_record->project_icon = $action_obj->getAbsoluteImageURL('') . '/' . $project_icon_name;
			}
			$message_record->project_link = $project_record;
		}
		return $message_record;
	}
	private static function fireProjectMessageMail($action_obj, $lead, $to_user, $message_record, $project_record, $target_file = '') {
		$config = $action_obj->getConfig();
		$from_email = ConfigRecordPeer :: getFromEmailAddress($action_obj);
		$website_name = ConfigRecordPeer :: getWebSiteName($action_obj);

		if ($from_email != '' && $to_user->getEmail() != '') {
			list ($subject_template, $body_template) = Util :: getMailTemplateContents($config, Constants :: MESSAGE_MAIL_TEMPLATE);
			$project_url = $action_obj->getAbsoluteURLWithoutSession('/user/show/m/project/a/view/id/') . $project_record->getId() . '/u/' . $to_user->getId();
			$subject = Util :: getSubstitutedMessageTemplate($subject_template, $lead, $to_user, $message_record->getSubject(), $message_record->getCont(), $project_url, $website_name);

			$body = Util :: getSubstitutedMessageTemplate($body_template, $lead, $to_user, $message_record->getSubject(), $message_record->getCont(), $project_url, $website_name);
			$st = Util :: sendTextMail($from_email, $to_user->getEmail(), $subject, $body, $target_file);

		}
	}
	public static function sendTaskMessage($action_obj, $task_record, $message_record) {
		$db = Db :: getInstance($action_obj->getConfig());
		$task_permitted_user_ids = UserPermissionPeer :: findContentPermittedUserIds($db, Constants :: TASK, $task_record->getId());

		foreach ($task_permitted_user_ids as $task_user_id) {
			$to_user = UserRecordPeer :: findByPK($db, $task_user_id);
			if ($task_user_id != $action_obj->user->getId()) {

				MessageBoardRecordPeer :: addMessageToMessageBoard($db, $message_record->getId(), $to_user->getId());
			}
			self :: fireTaskMessageMail($action_obj, $action_obj->user, $to_user, $message_record, $task_record, '');

		}

		$admin = UserRecordPeer :: getAdminUser($db);
		MessageBoardRecordPeer :: addMessageToMessageBoard($db, $message_record->getId(), $admin->getId());

		if (!$action_obj->isAdmin && ConfigRecordPeer :: getCopyMailsToAdmin($action_obj)) {

			self :: fireTaskMessageMail($action_obj, $action_obj->user, $admin, $message_record, $task_record, '');
		}
	}
	public static function sendIssueMessage($action_obj, $issue_tracking_user_ids, $message_record) {
		$db = Db :: getInstance($action_obj->getConfig());
		if (!$action_obj->isAdmin) {
			$admin = UserRecordPeer :: getAdminUser($db);
			MessageBoardRecordPeer :: addMessageToMessageBoard($db, $message_record->getId(), $admin->getId());
			$copy_mails = ConfigRecordPeer :: getCopyMailsToAdmin($action_obj);
			if ($copy_mails) {

				self :: fireIssueMessageMail($action_obj, $action_obj->user, $admin, $message_record);
			}

		}

		if (!empty ($issue_tracking_user_ids)) {
			foreach ($issue_tracking_user_ids as $issue_tracking_user_id) {
				$to_user = UserRecordPeer :: findByPK($db, $issue_tracking_user_id);
				if ($issue_tracking_user_id != $action_obj->user->getId()) {

					MessageBoardRecordPeer :: addMessageToMessageBoard($db, $message_record->getId(), $issue_tracking_user_id);

				}
				self :: fireIssueMessageMail($action_obj, $action_obj->user, $to_user, $message_record);
			}
		}

	}
	public static function fireTaskMessageMail($action_obj, $from_user, $to_user, $message_record, $task_record, $target_file = '') {
		$config = $action_obj->getConfig();
		$db = Db :: getInstance($config);
		$from_email = ConfigRecordPeer :: getFromEmailAddress($action_obj);
		$website_name = ConfigRecordPeer :: getWebSiteName($action_obj);

		if ($from_email != '') {
			list ($subject_template, $body_template) = Util :: getMailTemplateContents($config, Constants :: MESSAGE_MAIL_TEMPLATE);
			$task_url = $action_obj->getAbsoluteURLWithoutSession('/user/show/m/task/a/view/id/') . $task_record->getId() . '/u/' . $to_user->getId();
			$subject = Util :: getSubstitutedMessageTemplate($subject_template, $action_obj->user, $to_user, $message_record->getSubject(), $message_record->getCont(), $task_url, $website_name);
			$body = Util :: getSubstitutedMessageTemplate($body_template, $action_obj->user, $to_user, $message_record->getSubject(), $message_record->getCont(), $task_url, $website_name);

			$st = Util :: sendTextMail($from_email, $to_user->getEmail(), $subject, $body, $target_file);
			if ($st) {
				AppLogPeer :: logInfo($db, " Task message sent to " . $to_user->getEmail());
			} else {
				AppLogPeer :: logError($db, "Error, task message could not be sent to " . $to_user->getEmail());
			}
		}
	}
	public static function updateIssueDisplayDetails($action_obj, $issue_records, $max_label_len = 64) {
		$config = $action_obj->getConfig();
		$db = Db :: getInstance($config);
		if ($issue_records != null) {
			foreach ($issue_records as $issue_record) {
				$issue_task = IssuePeer :: isIssuePromotedAsTask($db, $issue_record);
				$issue_record->isPromoted = ($issue_task != null) ? true : false;
				// 05-11-2013
				$view_url = $action_obj->getAbsoluteURL('/issue/view/id/' . $issue_record->getId());
				$more_link = '<a href="' . $view_url . '" class="more_link">...</a>';
				$issue_record->short_name = Util :: truncate($issue_record->getTitle(), $max_label_len, $more_link);
			}
		}
		return $issue_records;
	}

	public static function updateTaskDisplayDetails($action_obj, $task_records, $max_label_len = 64, $max_desc_len = 220) {
		$config = $action_obj->getConfig();
		$db = Db :: getInstance($config);
		if ($task_records != null) {
			foreach ($task_records as $task_record) {
				$view_url = $action_obj->getAbsoluteURL('/task/view/id/' . $task_record->getId());
				$more_link = '<a href="' . $view_url . '" class="more_link">...</a>';
				$task_record->short_name = Util :: truncate($task_record->getName(), $max_label_len, $more_link);
				$task_record->short_desc = Util :: truncate($task_record->getDescription(), $max_desc_len, $more_link);
				//$task_record->setIsViewOnly(UserPermissionPeer::canViewTask($db, $action_obj->user->getId(), $task_record->getId())) ;

				$task_record->setIsViewOnly(UserPermissionPeer :: getIsViewOnlyTask($db, $task_record));
				$task_record->view_only_for_self = false;
				if (!$action_obj->isAdmin && !UserPermissionPeer :: canLeadTask($db, $action_obj->user->getId(), $task_record->getId())) {
					$task_record->view_only_for_self = UserPermissionPeer :: canExecTask($db, $action_obj->user->getId(), $task_record->getId()) ? false : true;

				}
				$issue_task = IssuePeer :: isIssuePromotedAsTask($db, $task_record);
				$task_record->isPromoted = ($issue_task != null) ? true : false;
			}
		}
		return $task_records;
	}
	// 05-06-2013 private changed to public
	public static function fireIssueMessageMail($action_obj, $from_user, $to_user, $message_record) {
		$config = $action_obj->getConfig();
		$from_email = ConfigRecordPeer :: getFromEmailAddress($action_obj);
		$website_name = ConfigRecordPeer :: getWebSiteName($action_obj);

		if ($from_email != '' && $to_user->getEmail() != '') {

			$st = Util :: sendTextMail($from_email, $to_user->getEmail(), $message_record->getSubject(), $message_record->getCont(), '');

		}
	}
	public static function getValidAttachmentTypes($action_obj) {
		/*$types = $config->getValue('ETC', 'attachment_types');*/
		$types_str = ConfigRecordPeer :: getAttachmentTypes($action_obj);
		if ($types_str) {
			return explode(",", $types_str);
		}
		return array (
			'png',
			'zip'
		);
	}

	//$attachment_names - array or scalar
	public static function getIsValidAttachment($action_obj) {
		for ($cnt = 0; $cnt < 10; $cnt++) {
			$upload_file_key = 'uploadedfile' . "_$cnt";

			if (!isset ($_FILES[$upload_file_key])) {
				continue;
			}
			$uploaded_file_name = isset ($_FILES[$upload_file_key]['name']) ? basename($_FILES[$upload_file_key]['name']) : '';

			if ($uploaded_file_name == '') {
				continue;
			}
			$type = substr(strrchr($uploaded_file_name, "."), 1);

			if (!in_array(strtolower($type), self :: getValidAttachmentTypes($action_obj))) {
				return false;
			}
		}

		return true;
	}
	
	//todo - use this functon, refer project/index method for usage
    public static function getProjectIconURL($action_obj, $project_record) {
    	$config = $action_obj->getConfig();
    	
    	$project_icons_folder = Util :: getAttachmentFolderName($config, $project_record->getType());
    	$icon_url = "";
				if ($project_record->getIconName() != null) {
					$project_icon_name = $project_record->getIconName();
					$icon_url = $config->getValue('FW', 'base_url') . '/' . $project_icons_folder . $project_record->getId() . '/' . $project_icon_name;
				} else {
					$project_icon_name = 'default.png';
					$icon_url = $action_obj->getAbsoluteImageURL('') . '/' . $project_icon_name;
				}
				return $icon_url;
    }

	public static function jsonResponse($data, $status_code = '200') {
		header("Access-Control-Allow-Orgin: *");
		header("Access-Control-Allow-Methods: *");
		header("Content-Type: application/json");
		header("HTTP/1.1 " . $status_code . " " . self :: getStatus($status_code));
		echo json_encode($data);
		exit ();
	}
	private static function getStatus($status_code) {
		$status = array (
			200 => 'OK',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			500 => 'Internal Server Error',
			
		);
		return ($status[$status_code]) ? $status[$status_code] : $status[500];
	}
	
	function generateSignature($config, $uri, $secret, array $args) {
		
		$method = $_SERVER['REQUEST_METHOD'];
		$base_url = $config->getValue('FW', 'base_url');
		$host= str_replace(array("http://", "https://"), '',$base_url);
		$query = array();
		
		if (!empty ($args)) {
			ksort($args);
			foreach ($args as $k => $v) {
					$k = strtolower($k);
					$k = str_replace('%7E', '~', rawurlencode($k));
					$v = str_replace('%7E', '~', rawurlencode($v));
					$query[] = $k . '=' . $v;
			}
		
		}
		$query_str = implode('&', $query);
		
		
		$data = $method . "\n" . $host . "\n" . $uri . "\n" . $query_str;
		
		//do not do this! return  str_replace('%7E', '~', rawurlencode(base64_encode(hash_hmac('sha256', $data, $secret, true)));
		return  base64_encode(hash_hmac('sha256', $data, $secret, true));
	
	}
	function  getSignableParams($args, $key_arr) {
		if (!in_array('public_key', $key_arr)) {
			$key_arr[] = 'public_key';
		}

		$key_vals = array ();
		foreach ($key_arr as $key) {
			if (isset ($args[$key])) {
				$key_vals[$key] = $args[$key];
			}

		}
		return $key_vals;
	}

}
?>