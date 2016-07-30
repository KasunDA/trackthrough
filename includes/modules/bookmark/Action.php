<?php
require_once 'BaseController.php';
require_once 'FlexyView.php';
require_once 'Db.php';
require_once 'Constants.php';
require_once 'PreferenceRecordPeer.php';
require_once 'ProjectRecordPeer.php';
require_once 'TaskRecordPeer.php';
require_once 'MessageRecordPeer.php';
require_once 'MessageBoardRecordPeer.php';
require_once 'UserRecordPeer.php';
require_once 'BookmarkRecordPeer.php';
require_once 'ConfigRecordPeer.php';
require_once 'ActionHelper.php';
require_once 'AppLogPeer.php';
require_once 'BookmarkRecord.php';
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
	function index($args) {
		$this->common($args);
		$config = $this->getConfig();
		//$meta_data = Util :: getMetaData('bookmark', $config);
		$this->title = "Bookmarks"; /* Abhilash 26-10-13 */
		if (!$this->getIsAuthorised()) {
			$this->appendErrorMessage('Unknown access, authorization required! ');
			return new FlexyView('error.html', $this);
		}
		$this->bookmarks = true;
		$this->bookmark_records = array ();

		try {
			$bookmarks = array ();
			$config = $this->getConfig();
			$db = Db :: getInstance($config);
			$bookmarks = BookmarkRecordPeer :: getBookmarks($db, $this->user->getId());

			if (!empty ($bookmarks)) {
				$bookmarks = BookmarkRecordPeer :: getBookmarksWithCategory($db, $config, $bookmarks);
				if ($bookmarks != null) {
					foreach ($bookmarks as $bookmark) {
						$bookmark = $this->updateBookmarkDescription($bookmark);

						if ($bookmark->getIsProjectBookmark() || $bookmark->getIsProjectMessageBookmark()) {
							$project_id = ($bookmark->getIsProjectMessageBookmark()) ? $bookmark->getMessageProjectId() : $bookmark->getCategoryId();
							$project_record = ProjectRecordPeer :: findByPK($db, $project_id);
							if ($project_record != null) {
								$project_icon = ActionHelper :: getProjectIcon($this, $project_record);
								$bookmark->setBookmarkIcon($project_icon);
							}
						}
						if ($bookmark->getIsTaskBookmark() || $bookmark->getIsTaskMessageBookmark()) {
							$task_id = ($bookmark->getIsTaskMessageBookmark()) ? $bookmark->getMessageTaskId() : $bookmark->getCategoryId();
							$task_record = TaskRecordPeer :: findByPK($db, $task_id);
							if ($task_record != null) {
								if ($task_record->getStatus() == Constants :: TASK_OPEN) {
									$task_icon = $this->getAbsoluteImageURL('open_status.png');
									$bookmark->task_status = "Open";
								}
								if ($task_record->getStatus() == Constants :: TASK_INPROGRESS) {
									$task_icon = $this->getAbsoluteImageURL('inprogress_status.png');
									$bookmark->task_status = "In progress ";
								}
								if ($task_record->getStatus() == Constants :: TASK_CLOSED) {
									$task_icon = $this->getAbsoluteImageURL('closed_status.png');
									$bookmark->task_status = "Closed";
								}
								if ($task_record->getStatus() == Constants :: TASK_REVIEW_PENDING) {
									$task_icon = $this->getAbsoluteImageURL('pending_status.png');
									$bookmark->task_status = "Review Pending";
									
								}
								$bookmark->view_only_for_self = false;
								if(!$this->isAdmin && !UserPermissionPeer :: canLeadTask($db, $this->user->getId(), $task_record->getId())) {
									$bookmark->view_only_for_self = UserPermissionPeer :: canExecTask($db, $this->user->getId(), $task_record->getId()) ? false : true;
								}
								
								/* megha 13.3.15*/
								if ($bookmark->view_only_for_self == true) {
									$task_icon = $this->getAbsoluteImageURL('viewonly_status.png');
									$bookmark->task_status = "View Only";
								}
								/* *** */
				
								$bookmark->setBookmarkIcon($task_icon);
								$issue_task = IssuePeer :: isIssuePromotedAsTask($db, $task_record); /* Abhilash */
								$bookmark->isPromoted = ($issue_task != null) ? true : false;
							}
						}
						if ($bookmark->getIsIssueBookmark() || $bookmark->getIsIssueMessageBookmark()) {
							$issue_id = ($bookmark->getIsIssueMessageBookmark()) ? $bookmark->getMessageIssueId() : $bookmark->getCategoryId();
							$issue_record = IssuePeer :: findByPK($db, $issue_id);
							if ($issue_record != null) {
								if ($issue_record->getStatus() == Constants :: ISSUE_OPEN) {
									$issue_icon = $this->getAbsoluteImageURL('open_status.png');
									$bookmark->issue_status = "Open";
								}
								if ($issue_record->getStatus() == Constants :: ISSUE_CLOSED) {
									$issue_icon = $this->getAbsoluteImageURL('closed_status.png');
									$bookmark->issue_status = "Closed";
								}
								$issue_task = IssuePeer :: isIssuePromotedAsTask($db, $issue_record); /* Abhilash */
								$bookmark->isPromoted = ($issue_task != null) ? true : false;
								$bookmark->setBookmarkIcon($issue_icon);
							}
						}
						$this->bookmark_records[] = $bookmark;
					}
				}
			}
			$this->no_records = empty ($this->bookmark_records) ? true : false;
			$this->total_records = count($this->bookmark_records);				/* Abhilash 28-10-13 */
		} catch (Exception $exception) {
			$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
		}
		return new FlexyView('bookmark/bookmarks.html', $this);
	}

	function delete($args) {
		$this->common($args);
		
		$ids_for_delete = array();
				if(isset ($args['id']) &&  $args['id'] != '') {
					$ids_for_delete[] = $args['id'];
				}
				if(isset ($args['selected_id']) &&  !empty($args['selected_id'])) {
					$ids_for_delete = array_merge($args['selected_id'], $ids_for_delete);
				}

		if ( empty($ids_for_delete)) {
			$this->appendErrorMessage('Error, you did not select any bookmarks for deletion!');
		}
		
		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($this->getConfig());
				
				
				BookmarkRecordPeer :: deleteBookmarks($db, $ids_for_delete);
			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}

		$new_args = array ();
		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);

		} else {
			$this->setFlashMessage('Bookmark deleted');
		}

		$this->callModuleMethod('bookmark', 'index', $new_args);

	}

	function project($args) {
		$this->common($args);
		if (!isset ($args['id']) || $args['id'] == '') {
			$this->appendErrorMessage('Could not find dataset!');
		}
		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($this->getConfig());
				$category_id = $args['id'];
				$project_record = ProjectRecordPeer :: findByPK($db, $category_id);
				if ($project_record == null) {
					throw new Exception(' could not find dataset!');
				}
				$category = $project_record->getType();
				$user_id = $this->user->getId();
				BookmarkRecordPeer :: bookmark($db, $user_id, $category, $category_id);

			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		$new_args = array ();

		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
		} else {
			$this->setFlashMessage('Project bookmarked');
		}
		if ((!$this->has_error) && (isset ($args['search_text'])) && ($args['search_text'] != '')) {
			$new_args['search_text'] = $args['search_text'];
			$this->callModuleMethod('search', 'index', $new_args);
		} else
			if (isset ($args['id'])) {
				$new_args['id'] = $args['id'];
				$this->callModuleMethod('project', 'view', $new_args);
			} else {

				$this->callModuleMethod('project', 'index', $new_args);
			}
	}

	function task($args) {
		$this->common($args);
		if (!isset ($args['id']) || $args['id'] == '') {
			$this->appendErrorMessage('Could not find dataset!');
		}
		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($this->getConfig());
				$category_id = $args['id'];
				$task_record = TaskRecordPeer :: findByPK($db, $category_id);
				if ($task_record == null) {
					throw new Exception(' could not find dataset!');
				}
				$category = $task_record->getType();
				$user_id = $this->user->getId();
				BookmarkRecordPeer :: bookmark($db, $user_id, $category, $category_id);
				$this->setFlashMessage('Task bookmarked');
				
				if ((isset ($args['from_page'])) && ($args['from_page'] == 'dashboard')) {
					$this->callModuleMethod('dashboard', 'index', array());
				}
				else if ((isset ($args['from_page'])) && ($args['from_page'] == 'report')) {
					$this->callModuleMethod('report', 'task', array());
				}
				else if ( isset ($args['search_text']) && ($args['search_text'] != '')) {
					$new_args = array ();
					$new_args['search_text'] = $args['search_text'];
					$this->callModuleMethod('search', 'index', $new_args);
				}
				
			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		$new_args = array ();

		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
		} 
		

		if (isset ($args['id'])) {
			$new_args['id'] = $args['id'];

			if ((isset ($args['page_index'])) && ($args['page_index'] != '')) {

				$task_comments_per_page = PreferenceRecordPeer :: getTaskCommentsPerPage($this, $this->user->getId());

				$new_args['from'] = ($args['page_index'] - 1) * $task_comments_per_page;
				$new_args['page_index'] = $args['page_index'];
			}
			$this->callModuleMethod('task', 'view', $new_args);
		} else {

			$this->callModuleMethod('project', 'index', $new_args);
		}
	}
	function issue($args) {
		$this->common($args);
		if (!isset ($args['id']) || $args['id'] == '') {
			$this->appendErrorMessage('Could not find dataset!');
		}
		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($this->getConfig());
				$category_id = $args['id'];
				$issue_record = IssuePeer :: findByPK($db, $category_id);
				if ($issue_record == null) {
					throw new Exception(' could not find dataset!');
				}
				$category = $issue_record->getType();
				$user_id = $this->user->getId();
				BookmarkRecordPeer :: bookmark($db, $user_id, $category, $category_id);
				$this->setFlashMessage('Issue bookmarked');
				if (isset ($args['search_text']) && ($args['search_text'] != '')) {
					$new_args = array ();
					$new_args['search_text'] = $args['search_text'];
					$this->callModuleMethod('search', 'index', $new_args);
				}
				else if ((isset ($args['from_page'])) && ($args['from_page'] == 'report')) {
					$this->callModuleMethod('report', 'issue', array());
				}
			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		$new_args = array ();

		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
		}

		if (isset ($args['id'])) {
			$new_args['id'] = $args['id'];
			$this->callModuleMethod('issue', 'view', $new_args);
		} else {
			$this->callModuleMethod('project', 'index', $new_args);
		}
	}

	function message($args) {
		$this->common($args);
		if (!isset ($args['id']) || $args['id'] == '') {
			$this->appendErrorMessage('Could not find dataset!');
		}
		$view_id = null;

		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($this->getConfig());
				$category_id = $args['id'];
				$message_record = MessageRecordPeer :: findByPK($db, $category_id);
				if ($message_record == null) {
					throw new Exception(' could not find dataset!');
				}
				$category = $message_record->getType();
				$user_id = $this->user->getId();
				BookmarkRecordPeer :: bookmark($db, $user_id, $category, $category_id);

				$view_id = $message_record->getTypeId();
				if ($category == Constants :: PROJECT_MESSAGE) {
					$view_type = Constants :: PROJECT_MESSAGE;
				} else
					if ($category == Constants :: TASK_MESSAGE) {
						$view_type = Constants :: TASK_MESSAGE;
					} else
						if ($category == Constants :: ISSUE_MESSAGE) {
							$view_type = Constants :: ISSUE_MESSAGE;
						}

				if ($view_type == Constants :: PROJECT_MESSAGE) {
					$this->setFlashMessage('Description bookmarked');
				} else {
					$this->setFlashMessage('Comment bookmarked');
				}
				$new_args = array ();
				if (isset ($args['from_page']) && ($args['from_page'] == 'messageboard')) {
					$new_args['from'] = $this->getParameter('MESSAGEBOARD_PAGE_FROM');
					if($new_args['from'] == null) {
						$new_args['from'] = 0;
					}
					$new_args['page_index'] = $this->getParameter('MESSAGEBOARD_PAGE_INDEX');
					if($new_args['page_index'] == null) {
						$new_args['page_index'] = 0;
					}
					
					$this->callModuleMethod('message', 'index', $new_args);
				}
				if (isset ($args['from_page']) && ($args['from_page'] == 'dashboard')) {
					$this->callModuleMethod('dashboard', 'index', array ());
				}
				if (isset ($args['search_text']) && ($args['search_text'] != '')) {
					$new_args['search_text'] = $args['search_text'];
					$this->callModuleMethod('search', 'index', $new_args);
				}
				$new_args['id'] = $view_id;
				if ($view_type == Constants :: PROJECT_MESSAGE) {
					$this->callModuleMethod('project', 'view', $new_args);
				} else
					if ($view_type == Constants :: TASK_MESSAGE) {
						if ((isset ($args['page_index'])) && ($args['page_index'] != '')) {

							$task_comments_per_page = PreferenceRecordPeer :: getTaskCommentsPerPage($this, $this->user->getId());
							$new_args['from'] = ($args['page_index'] - 1) * $task_comments_per_page;
							$new_args['page_index'] = $args['page_index'];
						}
						$this->callModuleMethod('task', 'view', $new_args);
					} else
						if ($view_type == Constants :: ISSUE_MESSAGE) {
							$this->callModuleMethod('issue', 'view', $new_args);
						}

			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}

		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
		}

		$this->callModuleMethod('project', 'index', array ());

	}

	private function updateBookmarkDescription($bookmark) {
		$more_link = '...';
		//$bookmark->short_description = Util :: truncate($bookmark->getBookmarkDescription(), 150, $more_link); /* abhilash 16-10-13 */
		$bookmark->short_description = Util :: truncate($bookmark->getBookmarkDescription(), 130, $more_link); /* megha 11-03-15 */
		
		$bookmark->short_description = wordwrap($bookmark->short_description, 200, "\n", 1);
		return $bookmark;
	}

}
?>