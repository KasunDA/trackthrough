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
require_once 'PageCollection.php';

class Action extends FW_BaseController {
	private function common($args) {
		$this->isAdmin = false;
		$this->isLanding = false;
		$this->version = Util :: getVersion();
		$this->message_f = $this->getFlashMessageObj();

		if ($this->getIsAuthorised()) {
			$user_id = $this->getParameter('USER_ID');
			$this->theme_color = $this->getParameter('THEME_COLOR');
			$this->theme_pallette = ActionHelper::getThemePallette($this,$this->theme_color);
		
			$config = $this->getConfig();
			$this->user = UserRecordPeer :: findByConfigAndPK($config, $user_id);

			$this->isAdmin = $this->getParameter('is_admin');
		}
		$this->record_count = MessageBoardRecordPeer :: getUnreadCountsForUser($config, $user_id);
		$this->unreadMessages = ($this->record_count > 0) ? true : false;
	}

	function assign($args) {
		$this->common($args);
		$this->projects = true;
		if (!isset ($args['task_id']) || $args['task_id'] == '') {
			$this->appendErrorMessage('Task id is not set!');
		}
		if (!isset ($args['task_permission']) || $args['task_permission'] == '') {
			$this->appendErrorMessage('Task permission type is not set!');
		}
		if (!isset ($args['team_id']) || $args['team_id'] == '') {
			$this->appendErrorMessage('No user or team  selected!');
		}
		if (!$this->has_error) {
			try {
				$config = $this->getConfig();
				$db = Db :: getInstance($config);
				$task_record = TaskRecordPeer :: findByPK($db, $args['task_id']);

				$existing_team = false;
				if ($task_record == null) {
					throw new Exception(' could not find dataset!');
				}
				
				if($task_record->getIsClosed()) {
					throw new Exception(' can not assign when the task is closed.');
				}

				if (!$this->isAdmin) {
					$can_lead_project = UserPermissionPeer :: canLeadProject($db, $this->user->getId(), $task_record->getParentProjectId());
					if (!$can_lead_project) {
						throw new Exception(' authorization required!');
					}

				}

				$team_ids_selected = isset ($args['team_id']) ? $args['team_id'] : array ();
				$assigned_users = array ();
				$team_signin_ids = array ();

				for ($i = 0; $i < count($team_ids_selected); $i++) {
					if ($team_ids_selected[$i] != '0') {
						$team_record = UserRecordPeer :: findByPK($db, $team_ids_selected[$i]);

						$existing_team = false;
						if (UserPermissionPeer :: canExecTask($db, $team_ids_selected[$i], $task_record->getId()) || UserPermissionPeer :: canViewTask($db, $team_ids_selected[$i], $task_record->getId())) {
							$existing_team = true;
						}

						/* create a permission record in user permission table */
						if ($team_record != null && !$existing_team) {

							if ($args['task_permission'] == Constants :: CAN_PERFORM_TASK) {
								UserPermissionPeer :: setTeamExecPermission($db, $team_record->getId(), $task_record->getParentProjectId(), $task_record->getId());
								//only when team with exec permission is added
								$task_record->setStatus(Constants :: TASK_INPROGRESS);
							} else {
								UserPermissionPeer :: setTeamViewPermission($db, $team_record->getId(), $task_record->getParentProjectId(), $task_record->getId());
							}

							$task_record->store();
							ProjectRecordPeer :: updateProjectProgress($db, $task_record->getParentProjectId());

							$assigned_users[] = $team_record;
							$team_signin_ids[] = $team_record->getSigninId();
						}

					}
				}

				if (!empty ($assigned_users)) {
					$task_record = $this->getTeamAndLeadSigninId($db, $task_record);

					$message_record = new MessageRecord($db);
					$message_record->setType(Constants :: TASK_MESSAGE);
					$message_record->setSubject('Task Assigned ');
					$message_record->setFromId($this->user->getId());
					$message_record->setTypeId($task_record->getId());

					$message_record->setCont("Task assigned to " . implode(",", $team_signin_ids));
					$message_record->setType(Constants :: TASK_MESSAGE);
					$message_record->store();

					foreach ($assigned_users as $user) {
						MessageBoardRecordPeer :: addMessageToMessageBoard($db, $message_record->getId(), $user->getId());

					}

					//fire mails to team and lead
					$assigned_users[] = $this->user;
					foreach ($assigned_users as $to_user) {

						ActionHelper :: fireTaskMessageMail($this, $this->user, $to_user, $message_record, $task_record, '');

					}

					$admin = UserRecordPeer :: getAdminUser($db);
					if (!$this->isAdmin) {
						MessageBoardRecordPeer :: addMessageToMessageBoard($db, $message_record->getId(), $admin->getId());

					}

					$copy_mails = ConfigRecordPeer :: getCopyMailsToAdmin($this);

					if ($copy_mails && !$this->isAdmin) {

						if ($admin->getEmail() != '') {

							ActionHelper :: fireTaskMessageMail($this, $this->user, $admin, $message_record, $task_record, '');
						}
					}
				}

			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		$new_args = array ();
		$new_args['id'] = (isset ($args['task_id'])) ? $args['task_id'] : '';
		if ((isset ($args['page_index'])) && ($args['page_index'] != '')) {

			$task_comments_per_page = PreferenceRecordPeer :: getTaskCommentsPerPage($this, $this->user->getId());
			$new_args['page_index'] = $args['page_index'];
		}
		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
		}
		$this->callModuleMethod('task', 'view', $new_args);
	}

	function removeTeam($args) {
		$this->common($args);
		$this->projects = true;
		if (!isset ($args['task_id']) || $args['task_id'] == '') {
			$this->appendErrorMessage('Task id is not set!');
		}
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
				$task_id = $args['task_id'];
				$user_id = $args['user_id'];
				$project_id = $args['project_id'];

				$task_record = TaskRecordPeer :: findByPK($db, $task_id);
				if ($task_record == null) {
					throw new Exception(' could not find dataset!');
				}
				
				if($task_record->getIsClosed()) {
					throw new Exception(' can not unassign when the task is closed.');
				}

				//$permission_record = UserPermissionPeer :: findContentPermission($db, $this->user->getId(), constants :: PROJECT, $project_id);
				//$permission = ($permission_record != null) ? $permission_record->getPermission() : null;

				if (!$this->isAdmin) {
					$can_lead_project = UserPermissionPeer :: canLeadProject($db, $this->user->getId(), $project_id);
					if (!$can_lead_project) {
						throw new Exception(' You are not authorized to unassign the team');
					}

				}

				//before removing team permission, send a message/mail
				$message_record = new MessageRecord($db);
				$message_record->setType(Constants :: TASK_MESSAGE);

				$subject = 'Task unassigned';
				$message_record->setSubject($subject);
				$message_record->setCont(" Unassigned Team/User - " . UserRecordPeer :: getSigninId($db, $user_id));
				$message_record->setFromId($this->user->getId());
				$message_record->setTypeId($task_id);
				$message_record->store();

				ActionHelper :: sendTaskMessage($this, $task_record, $message_record);

				UserPermissionPeer :: unsetTeamPermission($db, $user_id,  $task_id);
				if (TaskRecordPeer :: getUserTaskCountHavingPermissions($db, array($project_id), $user_id, array (
						Constants :: CAN_PERFORM_TASK,
						Constants :: CAN_VIEW_TASK
					)) <= 0) {
					UserPermissionPeer :: unsetProjectExecPermission($db, $user_id, $project_id);
				}

				$task_team_ids = UserPermissionPeer :: getTaskExecTeam($db, $task_record->getId(), 1);
				if (empty ($task_team_ids)) {
					$task_record->setStatus(Constants :: TASK_OPEN);
					$task_record->store();
					ProjectRecordPeer :: updateProjectProgress($db, $task_record->getParentProjectId());
				}

			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		$new_args = array ();
		$new_args['id'] = (isset ($args['task_id'])) ? $args['task_id'] : '';
		if ((isset ($args['page_index'])) && ($args['page_index'] != '')) {
			$task_comments_per_page = PreferenceRecordPeer :: getTaskCommentsPerPage($this, $this->user->getId());

			$new_args['page_index'] = $args['page_index'];
		}
		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
		}
		$this->callModuleMethod('task', 'view', $new_args);

	}

	function delete($args) {
		$this->common($args);
		$this->projects = true;
		if (!isset ($args['task_id'])) {
			$this->appendErrorMessage('Task id is not set!');
		}
		$config = $this->getConfig();
		$from_page = isset ($args['from_page']) ? $args['from_page'] : 'index_project';
		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($config);
				$task_record = TaskRecordPeer :: findByPK($db, $args['task_id']);
				$parent_project_id = $task_record->getParentProjectId();
				if ($task_record == null) {
					throw new Exception(' could not find dataset!');
				}

				if ((!$this->isAdmin) && ($task_record->getLeadId() != $this->user->getId())) {
					throw new Exception(' authorization required!');
				}
				$task_team_ids = UserPermissionPeer :: getTaskAnyTeam($db, $task_record->getId());

				if (!empty ($task_team_ids)) {
					throw new Exception(' you can not delete a task when it is assigned to a Team!');
				}
				Util :: deleteTaskAttachments($config, $task_record);
				$message_records_db = MessageRecordPeer :: getMessageRecords($db, null, '', Constants :: TASK_MESSAGE, $task_record->getId());

				foreach ($message_records_db as $message_record) {
					MessageBoardRecordPeer :: deleteUserMessageBoardRecord($config, $message_record);
				}
				$bookmarks = BookmarkRecordPeer :: getCategoryBookmarks($db, $task_record->getType(), $task_record->getId());
				if (!empty ($bookmarks)) {
					$ids_for_delete = array ();
					foreach ($bookmarks as $bookmark) {
						$ids_for_delete[] = $bookmark->getId();
					}
					BookmarkRecordPeer :: deleteBookmarks($db, $ids_for_delete);
				}
				//UserPermissionPeer :: unsetContentPermission($db, '', '',$task_record->getType(), $task_record->getId());
				UserPermissionPeer :: unsetTaskPermissions($db, $task_record->getId());

				TaskRecordPeer :: deleteTask($db, $task_record->getId());
				ProjectRecordPeer :: updateProjectProgress($db, $parent_project_id);

				$project_record = ProjectRecordPeer :: findByPK($db, $parent_project_id);

				$message_record = new MessageRecord($db);
				$message_record->setType(Constants :: PROJECT_MESSAGE);

				$subject = 'Task Deleted';
				$message_record->setSubject($subject);
				$message_record->setCont("Task [" . $task_record->getName() . "] deleted");
				$message_record->setFromId($this->user->getId());
				$message_record->setTypeId($project_record->getId());
				$message_record->store();

				$to_user_ids = UserPermissionPeer :: findContentPermittedUserIds($db, Constants :: PROJECT, $parent_project_id);
				ActionHelper :: sendProjectMessage($this, $to_user_ids, $project_record, $message_record);

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
		}
		if ($from_page == 'index_project') {
			$this->callModuleMethod('project', 'index', $new_args);
		} else {
			$this->callModuleMethod('project', 'view', $new_args);
		}
	}

	function view($args) {
		$this->common($args);
		$this->projects = true;
		$config = $this->getConfig();
		$this->task_attachment_names = array ();
		$zeroprogress = false;

		$this->const_exec_task_permission = Constants :: CAN_PERFORM_TASK;
		$this->const_view_task_permission = Constants :: CAN_VIEW_TASK;

		//$meta_data = Util :: getMetaData('task view', $config);
		$this->title = "Task View"; /* Abhilash 26-10-13 */

		$from = isset ($args['from']) ? $args['from'] : 0;
		$offset = $from;
		$limit = 0;
		$total_records = 0;
		$this->is_compose_desc_visible = false;
		
		if (!isset ($args['id'])) {
			$this->appendErrorMessage('Error, could not find dataset!');
		}
		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($config);
				$this->task_record = TaskRecordPeer :: findByPK($db, $args['id']);
				

				if ($this->task_record == null) {
					throw new Exception(' could not find dataset!');
				}

				$this->project_record = ProjectRecordPeer :: findByPK($db, $this->task_record->getParentProjectId());
				
				if ($this->project_record == null) {
					throw new Exception(' could not find dataset!');
				}

				$this->task_record = $this->getTeamAndLeadSigninId($db, $this->task_record);
				
				if ($this->task_record == null) {
					throw new Exception(' could not find dataset, invalid task id!');
				}
				if (!ActionHelper :: isAuthorizedTask($this, $this->task_record)) {
					$this->makeAllTaskMessagesRead($db, $this->user->getId(), $this->task_record->getId());
					$this->task_record = null;
					throw new Exception(' you are not authorized to view this task!');
				}

				$this->task_record->setIsViewOnly(UserPermissionPeer :: getIsViewOnlyTask($db, $this->task_record)); /* Abhilash 17-10-13 */
				
				
				$project_name = wordwrap($this->project_record->getName(), 100, "\n", 1);
				$this->project_record->setName($project_name);

				$issue_task = IssuePeer :: isIssuePromotedAsTask($db, $this->task_record);
				
				$this->isPromoted = ($issue_task != null) ? true : false;
				if ($this->isPromoted) {
					//$issue_permission = UserPermissionPeer :: getAddIssuePermission($db, $this->project_record->getId(), $this->user->getId());
					$can_add_issue = UserPermissionPeer :: canAddIssue($db, $this->user->getId(), $this->project_record->getId());
					$this->view_issue = false;
					if ($this->isAdmin || $can_add_issue) {
						$this->view_issue = true;
						$this->issue_id = $issue_task->getIssueId();
					}
				}

				
				$project_icons_folder = Util :: getAttachmentFolderName($config, $this->project_record->getType());
				if ($this->project_record->getIconName() != null) {
					$project_icon_name = $this->project_record->getIconName();
					$this->project_icon = $config->getValue('FW', 'base_url') . '/' . $project_icons_folder . $this->project_record->getId() . '/' . $project_icon_name;
				} else {
					$project_icon_name = 'default.png';
					$this->project_icon = $this->getAbsoluteImageURL('') . '/' . $project_icon_name;
				}

				$task_name = wordwrap($this->task_record->getName(), 100, "\n", 1);
				$this->task_record->setName($task_name);

				$description = nl2br($this->task_record->getDescription());
				$this->task_record->setDescription(wordwrap($description, 105, "\n", 1));

				$this->task_progress = $this->task_record->getProgress();
				$this->task_progress_values = array (
					"0",
					"25",
					"50",
					"75",
					"95"
				);
				if ($this->lead_task) {
					//let us give 100% progress values only for the lead, experimental
					$this->task_progress_values = array (
						"0",
						"25",
						"50",
						"75",
						"95",
						"100"
					);
				}

				$this->isBookmarked = BookmarkRecordPeer :: getIsBookmarked($db, $this->user->getId(), $this->task_record->getType(), $this->task_record->getId());
				//$this->isBookmarked = ($bookmark != null) ? true : false;

				$task_team_ids = UserPermissionPeer :: getTaskAnyTeam($db, $this->task_record->getId());
				
				$this->hasTeam = !empty ($task_team_ids) ? true : false;
				$count = 1;
				$this->task_team = array();
				if ($this->hasTeam) {
					
					$sort_assoc = array();
					foreach ($task_team_ids as $t_id) {
						$sort_assoc[$t_id] = "";
					}
					
					$task_team_members = UserRecordPeer :: getUserRecordsWithSigninId($db, $task_team_ids);
					foreach ($task_team_members as $team) {
						$obj = new StdClass;
						$obj->id = $team->getId();
						$obj->can_exec_task = UserPermissionPeer :: canExecTask($db, $team->getId(), $this->task_record->getId());
				
						$obj->signinId = $team->getSigninId();
						$obj->count_end = (count($task_team_members) == $count) ? true : false;
						//$this->task_team[] = $obj;
						
						$sort_assoc[$team->getId()] = $obj;
						$count++;

					}
					$this->task_team = array_values($sort_assoc);
				}
				
				
				$this->is_closed_task = ($this->task_record->getStatus() == Constants :: TASK_CLOSED) ? true : false;
			
				if ($this->can_assign_task && !$this->is_closed_task) {

					$exclude_user_ids = array (
						$this->task_record->getLeadId()
					);
					if (!empty ($task_team_members)) {
						foreach ($task_team_members as $tm) {
							$exclude_user_ids[] = $tm->getId();
						}
					}

					$new_team_ids = UserRecordPeer :: getNonAdminUserIds($config, $exclude_user_ids);
					
					
					
					$this->teams_array = array();
					if(!empty($new_team_ids)) {
						//check whether user has basic "user permission "
						$exec_team_ids = array();
						foreach ($new_team_ids as $new_team_id) {
							$can_perform_task = UserPermissionPeer :: canPerformTask($db, $new_team_id);
							if($can_perform_task) {
								$exec_team_ids[] = $new_team_id;
							}
						}
						if(!empty($exec_team_ids)) {
							$this->teams_array = UserRecordPeer :: getUserRecordsWithSigninId($db, $exec_team_ids);
						}
						
					}
				
							
							
				}
			$this->is_compose_desc_visible = PreferenceRecordPeer :: getIsComposeTaskDescVisible($this, $this->user->getId(), $this->task_record->getId());		/* Abhilash 3.1.15 */
				
				
			
				$attchment_names = explode(':', $this->task_record->getAttachmentName());
				$task_attachment_names = Util :: getExistingAttachments($config, $attchment_names, $this->task_record->getParentProjectId(), Constants :: TASK_MESSAGE);
				$this->file_missing = false;
				if (count($attchment_names) > 0) {
					
					foreach ($task_attachment_names as $attachment_name) {
						if ($attachment_name != null) {
							$attachment_icon = Util :: getAttachmentIcon($config, $attachment_name);
							$obj = new StdClass;
							$obj->attachment_name = $attachment_name;
							if ($attachment_icon == 'image.png') {
								$obj->is_image = true;
								$attachment_name_with_prefix = Util :: getAttachmentNamePrefix($this->task_record) . $attachment_name;
								
								$obj->image_path = Util :: getAttachmentURL($config, $this->task_record->getParentProjectId(), $attachment_name_with_prefix);
								
							}

							$obj->attachment_icon = 'attachment_icons' . '/' . $attachment_icon;
							$this->task_attachments[] = $obj;
						} else {
							$this->file_missing = true;
						}
					}
				}
				$total_records = $this->isAdmin ? MessageRecordPeer :: countTaskMessageRecords($db, '', $this->task_record->getId()) : MessageRecordPeer :: countTaskMessageRecords($db, Constants :: DELETED_MESSAGE, $this->task_record->getId());
				$limit = PreferenceRecordPeer :: getTaskCommentsPerPage($this, $this->user->getId());

				$this->message_records = array ();

				$message_records_db = $this->isAdmin ? MessageRecordPeer :: getMessageRecords($db, null, '', Constants :: TASK_MESSAGE, $this->task_record->getId(), $offset, $limit) : MessageRecordPeer :: getMessageRecords($db, null, Constants :: DELETED_MESSAGE, Constants :: TASK_MESSAGE, $this->task_record->getId(), $offset, $limit);
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
					//$obj->isCommentBookmarked = ($bookmark != null) ? true : false;

					$obj->msg_attachment_missing = false;

					$msg_attchment_names = explode(':', $message_record->getAttachmentName());
					$msg_attchment_names = Util :: getExistingAttachments($config, $msg_attchment_names, $this->task_record->getParentProjectId(), Constants :: TASK_MESSAGE);
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
									
									$attachment_obj->image_path = Util :: getAttachmentURL($config, $this->task_record->getParentProjectId(), $attachment_name_with_prefix);
									
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
					//MessageBoardRecordPeer :: deleteUserMessageBoardRecord($config, $message_record, $this->user->getId());
				}

				if (!empty ($read_message_ids)) {
					MessageBoardRecordPeer :: setMessagesReadForUser($db, $read_message_ids, $this->user->getId());

				}

				$this->record_count = MessageBoardRecordPeer :: getUnreadCountsForUser($config, $this->user->getId());
				$this->unreadMessages = ($this->record_count > 0) ? true : false;
				$this->max_upload_size = ini_get('upload_max_filesize');
				$this->allowed_attachments = ConfigRecordPeer :: getAttachmentTypes($this);		/* Abhilash 28-10-13 */
			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
			$this->no_records = empty ($this->message_records) ? true : false;
			$this->pc = null;
			if ($this->task_record) {
				
				$this->pc = new PageCollection($args, $limit, $total_records, $this->getAbsoluteURL('/task/view/id/' . $this->task_record->getId()));

			}

		}
		$new_args = array ();

		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
			$this->callModuleMethod('project', 'index', $new_args);
		}
		return new FlexyView('task/viewTask.html', $this);
	}
/* Abhilash 3.1.15 */
	function setTaskDescVisibility($args) {
	
		$this->common($args);
		if (!isset ($args['task_id']) || $args['task_id'] == '') {
			$this->appendErrorMessage('Error, could not find dataset! ');
		}
	
		if (!isset ($args['visibility']) || $args['visibility'] == '') {
			$this->appendErrorMessage('Error, required current visibility status! ');
		}
	
		if (!$this->has_error) {
			try {
				$task_id = $args['task_id'];
				$visibility = $args['visibility'];
				
				//$db = Db :: getInstance($this->getConfig());
	
				PreferenceRecordPeer :: setComposeTaskDescVisible($this, $this->user->getId(), $task_id, ($visibility == 'show'));
	
	
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
			$new_args['id'] = $args['task_id'];
			$this->callModuleMethod('task', 'view', $new_args);
		}
		$this->callModuleMethod('project', 'index', $new_args);
	}
	function message($args) {
		$this->common($args);
		$config = $this->getConfig();
		
		if (isset ($args['comments'])) {
			$args['comments'] = strip_tags($args['comments']);
		}
		else {
			$args['comments'] = 'No comments';
		}
		if (!isset ($args['task_id']) || $args['task_id'] == '') {
			$this->appendErrorMessage(' Error, Task id not set! ');
		}
		if (!isset ($args['subject']) ) {
			//$this->appendErrorMessage(' Error, Subject not set! ');
			$args['subject'] = '';
		}
		if (!ActionHelper :: getIsValidAttachment($this)) {
			$this->appendErrorMessage('Unsupported  attachment; allowed formats ' . implode("|", ActionHelper :: getValidAttachmentTypes($this)));
		}

		$message_record = null;

		if (!$this->has_error) {
			try {

				$subject = $args['subject'];
				if (isset ($args['close_task']) && $args['close_task'] != '') {
					$subject = ' Task closed';

				} else
					if (isset ($args['reassign_task']) && $args['reassign_task'] != '') {
						$subject = ' Task reassigned';
					} else
						if (isset ($args['review_task']) && $args['review_task'] != '') {
							$subject = ' Review requested';
						}

				$db = Db :: getInstance($config);
				$db->begin();
				$message_record = new MessageRecord($db);
				$message_record->setType(Constants :: TASK_MESSAGE);

				$message_record->setFromId($this->user->getId());
				$message_record->setTypeId($args['task_id']);
				$message_record->setCont(strip_tags($args['comments']));
				$message_record->setSubject($subject);

				$task_record = TaskRecordPeer :: findByPK($db, $args['task_id']);

				if ($task_record == null) {
					throw new Exception(' error while retrieving task record!');
				}

				$project_record = ProjectRecordPeer :: findByPK($db, $task_record->getParentProjectId());

				if ($project_record == null) {
					throw new Exception(' error while retrieving project record!');
				}

				//store $message_record as createAttachmentHelper needs an id
				$message_record->store();

				$attachment_folder = Util :: getAttachmentFolderName($config, $message_record->getType());

				$attach_log_msg = Util :: createAttachmentHelper($message_record, $attachment_folder, $project_record->getId());
				$target_file = '';

				if ($attach_log_msg != '') { //has attachmet
					$target_file = Util :: getAttachmentFilePath($project_record->getId(), $message_record->getAttachmentName(), $attachment_folder);
				}

				//retrieve TASK
				$task_record = TaskRecordPeer :: findByPK($db, $args['task_id']);
				if ($task_record == null) {
					throw new Exception(' error while retrieving task record! ');
				}

				$progress = $args['task_progress'];
				if ($task_record->getProgress() != $progress) {
					$task_record->setProgress($progress);

				}
				if ($task_record->getLeadId() == $this->user->getId()) {
					if (isset ($args['close_task']) && $args['close_task'] != '') {
						$task_record->setStatus(Constants :: TASK_CLOSED);
						$task_record->setProgress(100);

					} else
						if (isset ($args['reassign_task']) && $args['reassign_task'] != '') {
							$task_record->setStatus(Constants :: TASK_INPROGRESS);
						}

				} else
					if (isset ($args['review_task']) && $args['review_task'] != '') {
						$task_record->setStatus(Constants :: TASK_REVIEW_PENDING);

					}
				$task_record->setUpdatedAt(date('Y-m-d H:i:s'));
				$task_record->store();
				$project_record->setUpdatedAt(date('Y-m-d H:i:s'));
				$project_record->store();

				ProjectRecordPeer :: updateProjectProgress($db, $task_record->getParentProjectId());

				ActionHelper :: sendTaskMessage($this, $task_record, $message_record);

				if (isset ($args['close_task']) && $args['close_task'] != '') {
					$is_close_issue = PreferenceRecordPeer :: getCloseIssueWhenTaskClosed($this, $this->user->getId());
					if ($is_close_issue) {
						$issue_task = IssuePeer :: isIssuePromotedAsTask($db, $task_record);
						if (!is_null($issue_task)) {
							$issue_record = IssuePeer :: findByPK($db, $issue_task->getIssueId());
							if (!is_null($issue_record) && !$issue_record->getIsClosed()) {
								$issue_record->setStatus(Constants :: ISSUE_CLOSED);
								$issue_record->setUpdatedAt(date('Y-m-d H:i:s'));
								$issue_record->store();

								$close_message_record = new MessageRecord($db);
								$close_message_record->setType(Constants :: ISSUE_MESSAGE);
								$close_message_record->setSubject('Issue Closed');

								$close_message_record->setFromId($this->user->getId());
								$close_message_record->setTypeId($issue_task->getIssueId());
								$close_message_record->setCont("Issue is closed as the related task was closed");

								$close_message_record->store();
								$issue_tracking_user_ids = UserPermissionPeer :: getIssueTrackingUserIds($db, $project_record->getId());
								ActionHelper :: sendIssueMessage($this, $issue_tracking_user_ids, $close_message_record);

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
			if (isset ($args['task_id']) && $args['task_id'] != null) {
				$new_args['id'] = $args['task_id'];
				$this->setFlashMessage($this->error_message, true);

				$this->callModuleMethod('task', 'view', $new_args);
			}
		}

		$new_args['id'] = (isset ($args['task_id']) && $args['task_id'] != '') ? $args['task_id'] : '';
		$this->callModuleMethod('task', 'view', $new_args);
	}

	function openTask($args) {
		$this->common($args);
		if (!isset ($args['task_id']) || $args['task_id'] == '') {
			$this->appendErrorMessage('Task id is required!');
		}

		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($this->getConfig());

				$task_id = $args['task_id'];
				$task_record = TaskRecordPeer :: findByPK($db, $task_id);
				if ($task_record == null) {
					throw new Exception(' could not find dataset, invalid task id!');
				}
				if ($task_record->getLeadId() != $this->user->getId()) {
					throw new Exception(' You are not authorized to open this task!');
				}
				$task_record->setStatus(Constants :: TASK_INPROGRESS);
				$task_record->setProgress(0);
				$task_record->setUpdatedAt(date('Y-m-d H:i:s'));
				$task_record->store();

				ProjectRecordPeer :: updateProjectProgress($db, $task_record->getParentProjectId());

				$message_record = new MessageRecord($db);
				$message_record->setType(Constants :: TASK_MESSAGE);

				$subject = 'Task Reopened';
				$message_record->setSubject($subject);
				$message_record->setCont("Task [" . $task_record->getName() . "] reopened");
				$message_record->setFromId($this->user->getId());
				$message_record->setTypeId($task_id);
				$message_record->store();

				ActionHelper :: sendTaskMessage($this, $task_record, $message_record);

			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		$new_args['id'] = (isset ($args['task_id'])) ? $args['task_id'] : '';
		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
		} else {
			$this->setFlashMessage('Task reopened');
		}
		$this->callModuleMethod('task', 'view', $new_args);
	}

	function download($args) {
		$this->common($args);
		if (!isset ($args['task_id'])) {
			$this->appendErrorMessage('Error, attachment location undefined!');
		}
		if (!isset ($args['file_name'])) {
			$this->appendErrorMessage('Error, file name undefined!');
		}
		$config = $this->getConfig();
		if (!$this->has_error) {
			try {
				$task_id = $args['task_id'];
				$db = Db :: getInstance($this->getConfig());
				$task_record = TaskRecordPeer :: findByPK($db, $task_id);
				if ($task_record == null) {
					throw new Exception(' could not find dataset, invalid task id!');
				}
				if (!ActionHelper :: isAuthorizedTask($this, $task_record)) {
					throw new Exception(' you are not authorized to download the content!');
				}

				$parent_project_id = $task_record->getParentProjectId();
				$project_record = ProjectRecordPeer :: findByPK($db, $parent_project_id);
				$attachment_name = Util :: getAttachmentNamePrefix($task_record) . $args['file_name'];
				$attachment_folder = Util :: getAttachmentFolderName($config, Constants :: TASK_MESSAGE);
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

	private function verifyCanAddTask($db, $project_id) {

		//$permission_record = UserPermissionPeer :: findContentPermission($db, $this->user->getId(), constants :: PROJECT, $project_id);
		//$project_permission = ($permission_record != null) ? $permission_record->getPermission() : null;

		$can_lead_project = UserPermissionPeer :: canLeadProject($db, $this->user->getId(), $project_id);

		//if ($project_permission == null) {
		//	throw new Exception(' error, authorization required, please signin using appropriate credentials!');
		//}
		//if ($project_permission && ($project_permission != Constants :: LEAD_PROJECT)) {
		//	throw new Exception(' error, authorization required, please signin using appropriate credentials!');
		//}
		if (!$can_lead_project) {
			throw new Exception(' error, authorization required, please signin using appropriate credentials!');
		}

		return true;
	}
	
	function edit($args) {
		$this->common($args);
		$this->projects = true;
		$config = $this->getConfig();
	
		$this->title = "Edit Task"; 	
		$this->edit_action = true;
		if (!isset ($args['task_id'])) {
			$this->appendErrorMessage('Error, task id is undefined!');
		}
		$this->from_page = 'index_project';
		if (isset ($args['from_page'])) {
			$this->from_page = $args['from_page'];
		}
		
		

		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($this->getConfig());
				$this->task_record = TaskRecordPeer :: findByPK($db, $args['task_id']);
				if ($this->task_record == null) {
					throw new Exception(' could not find dataset!');
				}
				
				if($this->task_record->getIsClosed()) {
						throw new Exception(' can not edit when the task is closed.');
				}
				

				$this->project_record = ProjectRecordPeer :: findByPK($db, $this->task_record->getParentProjectId());
				if ($this->project_record == null) {
					throw new Exception(' could not find dataset!');
				}
				if ($this->task_record->getLeadId() != $this->user->getId()) {
					throw new Exception(' you are not authorized to edit this task!');
				}
				
				
				$this->project_id =$this->project_record->getId();
				

				$this->verifyCanAddTask($db, $this->project_id);
			
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
				$this->allowed_attachments = ConfigRecordPeer :: getAttachmentTypes($this);		/* Abhilash 28-10-13 */
				
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
		
		return new FlexyView('task/editTask.html', $this);
	}
	

	function create($args) {
		$this->common($args);
		$this->projects = true;
		$config = $this->getConfig();
		//$meta_data = Util :: getMetaData('task create', $config);
		$this->title = "Add Task"; 	/* Abhilash 26-10-13 */
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
				$this->project_record = ProjectRecordPeer :: findByPK($db, $this->project_id);
				if ($this->project_record == null) {
					throw new Exception(' could not find project dataset!');
				}

				$this->verifyCanAddTask($db, $this->project_id);
				$this->task_record = new TaskRecord($args);
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
				$this->allowed_attachments = ConfigRecordPeer :: getAttachmentTypes($this);		/* Abhilash 28-10-13 */
			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		/*$new_args = array ();
		if ($this->has_error) {
		
			$this->setFlashMessage($this->error_message, true);
			$this->callModuleMethod('project', 'index', $new_args);
			return;
		}*/
		return new FlexyView('task/editTask.html', $this);
	}

	function update($args) {
		$this->common($args);
		$this->projects = true;
		$config = $this->getConfig();

		if (isset ($args['name'])) {
			$args['name'] = strip_tags($args['name']);
		}
		if (isset ($args['task_description'])) {
			$args['task_description'] = strip_tags($args['task_description']);
		}
		if (!isset ($args['name']) || $args['name'] == '') {
			$this->appendErrorMessage('Task title can not be blank!<br />');
		}
		if (!isset ($args['task_description']) || $args['task_description'] == '') {
			$this->appendErrorMessage('Task description can not be blank!');
		}
		if (!ActionHelper :: getIsValidAttachment($this)) {
			$this->appendErrorMessage('Unsupported  attachment; allowed formats ' . implode("|", ActionHelper :: getValidAttachmentTypes($this)));
		}
		$task_description = isset ($args["task_description"]) ? $args["task_description"] : '';
		$task_name = isset ($args["name"]) ? $args["name"] : '';
		$parent_project_id = isset ($args["project_id"]) ? $args["project_id"] : '';
		$edit_action = isset ($args["task_id"]) &&  $args["task_id"] ? true : false;
		
		$priority = isset($args["priority"]) ? $args["priority"] : Constants::NORMAL_PRIORITY;
		$this->task_record = null;
		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($config);
				$this->project_record = ProjectRecordPeer :: findByPK($db, $parent_project_id);
				if ($this->project_record == null) {
					throw new Exception(' could not find project dataset!');
				}
				$this->verifyCanAddTask($db, $parent_project_id);
				$db->begin();
				$this->task_record = new TaskRecord($db);
				if($edit_action) {
					$this->task_record = TaskRecordPeer :: findByPK($db, $args["task_id"]);
					if ($this->task_record == null) {
						throw new Exception(' could not find task dataset!');
					}
				}
				if(!$edit_action) {
					$this->task_record->setParentProjectId($parent_project_id);
						$this->task_record->setLeadId($this->user->getId());
						$status = Constants :: TASK_OPEN;
						$this->task_record->setStatus($status);
						$this->task_record->setType(Constants :: TASK);
				
						$this->task_record->setProgress(0);
				}
				
				
				$this->task_record->setDescription($task_description);
				$this->task_record->setName($task_name);
				$this->task_record->setUpdatedAt(date('Y-m-d H:i:s'));
				$this->task_record->setPriority($priority);
				
				
				$project_record = ProjectRecordPeer :: findByPK($db, $parent_project_id);

				$this->task_record->store();
				$project_record->setUpdatedAt(date('Y-m-d H:i:s'));
				$project_record->store();
				$attachment_folder = Util :: getAttachmentFolderName($config, Constants :: TASK_MESSAGE);

				$attach_log_msg = Util :: createAttachmentHelper($this->task_record, $attachment_folder, $project_record->getId());
				$str = $edit_action ? "updated" : "created";
				AppLogPeer :: logInfo($db, "Project Task [" . $project_record->getId() . "] $str; $attach_log_msg");
				//UserPermissionPeer :: createPermissionRecord($db, $this->user->getId(), $this->task_record->getType(), Constants :: LEAD_TASK, $this->task_record->getId());
				UserPermissionPeer :: setLeadTaskPermission($db, $this->user->getId(), $this->task_record->getId());

				$message_record = new MessageRecord($db);
				$message_record->setType(Constants :: TASK_MESSAGE);

				$message_record->setSubject($edit_action ? 'Task updated': 'New task created');
				$message_record->setCont(($edit_action ? 'Task updated ': 'Added new task').'[' . $task_name . ']');
				$message_record->setFromId($this->user->getId());
				$message_record->setTypeId($this->task_record->getId());
				$message_record->store();

				//change my preferences
				PreferenceRecordPeer :: makeProjectAndTasksVisible($this, $this->user->getId(), $project_record->getId());

				$admin = UserRecordPeer :: getAdminUser($db);
				MessageBoardRecordPeer :: addMessageToMessageBoard($db, $message_record->getId(), $admin->getId());
				$db->commit();

				
				
				if($edit_action) {
					ActionHelper :: sendTaskMessage($this, $this->task_record, $message_record);
				}
				else {
					if (!$this->isAdmin && ConfigRecordPeer :: getCopyMailsToAdmin($this)) {
						$this->fireNewTaskMail($db, $this->user, $admin, $this->task_record, '');
					}
				}
				

			} catch (Exception $exception) {
				$db->rollback();
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		$new_args = array ();
		
		if ($this->has_error) {
			if ($this->task_record) {
				$this->task_record->setName('');

			}
			$new_args['id'] = $args['project_id'];
			$this->setFlashMessage($this->error_message, true);
			if($edit_action) {
				
				$this->callModuleMethod('task', 'edit', array('task_id'=>$args['task_id']));
			}
			if(!$edit_action) {
					$this->callModuleMethod('task', 'create', $new_args);
			}
			
		}
		
		if($this->task_record != null) {
			$this->callModuleMethod('task', 'view', array('id'=>$this->task_record->getId()));
		}
	
		if(isset($args['from_page']) && $args['from_page'] == 'view_project') {
			$this->callModuleMethod('project', 'view', array('id'=>$args['project_id']));
		}
		else {
			$this->callModuleMethod('project', 'index', $new_args);
		}
		
	}
/*
	function updateTitle($args) {
		$this->common($args);
		$config = $this->getConfig();
		if (!isset ($args['task_id']) || $args['task_id'] == '') {
			$this->appendErrorMessage('Error, could not find dataset! ');
		}
		if (!isset ($args['name']) || $args['name'] == '') {
			$this->appendErrorMessage('Error, task title can not be blank! ');
		}

		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($this->getConfig());
				$this->task_record = TaskRecordPeer :: findByPK($db, $args['task_id']);
				
				
				

				if ($this->task_record == null) {
					throw new Exception(' could not find dataset!');
				}
				
				if($this->task_record->getIsClosed()) {
						throw new Exception(' can not update title when the task is closed.');
				}
				

				$project_record = ProjectRecordPeer :: findByPK($db, $this->task_record->getParentProjectId());
				if ($project_record == null) {
					throw new Exception(' could not find dataset!');
				}
				if ($this->task_record->getLeadId() != $this->user->getId() && !$this->isAdmin) {
					throw new Exception(' you are not authorized to edit this task!');
				}
				$old_name = $this->task_record->getName();

				$name = strip_tags($args['name']);
				if ($old_name != $name) {
					$this->task_record->setName($name);
					$this->task_record->setUpdatedAt(date('Y-m-d H:i:s'));
					$this->task_record->store();
					$project_record->setUpdatedAt(date('Y-m-d H:i:s'));
					$project_record->store();

					$message_record = new MessageRecord($db);
					$message_record->setType(Constants :: TASK_MESSAGE);

					$subject = 'Task title changed';
					$message_record->setSubject($subject);
					$message_record->setCont("Task title changed from [$old_name] to [$name]");
					$message_record->setFromId($this->user->getId());
					$message_record->setTypeId($this->task_record->getId());
					$message_record->store();

					ActionHelper :: sendTaskMessage($this, $this->task_record, $message_record);

					$this->setFlashMessage('Task title updated');
				}

			} catch (Exception $exception) {
				$db->rollback();
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		$new_args = array ();

		$new_args['id'] = $args['task_id'];

		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
		}
		$this->callModuleMethod('task', 'view', $new_args);
	}

	function updateTaskDesc($args) {
		$this->common($args);
		$this->addTasks = true;
		$config = $this->getConfig();
		if (!isset ($args['task_id']) || $args['task_id'] == '') {
			$this->appendErrorMessage('Error, could not find dataset! ');
		}
		if (!isset ($args['task_desc_area']) || $args['task_desc_area'] == '') {
			$this->appendErrorMessage('Error, task description can not be blank! ');
		}
		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($this->getConfig());
				$this->task_record = TaskRecordPeer :: findByPK($db, $args['task_id']);
				if ($this->task_record == null) {
					throw new Exception(' could not find dataset!');
				}
				if ($this->task_record->getLeadId() != $this->user->getId()) {
					throw new Exception(' you are not authorized to view this Task!');
				}
				$project_record = ProjectRecordPeer :: findByPK($db, $this->task_record->getParentProjectId());
				if ($project_record == null) {
					throw new Exception(' could not find dataset!');
				}
				if ($this->task_record->getLeadId() != $this->user->getId()) {
					throw new Exception(' you are not authorized to view this Task!');
				}
				if($this->task_record->getIsClosed()) {
						throw new Exception(' can not update description when the task is closed.');
				}
				

				$desc = strip_tags($args['task_desc_area']);
				$this->task_record->setDescription($desc);
				$this->task_record->setUpdatedAt(date('Y-m-d H:i:s'));
				$this->task_record->store();
				$project_record->setUpdatedAt(date('Y-m-d H:i:s'));
				$project_record->store();

				$message_record = new MessageRecord($db);
				$message_record->setType(Constants :: TASK_MESSAGE);

				$subject = 'Task description changed';
				$message_record->setSubject($subject);
				$message_record->setCont("Description for task [" . $this->task_record->getName() . "] has changed.");
				$message_record->setFromId($this->user->getId());
				$message_record->setTypeId($this->task_record->getId());
				$message_record->store();

				ActionHelper :: sendTaskMessage($this, $this->task_record, $message_record);

			} catch (Exception $exception) {
				$db->rollback();
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
			$new_args = array (
				'id' => $args['task_id']
			);
			$this->setFlashMessage('Task description updated');

		}
		if ($this->has_error) {
			$new_args['id'] = !isset ($args['task_id']) ? '' : $args['task_id'];
			$this->setFlashMessage($this->error_message, true);

		}
		$this->callModuleMethod('task', 'view', $new_args);
	}
*/
	function copyTask($args) {
		$this->common($args);
		$config = $this->getConfig();
		//$meta_data = Util :: getMetaData('task copy', $config);
		$this->title =  "Copy Task";		/* Abhilash 26-10-13 */

		if (!isset ($args['task_id'])) {
			$this->appendErrorMessage('Error, task id is undefined!');
		}
		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($this->getConfig());

				$this->task_record = TaskRecordPeer :: findByPK($db, $args['task_id']);
				
				
				/* Abhilash 28.4.15 */
				$issue_task = IssuePeer :: isIssuePromotedAsTask($db, $this->task_record);
				$this->isPromoted = ($issue_task != null) ? true : false;
				//may be required for status icon display ?
				$this->task_record->setIsViewOnly(UserPermissionPeer :: getIsViewOnlyTask($db, $this->task_record));

				if ($this->task_record == null) {
					throw new Exception(' could not find dataset!');
				}
				
				$this->project_record = ProjectRecordPeer :: findByPK($db, $this->task_record->getParentProjectId());
				$project_icons_folder = Util :: getAttachmentFolderName($config, $this->project_record->getType());
				if ($this->project_record->getIconName() != null) {
					$project_icon_name = $this->project_record->getIconName();
					$this->project_icon = $config->getValue('FW', 'base_url') . '/' . $project_icons_folder . $this->project_record->getId() . '/' . $project_icon_name;
				} else {
					$project_icon_name = 'default.png';
					$this->project_icon = $this->getAbsoluteImageURL('') . '/' . $project_icon_name;
				}
				
				//$permission_record = UserPermissionPeer :: findContentPermission($this,$this->user->getId(),$this->task_record->getType(), $this->task_record->getId());
				//$permission  = ($permission_record != null)? $permission_record->getPermission() : null;

				$can_copy_task = ($this->task_record->getLeadId() == $this->user->getId()) || $this->isAdmin;
				//if ($this->task_record->getLeadId() != $this->user->getId() || !$this->isAdmin) {
				//	throw new Exception('You are not authorised to access the page. Authorization required!');
				//}
				if (!$can_copy_task) {
					throw new Exception('You are not authorised to access the page. Authorization required!');
				}
				$lead_project_records = array ();
				//$excluded_project_id = $this->task_record->getParentProjectId();
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
		return new FlexyView('task/copyTask.html', $this);
	}
	//admins and lead can clone task
	function cloneTask($args) {
		$this->common($args);

		$config = $this->getConfig();
		if (!isset ($args['task_id']) || $args['task_id'] == '') {
			$this->appendErrorMessage('Error, could not find dataset! ');
		}
		if (!isset ($args['new_project_id']) || $args['new_project_id'] == '') {
			$this->appendErrorMessage('Error, could not find destination project! ');
		}
		$new_task_record = null;
		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($this->getConfig());
				$old_task_id = $args['task_id'];
				$old_task_record = TaskRecordPeer :: findByPK($db, $old_task_id);
				if ($old_task_record == null) {
					throw new Exception(' could not find dataset!');
				}
				$can_copy_from_task = ($old_task_record->getLeadId() == $this->user->getId()) || $this->isAdmin;

				if (!$can_copy_from_task) {
					throw new Exception(' you are not authorized to copy this task!');
				}
				$old_project_id = $old_task_record->getParentProjectId();

				$new_project_id = $args['new_project_id'];
				$new_project_record = ProjectRecordPeer :: findByPK($db, $new_project_id);
				if ($new_project_record == null) {
					throw new Exception(' could not find destination project!');
				}
				$can_copy_to_task = ($new_project_record->getLeadId() == $this->user->getId()) || $this->isAdmin;

				if (!$can_copy_to_task) {
					throw new Exception(' you are not authorized to access the destination project!');
				}

				if (!$this->isAdmin) { //admin can clone task
					$this->verifyCanAddTask($db, $new_project_id);
				}

				$new_task_lead_id = $this->user->getId(); //if not admin
				if ($this->isAdmin) {
					$new_project_lead_ids = UserPermissionPeer :: getLeadUserIdsForProject($db, $new_project_id);
					if (empty ($new_project_lead_ids)) {
						throw new Exception(' Error while finding  project lead!');
					}
					$new_task_lead_id = $new_project_lead_ids[0];
				}

				$db->begin();
				$new_task_record = new TaskRecord($db);
				$new_task_record->setParentProjectId($new_project_id);
				$new_task_record->setDescription($old_task_record->getDescription());
				$new_task_record->setLeadId($new_task_lead_id);
				$new_task_record->setStatus(Constants :: TASK_OPEN);
				$new_task_record->setType(Constants :: TASK);
				$new_task_record->setName($old_task_record->getName());
				$new_task_record->setProgress($old_task_record->getProgress()); //indicative
				$new_task_record->setPriority($old_task_record->getPriority());
				$new_task_record->store();
				if ($old_task_record->getHasAttachment()) {
					$attchment_names = explode(':', $old_task_record->getAttachmentName());
					$attachment_folder = Util :: getAttachmentFolderName($config, Constants :: TASK_MESSAGE);
					$attached_file_names = Util :: createAttachmentCloneHelper($config, $new_task_record, $attchment_names, $attachment_folder, $old_project_id, $new_project_id);
					if ($attached_file_names) {
						$new_task_record->setAttachmentName($attached_file_names);
						$new_task_record->store();
					}
				}
				$this->cloneTaskMessages($db, $old_task_record, $new_task_record);
				//	UserPermissionPeer :: createPermissionRecord($db, $new_task_lead_id, Constants :: LEAD_TASK, $new_task_record->getType(),  $new_task_record->getId());
				UserPermissionPeer :: setLeadTaskPermission($db, $new_task_lead_id, $new_task_record->getId());

				//send message
				$message_record = new MessageRecord($db);
				$message_record->setType(Constants :: TASK_MESSAGE);

				$subject = 'New task [copied/cloned]';
				$message_record->setSubject($subject);
				$message_record->setCont("Task has been cloned/copied as [" . $new_task_record->getName() . "]");
				$message_record->setFromId($this->user->getId());
				$message_record->setTypeId($new_task_record->getId());
				$message_record->store();

				$db->commit();

				if ($this->isAdmin) { //admin cloned this task, send message to lead
					$to_user_id = $new_task_lead_id;
					$to_user = UserRecordPeer :: findByPK($db, $to_user_id);
					MessageBoardRecordPeer :: addMessageToMessageBoard($db, $message_record->getId(), $to_user_id);
					ActionHelper :: fireTaskMessageMail($this, $this->user, $to_user, $message_record, $new_task_record, '');
				} else { //lead cloned the task, send message to admin
					$admin = UserRecordPeer :: getAdminUser($db);
					$to_user_id = $admin->getId();
					MessageBoardRecordPeer :: addMessageToMessageBoard($db, $message_record->getId(), $to_user_id);
					if (!$this->isAdmin && ConfigRecordPeer :: getCopyMailsToAdmin($this)) {
						ActionHelper :: fireTaskMessageMail($this, $this->user, $admin, $message_record, $new_task_record, '');

					}

				}

			} catch (Exception $exception) {
				$db->rollback();
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		$new_args = array ();
		if ($this->has_error) {
			if (isset ($args['task_id']) && $args['task_id'] != '') {
				$new_args['task_id'] = $args['task_id'];
			}
			$this->setFlashMessage($this->error_message, true);
			$this->callModuleMethod('task', 'copyTask', $new_args);
		} else {
			$new_args['id'] = !is_null($new_task_record) ? $new_task_record->getId() : $args['task_id'];
			$this->setFlashMessage('Task copied');
			$this->callModuleMethod('task', 'view', $new_args);

		}
	}
	

	private function fireNewTaskMail($db, $lead, $to_user, $task_record, $target_file = '') {
		$config = $this->getConfig();
		$from_email = ConfigRecordPeer :: getFromEmailAddress($this);
		$website_name = ConfigRecordPeer :: getWebSiteName($this);

		if ($from_email != '') {
			list ($subject_template, $body_template) = Util :: getMailTemplateContents($config, Constants :: NEW_TASK_MAIL_TEMPLATE);

			$task_url = $this->getAbsoluteURLWithoutSession('/user/show/m/task/a/view/id/') . $task_record->getId() . '/u/' . $to_user->getId();

			$subject = Util :: getSubstitutedTaskTemplate($subject_template, $lead, $to_user, $task_url, $website_name, $task_record);

			$body = Util :: getSubstitutedTaskTemplate($body_template, $lead, $to_user, $task_url, $website_name, $task_record);

			$st = Util :: sendTextMail($from_email, $to_user->getEmail(), $subject, $body, $target_file);

		}
	}

	private function getTeamAndLeadSigninId($db, $task_record) {
		if ($task_record->getLeadId() && $task_record->getLeadId() != '') {
			$user_ids_assoc[$task_record->getLeadId()] = '';
		}

		$user_ids = array_keys($user_ids_assoc);
		require_once 'UserRecordPeer.php';
		$userid_signinid_assoc = UserRecordPeer :: getUserIdSigninIdAssoc($db, $user_ids);
		if (isset ($userid_signinid_assoc[$task_record->getLeadId()])) {
			$task_record->setLeadSigninId($userid_signinid_assoc[$task_record->getLeadId()]);
		} else {
			$task_record->setLeadSigninId('None');
		}
		return $task_record;
	}

	private function cloneTaskMessages($db, $old_task_record, $new_task_record) {
		$old_task_message_records = MessageRecordPeer :: getMessageRecords($db, null, '', constants :: TASK_MESSAGE, $old_task_record->getId(), '', '', 'asc');
		foreach ($old_task_message_records as $old_message_record) {

			$new_message_record = new MessageRecord($db);
			$new_message_record->setType(Constants :: TASK_MESSAGE);
			$new_message_record->setSubject($old_message_record->getSubject());
			$new_message_record->setFromId($old_message_record->getFromId());
			$new_message_record->setTypeId($new_task_record->getId());
			$new_message_record->setCont($old_message_record->getCont());
			$new_message_record->setStatus($old_message_record->getStatus());
			$new_message_record->setDate($old_message_record->getDate());
			//store $message_record as createAttachmentHelper needs an id
			$new_message_record->store();

			if ($old_message_record->getHasAttachment()) {
				$attchment_names = explode(':', $old_message_record->getAttachmentName());
				$attachment_folder = Util :: getAttachmentFolderName($this->getConfig(), Constants :: TASK_MESSAGE);
				$attached_file_names = Util :: createAttachmentCloneHelper($this->getConfig(), $new_message_record, $attchment_names, $attachment_folder, $old_task_record->getParentProjectId(), $new_task_record->getParentProjectId());
				if ($attached_file_names) {
					$new_message_record->setAttachmentName($attached_file_names);
					$new_message_record->store();
				}
			}
		}
		return;
	}
	private function makeAllTaskMessagesRead($db, $user_id, $task_record_id) {
		
		$read_message_ids = array();
		$message_records_db =  MessageRecordPeer :: getMessageRecords($db, null, Constants :: DELETED_MESSAGE, Constants :: TASK_MESSAGE, $task_record_id, '', '');
				foreach ($message_records_db as $message_record) {
					$read_message_ids[] = $message_record->getId();
				}
				
		if (!empty ($read_message_ids)) {
					MessageBoardRecordPeer :: setMessagesReadForUser($db, $read_message_ids, $user_id);

				}
	}
}
?>