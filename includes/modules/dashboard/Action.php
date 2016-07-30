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
		$config = $this->getConfig();
		//$meta_data = Util :: getMetaData('dashboard', $config);
		$this->title = "Dashboard"; /* Abhilash 26-10-13 */
		$this->dashboard = true;
		$this->total_unread_messages = null;
		$this->message_records = array ();
		$this->trecords_others = array ();
		$this->trecords_me = array ();

		if (!$this->getIsAuthorised()) {
			$this->appendErrorMessage('Unknown access, authorization required! ');
			return new FlexyView('error.html', $this);
		}
		try {
			$db = Db :: getInstance($this->getConfig());

			$this->isLead = $this->getParameter('can_create_project');

			$max_dashboard_block_items = PreferenceRecordPeer :: getMaxDashboardItemsPerPage($this, $this->user->getId());

			$this->show_dashboard_unread_messages = $this->isAdmin ? true : PreferenceRecordPeer :: getDashboardShowUnreadMessages($this, $this->user->getId());
			$this->show_tasks_for_others = PreferenceRecordPeer :: getDashboardShowOthersTasks($this, $this->user->getId());
			$this->show_tasks_for_me = PreferenceRecordPeer :: getDashboardShowMyTasks($this, $this->user->getId());

			if (!$this->isAdmin && $this->show_tasks_for_others) {

				$status_arr = array (
					Constants :: TASK_OPEN,
					Constants :: TASK_REVIEW_PENDING,
					Constants :: TASK_INPROGRESS
				);
				//$this->trecords_others = TaskRecordPeer :: getTasksAssignedToOthers($db, $this->user->getId(), '', '', $status_arr, 0, $max_dashboard_block_items, TaskRecord :: STATUS_COL, 'desc');
				$this->trecords_others = TaskRecordPeer :: getTasksAssignedToOthers($db, $this->user->getId(), '', '', $status_arr, 0, $max_dashboard_block_items, TaskRecord :: UPDATED_AT_COL, 'desc');
				//$this->trecords_others = $this->updateTaskNameAndShortDescription($this->trecords_others);

				$this->trecords_others = ActionHelper :: updateTaskDisplayDetails($this, $this->trecords_others, 78, 108);
				$this->trecords_others = TaskRecordPeer :: getTaskRecordsWithSigninId($db, $this->trecords_others);
				foreach ($this->trecords_others as $task_record) {

					$task_record = $this->getProjectLinkDetails($db, $task_record);
				}
			}

			if (!$this->isAdmin && $this->show_tasks_for_me) {
				//me
				$status_arr = array (
					Constants :: TASK_REVIEW_PENDING,
					Constants :: TASK_INPROGRESS,
					Constants :: TASK_OPEN //view only tasks

	
				);
				$this->trecords_me = TaskRecordPeer :: getTasksAssignedToMe($db, false, $this->user->getId(), '', $status_arr, 0, $max_dashboard_block_items, TaskRecord :: UPDATED_AT_COL, 'desc');
				//$this->trecords_me = $this->updateTaskNameAndShortDescription($this->trecords_me);
				$this->trecords_me = ActionHelper :: updateTaskDisplayDetails($this, $this->trecords_me, 78);

				$this->trecords_me = TaskRecordPeer :: getTaskRecordsWithSigninId($db, $this->trecords_me);
				foreach ($this->trecords_me as $task_record) {
					$task_record = $this->getProjectLinkDetails($db, $task_record);
				}
			}

			//to display latest messages
			if ($this->isAdmin || $this->show_dashboard_unread_messages) {
				$user_id = $this->user->getId();
				$this->message_board_records = MessageBoardRecordPeer :: getUnreadMessageList($db, $user_id, 0, $max_dashboard_block_items);
				$this->total_unread_message_cnt = MessageBoardRecordPeer :: getUnreadCountsForUser($this->getConfig(), $user_id);
				for ($cnt = 0; $cnt < count($this->message_board_records); $cnt++) {
					$message_record = MessageRecordPeer :: findByPK($db, $this->message_board_records[$cnt]->getMessageId());

					if (!is_null($message_record)) {
						$message_record = MessageRecordPeer :: getMessageWithName($db, $message_record);

						$message_record = ActionHelper :: updateProjectLinkDetails($this, $message_record, 72);
						$message = ($message_record->getCont() == '') ? 'No comments' : $message_record->getCont();
						$message_record->short_description = Util :: truncate($message, 126, '..');
						$this->message_records[$cnt] = $message_record;

					}

				}
			}

			//book marks
			foreach ($this->trecords_others as $t_record) {
				$t_record->isBookmarked = BookmarkRecordPeer :: getIsBookmarked($db, $this->user->getId(), $t_record->getType(), $t_record->getId());

			}

			foreach ($this->trecords_me as $t_record) {
				$t_record->isBookmarked = BookmarkRecordPeer :: getIsBookmarked($db, $this->user->getId(), $t_record->getType(), $t_record->getId());

			}

			foreach ($this->message_records as $m_record) {
				$m_record->isBookmarked = BookmarkRecordPeer :: getIsBookmarked($db, $this->user->getId(), $m_record->getType(), $m_record->getId());

			}

		} catch (Exception $exception) {
			$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
		}

		$this->no_tasks_others = empty ($this->trecords_others) ? true : false;
		$this->total_tasks_others = count($this->trecords_others);

		$this->no_tasks_me = empty ($this->trecords_me) ? true : false;
		$this->total_tasks_me = count($this->trecords_me);

		$this->total_unread_messages = count($this->message_records);
		$this->no_message_records = empty ($this->message_records) ? true : false;

		//megha - pie chart stuff
		if (!$this->isAdmin) {
			$this->n_open_tasks = TaskRecordPeer :: getUserOpenTasks($db, $this->user->getId(), false, true);

			$this->n_review_pending_tasks_me = TaskRecordPeer :: getTasksAssignedToMe($db, false, $this->user->getId(), false, array (
				Constants :: TASK_REVIEW_PENDING
			), '', '', false, false, true);
			$this->n_review_pending_tasks_others = TaskRecordPeer :: getTasksAssignedToOthers($db, $this->user->getId(), '', false, array (
				Constants :: TASK_REVIEW_PENDING
			), '', '', false, false, true);

			$this->n_closed_tasks_me = TaskRecordPeer :: getTasksAssignedToMe($db, false, $this->user->getId(), false, array (
				Constants :: TASK_CLOSED
			), '', '', false, false, true);
			$this->n_closed_tasks_others = TaskRecordPeer :: getTasksAssignedToOthers($db, $this->user->getId(), '', false, array (
				Constants :: TASK_CLOSED
			), '', '', false, false, true);

			$this->n_wip_tasks_me = TaskRecordPeer :: getTasksAssignedToMe($db, false, $this->user->getId(), false, array (
				Constants :: TASK_INPROGRESS
			), '', '', false, false, true);
			$this->n_wip_tasks_others = TaskRecordPeer :: getTasksAssignedToOthers($db, $this->user->getId(), '', false, array (
				Constants :: TASK_INPROGRESS
			), '', '', false, false, true);

			$this->n_total_tasks = $this->n_open_tasks + $this->n_review_pending_tasks_me + $this->n_review_pending_tasks_others + $this->n_closed_tasks_me + $this->n_closed_tasks_others + $this->n_wip_tasks_me + $this->n_wip_tasks_others;
			$this->n_wip_tasks = $this->n_wip_tasks_me + $this->n_wip_tasks_others;
			$this->n_review_pending_tasks = $this->n_review_pending_tasks_me + $this->n_review_pending_tasks_others;
			$this->n_closed_tasks = $this->n_closed_tasks_me + $this->n_closed_tasks_others;
			
			$this->drawTaskPieChart($this->n_total_tasks, $this->n_open_tasks, $this->n_wip_tasks, $this->n_review_pending_tasks, $this->n_closed_tasks);
			
			$this->n_open_issues = IssuePeer :: getIssuesPostedByUserId($db, $this->user->getId(), array (
				Constants :: ISSUE_OPEN
			), '', '', false, false, true);
			$this->n_closed_issues = IssuePeer :: getIssuesPostedByUserId($db, $this->user->getId(), array (
				Constants :: ISSUE_CLOSED
			), '', '', false, false, true);
			$this->n_total_issues = $this->n_open_issues + $this->n_closed_issues;
			/* Abhilash 9.4.15 */
			$this->taskpie = $this->n_total_tasks > 0;
			
			$this->issuepie = $this->n_total_issues > 0;
			
			$this->drawIssuePieChart($this->n_total_issues, $this->n_open_issues, $this->n_closed_issues); /*megha 17.9.14*/
		}

		return new FlexyView('user/dashboard.html', $this);
	}
	
	function chart_image($args) {
		$this->common($args);
		$chart_file = Util :: getChartFilePath($this->user->getId(), $args['file'].".png");
		if (file_exists($chart_file)) {
					Util :: downloadFile($chart_file);
		}
		exit();
	}
	

	// jl jl 03-01-2013
	private function getProjectLinkDetails($db, $task_record) {
		$config = $this->getConfig();
		$project_record = ProjectRecordPeer :: findByPK($db, $task_record->getParentProjectId());
		if ($project_record != null) {
			$task_record->project_name = $project_record->getName();
			$task_record->project_description = $project_record->getDescription();
			$view_url = $this->getAbsoluteURL('/project/view/id/' . $project_record->getId());
			$more_link = '<a href="' . $view_url . '" class="more_link">...</a>';
			//$task_record->project_short_name = Util :: truncate($project_record->getName(), 100, $more_link);
			$task_record->project_short_name = Util :: truncate($project_record->getName(), 65, $more_link); /*megha 10.1.15*/
			$task_record->project_id = $project_record->getId();

			$project_icons_folder = Util :: getAttachmentFolderName($config, $project_record->getType());
			if ($project_record->getIconName() != null) {
				$project_icon_name = $project_record->getIconName();
				$task_record->project_icon = $config->getValue('FW', 'base_url') . '/' . $project_icons_folder . $project_record->getId() . '/' . $project_icon_name;
			} else {
				$project_icon_name = 'default.png';
				$task_record->project_icon = $this->getAbsoluteImageURL('') . '/' . $project_icon_name;
			}
		}
		return $task_record;
	}

	/*megha 18.9.14*/
	private function drawTaskPieChart($n_tasks, $open, $inprogress, $review, $closed) {
		$chart_file = Util :: getChartFilePath($this->user->getId(), "pie1.png");
		if (file_exists($chart_file)) {
			@ unlink($chart_file);
		}
		if ($n_tasks <= 0) {
			return;
		}

		$image = imagecreate(120, 120);
		$background_color = imagecolorallocate($image, 255, 255, 255);

		$start_Angle = 0;
		$end_Angle = ((360 * $open) / $n_tasks) + $start_Angle;
		$end_Angle1 = ((360 * $inprogress) / $n_tasks) + $end_Angle;
		$end_Angle2 = ((360 * $review) / $n_tasks) + $end_Angle1;
		$end_Angle3 = ((360 * $closed) / $n_tasks) + $end_Angle2;

		$SA = $start_Angle;
		$SA1 = $end_Angle;
		$SA2 = $end_Angle1;
		$SA3 = $end_Angle2;

		$EA = $end_Angle;
		$EA1 = $end_Angle1;
		$EA2 = $end_Angle2;
		$EA3 = $end_Angle3;

		// allocate some colors		
		$orange = imagecolorallocate($image, 0xFF, 0x66, 0x00);
		$blue = imagecolorallocate($image, 0x09, 0x8D, 0xAE);
		$green = imagecolorallocate($image, 0x51, 0xF7, 0x1E);
		$gray = imagecolorallocate($image, 0xA9, 0xAB, 0xAC);

		//imagefilledarc($image, centerX, centerY, width, height, startAngle, EndAngle, $blue, IMG_ARC_PIE);
		if ($SA != $EA) {
			imagefilledarc($image, 60, 60, 120, 120, $SA, $EA, $orange, IMG_ARC_PIE);
		}
		if ($SA1 != $EA1) {
			imagefilledarc($image, 60, 60, 120, 120, $SA1, $EA1, $blue, IMG_ARC_PIE);
		}
		if ($SA2 != $EA2) {
			imagefilledarc($image, 60, 60, 120, 120, $SA2, $EA2, $green, IMG_ARC_PIE);
		}
		if ($SA3 != $EA3) {
			imagefilledarc($image, 60, 60, 120, 120, $SA3, $EA3, $gray, IMG_ARC_PIE);
		}
		// flush image
		imagepng($image, $chart_file);
		imagedestroy($image);

	}
	/*megha 18.9.14*/
	private function drawIssuePieChart($n_issues, $open, $closed) {

		$chart_file = Util :: getChartFilePath($this->user->getId(), "pie2.png");
		if (file_exists($chart_file)) {
			@ unlink($chart_file);
		}
		if ($n_issues <= 0) {
			return;
		}

		$image = imagecreate(120, 120);
		$background_color = imagecolorallocate($image, 255, 255, 255);

		$start_Angle = 0;
		$end_Angle = ((360 * $open) / $n_issues) + $start_Angle;
		$end_Angle1 = ((360 * $closed) / $n_issues) + $end_Angle;

		$SA = $start_Angle;
		$SA1 = $end_Angle;

		$EA = $end_Angle;
		$EA1 = $end_Angle1;

		// allocate some colors		
		$orange = imagecolorallocate($image, 0xFF, 0x66, 0x00);
		$gray = imagecolorallocate($image, 0xA9, 0xAB, 0xAC);

		//imagefilledarc($image, centerX, centerY, width, height, startAngle, EndAngle, $blue, IMG_ARC_PIE);
		if ($SA != $EA) {
			imagefilledarc($image, 60, 60, 120, 120, $SA, $EA, $orange, IMG_ARC_PIE);
		}
		if ($SA1 != $EA1) {
			imagefilledarc($image, 60, 60, 120, 120, $SA1, $EA1, $gray, IMG_ARC_PIE);
		}
		// flush image
		imagepng($image, $chart_file);
		imagedestroy($image);
	}

}