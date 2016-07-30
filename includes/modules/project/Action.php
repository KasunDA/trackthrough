<?
require_once 'BaseController.php';
require_once 'FlexyView.php';
require_once 'Db.php';
require_once 'Constants.php';
require_once 'ProjectRecord.php';
require_once 'ProjectRecordPeer.php';
require_once 'TaskRecord.php';
require_once 'TaskRecordPeer.php';
require_once 'ConfigRecord.php';
require_once 'ConfigRecordPeer.php';
require_once 'MessageRecordPeer.php';
require_once 'MessageBoardRecordPeer.php';
require_once 'UserRecord.php';
require_once 'UserRecordPeer.php';
require_once 'ActionHelper.php';
require_once 'AppLogPeer.php';
require_once 'PreferenceRecord.php';
require_once 'PreferenceRecordPeer.php';
require_once 'BookmarkRecordPeer.php';
require_once 'UserPermissionPeer.php';
require_once 'IssuePeer.php';
require_once 'PageCollection.php';

class Action extends FW_BaseController {
	private function common($args) {
		$this->isAdmin = false;
		$this->isLanding = false;
		$this->company_name = ConfigRecordPeer :: getCompanyName($this);
		$this->version = Util :: getVersion();
		$this->message_f = $this->getFlashMessageObj();

		if ($this->getIsAuthorised()) {
			$user_id = $this->getParameter('USER_ID');
			$this->theme_color = $this->getParameter('THEME_COLOR');
			$this->theme_pallette = ActionHelper :: getThemePallette($this, $this->theme_color);
			$config = $this->getConfig();
			$this->user = UserRecordPeer :: findByConfigAndPK($config, $user_id);

			$this->isAdmin = $this->getParameter('is_admin');

			$this->record_count = MessageBoardRecordPeer :: getUnreadCountsForUser($config, $user_id);
			$this->unreadMessages = ($this->record_count > 0) ? true : false;
		}
	}

	function index($args) {
		$this->common($args);
		$this->projects = true;

		$config = $this->getConfig();
		//$meta_data = Util :: getMetaData('project', $config);
		$this->title = "Projects"; /* Abhilash 26-10-13 */

		$this->allowUser = true;
		// jl jl 03-01-2013
		$this->all_type_label = Constants :: ALL_TYPE;
		$this->project_task_records = array ();
		
		
		$this->high_priority = Constants :: HIGH_PRIORITY;
		$this->normal_priority = Constants :: NORMAL_PRIORITY;
		$this->low_priority = Constants :: LOW_PRIORITY;
		
		$this->task_open = Constants :: TASK_OPEN;
			$this->task_inprogress = Constants :: TASK_INPROGRESS;
			$this->task_review = Constants :: TASK_REVIEW_PENDING;
			$this->task_closed = Constants :: TASK_CLOSED;
			$this->issue_open = Constants :: ISSUE_OPEN;
			$this->issue_closed = Constants :: ISSUE_CLOSED;
		
		$hidden_project_ids = array();
		

		try {
			$db = Db :: getInstance($this->getConfig());
			$from = isset ($args['from']) ? $args['from'] : 0;
			$offset = $from;
			
			$limit = PreferenceRecordPeer :: getProjectsPerPage($this, $this->user->getId());

			$this->can_create_project = $this->getParameter('can_create_project');

			$this->task_type = Constants :: TASK;
			$this->issue_type = Constants :: ISSUE;

			if ($this->getParameter('can_perform_task')) {
				$this->allowUser = false;
			}
			$this->project_filter_id = $this->getParameter('call_from_api') ? false : PreferenceRecordPeer :: getProjectFilter($this, $this->user->getId());

			
			$hidden_project_ids_str = PreferenceRecordPeer::getHiddenProjectIds($this, $this->user->getId());
			if($hidden_project_ids_str) {
				$hidden_project_ids =@explode(",",$hidden_project_ids_str );
			}

			$this->project_filter_records = array ();
			if ($this->isAdmin) {
				$this->project_filter_records = ProjectRecordPeer :: getUserProjectRecords($db, '', '', true, '', array (), ProjectRecord :: NAME_COL, 'asc', $hidden_project_ids);
			} else {
				$permission_types = array (
					Constants :: ADD_ISSUE,
					Constants :: LEAD_PROJECT,
					Constants :: CAN_PERFORM_TASK
				);
				$this->project_filter_records = ProjectRecordPeer :: getUserProjectRecords($db, '', '', false, $this->user->getId(), $permission_types, ProjectRecord :: NAME_COL, 'asc', $hidden_project_ids);

			}

			$project_records = array ();

			if ($this->project_filter_id) {
				$obj = ProjectRecordPeer :: findByPK($db, $this->project_filter_id);
				if (!is_null($obj)) {
					$project_records[] = $obj;
				} else { //invalid project filter
					//change my preferences
					PreferenceRecordPeer :: setProjectFilter($this, $this->user->getId(), 0); //all
					$this->project_filter_id = 0;
				}

			}
			//DO NOT use else, when filter is invalid (e.g. deleted project), we should unset
			if (!$this->project_filter_id) {
				$project_records = ($this->isAdmin) ? ProjectRecordPeer :: getUserProjectRecords($db, $offset, $limit, true, '', array (), ProjectRecord :: UPDATED_AT_COL, 'desc', $hidden_project_ids) : ProjectRecordPeer :: getUserProjectRecords($db, $offset, $limit, false, $this->user->getId(), array (), ProjectRecord :: UPDATED_AT_COL, 'desc', $hidden_project_ids);
			}

			$project_records = ProjectRecordPeer :: getProjectRecordsWithSigninId($db, $project_records);

			if ($this->getParameter('call_from_api')) {
				return $project_records;
			}
			foreach ($project_records as $project_record) {
				$obj = new StdClass;
				/*$project_icons_folder = Util :: getAttachmentFolderName($config, $project_record->getType());
				if ($project_record->getIconName() != null) {
					$project_icon_name = $project_record->getIconName();
					$obj->project_icon = $config->getValue('FW', 'base_url') . '/' . $project_icons_folder . $project_record->getId() . '/' . $project_icon_name;
				} else {
					$project_icon_name = 'default.png';
					$obj->project_icon = $this->getAbsoluteImageURL('') . '/' . $project_icon_name;
				}*/

				$obj->project_icon = ActionHelper :: getProjectIconURL($this, $project_record);

				$obj->is_project_visible = PreferenceRecordPeer :: getIsProjectVisible($this, $this->user->getId(), $project_record->getId());
				$obj->project_record = $this->updateProjectNameAndShortDescription($project_record);

				$obj->can_delete_project = (($this->isAdmin) || ($project_record->getLeadId() == $this->user->getId())) ? true : false;

				$obj->task_records = array ();

				if ($obj->is_project_visible) {
					$task_status_filter = PreferenceRecordPeer :: getTaskStatusFilter($this, $this->user->getId(), $project_record->getId());
					$task_sort_order = PreferenceRecordPeer :: getTaskSortOrder($this, $this->user->getId(), $project_record->getId());
					$issue_sort_order = PreferenceRecordPeer :: getIssueSortOrder($this, $this->user->getId(), $project_record->getId());
					
					
					$task_priority_filter =  PreferenceRecordPeer :: getTaskPriorityFilter($this, $this->user->getId(), $project_record->getId());
			$issue_priority_filter =  PreferenceRecordPeer :: getIssuePriorityFilter($this, $this->user->getId(), $project_record->getId());
			

					$obj->is_task_filter_all = ($task_status_filter == '') ? true : false;
					$obj->is_task_filter_open = ($task_status_filter == Constants :: TASK_OPEN) ? true : false;
					$obj->is_task_filter_review = ($task_status_filter == Constants :: TASK_REVIEW_PENDING) ? true : false;
					$obj->is_task_filter_inprogress = ($task_status_filter == Constants :: TASK_INPROGRESS) ? true : false;
					$obj->is_task_filter_closed = ($task_status_filter == Constants :: TASK_CLOSED) ? true : false;
					$obj->task_sort_order =  ($task_sort_order == 'asc') ? 'asc': 'desc';
					$obj->issue_sort_order =  ($issue_sort_order == 'asc') ? 'asc': 'desc';
					
					
						$obj->is_task_priority_filter_all = ($task_priority_filter == '') ? true : false;
						$obj->is_task_priority_filter_high = ($task_priority_filter == Constants :: HIGH_PRIORITY) ? true : false;
					$obj->is_task_priority_filter_medium = ($task_priority_filter == Constants :: NORMAL_PRIORITY) ? true : false;
					$obj->is_task_priority_filter_low = ($task_priority_filter == Constants :: LOW_PRIORITY) ? true : false;
			
			
				$obj->is_issue_priority_filter_all = ($issue_priority_filter == '') ? true : false;
						$obj->is_issue_priority_filter_high = ($issue_priority_filter == Constants :: HIGH_PRIORITY) ? true : false;
					$obj->is_issue_priority_filter_medium = ($issue_priority_filter == Constants :: NORMAL_PRIORITY) ? true : false;
					$obj->is_issue_priority_filter_low = ($issue_priority_filter == Constants :: LOW_PRIORITY) ? true : false;
			
			
			

					$is_lead_project = false;
					$is_team_project = false;
					if ($this->isAdmin) {
						$obj->task_access_permission = true;
					} else {

						$is_lead_project = UserPermissionPeer :: canLeadProject($db, $this->user->getId(), $project_record->getId());
						$is_team_project = UserPermissionPeer :: canExecProject($db, $this->user->getId(), $project_record->getId());
						$obj->task_access_permission = $this->isAdmin || $is_lead_project || $is_team_project;
					}
					if ($obj->task_access_permission) {
						$access_all_tasks = ($this->isAdmin || $is_lead_project) ? true : false;
						if ($this->isAdmin) {
							$obj->task_records = TaskRecordPeer :: filterProjectTasks($db, $project_record->getId(), $access_all_tasks, $this->user->getId(), $task_status_filter, $task_priority_filter, $task_sort_order);
						} else {
							$obj->task_records = TaskRecordPeer :: filterProjectTasks($db, $project_record->getId(), $access_all_tasks, $this->user->getId(), $task_status_filter, $task_priority_filter, $task_sort_order);
						}

						$obj->task_records = TaskRecordPeer :: getTaskRecordsWithSigninId($db, $obj->task_records);
						//	$obj->task_records = $this->updateTaskNameAndShortDescription($obj->task_records);

						//$obj->task_records = ActionHelper :: updateTaskDisplayDetails($this, $obj->task_records, 108);
						$obj->task_records = ActionHelper :: updateTaskDisplayDetails($this, $obj->task_records, 70);/* megha 10.1.15*/

						$obj->can_add_task = $is_lead_project ? true : false;
						$obj->can_delete_task = (($this->isAdmin) || $is_lead_project /*($project_record->getLeadId() == $this->user->getId())*/
						) ? true : false;
						$obj->count_tasks = count($obj->task_records);
						$obj->no_tasks = empty ($obj->task_records);
						$obj->is_task_visible = PreferenceRecordPeer :: getIsTaskVisible($this, $this->user->getId(), $project_record->getId());

					}

					//issues				
					$obj->issue_records = array ();
					if ($project_record->isIssueTrackingEnabled()) {
						$can_add_issue = UserPermissionPeer :: canAddIssue($db, $this->user->getId(), $project_record->getId());

						$obj->add_issue_permission = ($this->isAdmin || $can_add_issue) ? true : false;
						if ($obj->add_issue_permission) {
							$issue_status_filter = PreferenceRecordPeer :: getIssueStatusFilter($this, $this->user->getId(), $project_record->getId());
							$obj->is_issue_all = ($issue_status_filter == '') ? true : false;
							$obj->is_issue_open = ($issue_status_filter == Constants :: ISSUE_OPEN) ? true : false;
							$obj->is_issue_closed = ($issue_status_filter == Constants :: ISSUE_CLOSED) ? true : false;

							$obj->issue_records = IssuePeer :: getProjectIssues($db, array (
								$project_record->getId()
							), $issue_status_filter, $issue_priority_filter,false, $issue_sort_order );
							$obj->issue_records = IssuePeer :: getIssueWithUserName($db, $obj->issue_records);
							$obj->issue_records = $this->updateIssueTitle($obj->issue_records);
							//$obj->issue_records = ActionHelper :: updateIssueDisplayDetails($this, $obj->issue_records, 110);
				           // $obj->issue_records = ActionHelper :: updateIssueDisplayDetails($this, $obj->issue_records, 90); /* Abhilash 6.1.15 */ 
				          //  $obj->issue_records = ActionHelper :: updateIssueDisplayDetails($this, $obj->issue_records, 80); /* megha 10.1.15 */ 
				              $obj->issue_records = ActionHelper :: updateIssueDisplayDetails($this, $obj->issue_records, 70); /* megha 19.1.15 */ 
				            

							

							$obj->count_issues = count($obj->issue_records);

							$obj->no_issues = empty ($obj->issue_records);
							$obj->is_issue_visible = PreferenceRecordPeer :: getIsIssueVisible($this, $this->user->getId(), $project_record->getId());
						}
					}
				}
				$this->project_task_records[] = $obj;
			} // end of foreach 

		} catch (Exception $exception) {
			$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
		}
		if ($this->getParameter('call_from_api')) {
			if ($this->has_error) {
				throw new Exception($this->error_message);
			}

		}
		$this->no_records = empty ($this->project_task_records) ? true : false;
		//pagination
		///////////////////////////		
		if ($this->project_filter_id) {
			$total_records = 1;
		} else {
			$total_records = ($this->isAdmin) ? ProjectRecordPeer :: countUserProjectRecords($db, true, '', $hidden_project_ids) : ProjectRecordPeer :: countUserProjectRecords($db, false, $this->user->getId(), $hidden_project_ids);

		}
		$this->pc = new PageCollection($args, $limit, $total_records, $this->getAbsoluteURL('/project/index'));

		return new FlexyView('project/indexProject.html', $this);
	}

	function delete($args) {
		$this->common($args);
		$this->projects = true;

		if (!isset ($args['project_id'])) {
			$this->appendErrorMessage('Project id is not set! ');
		}
		$config = $this->getConfig();
		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($config);
				$project_record = ProjectRecordPeer :: findByPK($db, $args['project_id']);

				if ($project_record == null) {
					throw new Exception(' could not find dataset!');
				}

				if (!$this->isAdmin && $project_record->getLeadId() != $this->user->getId()) {
					throw new Exception(' authorization required!');
				}
				$n_project_tasks = TaskRecordPeer :: getNumberOfAssignedTasks($db, $args['project_id']);

				if ($n_project_tasks > 0) {
					throw new Exception(' you can not delete a project when it has tasks assigned to users.');
				}
				$task_records = TaskRecordPeer :: getProjectTasks($db, $project_record->getId());

				foreach ($task_records as $task_record) {
					Util :: deleteTaskAttachments($config, $task_record);
					$message_records_db = MessageRecordPeer :: getMessageRecords($db, null, '', Constants :: TASK_MESSAGE, $task_record->getId());
					foreach ($message_records_db as $message_record) {
						MessageBoardRecordPeer :: deleteUserMessageBoardRecord($config, $message_record, '');
					}
					$bookmarks = BookmarkRecordPeer :: getCategoryBookmarks($db, $task_record->getType(), $task_record->getId());
					if (!empty ($bookmarks)) {
						$ids_for_delete = array ();
						foreach ($bookmarks as $bookmark) {
							$ids_for_delete[] = $bookmark->getId();
						}
						BookmarkRecordPeer :: deleteBookmarks($db, $ids_for_delete);
					}
					TaskRecordPeer :: deleteTask($db, $task_record->getId());
					//UserPermissionPeer :: unsetContentPermission($db, '', '',$task_record->getType(), $task_record->getId());
					UserPermissionPeer :: unsetTaskPermissions($db, $task_record->getId());
				}

				$issue_records = IssuePeer :: getProjectIssues($db, array (
					$project_record->getId()
				));
				if ($issue_records != null) {
					foreach ($issue_records as $issue_record) {
						Util :: deleteIssueAttachments($config, $issue_record);
						$message_records_db = MessageRecordPeer :: getMessageRecords($db, null, '', Constants :: ISSUE_MESSAGE, $issue_record->getId());
						foreach ($message_records_db as $message_record) {
							MessageBoardRecordPeer :: deleteUserMessageBoardRecord($config, $message_record, '');
						}
						$bookmarks = BookmarkRecordPeer :: getCategoryBookmarks($db, $issue_record->getType(), $issue_record->getId());
						if (!empty ($bookmarks)) {
							$ids_for_delete = array ();
							foreach ($bookmarks as $bookmark) {
								$ids_for_delete[] = $bookmark->getId();
							}
							BookmarkRecordPeer :: deleteBookmarks($db, $ids_for_delete);
						}
						//UserPermissionPeer :: unsetContentPermission($db, '', '',$issue_record->getType(), $issue_record->getId());
						UserPermissionPeer :: unsetIssuePermissions($db, $issue_record->getId());
						IssuePeer :: deleteIssue($db, $issue_record->getId());
					}
				}
				//$message_records_db =MessageRecordPeer :: getProjectMessageRecords($db, '', $project_record->getId());
				$message_records_db = MessageRecordPeer :: getMessageRecords($db, null, '', Constants :: PROJECT_MESSAGE, $project_record->getId());
				foreach ($message_records_db as $message_record) {
					MessageBoardRecordPeer :: deleteUserMessageBoardRecord($config, $message_record, '');
				}
				$attachment_name = $project_record->getAttachmentName();
				$bookmarks = BookmarkRecordPeer :: getCategoryBookmarks($db, $project_record->getType(), $project_record->getId());
				if (!empty ($bookmarks)) {
					$ids_for_delete = array ();
					foreach ($bookmarks as $bookmark) {
						$ids_for_delete[] = $bookmark->getId();
					}
					BookmarkRecordPeer :: deleteBookmarks($db, $ids_for_delete);
				}
				if ($attachment_name != null) {
					Util :: deleteProjectAttachments($config, $project_record);
				}
				$project_icon_name[] = $project_record->getIconName();
				if ($project_icon_name != null) {
					Util :: deleteAttachments(Constants :: PROJECT_MESSAGE, $config, $project_icon_name);
				}
				UserPermissionPeer :: unsetProjectPermissions($db, $project_record->getId());

				//UserPermissionPeer :: unsetContentPermission($db, '','', $project_record->getType(), $project_record->getId());
				ProjectRecordPeer :: deleteProject($db, $project_record->getId());
				PreferenceRecordPeer :: setProjectFilter($this, $this->user->getId(), 0); //all

				//if admin deletes, send mail to lead. if lead deletes send mails to admin.
				if ($this->isAdmin) {
					$to_user = UserRecordPeer :: findByPK($db, $project_record->getLeadId());
				} else {
					$to_user = UserRecordPeer :: getAdminUser($db);
				}
				$this->fireProjectDeleteMail($this->user, $to_user, $project_record->getName());

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
		$this->callModuleMethod('project', 'index', $new_args);
	}

	function view($args) {
		$this->common($args);
		$this->projects = true;
		$this->project_attachment_names = array ();
		$this->all_type_label = Constants :: ALL_TYPE;
		$this->task_open = Constants :: TASK_OPEN;
		$this->task_inprogress = Constants :: TASK_INPROGRESS;
		$this->task_review = Constants :: TASK_REVIEW_PENDING;
		$this->task_closed = Constants :: TASK_CLOSED;
		$this->issue_open = Constants :: ISSUE_OPEN;
		$this->issue_closed = Constants :: ISSUE_CLOSED;
		
		
		$this->high_priority = Constants :: HIGH_PRIORITY;
		$this->normal_priority = Constants :: NORMAL_PRIORITY;
		$this->low_priority = Constants :: LOW_PRIORITY;
		
		
		$config = $this->getConfig();
		//$meta_data = Util :: getMetaData('project view', $config);
		$this->title = "Project View"; /* Abhilash 26-10-13 */

		$this->project_record = null;
		$this->task_records = $this->issue_records = $this->message_records = array ();
		$this->project_attachments = array ();

		if (!isset ($args['id']) || $args['id'] == '') {
			$this->appendErrorMessage('Error, could not find dataset! ');
		}
		if (!$this->has_error) {
			try {
				$config = $this->getConfig();
				$db = Db :: getInstance($config);
				$this->project_record = ProjectRecordPeer :: findByPK($db, $args['id']);

				if ($this->project_record == null) {
					throw new Exception(' could not find dataset!');
				}
				if (!ActionHelper :: isAuthorizedProject($this, $this->project_record)) {

					$this->makeAllProjectMessagesRead($db, $this->user->getId(), $args['id']);
					throw new Exception('you are not authorized to view this Project!');
				}
				///////////////
				$task_status_filter = $this->getParameter('call_from_api') ? '' : PreferenceRecordPeer :: getTaskStatusFilter($this, $this->user->getId(), $this->project_record->getId());
				$task_sort_order = $this->getParameter('call_from_api') ? '' : PreferenceRecordPeer :: getTaskSortOrder($this, $this->user->getId(), $this->project_record->getId());
				$issue_sort_order = $this->getParameter('call_from_api') ? '' : PreferenceRecordPeer :: getIssueSortOrder($this, $this->user->getId(), $this->project_record->getId());
				
				$task_priority_filter = $this->getParameter('call_from_api') ? '' : PreferenceRecordPeer :: getTaskPriorityFilter($this, $this->user->getId(), $this->project_record->getId());
				$issue_priority_filter = $this->getParameter('call_from_api') ? '' : PreferenceRecordPeer :: getIssuePriorityFilter($this, $this->user->getId(), $this->project_record->getId());
			
			

				$this->project_record->is_task_filter_all = ($task_status_filter == '') ? true : false;
				$this->project_record->is_task_filter_open = ($task_status_filter == Constants :: TASK_OPEN) ? true : false;
				$this->project_record->is_task_filter_review = ($task_status_filter == Constants :: TASK_REVIEW_PENDING) ? true : false;
				$this->project_record->is_task_filter_inprogress = ($task_status_filter == Constants :: TASK_INPROGRESS) ? true : false;
				$this->project_record->is_task_filter_closed = ($task_status_filter == Constants :: TASK_CLOSED) ? true : false;
				$this->project_record->task_sort_order =  ($task_sort_order == 'asc') ? 'asc': 'desc';
				$this->project_record->issue_sort_order =  ($issue_sort_order == 'asc') ? 'asc': 'desc';
				
				
					$this->project_record->is_task_priority_filter_all = ($task_priority_filter == '') ? true : false;
						$this->project_record->is_task_priority_filter_high = ($task_priority_filter == Constants :: HIGH_PRIORITY) ? true : false;
					$this->project_record->is_task_priority_filter_medium = ($task_priority_filter == Constants :: NORMAL_PRIORITY) ? true : false;
					$this->project_record->is_task_priority_filter_low = ($task_priority_filter == Constants :: LOW_PRIORITY) ? true : false;
			
				$this->project_record->is_issue_priority_filter_all = ($issue_priority_filter == '') ? true : false;
						$this->project_record->is_issue_priority_filter_high = ($issue_priority_filter == Constants :: HIGH_PRIORITY) ? true : false;
					$this->project_record->is_issue_priority_filter_medium = ($issue_priority_filter == Constants :: NORMAL_PRIORITY) ? true : false;
					$this->project_record->is_issue_priority_filter_low = ($issue_priority_filter == Constants :: LOW_PRIORITY) ? true : false;
			
			
			
				

				$issue_status_filter = $this->getParameter('call_from_api') ? '' : PreferenceRecordPeer :: getIssueStatusFilter($this, $this->user->getId(), $this->project_record->getId());
				$this->project_record->is_issue_all = ($issue_status_filter == '') ? true : false;
				$this->project_record->is_issue_open = ($issue_status_filter == Constants :: ISSUE_OPEN) ? true : false;
				$this->project_record->is_issue_closed = ($issue_status_filter == Constants :: ISSUE_CLOSED) ? true : false;

				////////////

				if ($this->project_record->getLeadId() && $this->project_record->getLeadId() != '') {
					$user_ids_assoc[$this->project_record->getLeadId()] = '';
				}
				$user_ids = array_keys($user_ids_assoc);
				require_once 'UserRecordPeer.php';
				$userid_signinid_assoc = UserRecordPeer :: getUserIdSigninIdAssoc($db, $user_ids);

				if (isset ($userid_signinid_assoc[$this->project_record->getLeadId()])) {
					$this->project_record->setLeadSigninId($userid_signinid_assoc[$this->project_record->getLeadId()]);

				} else {
					$this->project_record->setLeadSigninId('None');
				}

				if ($this->project_record == null) {
					throw new Exception(' could not find dataset, invalid project id!');
				}

				$name = wordwrap($this->project_record->getName(), 100, "\n", 1);
				$this->project_record->setName($name);

				$description = nl2br($this->project_record->getDescription());
				$this->project_record->setDescription(wordwrap($description, 105, "\n", 1));

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
				$attchment_names = explode(':', $this->project_record->getAttachmentName());
				$this->file_missing = false;

				$project_attachment_names = Util :: getExistingAttachments($config, $attchment_names, $this->project_record->getId(), Constants :: PROJECT_MESSAGE);

				if (count($attchment_names) > 0) {

					foreach ($project_attachment_names as $attachment_name) {
						if ($attachment_name != null) {
							$attachment_icon = Util :: getAttachmentIcon($config, $attachment_name);
							$obj = new StdClass;
							if ($attachment_icon == 'image.png') {
								$obj->is_image = true;
								$attachment_name_with_prefix = Util :: getAttachmentNamePrefix($this->project_record) . $attachment_name;
							
								$obj->image_path = Util :: getAttachmentURL($config, $this->project_record->getId(), $attachment_name_with_prefix);
								
							}
							$obj->attachment_name = $attachment_name;
							$obj->attachment_icon = 'attachment_icons' . '/' . $attachment_icon;
							$this->project_attachments[] = $obj;
						} else {
							$this->file_missing = true;
						}
					}
				}
				$project_id = $this->project_record->getId();

				$this->isBookmarked = BookmarkRecordPeer :: getIsBookmarked($db, $this->user->getId(), $this->project_record->getType(), $project_id);
				//$this->isBookmarked = ($bookmark != null) ? true : false;

				$message_records_db = $this->isAdmin ? MessageRecordPeer :: getMessageRecords($db, null, '', Constants :: PROJECT_MESSAGE, $this->project_record->getId()) : MessageRecordPeer :: getMessageRecords($db, null, Constants :: DELETED_MESSAGE, Constants :: PROJECT_MESSAGE, $this->project_record->getId());
				$message_records_with_uname = MessageRecordPeer :: getMessageRecordsWithUserName($db, $message_records_db, $this->user->getId());

				$this->msg_attachment_missing = false;
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
					$message = nl2br($obj->m_record->getCont());
					$obj->m_record->setCont(wordwrap($message, 98, "\n", 1));

					$obj->isCommentBookmarked = BookmarkRecordPeer :: getIsBookmarked($db, $this->user->getId(), $message_record->getType(), $message_record->getId());
					//	$obj->isCommentBookmarked = ($bookmark != null) ? true : false;

					$obj->msg_attachment_missing = false;

					$msg_attchment_names = explode(':', $message_record->getAttachmentName());
					$msg_attchment_names = Util :: getExistingAttachments($config, $msg_attchment_names, $message_record->getTypeId(), Constants :: PROJECT_MESSAGE);
					if (count($msg_attchment_names) > 0) {
						$m_attachment_names = array ();
						$msg_attachment_missing = false;

						foreach ($msg_attchment_names as $msg_attachment_name) {
							if ($msg_attachment_name != null) {
								$attachment_icon = Util :: getAttachmentIcon($config, $msg_attachment_name);
								$attachment_obj = new StdClass;
								if ($attachment_icon == 'image.png') {
									$attachment_obj->is_image = true;
									$attachment_name_with_prefix = Util :: getAttachmentNamePrefix($message_record) . $msg_attachment_name;
									$attachment_thumb = "thumb_" . $attachment_name_with_prefix;
									$attachment_obj->image_path = Util :: getAttachmentURL($config, $this->project_record->getId(), $attachment_name_with_prefix);
									$attachment_obj->thumb_path = Util :: getAttachmentURL($config, $this->project_record->getId(), $attachment_thumb);
								}
								$attachment_obj->attachment_name = $msg_attachment_name;
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
				$this->task_type = Constants :: TASK;

				if ($this->can_access_tasks) {
					$this->is_task_visible = PreferenceRecordPeer :: getIsTaskVisible($this, $this->user->getId(), $this->project_record->getId());
					$this->is_compose_desc_visible = PreferenceRecordPeer :: getIsComposeProjectDescVisible($this, $this->user->getId(), $this->project_record->getId()); /* Abhilash 22.12.14 */
					//$access_all_tasks = ($this->isAdmin || $this->project_record->getPermission() == Constants :: LEAD_PROJECT) ? true : false;
					$access_all_tasks = ($this->isAdmin || $this->lead_project) ? true : false;
					$this->task_records = ($this->isAdmin) ? TaskRecordPeer :: filterProjectTasks($db, $this->project_record->getId(), $access_all_tasks, $this->user->getId(), $task_status_filter, $task_priority_filter,$task_sort_order) : TaskRecordPeer :: filterProjectTasks($db, $this->project_record->getId(), $access_all_tasks, $this->user->getId(), $task_status_filter,$task_priority_filter,$task_sort_order);
					$this->task_records = TaskRecordPeer :: getTaskRecordsWithSigninId($db, $this->task_records);

					$this->task_records = ActionHelper :: updateTaskDisplayDetails($this, $this->task_records, 64);

					//$this->task_records = $this->updateTaskNameAndShortDescription($this->task_records);
				}
				$this->issue_tracking = $this->project_record->isIssueTrackingEnabled();
				$this->issue_type = Constants :: ISSUE;

				if ($this->issue_tracking) {
					$this->add_team_to_track_issues = ($this->isAdmin || $this->lead_project) ? true : false;

					//For lead and admin - All team records
					if ($this->add_team_to_track_issues) {
						//Existing issue tracking permitted user records 
						//$issue_tracking_members = UserPermissionPeer :: getAddIssuePermission($db, $this->project_record->getId());

						$issue_tracking_user_ids = UserPermissionPeer :: getIssueTrackingUserIds($db, $this->project_record->getId());

						//Get all teams					
						$this->teams_array = array ();
						$this->issue_tacking_teams = array (); //existing
						$this->can_view_issues = true;

						$new_team_ids = UserRecordPeer :: getNonAdminUserIds($config, $issue_tracking_user_ids);

						if (!empty ($new_team_ids)) {
							$this->teams_array = UserRecordPeer :: getUserRecordsWithSigninId($db, $new_team_ids);
						}
						if (!empty ($issue_tracking_user_ids)) {
							$count = 1;
							foreach ($issue_tracking_user_ids as $issue_tracking_user_id) {
								$team_record = UserRecordPeer :: findByPK($db, $issue_tracking_user_id);
								if ($team_record != null) {
									$obj = new StdClass;
									$obj->id = $team_record->getId();
									$obj->signinId = $team_record->getSigninId();
									$obj->isLead = UserPermissionPeer :: canLeadProject($db, $issue_tracking_user_id, $this->project_record->getId());
									$obj->count_end = (count($issue_tracking_user_ids) == $count) ? true : false;
									$this->issue_tacking_teams[] = $obj;
									$count++;
								}
							}
						}
						$this->has_issue_tracking_team = (count($this->issue_tacking_teams) > 0) ? true : false;

					}
					$can_add_issue = UserPermissionPeer :: canAddIssue($db, $this->user->getId(), $this->project_record->getId());

					$this->add_issue_permission = ($this->isAdmin || $can_add_issue) ? true : false;
					if ($this->add_issue_permission) {
						$issue_records = IssuePeer :: getProjectIssues($db, array (
							$this->project_record->getId()
						), $issue_status_filter, $issue_priority_filter, false, $issue_sort_order);
						$this->issue_records = IssuePeer :: getIssueWithUserName($db, $issue_records);
						$this->issue_records = ActionHelper :: updateIssueDisplayDetails($this, $this->issue_records);

						$this->is_issue_visible = PreferenceRecordPeer :: getIsIssueVisible($this, $this->user->getId(), $this->project_record->getId());

					}
				}
				$this->no_task_records = empty ($this->task_records) ? true : false;
				$this->count_task = count($this->task_records);

				$this->no_issue_records = empty ($this->issue_records) ? true : false;
				$this->count_issues = count($this->issue_records);

				$this->no_message_records = empty ($this->message_records) ? true : false;

			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}

		}

		if ($this->getParameter('call_from_api')) {
			if ($this->has_error) {
				throw new Exception($this->error_message);
			}
			$o = new StdClass;
			$o->project_record = $this->project_record;
			if ($o->project_record) {
				$o->project_record->attachments = $this->project_attachments;
			}
			$o->task_records = $this->task_records;
			$o->issue_records = $this->issue_records;
			$o->message_records = $this->message_records;

			return $o;

		}
		$new_args = array ();
		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
			$this->callModuleMethod('project', 'index', $new_args);
		}
		return new FlexyView('project/viewProject.html', $this);
	}

	function message($args) {
		$this->common($args);
		$config = $this->getConfig();
		if (isset ($args['description'])) {
			$args['description'] = strip_tags($args['description']);
		}

		if (!isset ($args['project_id']) || $args['project_id'] == '') {
			$this->appendErrorMessage(' Project id not set! ');
		}
		if (!isset ($args['subject'])) {
			//$this->appendErrorMessage(' Error, Subject not set! ');
			$args['subject'] = '';
		}
		if (!isset ($args['description']) || $args['description'] == '') {
			$this->appendErrorMessage(' Project description can not be blank! ');
		}
		if (!ActionHelper :: getIsValidAttachment($this)) {
			$this->appendErrorMessage('Unsupported  attachment; allowed formats ' . implode("|", ActionHelper :: getValidAttachmentTypes($this)));
		}
		$from_page = isset ($args['from_page']) ? $args['from_page'] : 'view_project';
		$message_record = null;

		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($config);
				$project_record = ProjectRecordPeer :: findByPK($db, $args['project_id']);
				if ($project_record == null) {
					throw new Exception(' error while retrieving project record!');
				}

				//$permission_record = UserPermissionPeer :: findContentPermission($db, $this->user->getId(), constants :: PROJECT, $project_record->getId());
				//$user_project_permission = ($permission_record != null) ? $permission_record->getPermission() : null;

				$can_lead_project = UserPermissionPeer :: canLeadProject($db, $this->user->getId(), $project_record->getId());

				//if ($user_project_permission == null) {
				//	throw new Exception('you are not authorized to view this Project!');
				//}
				//if ($user_project_permission && ($user_project_permission != Constants :: LEAD_PROJECT)) {
				//	throw new Exception('you are not authorized to send description!');
				//}
				if (!$can_lead_project) {
					throw new Exception('you are not authorized to send message!');
				}

				$message_record = new MessageRecord($db);
				$message_record->setType(Constants :: PROJECT_MESSAGE);

				$message_record->setSubject($args['subject']);
				$message_record->setCont($args['description']);
				$message_record->setFromId($this->user->getId());
				$message_record->setTypeId($args['project_id']);

				//$message_record->setToId(0);
				//store $message_record as createAttachmentHelper needs an id
				$message_record->store();

				$attachment_folder = Util :: getAttachmentFolderName($config, $message_record->getType());
				$attach_log_msg = Util :: createAttachmentHelper($message_record, $attachment_folder, $project_record->getId());

				$target_file = '';
				if ($attach_log_msg != '') { //has attachmet
					$from_signin_id = UserRecordPeer :: getSigninId($db, $message_record->getFromId());
					$target_file = Util :: getAttachmentFilePath($from_signin_id, $message_record->getAttachmentName(), $attachment_folder);
				}
				//retrieve project
				//project description message for any one
				//$message_record->setToId(0);

				//$message_record->store(); ?? why this again

				$to_user_ids = UserPermissionPeer :: findContentPermittedUserIds($db, Constants :: PROJECT, $project_record->getId());

				ActionHelper :: sendProjectMessage($this, $to_user_ids, $project_record, $message_record);

				if (!$this->has_error) {
					$project_record->setUpdatedAt(date('Y-m-d H:i:s'));
					$project_record->store();
				}
			} catch (Exception $exception) {

				if ($message_record != null) {
					$message_id = $message_record->getId();
					MessageRecordPeer :: deleteMessage($db, $message_id);
				}
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		if ($this->getParameter('call_from_api')) {
			if ($this->has_error) {
				throw new Exception($this->error_message);
			} else {
				return is_null($message_record) ? false : $message_record->getId();
			}
		}
		$new_args = array ();
		if ($this->has_error) {
			$new_args['id'] = !isset ($args['project_id']) ? '' : $args['project_id'];
			$this->setFlashMessage($this->error_message, true);
			$this->callModuleMethod('project', 'view', $new_args);
		}
		$new_args['id'] = $args['project_id'];
		//$this->callModuleMethod('project', 'view', $new_args);
		if ($from_page == 'index_project') {
			$this->callModuleMethod('project', 'index', $new_args);
		} else {
			$this->callModuleMethod('project', 'view', $new_args);
		}
	}

	function download($args) {
		$this->common($args);

		if (!isset ($args['project_id']) || $args['project_id'] == '') {
			$this->appendErrorMessage('Error, attachment_location undefined!');
		}
		if (!isset ($args['file_name']) || $args['file_name'] == '') {
			$this->appendErrorMessage('Error, file name undefined!');
		}
		$config = $this->getConfig();
		if (!$this->has_error) {
			try {
				$project_id = $args['project_id'];

				$db = Db :: getInstance($this->getConfig());
				$project_record = ProjectRecordPeer :: findByPK($db, $project_id);
				if ($project_record == null) {
					throw new Exception(' could not find dataset, invalid project id!');
				}
				if (!ActionHelper :: isAuthorizedProject($this, $project_record)) {
					throw new Exception(' you are not authorized to download the content!');
				}
				$project_name = Util :: truncate($project_record->getName(), 30, '');
				$attachment_name = Util :: getAttachmentNamePrefix($project_record) . $args['file_name'];

				$attachment_folder = Util :: getAttachmentFolderName($config, Constants :: PROJECT_MESSAGE);
				$target_file = Util :: getAttachmentFilePath($project_record->getId(), $attachment_name, $attachment_folder);
				
				
				if (isset ($args['thumb']) && $args['thumb']) { //additional size prefix in future
				
					
					$target_file = Util::getAttachmentThumbFile ($args['thumb'],$attachment_name, $attachment_folder, $target_file, $project_record );
				}
				
				
				
				
				if (file_exists($target_file)) {
					Util :: downloadFile($target_file);
					return;
				} else {
					$this->appendErrorMessage('There was an error, no such attachemnt exists!');
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

	function edit($args) {
		$this->common($args);
		$this->projects = true;
		$config = $this->getConfig();
		//$meta_data = Util :: getMetaData('project create', $config);
		$this->title = "Edit Project";

		if (!isset ($args['project_id'])) {
			$this->appendErrorMessage('Error, project id is undefined!');
		}
		$this->edit_action = true;

		if (!$this->getParameter('can_create_project')) {
			$this->appendErrorMessage('Error, authorization required, please signin using appropriate credentials!');
		}
		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($this->getConfig());
				$this->project_record = ProjectRecordPeer :: findByPK($db, $args['project_id']);
				if ($this->project_record == null) {
					throw new Exception(' could not find project dataset!');
				}

				if (!ActionHelper :: isAuthorizedProject($this, $this->project_record)) {
					throw new Exception('you are not authorized to edit this Project!');

				}
				if (!$this->lead_project) {
					throw new Exception('you are not authorized to edit this Project!');
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

		return new FlexyView('project/editProject.html', $this);

	}

	function create($args) {
		$this->common($args);
		$this->projects = true;
		$config = $this->getConfig();
		//$meta_data = Util :: getMetaData('project create', $config);
		$this->title = "Add Project"; /* Abhilash 26-10-13 */
		if (!$this->getParameter('can_create_project')) {
			$this->appendErrorMessage('Error, authorization required, please signin using appropriate credentials!');
		}
		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($this->getConfig());
				$this->project_record = new ProjectRecord(null);
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

		return new FlexyView('project/editProject.html', $this);
	}

	function update($args) {
		$this->common($args);
		$this->projects = true;
		$config = $this->getConfig();

		if (!$this->getParameter('can_create_project')) {
			$this->appendErrorMessage('Error, authorization required, please signin using appropriate credentials!');
			return new FlexyView('error.html', $this);
		}

		if (isset ($args['name'])) {
			$args['name'] = strip_tags($args['name']);
		}
		if (isset ($args['project_description'])) {
			$args['project_description'] = strip_tags($args['project_description']);

		}
		if (!isset ($args['name']) || $args['name'] == '') {
			$this->appendErrorMessage('Project title can not be blank!<br />');
		}
		if (!isset ($args['project_description']) || $args['project_description'] == '') {
			$this->appendErrorMessage('Project description can not be blank!');
		}
		if (!ActionHelper :: getIsValidAttachment($this)) {
			$this->appendErrorMessage('Unsupported  attachment; allowed formats ' . implode("|", ActionHelper :: getValidAttachmentTypes($this)));
		}
		if (isset ($_FILES['uploaded_icon']) && $_FILES['uploaded_icon']['name'] != "") {
			$project_icon = $_FILES['uploaded_icon'];
			//$isImage = Util :: isImageAttachment($project_icon['name']);
			$isImage = Util :: isValidImageFile($_FILES['uploaded_icon']['tmp_name'], Util :: getPermittedImageTypes());

			if (!$isImage) {
				$this->appendErrorMessage('Invalid project icon');
			}
		}

		$project_description = isset ($args["project_description"]) ? $args["project_description"] : '';
		$name = isset ($args["name"]) ? $args["name"] : '';
		$edit_action = isset ($args["project_id"]) && $args["project_id"] ? true : false;
		$this->project_record = null;

		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($config);
				$db->begin();
				$this->project_record = new ProjectRecord($db);

				if ($edit_action) {
					$this->project_record = ProjectRecordPeer :: findByPK($db, $args["project_id"]);
					if ($this->project_record == null) {
						throw new Exception(' could not find task dataset!');
					}
				}

				$this->project_record->setDescription($project_description);
				$this->project_record->setLeadId($this->user->getId());
				$this->project_record->setType(Constants :: PROJECT);
				$this->project_record->setName($name);

				$this->project_record->store();
				$project_id = $this->project_record->getId();
				$project_old_icon = $this->project_record->getIconName();

				$attachment_folder = Util :: getAttachmentFolderName($config, Constants :: PROJECT_MESSAGE);
				$attach_log_msg = Util :: createAttachmentHelper($this->project_record, $attachment_folder, $project_id);
				$project_icon_name = Util :: createIconHelper($project_id, $attachment_folder);

				if ($project_icon_name) {
					$this->project_record->setIconName($project_icon_name);
					$this->project_record->store();

					if ($project_old_icon && $project_old_icon != $project_icon_name) {
						Util :: deleteAttachments(Constants :: PROJECT_MESSAGE, $config, array (
							$project_old_icon
						));
					}
				}
				if (!$edit_action) {
					UserPermissionPeer :: createPermissionRecord($db, $this->user->getId(), Constants :: LEAD_PROJECT, $this->project_record->getType(), $project_id);
					UserPermissionPeer :: createPermissionRecord($db, $this->user->getId(), Constants :: ADD_ISSUE, $this->project_record->getType(), $project_id);
				}

				$message_record = new MessageRecord($db);
				$message_record->setType(Constants :: PROJECT_MESSAGE);

				$subject = $edit_action ? 'Project updated' : 'New project created';
				$message_record->setSubject($subject);
				$message_record->setCont(($edit_action ? "Updated " : "Added new ") . "project [" . $this->project_record->getName() . "]");
				$message_record->setFromId($this->user->getId());
				$message_record->setTypeId($project_id);
				$message_record->store();

				//change my preferences
				PreferenceRecordPeer :: setProjectFilter($this, $this->user->getId(), $project_id);
				PreferenceRecordPeer :: setProjectVisible($this, $this->user->getId(), $project_id, true);

				$admin = UserRecordPeer :: getAdminUser($db);
				MessageBoardRecordPeer :: addMessageToMessageBoard($db, $message_record->getId(), $admin->getId());

				$db->commit();
				//if (!$this->isAdmin && ConfigRecordPeer :: getCopyMailsToAdmin($this)) {
				//	$this->fireNewProjectMail($db, $this->user, $admin, $this->project_record, '');

				//}

				if ($edit_action) {

					$to_user_ids = UserPermissionPeer :: findContentPermittedUserIds($db, Constants :: PROJECT, $project_id);

					ActionHelper :: sendProjectMessage($this, $to_user_ids, $this->project_record, $message_record);
				} else {
					if (!$this->isAdmin && ConfigRecordPeer :: getCopyMailsToAdmin($this)) {
						$this->fireNewProjectMail($db, $this->user, $admin, $this->project_record, '');
					}
				}

			} catch (Exception $exception) {
				$db->rollback();
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}

		$new_args = array ();

		if ($this->has_error) {
			if ($this->project_record) {
				$this->project_record->setName('');

			}
			$this->setFlashMessage($this->error_message, true);
			if ($edit_action) {

				$this->callModuleMethod('project', 'edit', array (
					'project_id' => $args['project_id']
				));
			}
			if (!$edit_action) {
				$this->callModuleMethod('project', 'create', $new_args);
			}

		}
		
		if($this->project_record != null) {
			$this->callModuleMethod('project', 'view', array('id'=>$this->project_record->getId()));
		}

		$this->callModuleMethod('project', 'index', $new_args);
	}

	
	function setIssueFilter($args) {
		$this->common($args);
		if (!$this->has_error) {
			try {

				$project_id = $args['project_id'];
				$status = $args['issue_status'];

				//$db = Db :: getInstance($this->getConfig());

				PreferenceRecordPeer :: setIssueStatusFilter($this, $this->user->getId(), $project_id, $status);

			} catch (Exception $exception) {

				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}

		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
		}
		if (isset ($args['from_page']) && $args['from_page'] == 'view_project') {

			$this->callModuleMethod('project', 'view', array (
				'id' => $project_id
			));
		} else {
			$this->callModuleMethod('project', 'index', array ());
		}
	}

	function setTaskFilter($args) {
		$this->common($args);
		$project_id = '';
		if (!$this->has_error) {
			try {

				$project_id = $args['project_id'];
				$status = $args['task_status'];

				//	$db = Db :: getInstance($this->getConfig());

				PreferenceRecordPeer :: setTaskStatusFilter($this, $this->user->getId(), $project_id, $status);

			} catch (Exception $exception) {

				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}

		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
		}
		if (isset ($args['from_page']) && $args['from_page'] == 'view_project') {

			$this->callModuleMethod('project', 'view', array (
				'id' => $project_id
			));
		} else {
			$this->callModuleMethod('project', 'index', array ());
		}

	}
	function setPriorityFilter($args) {
		$this->common($args);
		$project_id = '';
		if (!$this->has_error) {
			try {

				$project_id = $args['project_id'];
				$priority = $args['priority'];
				$type = isset($args['type']) ? $args['type'] : 'task';

				if($type == 'issue') {
					PreferenceRecordPeer :: setIssuePriorityFilter($this, $this->user->getId(), $project_id, $priority);
				}
				else {
					PreferenceRecordPeer :: setTaskPriorityFilter($this, $this->user->getId(), $project_id, $priority);
				}

				

			} catch (Exception $exception) {

				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}

		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
		}
		if (isset ($args['from_page']) && $args['from_page'] == 'view_project') {

			$this->callModuleMethod('project', 'view', array (
				'id' => $project_id
			));
		} else {
			$this->callModuleMethod('project', 'index', array ());
		}

	}
	function toggleSortOrder($args) {
		$this->common($args);
		$project_id = '';
		if (!$this->has_error) {
			try {

				$project_id = $args['project_id'];
				$type = isset($args['type']) ? $args['type'] : 'task';
				

				//	$db = Db :: getInstance($this->getConfig());
				if($type == 'issue') {
					$sort_order = PreferenceRecordPeer :: getIssueSortOrder($this, $this->user->getId(), $project_id);
						PreferenceRecordPeer :: setIssueSortOrder($this, $this->user->getId(), $project_id, $sort_order == 'asc' ? 'desc' : 'asc');
				}
				else {
				$sort_order = 	 PreferenceRecordPeer :: getTaskSortOrder($this, $this->user->getId(), $project_id);
					PreferenceRecordPeer :: setTaskSortOrder($this, $this->user->getId(), $project_id, $sort_order == 'asc' ? 'desc' : 'asc');
				}
				
				
				

			

			} catch (Exception $exception) {

				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}

		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
		}
		if (isset ($args['from_page']) && $args['from_page'] == 'view_project') {

			$this->callModuleMethod('project', 'view', array (
				'id' => $project_id
			));
		} else {
			$this->callModuleMethod('project', 'index', array ());
		}

	}

	function setProjectFilter($args) {
		$this->common($args);
		if (!$this->has_error) {
			try {

				$project_id = $args['project_id'];

				PreferenceRecordPeer :: setProjectFilter($this, $this->user->getId(), $project_id);

			} catch (Exception $exception) {

				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}

		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
		}
		$this->callModuleMethod('project', 'index', array ());
	}

	function setProjectVisibility($args) {

		$this->common($args);
		if (!isset ($args['project_id']) || $args['project_id'] == '') {
			$this->appendErrorMessage('Error, could not find dataset! ');
		}
		if (!isset ($args['type']) || $args['type'] == '') {
			$this->appendErrorMessage('Error,required current visibility content! ');
		}
		if (!isset ($args['visibility']) || $args['visibility'] == '') {
			$this->appendErrorMessage('Error,required current visibility status! ');
		}

		if (!$this->has_error) {
			try {
				$project_id = $args['project_id'];
				$visibility = $args['visibility'];
				$type = $args['type'];
				//$db = Db :: getInstance($this->getConfig());
				if ($type == Constants :: PROJECT) {
					PreferenceRecordPeer :: setProjectVisible($this, $this->user->getId(), $project_id, ($visibility == 'show'));
				} else
					if ($type == Constants :: TASK) {
						PreferenceRecordPeer :: setTaskVisible($this, $this->user->getId(), $project_id, ($visibility == 'show'));
					} else
						if ($type == Constants :: ISSUE) {
							PreferenceRecordPeer :: setIssueVisible($this, $this->user->getId(), $project_id, ($visibility == 'show'));
						}

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
			$new_args['id'] = $args['project_id'];
			$this->callModuleMethod('project', 'view', $new_args);
		}
		$this->callModuleMethod('project', 'index', $new_args);
	}

	/* Abhilash 17.12.14 */
	function setProjectDescVisibility($args) {

		$this->common($args);
		if (!isset ($args['project_id']) || $args['project_id'] == '') {
			$this->appendErrorMessage('Error, could not find dataset! ');
		}

		if (!isset ($args['visibility']) || $args['visibility'] == '') {
			$this->appendErrorMessage('Error,required current visibility status! ');
		}

		if (!$this->has_error) {
			try {
				$project_id = $args['project_id'];
				$visibility = $args['visibility'];

				//$db = Db :: getInstance($this->getConfig());

				PreferenceRecordPeer :: setComposeProjectDescVisible($this, $this->user->getId(), $project_id, ($visibility == 'show'));

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
			$new_args['id'] = $args['project_id'];
			$this->callModuleMethod('project', 'view', $new_args);
		}
		$this->callModuleMethod('project', 'index', $new_args);
	}

	
	function changeIssueSetting($args) {
		$this->common($args);

		if (!isset ($args['project_id']) || $args['project_id'] == '') {
			$this->appendErrorMessage('Error, could not find dataset! ');
		}
		if (!isset ($args['setting']) || $args['setting'] == '') {
			$this->appendErrorMessage('Error, could not find dataset! ');
		}
		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($this->getConfig());
				$project_record = ProjectRecordPeer :: findByPK($db, $args['project_id']);

				if ($project_record == null) {
					throw new Exception('could not find dataset! ');
				}
				if ($this->user->getId() != $project_record->getLeadId()) {
					throw new Exception(' authorization required to enble the issue tracking!');
				}
				$setting = $args['setting'];
				$project_record->setEnableIssueTracking($setting);
				$project_record->store();
				if ($setting == 1) {
					//$add_issue_permission = UserPermissionPeer :: getAddIssuePermission($db, $project_record->getId(), $this->user->getId());
					$can_add_issue = UserPermissionPeer :: canAddIssue($db, $this->user->getId(), $project_record->getId());

					//if ($add_issue_permission == null) {
					if (!$can_add_issue) {
						//UserPermissionPeer :: createPermissionRecord($db, $this->user->getId(),Constants :: ADD_ISSUE, $project_record->getType(),  $project_record->getId());
						UserPermissionPeer :: setAddIssuePermission($db, $this->user->getId(), $project_record->getId());

					}
				}

			} catch (Exception $exception) {
				$db->rollback();
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
		}
		$this->callModuleMethod('project', 'view', array (
			'id' => $args['project_id']
		));
	}

	function changeLead($args) {
		$this->common($args);
		$config = $this->getConfig();
		//$meta_data = Util :: getMetaData('change project owner', $config);
		$this->title = "Change Lead"; /* Abhilash 26-10-13 */

		if (!$this->isAdmin) {
			throw new Exception('You are not authorised to access the page. Authorization required!');
		}

		if (!isset ($args['project_id'])) {
			$this->appendErrorMessage('Error, project id is undefined!');
		}
		$new_args = array ();
		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($this->getConfig());

				$this->project_record = ProjectRecordPeer :: findByPK($db, $args['project_id']);

				if ($this->project_record == null) {
					throw new Exception(' could not find dataset!');
				}
				/* Abhilash 26-10-13 */
				$project_icons_folder = Util :: getAttachmentFolderName($config, $this->project_record->getType());
				if ($this->project_record->getIconName() != null) {
					$project_icon_name = $this->project_record->getIconName();
					$this->project_icon = $config->getValue('FW', 'base_url') . '/' . $project_icons_folder . $this->project_record->getId() . '/' . $project_icon_name;
				} else {
					$project_icon_name = 'default.png';
					$this->project_icon = $this->getAbsoluteImageURL('') . '/' . $project_icon_name;
				}
				$old_lead_id = $this->project_record->getLeadId();
				$lead_type_user_ids = UserPermissionPeer :: listUserIdsHavingPermission($db, Constants :: LEAD_PROJECT, Constants :: USER);

				$admin = UserRecordPeer :: getAdminUser($db);
				$valid_lead_ids = array ();
				if (!empty ($lead_type_user_ids)) {
					foreach ($lead_type_user_ids as $lu_id) {
						if ($lu_id != $admin->getId() && $lu_id != $old_lead_id) {
							$valid_lead_ids[] = $lu_id;
						}
					}
				}

				if (empty ($valid_lead_ids)) {
					throw new Exception(' No valid users with project create permission found!');
				}
				if (isset ($args['submit'])) {
					if (!isset ($args['new_lead_id']) || $args['new_lead_id'] == '') {
						throw new Exception(' invalid owner id');
					}
					$new_lead_id = $args['new_lead_id'];
					if (!in_array($new_lead_id, $valid_lead_ids)) {
						throw new Exception(' not authorized to own project');
					}

					$this->project_record->setLeadId($new_lead_id);
					$this->project_record->store();
					//remove  permissions of old lead
					UserPermissionPeer :: unsetProjectLeadPermission($db, $old_lead_id, $this->project_record->getId());
					UserPermissionPeer :: unsetAddIssuePermission($db, $old_lead_id, $this->project_record->getId());
					PreferenceRecordPeer :: setProjectFilter($this, $old_lead_id, 0);

					//remove team  permissions for new lead
					$task_ids = TaskRecordPeer :: getProjectTaskIds($db, $this->project_record->getId());
					if (!empty ($task_ids)) {
						foreach ($task_ids as $task_id) {
							//remove lead task permssion for the old owner
							UserPermissionPeer :: unsetLeadTaskPermission($db, $old_lead_id, $task_id);

							UserPermissionPeer :: unsetTeamPermission($db, $new_lead_id, $task_id);
							//DD lead task permssion for the NEW owner
							UserPermissionPeer :: setLeadTaskPermission($db, $new_lead_id, $task_id);

							$task_record = TaskRecordPeer :: findByPK($db, $task_id);
							if ($task_record != null) {
								$task_record->setLeadId($new_lead_id);
								$task_record->store();
							}

						}
					}
					UserPermissionPeer :: unsetProjectExecPermission($db, $new_lead_id, $this->project_record->getId());

					UserPermissionPeer :: createPermissionRecord($db, $new_lead_id, Constants :: LEAD_PROJECT, $this->project_record->getType(), $this->project_record->getId());
					UserPermissionPeer :: createPermissionRecord($db, $new_lead_id, Constants :: ADD_ISSUE, $this->project_record->getType(), $this->project_record->getId());

					$new_lead = UserRecordPeer :: findByPK($db, $new_lead_id);

					$message_record = new MessageRecord($db);
					$message_record->setType(Constants :: PROJECT_MESSAGE);

					$subject = 'Project owner changed';
					$message_record->setSubject($subject);
					$message_record->setCont("Project owner changed: [" . $new_lead->getSigninId() . "]");
					$message_record->setFromId($this->user->getId());
					$message_record->setTypeId($this->project_record->getId());
					$message_record->store();

					$to_user_ids = UserPermissionPeer :: findContentPermittedUserIds($db, Constants :: PROJECT, $this->project_record->getId());

					ActionHelper :: sendProjectMessage($this, $to_user_ids, $this->project_record, $message_record);

					$this->setFlashMessage("Project owner  changed", false);
					$new_args['id'] = !isset ($args['project_id']) ? '' : $args['project_id'];
					$this->callModuleMethod('project', 'view', $new_args);

				}

				$this->lead_users = UserRecordPeer :: getUserRecordsWithSigninId($db, $valid_lead_ids);

			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}

		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
			$new_args['id'] = !isset ($args['project_id']) ? '' : $args['project_id'];
			$this->callModuleMethod('project', 'view', $new_args);
		}
		return new FlexyView('project/changeLead.html', $this);
	}

	function icon($args) {
		$this->common($args);
		$config = $this->getConfig();
		$project_id = isset ($args['id']) ? $args['id'] : '';
		$icon_name = "";
		try {
			$db = Db :: getInstance($this->getConfig());
			if ($project_id) {
				$icon_name = ProjectRecordPeer :: getIconName($db, $project_id);
			}
		} catch (Exception $exception) {

		}
		$attachment_folder = Util :: getAttachmentFolderName($config, Constants :: PROJECT);
		$icon_file = Util :: getAttachmentFilePath($project_id, $icon_name, $attachment_folder);
		if (!file_exists($icon_file) || !is_file($icon_file)) {
			$resources_folder = $config->getValue('FlexyView', 'resources_dir');
			$icon_file = ".".DIRECTORY_SEPARATOR.$resources_folder .DIRECTORY_SEPARATOR. 'images'.DIRECTORY_SEPARATOR . 'default.png';
		}
		
		Util :: downloadFile($icon_file);

		exit ();

	}

	private function updateProjectNameAndShortDescription($project_record) {
		if ($project_record) {
			$view_url = $this->getAbsoluteURL('/project/view/id/' . $project_record->getId());
			$more_link = ' <a href="' . $view_url . '" class="more_link">more...</a>';
			$name_more_link = '<a href="' . $view_url . '" class="more_link">...</a>';
			$project_record->short_description = Util :: truncate($project_record->getDescription(), 227, $more_link);
			//$project_record->short_name = Util :: truncate($project_record->getName(), 112, $name_more_link);
			$project_record->short_name = Util :: truncate($project_record->getName(), 95, $name_more_link); /*megha 10.1.15*/
			$name = wordwrap($project_record->getName(), 100, "\n", 1);
			$project_record->setName($name);
		}
		return $project_record;
	}

	private function updateIssueTitle($issue_records) {
		if ($issue_records != null) {
			foreach ($issue_records as $issue_record) {
				$view_url = $this->getAbsoluteURL('/issue/view/id/' . $issue_record->getId());
				$more_link = '<a href="' . $view_url . '" class="more_link">...</a>';

				$issue_record->short_name = Util :: truncate($issue_record->getTitle(), 111, $more_link);

			}
		}
		return $issue_records;
	}

	private function fireNewProjectMail($db, $lead, $to_user, $project_record, $target_file = '') {
		$config = $this->getConfig();
		$from_email = ConfigRecordPeer :: getFromEmailAddress($this);
		$website_name = ConfigRecordPeer :: getWebSiteName($this);

		if ($from_email != '' && $to_user->getEmail() != '') {

			list ($subject_template, $body_template) = Util :: getMailTemplateContents($config, Constants :: NEW_PROJECT_MAIL_TEMPLATE);
			$project_url = $this->getAbsoluteURLWithoutSession('/user/show/m/project/a/view/id/') . $project_record->getId() . '/u/' . $to_user->getId();
			$subject = Util :: getSubstitutedProjectTemplate($subject_template, $lead, $to_user, $project_url, $website_name, $project_record);
			$body = Util :: getSubstitutedProjectTemplate($body_template, $lead, $to_user, $project_url, $website_name, $project_record);
			$st = Util :: sendTextMail($from_email, $to_user->getEmail(), $subject, $body, $target_file);

		}
	}
	private function fireProjectDeleteMail($from_user, $to_user, $project_title) {
		$config = $this->getConfig();
		$from_email = ConfigRecordPeer :: getFromEmailAddress($this);
		$website_name = ConfigRecordPeer :: getWebSiteName($this);

		if ($from_email != '') {

			$subject = "Project [$project_title] has been deleted";
			$body = "Project [$project_title] has been deleted by " . $from_user->getSigninId();
			$st = Util :: sendTextMail($from_email, $to_user->getEmail(), $subject, $body, '');

		}
	}
	private function makeAllProjectMessagesRead($db, $user_id, $project_id) {

		$read_message_ids = array ();
		$message_records_db = MessageRecordPeer :: getMessageRecords($db, null, Constants :: DELETED_MESSAGE, Constants :: PROJECT_MESSAGE, $project_id);

		foreach ($message_records_db as $message_record) {
			$read_message_ids[] = $message_record->getId();
		}

		if (!empty ($read_message_ids)) {
			MessageBoardRecordPeer :: setMessagesReadForUser($db, $read_message_ids, $user_id);

		}
	}

}
?>