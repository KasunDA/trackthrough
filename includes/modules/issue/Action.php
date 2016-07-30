<?
require_once 'BaseController.php';
require_once 'FlexyView.php';
require_once 'Db.php';
require_once 'Constants.php';
require_once 'ProjectRecord.php';
require_once 'ProjectRecordPeer.php';
require_once 'TaskRecord.php';
require_once 'TaskRecordPeer.php';
require_once 'MessageRecordPeer.php';
require_once 'MessageBoardRecordPeer.php';
require_once 'UserRecord.php';
require_once 'UserRecordPeer.php';
require_once 'ConfigRecord.php';
require_once 'ConfigRecordPeer.php';
require_once 'ActionHelper.php';
require_once 'AppLogPeer.php';
require_once 'BookmarkRecord.php';
require_once 'BookmarkRecordPeer.php';
require_once 'UserPermissionPeer.php';
require_once 'Issue.php';
require_once 'IssuePeer.php';
require_once 'IssueTask.php';
require_once 'IssuePdf.php';

class Action extends FW_BaseController {
	private function common($args) {
		$this->isAdmin = false;
		$this->isLanding = false;
		$this->version = Util :: getVersion();
		$this->message_f = $this->getFlashMessageObj();

		if ($this->getIsAuthorised()) {
			$user_id = $this->getParameter('USER_ID');
			$this->theme_color = $this->getParameter('THEME_COLOR');
			$this->theme_pallette = ActionHelper :: getThemePallette($this, $this->theme_color);
			$config = $this->getConfig();
			$this->user = UserRecordPeer :: findByConfigAndPK($config, $user_id);

			$this->isAdmin = $this->getParameter('is_admin');
		}
		$this->record_count = MessageBoardRecordPeer :: getUnreadCountsForUser($config, $user_id);
		$this->unreadMessages = ($this->record_count > 0) ? true : false;
	}

	function edit($args) {
		$this->common($args);
		$this->projects = true;
		$config = $this->getConfig();

		$this->title = "Edit Issue";
		$this->edit_action = true;
		if (!isset ($args['issue_id'])) {
			$this->appendErrorMessage('Error, issue id is undefined!');
		}
		$this->from_page = 'index_project';
		if (isset ($args['from_page'])) {
			$this->from_page = $args['from_page'];
		}

		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($this->getConfig());
				$this->issue = IssuePeer :: findByPK($db, $args['issue_id']);
				if ($this->issue == null) {
					throw new Exception(' could not find dataset!');
				}

				if ($this->issue->getIsClosed()) {
					throw new Exception(' can not edit when the issue is closed.');
				}

				$this->project_record = ProjectRecordPeer :: findByPK($db, $this->issue->getProjectId());
				if ($this->project_record == null) {
					throw new Exception(' could not find dataset!');
				}
				$can_lead_project = UserPermissionPeer :: canLeadProject($db, $this->user->getId(), $this->project_record->getId());

				$this->allow_edit_issue = $can_lead_project || $this->isAdmin;
				if (!$this->allow_edit_issue) {
					throw new Exception('  you are not authorized to edit this issue.');
				}

				$this->project_id = $this->project_record->getId();

				$project_icons_folder = Util :: getAttachmentFolderName($config, $this->project_record->getType());
				if ($this->project_record->getIconName() != null) {
					$project_icon_name = $this->project_record->getIconName();
					$this->project_icon = $config->getValue('FW', 'base_url') . '/' . $project_icons_folder . $this->project_record->getId() . '/' . $project_icon_name;
				} else {
					$project_icon_name = 'default.png';
					$this->project_icon = $this->getAbsoluteImageURL('') . '/' . $project_icon_name;
				}
				$this->max_upload_size = ini_get('upload_max_filesize');
				$this->allowed_attachments = ConfigRecordPeer :: getAttachmentTypes($this);

			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		$new_args = array ();
		if ($this->has_error) {

			$this->setFlashMessage($this->error_message, true);
			$this->callModuleMethod('project', 'index', $new_args);
			return;
		}

		return new FlexyView('issue/editIssue.html', $this);
	}

	function create($args) {
		$this->common($args);
		$this->projects = true;
		$config = $this->getConfig();
		//$meta_data = Util :: getMetaData('issue create', $config);
		$this->title = "Add Issue"; /* Abhilash 26-10-13 */
		if (!isset ($args['id'])) {
			$this->appendErrorMessage('Error, project id is undefined!');
		}
		$this->from_page = 'index_project';
		if (isset ($args['from_page'])) {
			$this->from_page = $args['from_page'];
		}
		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($this->getConfig());
				$this->project_id = $args['id'];
				if (!$this->verifyCanAddIssue($db, $this->project_id)) {
					throw new Exception('You are not Authorized to add an issue!');
				}
				$this->project_record = ProjectRecordPeer :: findByPK($db, $this->project_id);
				if ($this->project_record == null) {
					throw new Exception('could not find dataset!');
				}
				$this->issue = new Issue($args);
				if ((isset ($args['page_index'])) && ($args['page_index'] != '')) {
					$projects_per_page = PreferenceRecordPeer :: getProjectsPerPage($this, $this->user->getId());

					$this->from = ($args['page_index'] - 1) * $projects_per_page;
					$this->page_index = $args['page_index'];
				}
				$project_icons_folder = Util :: getAttachmentFolderName($config, $this->project_record->getType());
				if ($this->project_record->getIconName() != null) {
					$project_icon_name = $this->project_record->getIconName();
					$this->project_icon = $config->getValue('FW', 'base_url') . '/' . $project_icons_folder . $this->project_record->getId() . '/' . $project_icon_name;
				} else {
					$project_icon_name = 'default.png';
					$this->project_icon = $this->getAbsoluteImageURL('') . '/' . $project_icon_name;
				}
				$this->max_upload_size = ini_get('upload_max_filesize');
				$this->allowed_attachments = ConfigRecordPeer :: getAttachmentTypes($this); /* Abhilash 28-10-13 */
			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		$new_args = array ();
		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
			$this->callModuleMethod('project', 'index', $new_args);
			return;
		}
		return new FlexyView('issue/editIssue.html', $this);
	}

	function update($args) {
		$this->common($args);
		$this->projects = true;
		$config = $this->getConfig();

		if (isset ($args['title'])) {
			$args['title'] = strip_tags($args['title']);
		}
		if (isset ($args['issue_description'])) {
			$args['issue_description'] = strip_tags($args['issue_description']);
		}
		if (!isset ($args['title']) || $args['title'] == '') {
			$this->appendErrorMessage('Issue title can not be blank!<br />');
		}
		if (!isset ($args['issue_description']) || $args['issue_description'] == '') {
			$this->appendErrorMessage('Issue description can not be blank!');
		}

		if (!isset ($args['project_id']) || $args['project_id'] == '') {
			$this->appendErrorMessage('Invalid project identifier!');
		}
		$issue_description = isset ($args["issue_description"]) ? $args["issue_description"] : '';
		$issue_title = isset ($args["title"]) ? $args["title"] : '';
		$project_id = isset ($args["project_id"]) ? $args["project_id"] : '';
		$priority = isset ($args["priority"]) ? $args["priority"] : Constants :: NORMAL_PRIORITY;
		$edit_action = isset ($args["issue_id"]) && $args["issue_id"] ? true : false;

		$this->issue = null;

		try {

			if (!ActionHelper :: getIsValidAttachment($this)) {
				$this->appendErrorMessage('Unsupported  attachment; allowed formats ' . implode("|", ActionHelper :: getValidAttachmentTypes($this)));
			}
			//}
			if (!$this->has_error) {
				$db = Db :: getInstance($config);
				$db->begin();
				$this->issue = new Issue($db);
				if ($edit_action) {
					$this->issue = IssuePeer :: findByPK($db, $args["issue_id"]);
					if ($this->issue == null) {
						throw new Exception(' could not find issue dataset!');
					}
				}

				$project_record = ProjectRecordPeer :: findByPK($db, $project_id);
				if ($project_record == null) {
					throw new Exception(' could not find project dataset!');
				}
				if ($edit_action) {

					$can_lead_project = UserPermissionPeer :: canLeadProject($db, $this->user->getId(), $project_record->getId());

					$this->allow_edit_issue = $can_lead_project || $this->isAdmin;
					if (!$this->allow_edit_issue) {
						throw new Exception('  you are not authorized to edit this issue.');
					}
				}

				$this->issue->setDescription($issue_description);
				$this->issue->setTitle($issue_title);
				$this->issue->setPriority($priority);
				if (!$edit_action) { //only while creating 
					$this->issue->setProjectId($project_id);
					$this->issue->setUserId($this->user->getId());
					$status = Constants :: ISSUE_OPEN;
					$this->issue->setStatus($status);
					$this->issue->setType(Constants :: ISSUE);

				}

				$this->issue->store();
				$project_record->setUpdatedAt(date('Y-m-d H:i:s'));
				$project_record->store();
				$attachment_folder = Util :: getAttachmentFolderName($config, Constants :: ISSUE_MESSAGE);

				$attach_log_msg = Util :: createAttachmentHelper($this->issue, $attachment_folder, $project_record->getId());
				$str = $edit_action ? "updated" : "created";
				AppLogPeer :: logInfo($db, "Project Issue [" . $project_record->getId() . "] $str; $attach_log_msg");
				UserPermissionPeer :: createPermissionRecord($db, $this->user->getId(), Constants :: ADD_ISSUE, $this->issue->getType(), $this->issue->getId());

				$message_record = new MessageRecord($db);
				$message_record->setType(Constants :: ISSUE_MESSAGE);
				$message_record->setSubject($edit_action ? 'Issue updated' : 'New issue created');
				$message_record->setFromId($this->user->getId());
				$message_record->setTypeId($this->issue->getId());
				$message_record->setCont(($edit_action ? 'Updated issue' : 'Added new issue') . ' [' . $this->issue->getTitle() . ']');
				$message_record->store();

				if (!$edit_action) { //only while creating 
					//change my preferences
					PreferenceRecordPeer :: makeProjectAndIssuesVisible($this, $this->user->getId(), $project_record->getId());
				}

				$issue_tracking_user_ids = UserPermissionPeer :: getIssueTrackingUserIds($db, $project_record->getId());

				$db->commit();
				ActionHelper :: sendIssueMessage($this, $issue_tracking_user_ids, $message_record);
			}

		} catch (Exception $exception) {
			$db->rollback();
			$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
		}
		$new_args = array ();

		if ($this->has_error) {
			if ($this->issue) {
				$this->issue->setName('');

			}
			$new_args['id'] = $args['project_id'];
			$this->setFlashMessage($this->error_message, true);
			if ($edit_action) {

				$this->callModuleMethod('issue', 'edit', array (
					'issue_id' => $args['issue_id']
				));
			}
			if (!$edit_action) {
				$this->callModuleMethod('issue', 'create', $new_args);
			}

		}

		$this->setFlashMessage('Issue posted');

		if (isset ($args['from_page']) && $args['from_page'] == 'view_project') {
			$this->callModuleMethod('project', 'view', array (
				'id' => $args['project_id']
			));
		} else {
			$this->callModuleMethod('project', 'index', $new_args);
		}

	}

	function view($args) {
		$this->common($args);
		$this->projects = true;
		$config = $this->getConfig();
		$this->issue_attachment_names = array ();
		$this->display_link = true;
		//$meta_data = Util :: getMetaData('issue view', $config);
		$this->title = "Issue View"; /* Abhilash 26-10-13 */

		if (!isset ($args['id']) || $args['id'] == '') {
			$this->appendErrorMessage('Error, could not find dataset!');
		}
		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($config);
				$this->issue_record = IssuePeer :: findByPK($db, $args['id']);
				$this->user_project_permission = null;
				if ($this->issue_record == null) {
					throw new Exception(' could not find dataset!');
				}

				$user_id = $this->issue_record->getUserId();
				$user_record = UserRecordPeer :: findByPK($db, $user_id);
				if ($user_record) {
					$this->issue_record->setUserSigninId($user_record->getSigninId());
				}
				
				$this->project_record = ProjectRecordPeer :: findByPK($db, $this->issue_record->getProjectId());
				if ($this->project_record == null) {
					throw new Exception(' could not find dataset!');
				}
				if (!ActionHelper :: isAuthorizedIssue($this, $this->project_record)) {
					$this->makeAllIssueMessagesRead($db, $user_id, $this->issue_record->getId());
					throw new Exception('you are not authorized to view this issue!');
				}

				if ($this->issue_record == null) {
					throw new Exception(' could not find dataset, invalid issue id!');
				}

				$project_name = wordwrap($this->project_record->getName(), 100, "\n", 1);
				$this->project_record->setName($project_name);

				$project_icons_folder = Util :: getAttachmentFolderName($config, $this->project_record->getType());
				if ($this->project_record->getIconName() != null) {
					$project_icon_name = $this->project_record->getIconName();
					$this->project_icon = $config->getValue('FW', 'base_url') . '/' . $project_icons_folder . $this->project_record->getId() . '/' . $project_icon_name;
				} else {
					$project_icon_name = 'default.png';
					$this->project_icon = $this->getAbsoluteImageURL('') . '/' . $project_icon_name;
				}

				$issue_title = wordwrap($this->issue_record->getTitle(), 100, "\n", 1);
				$this->issue_record->setTitle($issue_title);

				$description = nl2br($this->issue_record->getDescription());
				$this->issue_record->setDescription(wordwrap($description, 105, "\n", 1));

				$this->isBookmarked = BookmarkRecordPeer :: getIsBookmarked($db, $this->user->getId(), $this->issue_record->getType(), $this->issue_record->getId());
				//$this->isBookmarked = ($bookmark != null) ? true : false;

				$attchment_names = explode(':', $this->issue_record->getAttachmentName());
				$issue_attachment_names = Util :: getExistingAttachments($config, $attchment_names, $this->issue_record->getProjectId(), Constants :: ISSUE_MESSAGE);
				$this->file_missing = false;
				if (count($attchment_names) > 0) {

					foreach ($issue_attachment_names as $attachment_name) {
						if ($attachment_name != null) {
							$attachment_icon = Util :: getAttachmentIcon($config, $attachment_name);
							$obj = new StdClass;
							$obj->attachment_name = $attachment_name;
							if ($attachment_icon == 'image.png') {
								$obj->is_image = true;
								$attachment_name_with_prefix = Util :: getAttachmentNamePrefix($this->issue_record) . $attachment_name;
							
								$obj->image_path = Util :: getAttachmentURL($config, $this->issue_record->getProjectId(), $attachment_name_with_prefix);
								
							}

							$obj->attachment_icon = 'attachment_icons' . '/' . $attachment_icon;
							$this->issue_attachments[] = $obj;
						} else {
							$this->file_missing = true;
						}
					}
				}
				//authorize user to copy issue if he/she is the project owner 04-06-2013
				$can_lead_project = UserPermissionPeer :: canLeadProject($db, $this->user->getId(), $this->project_record->getId());
				$this->allowCopyIssue = $can_lead_project || $this->isAdmin;

				$this->allow_edit_issue = $can_lead_project || $this->isAdmin;

				$this->issue_task = IssuePeer :: isIssuePromotedAsTask($db, $this->issue_record);

				$this->isPromoted = ($this->issue_task != null) ? true : false;
				$this->is_compose_desc_visible = PreferenceRecordPeer :: getIsComposeIssueDescVisible($this, $this->user->getId(), $this->issue_record->getId()); /* Abhilash 3.1.15 */

				if ($this->isPromoted) {
					$this->task_record = TaskRecordPeer :: findByPK($db, $this->issue_task->getTaskId());
					if (!ActionHelper :: isAuthorizedTask($this, $this->task_record)) {
						$this->display_link = false;
					}
				}
				/* Abhilash */
				$this->is_closed_issue = ($this->issue_record->getStatus() == Constants :: ISSUE_CLOSED) ? true : false;

				$this->message_records = array ();

				$message_records_db = $this->isAdmin ? MessageRecordPeer :: getMessageRecords($db, null, '', Constants :: ISSUE_MESSAGE, $this->issue_record->getId()) : MessageRecordPeer :: getMessageRecords($db, null, Constants :: DELETED_MESSAGE, Constants :: ISSUE_MESSAGE, $this->issue_record->getId());
				$message_records_with_uname = MessageRecordPeer :: getMessageRecordsWithUserName($db, $message_records_db, $this->user->getId());

				$this->message_records = array ();
				$read_message_ids = array ();
				
				/* Abhilash 6.5.2015 */
				$last_message_record = empty($message_records_db) ? null: $message_records_db[count($message_records_db)-1];
				if($last_message_record != null) {
					$this->msg_btm_left_corner= $last_message_record->getFromSelf() ? 'msg_btm_left_corner' : 'alt_msg_btm_left_corner';
					$this->msg_btm_rt_corner=  $last_message_record->getFromSelf() ? 'msg_btm_rt_corner' : 'alt_msg_btm_rt_corner';
				}
				
				foreach ($message_records_db as $message_record) {
					$obj = new StdClass();
					$obj->m_record = $message_record;
					$obj->no_comment = true;
					if ($obj->m_record->getCont() != '') {
						$message = nl2br($obj->m_record->getCont());
						$obj->m_record->setCont(wordwrap($message, 98, "\n", 1));
						$obj->no_comment = false;
					}

					$obj->isCommentBookmarked = BookmarkRecordPeer :: getIsBookmarked($db, $this->user->getId(), $message_record->getType(), $message_record->getId());

					$obj->msg_attachment_missing = false;

					$msg_attchment_names = explode(':', $message_record->getAttachmentName());
					$msg_attchment_names = Util :: getExistingAttachments($config, $msg_attchment_names, $this->issue_record->getProjectId(), Constants :: ISSUE_MESSAGE);
					if (count($msg_attchment_names) > 0) {
						$m_attachment_names = array ();
						$msg_attachment_missing = false;

						foreach ($msg_attchment_names as $msg_attachment_name) {
							if ($msg_attachment_name != null) {
								$attachment_icon = Util :: getAttachmentIcon($config, $msg_attachment_name);
								$attachment_folder = Util :: getAttachmentFolderName($config, $message_record->getType());
								$attachment_obj = new StdClass;
								$attachment_obj->attachment_name = $msg_attachment_name;
								if ($attachment_icon == 'image.png') {
									$attachment_obj->is_image = true;
									$attachment_name_with_prefix = Util :: getAttachmentNamePrefix($message_record) . $msg_attachment_name;
									
									$attachment_obj->image_path = Util :: getAttachmentURL($config, $this->issue_record->getProjectId(), $attachment_name_with_prefix);
									

								} else {
									$obj->is_image = false;
								}
								$attachment_obj->attachment_icon = 'attachment_icons' . '/' . $attachment_icon;
								$m_attachment_names[] = $attachment_obj;
							} else {
								$obj->msg_attachment_missing = true;
							}
						}
					}
					$obj->m_attachments = $m_attachment_names;

					$this->message_records[] = $obj;
					$read_message_ids[] = $message_record->getId();
					//MessageBoardRecordPeer :: deleteUserMessageBoardRecord($config, $message_record,$this->user->getId());
				}
				if (!empty ($read_message_ids)) {
					MessageBoardRecordPeer :: setMessagesReadForUser($db, $read_message_ids, $this->user->getId());

				}
				$this->record_count = MessageBoardRecordPeer :: getUnreadCountsForUser($config, $this->user->getId());
				$this->unreadMessages = ($this->record_count > 0) ? true : false;
				$this->max_upload_size = ini_get('upload_max_filesize');

				$this->unreadMessages = ($this->record_count > 0) ? true : false;
				$this->max_upload_size = ini_get('upload_max_filesize');
				$this->allowed_attachments = ConfigRecordPeer :: getAttachmentTypes($this); /* Abhilash 28-10-13 */
			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
			$this->no_records = empty ($this->message_records) ? true : false;

		}
		$new_args = array ();

		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
			$this->callModuleMethod('project', 'index', $new_args);
		}
		return new FlexyView('issue/viewIssue.html', $this);
	}
	/* Abhilash 3.1.15 */
	function setIssueDescVisibility($args) {

		$this->common($args);
		if (!isset ($args['issue_id']) || $args['issue_id'] == '') {
			$this->appendErrorMessage('Error, could not find dataset! ');
		}

		if (!isset ($args['visibility']) || $args['visibility'] == '') {
			$this->appendErrorMessage('Error, required current visibility status! ');
		}

		if (!$this->has_error) {
			try {
				$issue_id = $args['issue_id'];
				$visibility = $args['visibility'];

				//$db = Db :: getInstance($this->getConfig());

				PreferenceRecordPeer :: setComposeIssueDescVisible($this, $this->user->getId(), $issue_id, ($visibility == 'show'));

			} catch (Exception $exception) {
				$db->rollback();
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		$new_args = array ();
		if (isset ($args['page_index']) && ($args['page_index'] != 1) && ($args['page_index'] != '')) {

			$projects_per_page = PreferenceRecordPeer :: getProjectsPerPage($this, $this->user->getId());

			$new_args['from'] = ($args['page_index'] - 1) * $projects_per_page;
			$new_args['page_index'] = $args['page_index'];
		}

		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
		}
		if (isset ($args['page']) && ($args['page'] == 'view')) {
			$new_args['id'] = $args['issue_id'];
			$this->callModuleMethod('issue', 'view', $new_args);
		}
		$this->callModuleMethod('project', 'index', $new_args);
	}

	function setAddIssuePermission($args) {
		$this->common($args);
		$this->projects = true;
		if (!isset ($args['project_id']) || $args['project_id'] == '') {
			$this->appendErrorMessage('Issue id is not set!');
		}
		if (!$this->has_error) {
			try {
				$config = $this->getConfig();
				$db = Db :: getInstance($config);
				$project_record = ProjectRecordPeer :: findByPK($db, $args['project_id']);
				if ($project_record == null) {
					throw new Exception(' could not find dataset!');
				}
				//	$permission_record = UserPermissionPeer :: findContentPermission($db,$this->user->getId(),constants :: PROJECT,$project_record->getId());
				//	$permission = ($permission_record != null)? $permission_record->getPermission() : null;

				$can_lead_project = UserPermissionPeer :: canLeadProject($db, $this->user->getId(), $project_record->getId());

				if ((!$this->isAdmin) && !$can_lead_project) {
					throw new Exception(' authorization required!');
				}

				$teams_selected = Array ();
				$teams_selected = $args['team_id'];
				$added_team_names = array ();
				for ($i = 0; $i <= count($teams_selected); $i++) {
					if (isset ($teams_selected[$i]) && $teams_selected[$i] != '0') {
						$team_record = UserRecordPeer :: findByPK($db, $teams_selected[$i]);
						if ($team_record != null) {

							/* create a permission record in user permission table */
							//	$team_permission = UserPermissionPeer :: getAddIssuePermission($db, $project_record->getId(), $team_record->getId());
							$can_add_issue = UserPermissionPeer :: canAddIssue($db, $team_record->getId(), $project_record->getId());
							//if ($team_permission == null) {
							if (!$can_add_issue) {
								//UserPermissionPeer :: createPermissionRecord($db, $team_record->getId(), Constants :: ADD_ISSUE, $project_record->getType(), $project_record->getId());
								$added_team_names[] = $team_record->getSigninId();
								UserPermissionPeer :: setAddIssuePermission($db, $team_record->getId(), $project_record->getId());

							}
						}
					}

				}

				if (!empty ($added_team_names)) {
					$message_record = new MessageRecord($db);
					$message_record->setType(Constants :: PROJECT_MESSAGE);
					$message_record->setCont('Added to issue tracking team - ' . implode(", ", $added_team_names)); /* abhilash 13-12-13 */

					$message_record->setSubject('Issue team updated');
					$message_record->setFromId($this->user->getId());
					$message_record->setTypeId($project_record->getId());
					$message_record->store();
					//issue tracking user ids cover leads as well
					$issue_tracking_user_ids = UserPermissionPeer :: getIssueTrackingUserIds($db, $project_record->getId());

					ActionHelper :: sendProjectMessage($this, $issue_tracking_user_ids, $project_record, $message_record);

				}

			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		$new_args = array ();
		$new_args['id'] = (isset ($args['project_id'])) ? $args['project_id'] : '';

		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
		}
		$this->callModuleMethod('project', 'view', $new_args);
	}

	function removeTeam($args) {
		$this->common($args);
		$this->projects = true;
		if (!isset ($args['user_id']) || $args['user_id'] == '') {
			$this->appendErrorMessage('User id is not set!');
		}

		if (!isset ($args['project_id']) || $args['project_id'] == '') {
			$this->appendErrorMessage('Project id is not set!');
		}
		if (!$this->has_error) {
			try {
				$config = $this->getConfig();
				$db = Db :: getInstance($config);
				$team_id = $args['user_id'];
				$project_id = $args['project_id'];

				$can_lead_project = UserPermissionPeer :: canLeadProject($db, $this->user->getId(), $project_id);

				//issue tracking user ids cover leads as well
				$issue_tracking_user_ids = UserPermissionPeer :: getIssueTrackingUserIds($db, $project_id);

				if ($this->isAdmin || $can_lead_project) {
					//UserPermissionPeer :: unsetProjectIssuePermission($db, $project_id, $user_id);
					UserPermissionPeer :: unsetAddIssuePermission($db, $team_id, $project_id);
				}

				$team_record = UserRecordPeer :: findByPK($db, $team_id);

				$message_record = new MessageRecord($db);
				$message_record->setType(Constants :: PROJECT_MESSAGE);
				$message_record->setCont('Issue tracking user removed ' . $team_record->getSigninId());
				$message_record->setSubject('Issue team updated');
				$message_record->setFromId($this->user->getId());
				$message_record->setTypeId($project_id);
				$message_record->store();

				$project_record = ProjectRecordPeer :: findByPK($db, $project_id);
				ActionHelper :: sendProjectMessage($this, $issue_tracking_user_ids, $project_record, $message_record);

			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		$new_args = array ();
		$new_args['id'] = (isset ($args['project_id'])) ? $args['project_id'] : '';

		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
		}
		$this->callModuleMethod('project', 'view', $new_args);

	}

	function delete($args) {
		$this->common($args);
		$this->projects = true;
		if (!isset ($args['issue_id'])) {
			$this->appendErrorMessage('Issue id is not set!');
		}
		$config = $this->getConfig();
		$db = Db :: getInstance($config);
		if (!$this->has_error) {
			try {
				$issue_record = IssuePeer :: findByPK($db, $args['issue_id']);
				if ($issue_record == null) {
					throw new Exception(' could not find dataset!');
				}
				$project_record = ProjectRecordPeer :: findByPK($db, $issue_record->getProjectId());
				if ($project_record == null) {
					throw new Exception(' could not find dataset!');
				}

				if ((!$this->isAdmin) && ($project_record->getLeadId() != $this->user->getId())) {
					throw new Exception(' authorization required!');
				}

				Util :: deleteIssueAttachments($config, $issue_record);
				$message_records_db = MessageRecordPeer :: getMessageRecords($db, null, '', Constants :: ISSUE_MESSAGE, $issue_record->getId());
				foreach ($message_records_db as $message_record) {
					MessageBoardRecordPeer :: deleteUserMessageBoardRecord($config, $message_record);
				}
				$bookmarks = BookmarkRecordPeer :: getCategoryBookmarks($db, $issue_record->getType(), $issue_record->getId());
				if (!empty ($bookmarks)) {
					$ids_for_delete = array ();
					foreach ($bookmarks as $bookmark) {
						$ids_for_delete[] = $bookmark->getId();
					}
					BookmarkRecordPeer :: deleteBookmarks($db, $ids_for_delete);
				}
				//	UserPermissionPeer :: unsetContentPermission($db, $issue_record->getUserId(), '', $issue_record->getType(), $issue_record->getId());

				$message_record = new MessageRecord($db);
				$message_record->setType(Constants :: PROJECT_MESSAGE);

				$message_record->setSubject('Issue deleted');
				$message_record->setCont("Issue [" . $issue_record->getTitle() . "] deleted.");
				$message_record->setFromId($this->user->getId());
				$message_record->setTypeId($project_record->getId());
				$message_record->store();

				//issue tracking user ids cover leads as well
				$issue_tracking_user_ids = UserPermissionPeer :: getIssueTrackingUserIds($db, $project_record->getId());

				UserPermissionPeer :: unsetIssuePermissions($db, $issue_record->getId());
				IssuePeer :: deleteIssue($db, $issue_record->getId());

				ActionHelper :: sendProjectMessage($this, $issue_tracking_user_ids, $project_record, $message_record);

			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		$new_args = array ();
		if ((isset ($args['page_index'])) && ($args['page_index'] != '')) {

			$projects_per_page = PreferenceRecordPeer :: getProjectsPerPage($this, $this->user->getId());

			$new_args['from'] = ($args['page_index'] - 1) * $projects_per_page;
			$new_args['page_index'] = $args['page_index'];
		}
		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
		} else {
			$this->setFlashMessage('Issue deleted');
		}
		$this->callModuleMethod('project', 'index', $new_args);
	}

	function message($args) {
		$this->common($args);

		$config = $this->getConfig();
		if (isset ($args['comments'])) {
			$args['comments'] = strip_tags($args['comments']);
		}
		if (!isset ($args['issue_id']) || $args['issue_id'] == '') {
			$this->appendErrorMessage(' Error, Issue id not set! ');
		}
		if (!isset ($args['subject']) || $args['subject'] == '') {
			$this->appendErrorMessage(' Error, Subject not set! ');
		}
		if (!ActionHelper :: getIsValidAttachment($this)) {
			$this->appendErrorMessage('Unsupported  attachment; allowed formats ' . implode("|", ActionHelper :: getValidAttachmentTypes($this)));
		}

		$message_record = null;
		$mail_sent = false;
		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($config);
				$db->begin();
				$message_record = new MessageRecord($db);
				$message_record->setType(Constants :: ISSUE_MESSAGE);
				$message_record->setSubject($args['subject']);
				$message_record->setFromId($this->user->getId());

				$issue_record = IssuePeer :: findByPK($db, $args['issue_id']);

				if ($issue_record == null) {
					throw new Exception(' error while retrieving issue record!');
				}
				$project_record = ProjectRecordPeer :: findByPK($db, $issue_record->getProjectId());

				if ($project_record == null) {
					throw new Exception(' error while retrieving project record!');
				}

				$message_record->setTypeId($args['issue_id']);

				$message_record->setCont($args['comments']);

				$message_record->store();

				$attachment_folder = Util :: getAttachmentFolderName($config, $message_record->getType());

				$attach_log_msg = Util :: createAttachmentHelper($message_record, $attachment_folder, $project_record->getId());
				$target_file = '';

				if ($attach_log_msg != '') { //has attachmet
					$target_file = Util :: getAttachmentFilePath($project_record->getId(), $message_record->getAttachmentName(), $attachment_folder);
				}

				//retrieve ISSUE
				$issue_record = IssuePeer :: findByPK($db, $args['issue_id']);
				if ($issue_record == null) {
					throw new Exception(' error while retrieving issue record! ');
				}

				AppLogPeer :: logInfo($db, "Message [" . $message_record->getId() . "] created; $attach_log_msg");

				//issue tracking user ids cover leads as well
				$issue_tracking_user_ids = UserPermissionPeer :: getIssueTrackingUserIds($db, $project_record->getId());

				ActionHelper :: sendIssueMessage($this, $issue_tracking_user_ids, $message_record);

				$project_record->setUpdatedAt(date('Y-m-d H:i:s'));
				$project_record->store();
				if (isset ($args['close_issue']) && $args['close_issue'] != '') {
					$issue_record->setStatus(Constants :: ISSUE_CLOSED);
				}
				$issue_record->setUpdatedAt(date('Y-m-d H:i:s'));
				$issue_record->store();

				if (isset ($args['close_issue']) && $args['close_issue'] != '') {
					$is_close_task = PreferenceRecordPeer :: getCloseTaskWhenIssueClosed($this, $this->user->getId());
					if ($is_close_task) {
						$issue_task = IssuePeer :: isIssuePromotedAsTask($db, $issue_record);
						if (!is_null($issue_task)) {
							$task_record = TaskRecordPeer :: findByPK($db, $issue_task->getTaskId());
							if (!is_null($task_record) && !$task_record->getIsClosed()) {

								$task_record->setStatus(Constants :: TASK_CLOSED);
								$task_record->setProgress(100);
								$task_record->store();

								$close_message_record = new MessageRecord($db);
								$close_message_record->setType(Constants :: TASK_MESSAGE);
								$close_message_record->setSubject('Task Closed');

								$close_message_record->setFromId($this->user->getId());
								$close_message_record->setTypeId($issue_task->getTaskId());
								$close_message_record->setCont("Task is closed as the related issue was closed");
								$close_message_record->store();

								ProjectRecordPeer :: updateProjectProgress($db, $task_record->getParentProjectId());
								ActionHelper :: sendTaskMessage($this, $task_record, $close_message_record);
							}

						}
					}
				}
				$db->commit();

			} catch (Exception $exception) {
				/*$message_id = $message_record->getId();
				if ($message_id != null) {
					MessageRecordPeer :: deleteMessage($db, $message_id);
				}*/
				$db->rollback();
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		$new_args = array ();
		if ($this->has_error) {
			if (isset ($args['issue_id']) && $args['issue_id'] != null) {
				$new_args['id'] = $args['issue_id'];
				$this->setFlashMessage($this->error_message, true);
				$this->callModuleMethod('issue', 'view', $new_args);
			}
		}
		$new_args['id'] = (isset ($args['issue_id']) && $args['issue_id'] != '') ? $args['issue_id'] : '';
		$this->callModuleMethod('issue', 'view', $new_args);
	}

	function download($args) {
		$this->common($args);
		if (!isset ($args['issue_id'])) {
			$this->appendErrorMessage('Error, attachment location undefined!');
		}
		if (!isset ($args['file_name'])) {
			$this->appendErrorMessage('Error, file name undefined!');
		}
		$config = $this->getConfig();
		if (!$this->has_error) {
			try {
				$issue_id = $args['issue_id'];

				$db = Db :: getInstance($this->getConfig());
				$issue_record = IssuePeer :: findByPK($db, $issue_id);
				if ($issue_record == null) {
					throw new Exception(' could not find dataset, invalid issue id!');
				}

				$project_id = $issue_record->getProjectId();
				$project_record = ProjectRecordPeer :: findByPK($db, $project_id);
				if (!ActionHelper :: isAuthorizedIssue($this, $project_record)) {
					throw new Exception(' you are not authorized to download the content!');
				}

				$attachment_name = Util :: getAttachmentNamePrefix($issue_record) . $args['file_name'];
				$attachment_folder = Util :: getAttachmentFolderName($config, Constants :: ISSUE_MESSAGE);
				$target_file = Util :: getAttachmentFilePath($project_record->getId(), $attachment_name, $attachment_folder);
				
				
				if (isset ($args['thumb']) && $args['thumb']) { //additional size prefix in future
					/*$attachment_name_thumb = 'thumb_' . $args['thumb'] . '_' . $attachment_name;
					$target_file_thumb = Util :: getAttachmentFilePath($project_record->getId(), $attachment_name_thumb, $attachment_folder);
					if (!file_exists($target_file_thumb)) {
						$attachment_name_thumb = 'thumb_' . $attachment_name;
						$target_file_thumb = Util :: getAttachmentFilePath($project_record->getId(), $attachment_name_thumb, $attachment_folder);
						if (file_exists($target_file_thumb)) {
							$target_file = $target_file_thumb;
						}
					}*/
					
					$target_file  = Util::getAttachmentThumbFile ($args['thumb'],$attachment_name, $attachment_folder, $target_file, $project_record );
				}
				
				$parts = pathinfo($target_file);
				$content_type = isset ($parts['extension']) ? $parts['extension'] : 'text';
				$base_name = $parts['basename'];
				if (file_exists($target_file)) {
					Util :: downloadFile($target_file);
					return;
				} else {
					$this->appendErrorMessage('There was an error, no such attachment exists!');
				}
			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		if ($this->has_error) {
			echo $this->error_message;
		}
		return;
	}

	function openIssue($args) {
		$this->common($args);
		if (!isset ($args['issue_id']) || $args['issue_id'] == '') {
			$this->appendErrorMessage('Issue id is required!');
		}
		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($this->getConfig());

				$issue_id = $args['issue_id'];
				$issue_record = IssuePeer :: findByPK($db, $issue_id);

				if ($issue_record == null) {
					throw new Exception(' could not find dataset, invalid issue id!');
				}
				//$db->begin();  trans may not be needed
				$issue_record->setStatus(Constants :: ISSUE_OPEN);
				$issue_record->setUpdatedAt(date('Y-m-d H:i:s'));
				$issue_record->store();

				$message_record = new MessageRecord($db);
				$message_record->setType(Constants :: ISSUE_MESSAGE);

				$message_record->setSubject('Issue reopened');
				$message_record->setCont("Issue [" . $issue_record->getTitle() . "] reopened");
				$message_record->setFromId($this->user->getId());
				$message_record->setTypeId($issue_record->getId());
				$message_record->store();

				//$db->commit();
				//issue tracking user ids cover leads as well
				$issue_tracking_user_ids = UserPermissionPeer :: getIssueTrackingUserIds($db, $issue_record->getProjectId());
				ActionHelper :: sendIssueMessage($this, $issue_tracking_user_ids, $message_record);

			} catch (Exception $exception) {
				//$db->rollback();
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		$new_args['id'] = (isset ($args['issue_id'])) ? $args['issue_id'] : '';
		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
		} else {
			$this->setFlashMessage('Issue reopened');
		}
		$this->callModuleMethod('issue', 'view', $new_args);
	}

	function promoteTask($args) {
		$this->common($args);
		if (!isset ($args['issue_id']) || $args['issue_id'] == '') {
			$this->appendErrorMessage('Issue id is required!');
		}
		$promoted_task_id = null;
		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($this->getConfig());
				$issue_id = $args['issue_id'];
				$issue_record = IssuePeer :: findByPK($db, $issue_id);
				if ($issue_record == null) {
					throw new Exeption(' could not find dataset, invalid issue id!');
				}

				if ($issue_record->getIsClosed()) {
					throw new Exception(' Can not promote when the issue is closed.');
				}

				$issue_task = IssuePeer :: isIssuePromotedAsTask($db, $issue_record);
				if (!is_null($issue_task)) {
					throw new Exeption(' This issue has already got an associated task!');
				}

				$db->begin();
				$task_record = new TaskRecord($db);
				$task_record->setParentProjectId($issue_record->getProjectId());
				$task_record->setDescription($issue_record->getDescription());
				$task_record->setLeadId($this->user->getId());
				$task_record->setPriority($issue_record->getPriority());
				$status = Constants :: TASK_OPEN;
				$task_record->setStatus($status);
				$task_record->setType(Constants :: TASK);
				$task_record->setName($issue_record->getTitle());
				$task_record->setProgress(0);
				$project_record = ProjectRecordPeer :: findByPK($db, $issue_record->getProjectId());
				if ($project_record == null) {
					throw new Exeption('Project record not found!');
				}
				if (!$this->has_error) {
					$task_record->store();

					if ($issue_record->getHasAttachment()) {
						$attchment_names = explode(':', $issue_record->getAttachmentName());
						$attachment_folder = Util :: getAttachmentFolderName($this->getConfig(), Constants :: TASK_MESSAGE);
						$attached_file_names = Util :: createIssueAttachmentCloneHelper($this->getConfig(), $task_record, $attchment_names, $attachment_folder, $project_record);
						if ($attached_file_names) {
							$task_record->setAttachmentName($attached_file_names);
							$task_record->store();
						}
					}

					UserPermissionPeer :: setLeadTaskPermission($db, $this->user->getId(), $task_record->getId());

					$issue_task = new IssueTask($db);
					$issue_task->setIssueId($issue_record->getId());
					$issue_task->setTaskId($task_record->getId());
					$issue_task->store();
					
					$promoted_task_id = $task_record->getId();

					$project_record->setUpdatedAt(date('Y-m-d H:i:s'));
					$project_record->store();

					$message_record = new MessageRecord($db);
					$message_record->setType(Constants :: ISSUE_MESSAGE);

					$subject = "Issue Promoted as Task ";
					$message_record->setSubject($subject);
					$message_record->setCont("Issue [" . $issue_record->getTitle() . "] has been prompted as task");
					$message_record->setFromId($this->user->getId());
					$message_record->setTypeId($issue_record->getId());
					$message_record->store();
					$db->commit();

					//issue tracking user ids cover leads as well
					$issue_tracking_user_ids = UserPermissionPeer :: getIssueTrackingUserIds($db, $project_record->getId());
					ActionHelper :: sendIssueMessage($this, $issue_tracking_user_ids, $message_record);
					
					
				}
			} catch (Exception $exception) {
				$db->rollback();
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		$new_args['id'] = $args['issue_id'];
		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
		} else {
			$this->setFlashMessage('Issue promoted as a task');
		}
		if($promoted_task_id != null) {
			$this->callModuleMethod('task', 'view', array('id'=>$promoted_task_id));
		}
		else {
			$this->callModuleMethod('issue', 'view', $new_args);
		}
		
	}

	private function verifyCanAddIssue($db, $project_id) {
		if ($this->isAdmin) {
			return true;
		}
		return UserPermissionPeer :: canAddIssue($db, $this->user->getId(), $project_id);
	}

	// 05-06-2013 START
	function copyIssue($args) {
		$this->common($args);
		$config = $this->getConfig();
		//$meta_data = Util :: getMetaData('issue copy', $config);
		$this->title = "Copy Issue"; /* Abhilash 26-10-13 */

		if (!isset ($args['issue_id'])) {
			$this->appendErrorMessage('Error, issue id is undefined!');
		}
		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($this->getConfig());
				$this->issue_record = IssuePeer :: findByPK($db, $args['issue_id']);
				$this->project_record = ProjectRecordPeer :: findByPK($db, $this->issue_record->getProjectId());
				$project_icons_folder = Util :: getAttachmentFolderName($config, $this->issue_record->getType());
				
				/* Abhilash 29.4.15 */
				$this->issue_task = IssuePeer :: isIssuePromotedAsTask($db, $this->issue_record);
				
				$this->isPromoted = ($this->issue_task != null) ? true : false;
				if ($this->project_record->getIconName() != null) {
					$project_icon_name = $this->project_record->getIconName();
					$this->project_icon = $config->getValue('FW', 'base_url') . '/' . $project_icons_folder . $this->project_record->getId() . '/' . $project_icon_name;
				} else {
					$project_icon_name = 'default.png';
					$this->project_icon = $this->getAbsoluteImageURL('') . '/' . $project_icon_name;
				}
				if ($this->issue_record == null) {
					throw new Exception(' could not find dataset!');
				}

				$can_lead_project = UserPermissionPeer :: canLeadProject($db, $this->user->getId(), $this->project_record->getId());
				$allowCopyIssue = $can_lead_project || $this->isAdmin;

				if (!$allowCopyIssue) {
					throw new Exception('You are not authorised to access the page. Authorization required!');
				}
				$lead_project_records = array ();

				$excluded_project_id = false; //not required to skip self project
				if ($this->isAdmin) {
					$this->lead_project_records = ProjectRecordPeer :: getAllProjectIdAndProjectName($db, $excluded_project_id);
				} else {
					$this->lead_project_records = ProjectRecordPeer :: getLeadProjectIdAndProjectName($db, $this->user->getId(), $excluded_project_id);
				}

			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		$new_args = array ();
		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
			$this->callModuleMethod('project', 'index', $new_args);
		}
		return new FlexyView('issue/copyIssue.html', $this);
	}

	function cloneIssue($args) {
		$this->common($args);
		$config = $this->getConfig();
		if (!isset ($args['issue_id']) || $args['issue_id'] == '') {
			$this->appendErrorMessage('Error, could not find dataset! ');
		}
		if (!isset ($args['new_project_id']) || $args['new_project_id'] == '') {
			$this->appendErrorMessage('Error, could not find destination project! ');
		}
		$new_issue_record = null;
		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($this->getConfig());
				$old_issue_id = $args['issue_id'];
				$old_issue_record = IssuePeer :: findByPK($db, $old_issue_id);
				if ($old_issue_record == null) {
					throw new Exception(' could not find dataset!');
				}
				$project_record = ProjectRecordPeer :: findByPK($db, $old_issue_record->getProjectId());
				$can_lead_project = UserPermissionPeer :: canLeadProject($db, $this->user->getId(), $project_record->getId());
				$allowCopyIssue = $can_lead_project || $this->isAdmin;

				if (!$allowCopyIssue) {
					throw new Exception(' you are not authorized to copy this issue!');
				}
				$old_project_id = $old_issue_record->getProjectId();

				$new_project_id = $args['new_project_id'];
				$new_project_record = ProjectRecordPeer :: findByPK($db, $new_project_id);
				if ($new_project_record == null) {
					throw new Exception(' could not find destination project!');
				}
				$can_copy_to_issue = ($new_project_record->getLeadId() == $this->user->getId()) || $this->isAdmin;

				if (!$can_copy_to_issue) {
					throw new Exception(' you are not authorized to access the destination project!');
				}
				if (!$this->isAdmin) { //admin can clone issue
					$this->verifyCanAddIssue($db, $new_project_id);
				}
				$new_issue_lead_id = $this->user->getId(); //if not admin
				if ($this->isAdmin) {
					$new_project_lead_ids = UserPermissionPeer :: getLeadUserIdsForProject($db, $new_project_id);
					if (empty ($new_project_lead_ids)) {
						throw new Exception(' Error while finding new project credentials!');
					}
					$new_issue_lead_id = $new_project_lead_ids[0];
				}
				$db->begin();
				$new_issue_record = new Issue($db);
				$new_issue_record->setProjectId($new_project_id);
				$new_issue_record->setDescription($old_issue_record->getDescription());
				$new_issue_record->setUserId($old_issue_record->getUserId()); /* Changed 8-6-2013 */
				$new_issue_record->setStatus($old_issue_record->getStatus()); /* Changed 8-6-2013 */
				$new_issue_record->setType(Constants :: ISSUE);
				$new_issue_record->setTitle($old_issue_record->getTitle());
				$new_issue_record->setPriority($old_issue_record->getPriority());

				$new_issue_record->store();
				if ($old_issue_record->getHasAttachment()) {
					$attchment_names = explode(':', $old_issue_record->getAttachmentName());
					$attachment_folder = Util :: getAttachmentFolderName($config, Constants :: ISSUE_MESSAGE);
					$attached_file_names = Util :: createAttachmentCloneHelper($config, $new_issue_record, $attchment_names, $attachment_folder, $old_project_id, $new_project_id);
					if ($attached_file_names) {
						$new_issue_record->setAttachmentName($attached_file_names);
						$new_issue_record->store();
					}
				}
				$this->cloneIssueMessages($db, $old_issue_record, $new_issue_record);

				//send message
				$message_record = new MessageRecord($db);
				$message_record->setType(Constants :: ISSUE_MESSAGE);

				$subject = 'New issue [copied/cloned]';
				$message_record->setSubject($subject);
				$message_record->setCont("Issue has been cloned/copied as [" . $new_issue_record->getTitle() . "]");
				$message_record->setFromId($this->user->getId());
				$message_record->setTypeId($new_issue_record->getId());
				$message_record->store();

				$db->commit();

				if ($this->isAdmin) { //admin cloned this issue, send message to lead
					$to_user_id = $new_issue_lead_id;
					$to_user = UserRecordPeer :: findByPK($db, $to_user_id);
					MessageBoardRecordPeer :: addMessageToMessageBoard($db, $message_record->getId(), $to_user_id);
					ActionHelper :: fireIssueMessageMail($this, $this->user, $to_user, $message_record, $new_issue_record, '');
				} else { //lead cloned the issue, send message to admin
					$admin = UserRecordPeer :: getAdminUser($db);
					$to_user_id = $admin->getId();
					MessageBoardRecordPeer :: addMessageToMessageBoard($db, $message_record->getId(), $to_user_id);
					if (!$this->isAdmin && ConfigRecordPeer :: getCopyMailsToAdmin($this)) {
						ActionHelper :: fireIssueMessageMail($this, $this->user, $admin, $message_record, $new_issue_record, '');
					}
				}
			} catch (Exception $exception) {
				$db->rollback();
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		$new_args = array ();
		if ($this->has_error) {
			if (isset ($args['issue_id']) && $args['issue_id'] != '') {
				$new_args['issue_id'] = $args['issue_id'];
			}
			$this->setFlashMessage($this->error_message, true);
			$this->callModuleMethod('issue', 'copyIssue', $new_args);
		} else {
			$new_args['id'] = !is_null($new_issue_record) ? $new_issue_record->getId() : $args['issue_id'];
			$this->setFlashMessage('Issue copied');
			$this->callModuleMethod('issue', 'view', $new_args);
		}
	}

	private function cloneIssueMessages($db, $old_issue_record, $new_issue_record) {
		$old_issue_message_records = MessageRecordPeer :: getMessageRecords($db, null, '', constants :: ISSUE_MESSAGE, $old_issue_record->getId(), '', '', 'asc');
		foreach ($old_issue_message_records as $old_message_record) {
			$new_message_record = new MessageRecord($db);
			$new_message_record->setType(Constants :: ISSUE_MESSAGE);
			$new_message_record->setSubject($old_message_record->getSubject());
			$new_message_record->setFromId($old_message_record->getFromId());
			$new_message_record->setTypeId($new_issue_record->getId());
			$new_message_record->setCont($old_message_record->getCont());
			$new_message_record->setStatus($old_message_record->getStatus());
			$new_message_record->setDate($old_message_record->getDate());
			//store $message_record as createAttachmentHelper needs an id
			$new_message_record->store();
			if ($old_message_record->getHasAttachment()) {
				$attchment_names = explode(':', $old_message_record->getAttachmentName());
				$attachment_folder = Util :: getAttachmentFolderName($this->getConfig(), Constants :: ISSUE_MESSAGE);
				$attached_file_names = Util :: createAttachmentCloneHelper($this->getConfig(), $new_message_record, $attchment_names, $attachment_folder, $old_issue_record->getProjectId(), $new_issue_record->getProjectId());
				if ($attached_file_names) {
					$new_message_record->setAttachmentName($attached_file_names);
					$new_message_record->store();
				}
			}
		}
		return;
	}
	private function makeAllIssueMessagesRead($db, $user_id, $issue_id) {

		$read_message_ids = array ();
		$message_records_db = MessageRecordPeer :: getMessageRecords($db, null, Constants :: DELETED_MESSAGE, Constants :: ISSUE_MESSAGE, $issue_id);

		foreach ($message_records_db as $message_record) {
			$read_message_ids[] = $message_record->getId();
		}

		if (!empty ($read_message_ids)) {
			MessageBoardRecordPeer :: setMessagesReadForUser($db, $read_message_ids, $user_id);

		}
	}
}
?>