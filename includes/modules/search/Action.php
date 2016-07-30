<?
require_once 'BaseController.php';
require_once 'FlexyView.php';
require_once 'Db.php';
require_once 'Constants.php';
require_once 'ConfigRecord.php';
require_once 'ConfigRecordPeer.php';
require_once 'UserRecord.php';
require_once 'UserRecordPeer.php';
require_once 'MessageRecord.php';
require_once 'ActionHelper.php';
require_once 'AppLogPeer.php';
require_once 'MessageRecordPeer.php';
require_once 'MessageBoardRecordPeer.php';
require_once 'ProjectRecordPeer.php';
require_once 'BookmarkRecord.php';
require_once 'BookmarkRecordPeer.php';
require_once 'PreferenceRecord.php';
require_once 'UserPermissionPeer.php';
require_once 'PageCollection.php';

class Action extends FW_BaseController {
	private function common($args) {
		$this->isAdmin = false;
		$this->isLanding = true;
		$this->company_name = ConfigRecordPeer :: getCompanyName($this);
		$this->version = Util :: getVersion();

		$this->message_f = $this->getFlashMessageObj();

		if ($this->getIsAuthorised()) {
			$this->isLanding = false;
			$config = $this->getConfig();
			$user_id = $this->getParameter('USER_ID');
			$this->theme_color = $this->getParameter('THEME_COLOR');
			$this->theme_pallette = ActionHelper::getThemePallette($this,$this->theme_color);
			$this->user = UserRecordPeer :: findByConfigAndPK($config, $user_id);
			
			$this->isAdmin = $this->getParameter('is_admin');
			$this->record_count = MessageBoardRecordPeer :: getUnreadCountsForUser($config, $user_id);
			$this->unreadMessages = ($this->record_count > 0) ? true : false;
		}
	}	

	function index($args) {
		$this->common($args);
		
		$config = $this->getConfig();
		//$meta_data = Util :: getMetaData('search', $config);
		$this->title = "Search Results"; /* Abhilash 26-10-13 */
		if (!$this->getIsAuthorised()) {
			$this->appendErrorMessage(' unknown access, authorization required ');
			return new FlexyView('error.html', $this);
		}
		if (!isset ($args['search_text']) || $args['search_text'] == '') { /* Abhilash 14.3.15 */
			$this->appendErrorMessage('You did not input any keyword to search. ');
			$this->search_error = true;
			return new FlexyView('search/results.html', $this);
		}
		try {
			$db = Db :: getInstance($config);
			$search_text = $args['search_text']; /* Abhilash 14.3.15 */
			
			$this->search_results = array ();
			$search_project_details = PreferenceRecordPeer::getSearchProjectDetails($this, $this->user->getId());
			$search_task_details = PreferenceRecordPeer::getSearchTaskDetails($this, $this->user->getId());
			$search_issue_details = PreferenceRecordPeer::getSearchIssueDetails($this, $this->user->getId());
			$search_messages = PreferenceRecordPeer::getSearchMessages($this, $this->user->getId());
			
			$search_results = ($this->isAdmin) ? UserRecordPeer :: search_text($db, $search_text, $this->user->getId(), true, $search_project_details, $search_task_details,$search_issue_details, $search_messages) :
			 UserRecordPeer :: search_text($db, $search_text, $this->user->getId(), false, $search_project_details, $search_task_details,$search_issue_details, $search_messages);

			if (count($search_results) > 0) {
				if (isset ($search_results['project_search'])) {
					foreach ($search_results['project_search'] as $project) {
						$project_record = ProjectRecordPeer :: findByPK($db, $project->id);

						$obj = new StdClass;
						$obj->project_action = true;
						$obj->pro_icon = ActionHelper::getProjectIcon($this, $project_record);
						$obj->search_id = $project->id;
						$obj->search_title = $this->updatesearchResult($project_record->getName(), 150);
						$obj->project_name_short = $this->updatesearchResult($project_record->getName(), 145);
						$obj->project_record = $project_record;
						
								
						$obj->search_description = $this->updatesearchResult($project_record->getDescription(), 148);
						$obj->search_type = 'project';
						$obj->isBookmarked  = BookmarkRecordPeer :: getIsBookmarked($db, $this->user->getId(),  Constants :: PROJECT, $project->id);
						$this->search_results[] = $obj;
					}
				}
				if (isset ($search_results['task_search'])) {
					foreach ($search_results['task_search'] as $task) {
						$task_record = TaskRecordPeer :: findByPK($db, $task->id);
						$project_record = ProjectRecordPeer :: findByPK($db, $task_record->getParentProjectId());
						
						$obj = new StdClass;
						$obj->view_only_for_self = false; /* Abhilash */
						if(!$this->isAdmin && !UserPermissionPeer :: canLeadTask($db, $this->user->getId(), $task_record->getId())) {
							$obj->view_only_for_self = UserPermissionPeer :: canExecTask($db, $this->user->getId(), $task_record->getId()) ? false : true;
						}
						$obj->task_action = true;
						$obj->pro_icon = ActionHelper::getProjectIcon($this, $project_record);
						$obj->project_record = $project_record;
						$obj->task_status_label = Constants :: getLabel($task_record->getStatus());
						$obj->task_icon = ActionHelper::getTaskIcon($this, $task_record);
						$obj->project_id = $project_record->getId();
						$obj->project_name_short = $this->updatesearchResult($project_record->getName(), 148);
						$obj->search_title = $this->updatesearchResult($task_record->getName(), 153);
						$obj->search_id = $task->id;
						$obj->search_description = $this->updatesearchResult($task_record->getDescription(), 158);
						$obj->search_type = 'task';
						$obj->isBookmarked  = BookmarkRecordPeer :: getIsBookmarked($db, $this->user->getId(),  Constants :: TASK, $task->id);
						$issue_task = IssuePeer :: isIssuePromotedAsTask($db, $task_record);

						$obj->isPromoted = ($issue_task != null) ? true : false;
						
						
						$this->search_results[] = $obj;
						
						
						
					}
				}
				if (isset ($search_results['issue_search'])) {
					foreach ($search_results['issue_search'] as $issue) {
						$issue_record = IssuePeer :: findByPK($db, $issue->id);
						$project_record = ProjectRecordPeer :: findByPK($db, $issue_record->getProjectId());
						
						$obj = new StdClass;
						$obj->issue_action = true;
						$obj->pro_icon = ActionHelper::getProjectIcon($this, $project_record);
						$obj->issue_status_label = Constants :: getLabel($issue_record->getStatus());
						$obj->issue_icon = ActionHelper::getIssueIcon($this, $issue_record);
						$obj->project_id = $project_record->getId();
						$obj->project_record = $project_record;
						$obj->project_name_short = $this->updatesearchResult($project_record->getName(), 155);
						$obj->search_title = $this->updatesearchResult($issue_record->getTitle(), 153);
						$obj->search_id = $issue->id;
						$obj->search_description = $this->updatesearchResult($issue_record->getDescription(), 158);
						$obj->search_type = 'issue';
						$obj->isBookmarked  = BookmarkRecordPeer :: getIsBookmarked($db, $this->user->getId(),  Constants :: ISSUE, $issue->id);
						
						$issue_task = IssuePeer :: isIssuePromotedAsTask($db, $issue_record);
						$obj->isPromoted = ($issue_task != null) ? true : false;
						
						
						$this->search_results[] = $obj;
					}
				}
				$message_ids = array();
				if (isset ($search_results['message_search'])) {
					foreach ($search_results['message_search'] as $message) {
						
						if(in_array($message->id, $message_ids)) {
							continue;
						}
						$message_ids[] = $message->id;
						
						$obj = new StdClass;
						$obj->search_description = $this->updatesearchResult($message->cont, 150);
						$obj->isBookmarked  = BookmarkRecordPeer :: getIsBookmarked($db, $this->user->getId(),  $message->type, $message->id);
						if ($message->type == Constants :: PROJECT_MESSAGE) {
							$project_record = ProjectRecordPeer :: findByPK($db, $message->type_id);

							$subject = $message->subject && ($message->subject != '') ? $message->subject : $project_record->getName();
							$obj->search_title = $this->updatesearchResult($subject, 80);
							$obj->project_action = true;
							$obj->project_record = $project_record;
							$obj->pro_icon = ActionHelper::getProjectIcon($this, $project_record);
							$obj->project_name_short = $this->updatesearchResult($project_record->getName(), 155);
								
							$obj->search_type = 'project message';
							$obj->search_id = $message->type_id;
							$obj->message_id = $message->id;
						} else
							if ($message->type == Constants :: TASK_MESSAGE) {
								$task_record = TaskRecordPeer :: findByPK($db, $message->type_id);
								if(!$task_record) {
									continue;
								}
								$project_record = ProjectRecordPeer :: findByPK($db, $task_record->getParentProjectId());
								$subject = $message->subject && ($message->subject != '') ? $message->subject : $project_record->getName();
						
								$obj->search_title = $this->updatesearchResult($subject, 90);
								$obj->task_action = true;
								$obj->pro_icon = ActionHelper::getProjectIcon($this, $project_record);
								$obj->project_id = $project_record->getId();
								$obj->project_record = $project_record;
								$obj->project_name_short = $this->updatesearchResult($project_record->getName(), 155);
								$obj->search_type = 'task message';
								$obj->search_id = $message->type_id;
								$obj->message_id = $message->id;
							} else
								if ($message->type == Constants :: ISSUE_MESSAGE) {
									$this->issue_record = IssuePeer :: findByPK($db, $message->type_id);
									if(is_null($this->issue_record)) {
										continue;
									}
									
									$project_record = ProjectRecordPeer :: findByPK($db, $this->issue_record->getProjectId());
									$subject = $message->subject && ($message->subject != '') ? $message->subject : $project_record->getName();
						
									$obj->search_title = $this->updatesearchResult($subject, 90);
									$obj->issue_action = true;
									$obj->pro_icon = ActionHelper::getProjectIcon($this, $project_record);
									$obj->project_id = $project_record->getId();
									$obj->project_record = $project_record;
									$obj->project_name_short = $this->updatesearchResult($project_record->getName(), 155);
									$obj->search_type = 'issue message';
									$obj->search_id = $message->type_id;
									$obj->message_id = $message->id;
								}
							$this->search_results[] = $obj;
					}
				}					
			}
			$this->search_text = $args['search_text'];
			$total_results = count($this->search_results);
			if ($total_results == 1) {
				$this->total_results = $total_results . ' match found.';
			} else {
				$this->total_results = $total_results . ' matches found.';
			}
			$this->no_results = (empty ($this->search_results)) ? true : false;
		} catch (Exception $exception) {
			throw new Exception('Error while searching; ' . $exception->getMessage());
		}
		$new_args = array ();
		if ($this->has_error) {
			$this->search_error = true;
			$this->setFlashMessage($this->error_message, true);
		}
		return new FlexyView('search/results.html', $this);
	}

	private function updatesearchResult($search_data, $limit) {
		$more_link = '...';
		$search_data = Util :: truncate($search_data, $limit, $more_link);
		$search_data = wordwrap($search_data, 100, "\n", 1);
		return $search_data;
	}
	
}
?>