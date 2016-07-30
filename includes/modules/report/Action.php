<?php
require_once 'BaseController.php';
require_once 'UserRecordPeer.php';
require_once 'MessageBoardRecordPeer.php';
require_once 'BookmarkRecordPeer.php';
require_once 'ActionHelper.php';
require_once 'Db.php';
require_once 'FlexyView.php';
require_once 'IssuePdf.php';
require_once 'TaskPdf.php';

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

	function task($args) {
		$this->common($args);
		$config = $this->getConfig();
		//$meta_data = Util :: getMetaData('task report', $config);
		$this->title = "Task Report"; /* Abhilash 26-10-13 */
		$this->task_reports = true;

		$this->all_type_label = Constants :: ALL_TYPE;
		$this->any_type_label = Constants :: ANY_TYPE;

		$this->task_open = Constants :: TASK_OPEN;
		$this->task_inprogress = Constants :: TASK_INPROGRESS;
		$this->task_review = Constants :: TASK_REVIEW_PENDING;
		$this->task_closed = Constants :: TASK_CLOSED;

		$this->high_priority = Constants :: HIGH_PRIORITY;
		$this->normal_priority = Constants :: NORMAL_PRIORITY;
		$this->low_priority = Constants :: LOW_PRIORITY;

		$this->task_records = array ();

		$this->my_report = $this->others_report = $this->open_report = false;

		$this->show_user_filter = false;

		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($config);

				$my_report_tab = PreferenceRecordPeer :: getMyTaskReportTab($this, $this->user->getId());
				$report_type = isset ($args['report_type']) ? $args['report_type'] : $my_report_tab;
				if (!$report_type || $report_type == '') {
					$report_type = 'my_report';
				}
				$report_settings = PreferenceRecordPeer :: getTaskReportPreferences($this, $this->user->getId(), $report_type);

				$user_filter_id = isset ($report_settings['user_filter_id']) ? $report_settings['user_filter_id'] : 0;
				$task_status = isset ($report_settings['status']) ? $report_settings['status'] : 0;

				$task_priority = isset ($report_settings['task_priority']) ? $report_settings['task_priority'] : 0;
				$project_id = isset ($report_settings['project_id']) ? $report_settings['project_id'] : 0;

				$from_date = isset ($report_settings['task_from_date']) ? $report_settings['task_from_date'] : '';
				$to_date = isset ($report_settings['task_to_date']) ? $report_settings['task_to_date'] : '';

				$this->report_type = isset ($args['report_type']) ? $args['report_type'] : $report_type;
				$this->task_status = isset ($args['task_status']) ? $args['task_status'] : $task_status;

				$this->task_priority = isset ($args['task_priority']) ? $args['task_priority'] : $task_priority;
				
				if(isset($args['from_date']) && $args['from_date']) {
					if(!isset($args['to_date']) || !$args['to_date']) {
						$args['to_date'] = date('d-m-y');
					}
					
				}

				$this->from_date = isset ($args['from_date']) ? $args['from_date'] : $from_date;
				$this->to_date = isset ($args['to_date']) ? $args['to_date'] : $to_date;

				$from_ymd = $this->ymd($this->from_date);
				$to_ymd = $this->ymd($this->to_date);
				
				if($from_ymd && $to_ymd) {
					if(strtotime($from_ymd ) > strtotime($to_ymd)) {
						throw new Exception('Invalid dates, end date before start!');
					}
				}

				$this->task_status = ($this->task_status == 'all') ? false : $this->task_status;
				$this->filter_users = array (); //only for others report and admin session

				if ($this->report_type == 'others_report') {
					$this->others_report = true;
				} else
					if ($this->report_type == 'open_report') {
						$this->open_report = true;
					} else {
						$this->my_report = true;
					}

				if ($this->others_report || $this->isAdmin) {

					$this->selected_user_filter = (isset ($args['user_filter_id'])) && ($args['user_filter_id'] != '') ? $args['user_filter_id'] : $user_filter_id;
				} else {
					$this->selected_user_filter = $this->user->getId();
				}
				$this->project_records = array ();
				
				$hidden_project_ids = array();
				$hidden_project_ids_str = PreferenceRecordPeer::getHiddenProjectIds($this, $this->user->getId());
				if($hidden_project_ids_str) {
					$hidden_project_ids =@explode(",",$hidden_project_ids_str );
				}
			

				if ($this->isAdmin) {
					$this->project_records = ProjectRecordPeer :: getUserProjectRecords($db, '', '', true, '', array (), ProjectRecord :: NAME_COL, 'asc', $hidden_project_ids);

				} else { 
					if ($this->open_report || $this->others_report) { //show my projects only - I am the lead
						$this->project_records = ProjectRecordPeer :: getLeadProjectIdAndProjectName($db, $this->user->getId(),$hidden_project_ids);

					} else { //show others project only 
						$this->project_records = ProjectRecordPeer :: getNonLeadProjectIdAndProjectName($db, $this->user->getId(), $hidden_project_ids);
					}
				}
				$this->selected_project_id = ((isset ($args['project_id'])) && ($args['project_id'] != '')) ? $args['project_id'] : $project_id;
				$this->show_user_filter = ($this->others_report || $this->isAdmin);

				//project filter

				$filter_user_ids = array ();
				if ($this->isAdmin) {
					$exclude_user_ids = array (
						$this->user->getId()
					);
					$filter_user_ids = UserRecordPeer :: getNonAdminUserIds($config, $exclude_user_ids);

				} else
					if ($this->others_report) {
						$lead_project_ids = array ();
						if (!empty ($this->project_records)) {
							foreach ($this->project_records as $lead_project) {
								$lead_project_ids[] = $lead_project->getId();
							}

						}

						$permission_types = array (
							Constants :: CAN_PERFORM_TASK //project permission

	
						);
						$filter_user_ids = UserPermissionPeer :: getProjectTeam($db, $lead_project_ids, $permission_types);

					}
				$this->filter_users = array ();
				if (!empty ($filter_user_ids)) {
					$this->filter_users = UserRecordPeer :: getUserRecordsWithSigninId($db, $filter_user_ids);

				}

				if ($this->isAdmin) {

					$admin_task_records = TaskRecordPeer :: getTaskReportForAdmin($db, $this->selected_user_filter, $this->selected_project_id, $this->task_status, $this->task_priority, $from_ymd, $to_ymd);
					$this->task_records = array_merge($admin_task_records->assignedToThisUser, $admin_task_records->assignedToOthers);
				} else {
					$status_arr = (!$this->task_status || $this->task_status == 0) ? array () : array (
						$this->task_status
					);
					if ($this->others_report) {
						//this is for tasks assigned to others, so not show open tasks even when the status drop down is ALL
						if (empty ($status_arr)) {

							$status_arr = array (
								Constants :: TASK_INPROGRESS,
								Constants :: TASK_CLOSED,
								Constants :: TASK_REVIEW_PENDING
							);
						}
						
						$by_user_id = $this->user->getId(); //me the lead
						$other_user_id = $this->selected_user_filter; //my team	

						$this->task_records = TaskRecordPeer :: getTasksAssignedToOthers($db, $by_user_id, $other_user_id, $this->selected_project_id, $status_arr, '', '', false, false, false, $this->task_priority, $from_ymd, $to_ymd);

					} else
						if ($this->my_report) {
							//$by_user_id = $this->selected_user_filter;
							$me_user_id = $this->user->getId();

							$this->task_records = TaskRecordPeer :: getTasksAssignedToMe($db, false, $me_user_id, $this->selected_project_id, $status_arr, '', '', false, false, false, $this->task_priority, $from_ymd, $to_ymd);
						} else { //open task report
							$by_user_id = $this->selected_user_filter;

							$this->task_records = TaskRecordPeer :: getUserOpenTasks($db, $by_user_id, $this->selected_project_id, false, $this->task_priority, $from_ymd, $to_ymd);
						}
				}
				//$this->task_records = $this->updateTaskNameAndShortDescription($this->task_records);
				/* abhilash */
				$this->task_records = ActionHelper :: updateTaskDisplayDetails($this, $this->task_records, 60); /* megha 10.3.15*/

				$this->task_records = TaskRecordPeer :: getTaskRecordsWithSigninId($db, $this->task_records);

				$task_records = array ();
				//for($cnt = 0; $cnt < count($this->task_records); $cnt++) {
				foreach ($this->task_records as $t_record) {

					//$t_record = TaskRecordPeer :: findByPK($db, $this->task_records[$cnt]->getId());

					$p_record = ProjectRecordPeer :: findByPK($db, $t_record->getParentProjectId());
					$project_icons_folder = Util :: getAttachmentFolderName($config, $p_record->getType());
					$view_url = $config->getValue('FW', 'base_url') . '/project/view/id/' . $p_record->getId();
					$name_more_link = '<a href="' . $view_url . '" class="more_link">...</a>';
					$t_record->project_short_name = Util :: truncate($p_record->getName(), 135, $name_more_link); /* megha 10.3.15*/
					$t_record->project_desc = $p_record->getDescription();
					$t_record->project_name = $p_record->getName();
					$t_record->project_id = $p_record->getId();
					//	$t_record->setIsViewOnly(UserPermissionPeer::canViewTask($db, $this->user->getId(), $t_record->getId())) ;
					if ($p_record->getIconName() != null) {
						$project_icon_name = $p_record->getIconName();
						$t_record->project_icon = $config->getValue('FW', 'base_url') . '/' . $project_icons_folder . $p_record->getId() . '/' . $project_icon_name;
					} else {
						$project_icon_name = 'default.png';
						$t_record->project_icon = $this->getAbsoluteImageURL('') . '/' . $project_icon_name;
					}
					$task_records[] = $t_record;
				}
				$this->total_tasks = count($task_records); /* Abhilash */
				$this->no_task_records = empty ($this->task_records) ? true : false;

				$this->no_project_filter_records = empty ($this->project_records) ? true : false;

				$this->is_task_all = ($this->task_status == 0) ? true : false;
				$this->is_task_inprogress = ($this->task_status == Constants :: TASK_INPROGRESS) ? true : false;
				$this->is_task_review = ($this->task_status == Constants :: TASK_REVIEW_PENDING) ? true : false;
				$this->is_task_closed = ($this->task_status == Constants :: TASK_CLOSED) ? true : false;
				$this->is_task_open = ($this->task_status == Constants :: TASK_OPEN) ? true : false;
				$this->is_project_all = ($this->selected_project_id == 0) ? true : false;

				$this->task_priority_all = ($this->task_priority == 0) ? true : false;
				$this->task_priority_high = ($this->task_priority == Constants :: HIGH_PRIORITY) ? true : false;
				$this->task_priority_medium = ($this->task_priority == Constants :: NORMAL_PRIORITY) ? true : false;
				$this->task_priority_low = ($this->task_priority == Constants :: LOW_PRIORITY) ? true : false;

				//this is for others report, if there are no projects, means I have not got any tasks to assign. 
				$this->hide_others_report = empty ($this->project_records) && ($this->others_report || $this->open_report) ? 'hidden' : 'visible';

				PreferenceRecordPeer :: setMyTaskReportTab($this, $this->user->getId(), $this->report_type);
			
			//not required	PreferenceRecordPeer :: setTaskReportPreferences($this, $this->user->getId(), $this->report_type, $this->selected_user_filter, $this->task_status, $this->task_priority, $this->selected_project_id, $from_ymd, $to_ymd);

				foreach ($this->task_records as $t_record) {
					$t_record->isBookmarked = BookmarkRecordPeer :: getIsBookmarked($db, $this->user->getId(), $t_record->getType(), $t_record->getId());

				}
			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
				$this->setFlashMessage($this->error_message, true);
				
			}
		}
		return new FlexyView('report/taskReport.html', $this);
	}

	function issue($args) {
		$this->common($args);
		$config = $this->getConfig();
		//$meta_data = Util :: getMetaData('issue report', $config);
		$this->title = "Issue Report"; /* Abhilash 26-10-13 */
		$this->issue_reports = true;
		$this->all_type_label = Constants :: ALL_TYPE;

		$this->high_priority = Constants :: HIGH_PRIORITY;
		$this->normal_priority = Constants :: NORMAL_PRIORITY;
		$this->low_priority = Constants :: LOW_PRIORITY;

		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($config);

				$report_settings = PreferenceRecordPeer :: getIssueReportPreferences($this, $this->user->getId());
				$issue_status = isset ($report_settings['status']) ? $report_settings['status'] : Constants :: ISSUE_OPEN;
				$issue_priority = isset ($report_settings['issue_priority']) ? $report_settings['issue_priority'] : 0;

				$from_date = isset ($report_settings['issue_from_date']) ? $report_settings['issue_from_date'] : '';
				$to_date = isset ($report_settings['issue_to_date']) ? $report_settings['issue_to_date'] : '';

				$project_id = isset ($report_settings['project_id']) ? $report_settings['project_id'] : 0;

				$this->selected_project_id = isset ($args['project_id']) ? $args['project_id'] : $project_id;
				$this->selected_issue_status = isset ($args['issue_status']) ? $args['issue_status'] : $issue_status;
				$this->selected_issue_priority = isset ($args['issue_priority']) ? $args['issue_priority'] : $issue_priority;
				
				
				if(isset($args['from_date']) && $args['from_date']) {
					if(!isset($args['to_date']) || !$args['to_date']) {
						$args['to_date'] = date('d-m-y');
					}
					
				}
				

				$this->from_date = isset ($args['from_date']) ? $args['from_date'] : $from_date;
				$this->to_date = isset ($args['to_date']) ? $args['to_date'] : $to_date;

				$from_ymd = $this->ymd($this->from_date);
				$to_ymd = $this->ymd($this->to_date);
				if($from_ymd && $to_ymd) {
					if(strtotime($from_ymd ) > strtotime($to_ymd)) {
						throw new Exception('Invalid dates, end date before start!');
					}
				}
				
				$hidden_project_ids = array();
				$hidden_project_ids_str = PreferenceRecordPeer::getHiddenProjectIds($this, $this->user->getId());
				if($hidden_project_ids_str) {
					$hidden_project_ids =@explode(",",$hidden_project_ids_str );
				}

				$filter_records = array ();
				if ($this->isAdmin) {
					$filter_records = ProjectRecordPeer :: getUserProjectRecords($db, '', '', true, '', array (), ProjectRecord :: NAME_COL, 'asc', $hidden_project_ids);
				} else {
					$permission_types = array (
						Constants :: ADD_ISSUE,
						Constants :: LEAD_PROJECT
					);
					$filter_records = ProjectRecordPeer :: getUserProjectRecords($db, '', '', false, $this->user->getId(), $permission_types, ProjectRecord :: NAME_COL, 'asc',$hidden_project_ids);
				}

				$this->project_filter_records = array ();
				for ($cnt = 0; $cnt < count($filter_records); $cnt++) {
					if ($filter_records[$cnt]->isIssueTrackingEnabled()) {
						$this->project_filter_records[] = $filter_records[$cnt];

					}
				}

				$project_ids = array ();
				if ($this->selected_project_id == 0) {
					foreach ($this->project_filter_records as $project_record) {
						$project_ids[] = $project_record->getId();
					}
					$this->selected_project_id = empty ($project_ids) ? 0 : $project_ids[0];
				}
				$project_ids = array (
					$this->selected_project_id
				);

				$issue_records = IssuePeer :: getProjectIssues($db, $project_ids, $this->selected_issue_status, $this->selected_issue_priority, false, 'desc', '', '', $from_ymd, $to_ymd);
				$this->issue_records = IssuePeer :: getIssueWithUserName($db, $issue_records);
				$this->issue_records = $this->updateIssueNameAndShortDescription($this->issue_records);
				$this->issue_records = ActionHelper :: updateIssueDisplayDetails($this, $this->issue_records, 90);
				if (!empty ($this->issue_records)) {
					foreach ($this->issue_records as $issue_record) {
						$issue_record->isBookmarked = BookmarkRecordPeer :: getIsBookmarked($db, $this->user->getId(), $issue_record->getType(), $issue_record->getId());

					}
				}
				$this->total_issues = count($this->issue_records); /* Abhilash */
				$this->no_records = empty ($this->issue_records) ? true : false;

				$this->open_status = Constants :: ISSUE_OPEN;
				$this->closed_status = Constants :: ISSUE_CLOSED;
				$this->is_open_selected = ($this->selected_issue_status == Constants :: ISSUE_OPEN) ? true : false;
				$this->is_closed_selected = ($this->selected_issue_status == Constants :: ISSUE_CLOSED) ? true : false;
				$this->is_project_all = ($this->selected_project_id == 0) ? true : false;

				$this->issue_priority_all = ($this->selected_issue_priority == 0) ? true : false;
				$this->issue_priority_high = ($this->selected_issue_priority == Constants :: HIGH_PRIORITY) ? true : false;
				$this->issue_priority_medium = ($this->selected_issue_priority == Constants :: NORMAL_PRIORITY) ? true : false;
				$this->issue_priority_low = ($this->selected_issue_priority == Constants :: LOW_PRIORITY) ? true : false;

			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		/* Abhilash 22.4.15 */
		$this->export_arg_priority = $this->selected_issue_priority ? $this->selected_issue_priority : 'none';
		$this->export_from_date = $this->from_date ? $this->from_date : 'none';
		$this->export_to_date = $this->to_date ? $this->to_date : 'none';
		
		return new FlexyView('report/issueReport.html', $this);
	}

	function setTaskReportSettings($args) {
		$this->common($args);

		$project_id = isset ($args['project_id']) && ($args['project_id'] != '') ? $args['project_id'] : 0;
		$selected_user_filter = isset ($args['user_filter_id']) && ($args['user_filter_id'] != '') ? $args['user_filter_id'] : 0;
		$task_status = isset ($args['task_status']) && ($args['task_status'] != '') ? $args['task_status'] : 0;
		$report_type = isset ($args['report_type']) && ($args['report_type'] != '') ? $args['report_type'] : 'my_report';
		$task_priority = isset ($args['task_priority']) && ($args['task_priority'] != '') ? $args['task_priority'] : 0;
		$from_date = isset ($args['from_date']) ? $args['from_date'] : '';
		$to_date = isset ($args['to_date']) ? $args['to_date'] : '';

		$new_args = array (
			'report_type' => $report_type,
			'project_id' => $project_id,
			'task_status' => $task_status,
			'task_priority' => $task_priority,
			

			
		);
		if($from_date) {
			$new_args['from_date'] = str_replace('/','-', $from_date);
		}
		if($to_date) {
			$new_args['to_date'] = str_replace('/','-', $to_date);;
		}

		if (!$this->has_error) {
			try {
				//$db = Db :: getInstance($this->getConfig());
				PreferenceRecordPeer :: setTaskReportPreferences($this, $this->user->getId(), $report_type, $selected_user_filter, $task_status, $task_priority, $project_id, $from_date, $to_date);
				//	PreferenceRecordPeer :: setMyTaskReportTab($this, $this->user->getId(), $report_type);
			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
		}
		$this->callModuleMethod('report', 'task', $new_args);
	}

	function setIssueReportSettings($args) {
		$this->common($args);

		$project_id = isset ($args['project_id']) && ($args['project_id'] != '') ? $args['project_id'] : 0;
		$issue_status = isset ($args['issue_status']) && ($args['issue_status'] != '') ? $args['issue_status'] : 0;
		$issue_priority = isset ($args['issue_priority']) && ($args['issue_priority'] != '') ? $args['issue_priority'] : 0;
		$from_date = isset ($args['from_date']) ? $args['from_date'] : '';
		$to_date = isset ($args['to_date']) ? $args['to_date'] : '';

		$new_args = array (
			'project_id' => $project_id,
			'issue_status' => $issue_status,
			'issue_priority' => $issue_priority,
			

			
		);
		if($from_date) {
			$new_args['from_date'] = str_replace('/','-', $from_date);
		}
		if($to_date) {
			$new_args['to_date'] = str_replace('/','-', $to_date);;
		}

		if (!$this->has_error) {
			try {

				PreferenceRecordPeer :: setIssueReportPreferences($this, $this->user->getId(), $issue_status, $issue_priority, $project_id, $from_date, $to_date);

			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}

		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
		}
		$this->callModuleMethod('report', 'issue', $new_args);
	}

	function exportIssue($args) {
		$this->common($args);
		$config = $this->getConfig();
		if (!isset ($args['project_id']) || $args['project_id'] == '') {
			$this->appendErrorMessage('Error, project not selected');
		}
		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($config);

				$message_records = array ();
				$this->project_record = ProjectRecordPeer :: findByPK($db, $args['project_id']);

				
				if ($this->project_record == null) {
					throw new Exception('Could not find the project dataset!');
				}
				$status = (isset ($args['status']) && $args['status'] != '') ? $args['status'] : '';
				if (!empty ($status)) {
					$status_type = 'Issues(' . Constants :: getLabel($status) . ')';
				} else {
					$status_type = 'Issues (All)';
				}
				
				/* Abhilash 20.4.15 */
				$priority = $args['priority'] &&  $args['priority'] != 'none' ?  $args['priority'] : '';
				$from_ymd = $args['from_date'] &&  $args['from_date'] != 'none' ?  $this->ymd($args['from_date']) : '';
				$to_ymd = $args['to_date'] &&  $args['to_date'] != 'none' ?  $this->ymd($args['to_date']) : '';
				
				//$issue_records = IssuePeer :: getProjectIssues($db, array (
				//	$this->project_record->getId()
				//), $status);
				
				$issue_records = IssuePeer :: getProjectIssues($db, array (
						$this->project_record->getId()
				), $status, $priority, false, 'desc', '', '', $from_ymd, $to_ymd);
				
				$this->issue_records = IssuePeer :: getIssueWithUserName($db, $issue_records);
				$this->count_open = 0;
				$this->count_closed = 0;

				foreach ($this->issue_records as $this->issue_record) {
					if ($this->issue_record->getIsOpen()) {
						$this->count_open++;
					} else {
						$this->count_closed++;
					}

				}
				$this->total_issues = $this->count_open + $this->count_closed;
				if (($this->issue_records) != null) {
					$project_name = $this->project_record->getName();
					$pdf = new IssuePdf();
					$pdf->AliasNbPages();
					$pdf->setTitle($status_type);
					if ($status_type == "Issues (All)") {
						$pdf->setCountOpen($this->count_open);
						$pdf->setCountClosed($this->count_closed);
						$pdf->setCountTotal($this->total_issues);
					}
					if ($status_type == "Issues(Open)") {
						$pdf->setCountTotal($this->total_issues);
					}
					if ($status_type == "Issues(Closed)") {
						$pdf->setCountTotal($this->total_issues);
					}
					$pdf->AddPage();
					$pdf->SetLeftMargin(20);
					$pdf->SetRightMargin(20);

					$pdf->SetFont('Arial', 'B', 10);
					$pdf->SetTextColor(204, 51, 0);
					$pdf->MultiCell(0, 6, 'Project: ' . $project_name);
					$pdf->Ln(10);

					foreach ($issue_records as $issue_record) {
						$issue_id = $issue_record->getId();
						$issue_user = $issue_record->getUserSigninId();
						$issue_status = $issue_record->getStatusLabel();
						$issue_date = date('Y-m-d h:i', strtotime($issue_record->getUpadtedAt()));

						$data = "";
						$header = array (
							'#' . $issue_id,
							'By:',
							$issue_user,
							'Status:',
							$issue_status,
							'Date:',
							$issue_date
						);

						if ($issue_status == "Open") {
							$pdf->FancyTable($header, $data, 'orange');
						} else {
							$pdf->FancyTable($header, $data, 'grey');
						}

						$pdf->Ln(0);
						$issue_title = $issue_record->getTitle();
						$issue_desc = $issue_record->getDescription();
						$content['issue_title'] = "{$issue_title} ";
						$content['issue_description'] = $issue_desc;
						$content_border = ($issue_record->getHasAttachment()) ? 'LRB' : 'LR';
						$pdf->content($content, $content_border);
						$pdf->Ln(0);

						// issue attachments
						if ($issue_record->getHasAttachment()) {
							$attachment_label = "\n Attached files: ";
							$pdf->SetTextColor(0, 51, 102);
							$pdf->Cell(0, 10, $attachment_label, 'LR', 0);
							$pdf->SetTextColor(0, 0, 0);
							$pdf->Ln(5);
							$pdf->SetTextColor(0, 0, 255);

							$attachment_names = explode(':', $issue_record->getAttachmentName());
							foreach ($attachment_names as $attachment_name) {
								if ($attachment_name != "") {

									$attachment_file = Util :: getAttachmentFilePath($args['project_id'], $attachment_name, Util :: getAttachmentFolderName($config, Constants :: ISSUE_MESSAGE));
									$isImage = Util :: isValidImageFile($attachment_file, Util :: getPermittedImageTypes());

									if (!$isImage) {
										$attachment_file_name = Util :: extractAttachmentName($attachment_name);
										$attachment_url = $this->getAbsoluteUrl('') . '/issue/download/issue_id/' . $issue_record->getId() . '/file_name/' . $attachment_file_name;
										$pdf->Cell(0, 10, $attachment_url, "LR", 1, 'L', false, $attachment_url);
										$pdf->SetTextColor(0, 0, 0);
									}
									if ($isImage) {
										$x = $pdf->x + 5;
										$attachment_image = $this->getAbsoluteUrl('') . '/' . Util :: getAttachmentFolderName($config, Constants :: ISSUE_MESSAGE) . $args['project_id'] . '/' . $attachment_name;
										if (($pdf->h - $pdf->y) >= 100) {
											$pdf->Line($pdf->x, $pdf->y, $pdf->x, $pdf->y + 110);
											$pdf->Line(190, $pdf->y, 190, $pdf->y + 110);
											$pdf->Image($attachment_image, $x, $pdf->y + 3, 150, 100);
											$pdf->Ln(105);
										} else {
											$pdf->AddPage();
											$pdf->Line($pdf->x, $pdf->y, $pdf->x, $pdf->y + 100);
											$pdf->Line(190, $pdf->y, 190, $pdf->y + 100);
											$pdf->Image($attachment_image, $x, $pdf->y + 3, 150, 100);
											$pdf->Ln(100);
										}
									}
								}
							}
						}

						//comments
						$message_records_db = MessageRecordPeer :: getMessageRecords($db, null, Constants :: DELETED_MESSAGE, Constants :: ISSUE_MESSAGE, $issue_record->getId());
						$message_records = MessageRecordPeer :: getMessageRecordsWithUserName($db, $message_records_db, $this->user->getId());
						$num_of_messages = count($message_records);

						$show_all_comments_of_open_issues = PreferenceRecordPeer :: getShowAllCommentsOpenIssues($this, $this->user->getId());
						$show_closed_comments_of_closed_issues = PreferenceRecordPeer :: getShowClosedCommentsClosedIssues($this, $this->user->getId());
						$show_attached_image_with_comments = PreferenceRecordPeer :: getShowImageWithIssues($this, $this->user->getId());

						if ($num_of_messages > 0) {
							//	check here for issue closed settings
							if ($show_closed_comments_of_closed_issues == true && $issue_status == "Closed") {
								$pdf->Cell(2, 10, '', 'LT', 0);
								$pdf->SetTextColor(0, 51, 102);
								$pdf->Cell(0, 10, ' Comments ', 'RT');
								$pdf->SetTextColor(0, 0, 0);
								$pdf->Ln(8);
								$pdf->Cell(4, 19, '', 'L', 0);
								//$pdf->Cell(1,6,'','LTB');
								$pdf->SetTextColor(128, 0, 0);
								//$pdf->Cell(15,6,'Posted by:','TB');
								$pdf->SetTextColor(0, 0, 0);
								$pdf->Cell(162, 6, $message_records[0]->getFromName(), 1);
								$pdf->Cell(4, 20, '', 'R', 0, 1);
								$pdf->Ln(6);
								$pdf->Cell(4, 19, '', 0, 0);
								$pdf->MultiCell(162, 6, $message_records[0]->getCont(), 1);

								//get and display image attachment for comment
								if ($show_attached_image_with_comments == true && $message_records[0]->getHasAttachment()) {
									$attachment_names = explode(':', $message_records[0]->getAttachmentName());

									foreach ($attachment_names as $attachment_name) {
										if ($attachment_name != "") {
											//$isImage = Util :: isImageAttachment($attachment_name);
											$attachment_file = Util :: getAttachmentFilePath($args['project_id'], $attachment_name, Util :: getAttachmentFolderName($config, Constants :: ISSUE_MESSAGE));
											$isImage = Util :: isValidImageFile($attachment_file, Util :: getPermittedImageTypes());
											if ($isImage) {
												$x = $pdf->x + 5;
												$attachment_image = $this->getAbsoluteUrl('') . '/' . Util :: getAttachmentFolderName($config, Constants :: ISSUE_MESSAGE) . $args['project_id'] . '/' . $attachment_name;
												if (($pdf->h - $pdf->y) >= 100) {
													$pdf->Line($pdf->x, $pdf->y, $pdf->x, $pdf->y + 110);
													$pdf->Line(190, $pdf->y, 190, $pdf->y + 110);
													$pdf->Image($attachment_image, $x, $pdf->y + 3, 150, 100);
													$pdf->Ln(105);
												} else {
													$pdf->AddPage();
													$pdf->Line($pdf->x, $pdf->y, $pdf->x, $pdf->y + 100);
													$pdf->Line(190, $pdf->y, 190, $pdf->y + 100);
													$pdf->Image($attachment_image, $x, $pdf->y + 3, 150, 100);
													$pdf->Ln(100);
												}
											}
										}
									}
								}
								//$pdf->Ln(3);
							} else
								if ($show_closed_comments_of_closed_issues == false && $issue_status == "Closed") {
									$pdf->Cell(2, 10, '', 'LT', 0);
									$pdf->SetTextColor(0, 51, 102);
									$pdf->Cell(0, 10, ' Comments ', 'RT');
									$pdf->SetTextColor(0, 0, 0);
									$pdf->Ln(8);
									foreach ($message_records as $message_record) {
										$pdf->Cell(4, 19, '', 'L', 0);
										$pdf->SetTextColor(128, 0, 0);
										$pdf->SetTextColor(0, 0, 0);
										$pdf->Cell(162, 6, $message_record->getFromName(), 1);
										$pdf->Cell(4, 20, '', 'R', 0, 1);
										$pdf->Ln(6);
										$pdf->Cell(4, 19, '', 0, 0);
										$pdf->MultiCell(162, 6, $message_record->getCont(), 1);
										if ($show_attached_image_with_comments == true && $message_record->getHasAttachment()) {
											$attachment_names = explode(':', $message_record->getAttachmentName());
											foreach ($attachment_names as $attachment_name) {
												if ($attachment_name != "") {
													//$isImage = Util :: isImageAttachment($attachment_name);
													$attachment_file = Util :: getAttachmentFilePath($args['project_id'], $attachment_name, Util :: getAttachmentFolderName($config, Constants :: ISSUE_MESSAGE));
													$isImage = Util :: isValidImageFile($attachment_file, Util :: getPermittedImageTypes());

													if ($isImage) {
														$x = $pdf->x + 5;
														$attachment_image = $this->getAbsoluteUrl('') . '/' . Util :: getAttachmentFolderName($config, Constants :: ISSUE_MESSAGE) . $args['project_id'] . '/' . $attachment_name;
														if (($pdf->h - $pdf->y) >= 100) {
															$pdf->Line($pdf->x, $pdf->y, $pdf->x, $pdf->y + 110);
															$pdf->Line(190, $pdf->y, 190, $pdf->y + 110);
															$pdf->Image($attachment_image, $x, $pdf->y + 3, 150, 100);
															$pdf->Ln(105);
														} else {
															$pdf->AddPage();
															$pdf->Line($pdf->x, $pdf->y, $pdf->x, $pdf->y + 100);
															$pdf->Line(190, $pdf->y, 190, $pdf->y + 100);
															$pdf->Image($attachment_image, $x, $pdf->y + 3, 150, 100);
															$pdf->Ln(100);
														}
													}
												}
											}
										}
										$pdf->Ln(3);
									}
								}
							if ($show_all_comments_of_open_issues == true && $issue_status == "Open") {
								$pdf->Cell(2, 10, '', 'LT', 0);
								$pdf->SetTextColor(0, 51, 102);
								$pdf->Cell(0, 10, ' Comments ', 'RT');
								$pdf->SetTextColor(0, 0, 0);
								$pdf->Ln(8);
								foreach ($message_records as $message_record) {
									$pdf->Cell(4, 19, '', 'L', 0);
									$pdf->SetTextColor(128, 0, 0);
									$pdf->SetTextColor(0, 0, 0);
									$pdf->Cell(162, 6, $message_record->getFromName(), 1);
									$pdf->Cell(4, 20, '', 'R', 0, 1);
									$pdf->Ln(6);
									$pdf->Cell(4, 19, '', 0, 0);
									$pdf->MultiCell(162, 6, $message_record->getCont(), 1);
									if ($show_attached_image_with_comments == true && $message_record->getHasAttachment()) {
										$attachment_names = explode(':', $message_record->getAttachmentName());
										foreach ($attachment_names as $attachment_name) {
											if ($attachment_name != "") {
												//$isImage = Util :: isImageAttachment($attachment_name);
												$attachment_file = Util :: getAttachmentFilePath($args['project_id'], $attachment_name, Util :: getAttachmentFolderName($config, Constants :: ISSUE_MESSAGE));
												$isImage = Util :: isValidImageFile($attachment_file, Util :: getPermittedImageTypes());

												if ($isImage) {
													$x = $pdf->x + 5;
													$attachment_image = $this->getAbsoluteUrl('') . '/' . Util :: getAttachmentFolderName($config, Constants :: ISSUE_MESSAGE) . $args['project_id'] . '/' . $attachment_name;
													if (($pdf->h - $pdf->y) >= 100) {
														$pdf->Line($pdf->x, $pdf->y, $pdf->x, $pdf->y + 110);
														$pdf->Line(190, $pdf->y, 190, $pdf->y + 110);
														$pdf->Image($attachment_image, $x, $pdf->y + 3, 150, 100);
														$pdf->Ln(105);
													} else {
														$pdf->AddPage();
														$pdf->Line($pdf->x, $pdf->y, $pdf->x, $pdf->y + 100);
														$pdf->Line(190, $pdf->y, 190, $pdf->y + 100);
														$pdf->Image($attachment_image, $x, $pdf->y + 3, 150, 100);
														$pdf->Ln(100);
													}
												}
											}
										}
									}
									$pdf->Ln(3);
								}
							}
							//	}
						}
						$pdf->Cell(0, 5, '', 'LR', 1);
						$pdf->SetDrawColor(255, 204, 153);
						$pdf->Cell(0, 0, '', 'B', 0);
						$pdf->Ln(3);
					}
					$filename = $issue_record->getProjectId() . '_' . $this->project_record->getName() . '_' . $status_type . '_issue_report.pdf';
					$pdf->Output($filename, 'D');
				}
			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		$new_args = array ();

		if (!$this->has_error) {
			$new_args['project_id'] = $args['project_id'];
			$new_args['status'] = $args['project_id'];
		}

		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
		}
		$this->callModuleMethod('report', 'issue', $new_args);
	}
	function exportTask($args) {
		$this->common($args);
		$config = $this->getConfig();

		if (!$this->has_error) {
			try {
				$pdf = new TaskPdf();
				$pdf->SetDrawColor(230, 230, 230);
				$status_type = 'All';
				$db = Db :: getInstance($config);

				$project_id = $args['project_id'];

				if (!isset ($args['exportable_tasks']) || empty ($args['exportable_tasks'])) {

					throw new Exception('No task records for export found.');
				}
				$task_ids = $args['exportable_tasks'];
				$project_record = ProjectRecordPeer :: findByPK($db, $project_id);
				$project_icons_folder = Util :: getAttachmentFolderName($config, $project_record->getType());

				if ($project_record->getIconName() != null) {
					$project_icon_name = $project_record->getIconName();
					$project_icon = $config->getValue('FW', 'base_url') . '/' . $project_icons_folder . $project_record->getId() . '/' . $project_icon_name;
				} else {
					$project_icon_name = 'default.png';
					$project_icon = $this->getAbsoluteImageURL('') . '/' . $project_icon_name;
				}
				$project_name = $project_record->getName();
				$x = $pdf->x + 20;
				$pdf->AliasNbPages();
				$pdf->setTitle($status_type);
				$pdf->AddPage();
				$pdf->SetLeftMargin(20);
				$pdf->SetRightMargin(20);
				$pdf->SetFont('Arial', '', 12);
				$pdf->SetTextColor(0, 128, 0);
				$pdf->Image($project_icon, $x, $pdf->y + 1, 5, 5);
				$pdf->MultiCell(0, 6, '     Project: ' . $project_name);
				$pdf->Ln(5);
				$task_records = array ();

				foreach ($task_ids as $task_id) {
					$task_records[] = TaskRecordPeer :: findByPK($db, $task_id);
				}
				$task_records = TaskRecordPeer :: getTaskRecordsWithSigninId($db, $task_records);
				foreach ($task_records as $task_record) {
					$header = array (
						'',
						$task_record->getCreatedAt(),
						'',
						$task_record->getTeamSigninId(),
						$task_record->getProgress() . '%'
					);
					$data = "";
					//$pdf->setX(40);
					$pdf->FancyTable($header, $data);
					$pdf->Ln(2);
					if ($task_record->getIsViewOnly()) {
						$content['task_status'] = "ViewOnly";
					}
					elseif ($task_record->getIsOpen()) {
						$content['task_status'] = "Open";
					}
					elseif ($task_record->getIsInProgress()) {
						$content['task_status'] = "In progress";
					}
					elseif ($task_record->getIsInReviewStatus()) {
						$content['task_status'] = "Review Pending";
					}
					elseif ($task_record->getIsClosed()) {
						$content['task_status'] = "Closed";
					}
					$content['task_title'] = "     " . $task_record->getName();
					$content['task_description'] = $task_record->getDescription();
					$content_border = 'LRB';
					$pdf->content($content, $content_border);
					$pdf->Ln(5);
					$ypos = $pdf->getY();
					if (($pdf->h - $pdf->y) <= 70) {
						$pdf->AddPage();
					}
				}
				$filename = $project_id . '_' . $project_name . '_task_report.pdf';
				$pdf->Output($filename, 'D');
			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}

		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
		}
		$this->callModuleMethod('report', 'task', array ());
	}

	private function updateIssueNameAndShortDescription($issue_records) {
		if (!empty ($issue_records)) {
			foreach ($issue_records as $issue_record) {
				$view_url = $this->getAbsoluteURL('/issue/view/id/' . $issue_record->getId());
				$more_link = '<a href="' . $view_url . '" class="more_link">...</a>';
				$issue_record->short_name = Util :: truncate($issue_record->getTitle(), 70, $more_link);
			}
		}
		return $issue_records;
	}

	private function ymd($str) {
		if ($str) {
			$arr = @ explode("/", $str);
			if (count($arr) < 3) {
				$arr = @ explode("-", $str);
			}
			if (count($arr) == 3) {
				$y = $arr[2];
				$m = $arr[1];
				$d = $arr[0];
				if (checkdate($m, $d, $y)) {
					return date ('Y-m-d', strtotime($y . "-" . $m . "-" . $d));
				}

			}
			throw new Exception('Invalid date ' . $str);
		}
		return '';
	}
}
?>