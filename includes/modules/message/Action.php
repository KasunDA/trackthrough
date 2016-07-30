<?php
require_once 'BaseController.php';
require_once 'FlexyView.php';
require_once 'Db.php';
require_once 'Constants.php';
require_once 'ActionHelper.php';
require_once 'CommonRecord.php';
require_once 'MessageRecord.php';
require_once 'MessageRecordPeer.php';
require_once 'MessageBoardRecordPeer.php';
require_once 'BookmarkRecordPeer.php';
require_once 'UserRecord.php';
require_once 'UserRecordPeer.php';
require_once 'ConfigRecordPeer.php';
require_once 'AppLogPeer.php';
require_once 'ProjectRecordPeer.php';
require_once 'PageCollection.php';

class Action extends FW_BaseController {
	private function common($args) {
		$this->isAdmin = false;
		$this->isLanding = true;
		$this->version = Util :: getVersion();
		$this->message_f = $this->getFlashMessageObj();

		if ($this->getIsAuthorised()) {
			$this->isLanding = false;
			$user_id = $this->getParameter('USER_ID');
			$this->theme_color = $this->getParameter('THEME_COLOR');
			$this->theme_pallette = ActionHelper :: getThemePallette($this, $this->theme_color);
			$config = $this->getConfig();
			$this->user = UserRecordPeer :: findByConfigAndPK($config, $user_id);

			$this->isAdmin = $this->getParameter('is_admin');
		}
		$this->record_count = MessageBoardRecordPeer :: getUnreadCountsForUser($config, $user_id);
		$this->unreadMessages = ($this->record_count > 0) ? true : false;
		$this->messages = true;
	}

	function index($args) {
		$this->common($args);

		$config = $this->getConfig();
		//$meta_data = Util :: getMetaData('messages', $config);
		$this->title = "Messages"; /* Abhilash 26-10-13 */
		if (!$this->getIsAuthorised()) {
			$this->appendErrorMessage('Unknown access, authorization required! ');
			return new FlexyView('error.html', $this);
		}
		$this->show_unread_only = isset ($args['type']) && $args['type'] == 'unread';

		try {
			$config = $this->getConfig();
			$db = Db :: getInstance($config);
			$user_id = $this->user->getId();

			$from = isset ($args['from']) ? $args['from'] : 0;
			$offset = $from;
			$limit = PreferenceRecordPeer :: getMaxMessagesPerPage($this, $user_id);

			$message_board_records = array ();
			$total_records = 0;
			$page_url = '';
			if ($this->show_unread_only) {
				$message_board_records = MessageBoardRecordPeer :: getUnreadMessageList($db, $user_id, $offset, $limit);
				$total_records = MessageBoardRecordPeer :: getUnreadCountsForUser($config, $user_id);
				$page_url = $this->getAbsoluteURL('/message/index/type/' . $args['type']);
			} else {
				$message_board_records = MessageBoardRecordPeer :: getUserMessageBoardRecords($db, $user_id, '', false, $offset, $limit, '');
				$total_records = MessageBoardRecordPeer :: getUserMessageCount($db, $user_id, '', false);
				$page_url = $this->getAbsoluteURL('/message/index');
			}
			//pagination
			///////////////////////////				

			$this->pc = new PageCollection($args, $limit, $total_records, $page_url);
			$this->setParameter('MESSAGEBOARD_PAGE_FROM', $from);
			$this->setParameter('MESSAGEBOARD_PAGE_INDEX', isset ($args['page_index']) ? $args['page_index'] : 0);

			///////////////////////////////

			$message_ids = array ();
			$message_id_inbox_id_assoc = array ();

			//maintain order ???
			for ($cnt = 0; $cnt < count($message_board_records); $cnt++) {
				$message_ids[] = $message_board_records[$cnt]->getMessageId();
				$message_id_inbox_id_assoc[$message_board_records[$cnt]->getMessageId()] = $message_board_records[$cnt]->getId();
			}

			$this->message_records = empty ($message_ids) ? array () : MessageRecordPeer :: getMessageRecords($db, $message_ids);

			for ($cnt = 0; $cnt < count($this->message_records); $cnt++) {
				$message_record = $this->message_records[$cnt];
				$message_board_record = $message_board_records[$cnt];
				if ($message_board_record->getIsUnread()) {
					$message_record->setIsUnread(true);
				}
				$message_record = MessageRecordPeer :: getMessageWithName($db, $message_record);

				//$message_record = ActionHelper :: updateProjectLinkDetails($this, $message_record, 140);
				$message_record = ActionHelper :: updateProjectLinkDetails($this, $message_record, 95); /*megha 10.1.15*/

				$message = $message_record->getCont();
				if ($message_record->getSubject() != '') {
					$message = $message_record->getSubject() . "-" . $message;
				}
				$message_record->short_description = Util :: truncate($message, 135, '..');
				$message_record->isBookmarked = BookmarkRecordPeer :: getIsBookmarked($db, $this->user->getId(), $message_record->getType(), $message_record->getId());
				if (isset ($message_id_inbox_id_assoc[$message_record->getId()])) {
					$message_record->message_board_id = $message_id_inbox_id_assoc[$message_record->getId()];
				}
				$this->message_records[$cnt] = $message_record;
			}
			$this->no_records = empty ($this->message_records) ? true : false;

		} catch (Exception $exception) {
			throw new Exception('Error while retrieving # new messages; ' . $exception->getMessage());
		}
		return new FlexyView('message/messageBoard.html', $this);
	}
	function download($args) {
		$this->common($args);
		if (!isset ($args['message_id'])) {
			$this->appendErrorMessage('Error, message id  undefined!');
		}

		if (!isset ($args['file_name'])) {
			$this->appendErrorMessage('Error, file name  undefined!');
		}

		$config = $this->getConfig();

		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($config);
				$message_id = $args['message_id'];
				$message_record = MessageRecordPeer :: findByPK($db, $message_id);

				if ($message_record == null) {
					throw new Exception(' error finding dataset!');
				}
				$project_record = null;

				switch ($message_record->getType()) {
					case Constants :: PROJECT_MESSAGE :
						$project_record = ProjectRecordPeer :: findByPK($db, $message_record->getTypeId());
						if (is_null($project_record) || !ActionHelper :: isAuthorizedProject($this, $project_record)) {
							throw new Exception(' you are not authorized to download the content!');
						}
						break;

					case Constants :: TASK_MESSAGE :
						$task_record = TaskRecordPeer :: findByPK($db, $message_record->getTypeId());

						if (is_null($task_record) || !ActionHelper :: isAuthorizedTask($this, $task_record)) {
							throw new Exception(' you are not authorized to download the content!');
						}

						$project_record = ProjectRecordPeer :: findByPK($db, $task_record->getParentProjectId());

						break;

					case Constants :: ISSUE_MESSAGE :
						$issue_record = IssuePeer :: findByPK($db, $message_record->getTypeId());
						if (!is_null($issue_record)) {
							$project_record = ProjectRecordPeer :: findByPK($db, $issue_record->getProjectId());
						}
						if (is_null($project_record) || !ActionHelper :: isAuthorizedIssue($this, $project_record)) {
							throw new Exception(' you are not authorized to download the content!');
						}
						break;

					default :
						//general message
						break;
				}

				if ($project_record == null) {
					throw new Exception(' error finding project dataset!');
				}

				$project_name = Util :: truncate($project_record->getName(), 30, '');

				$attachment_folder = Util :: getAttachmentFolderName($config, $message_record->getType());

				$attachment_name = Util :: getAttachmentNamePrefix($message_record) . $args['file_name'];
				$target_file = Util :: getAttachmentFilePath($project_record->getId(), $attachment_name, $attachment_folder);

				if (isset ($args['thumb']) && $args['thumb']) { //additional size prefix in future
					$attachment_name_thumb = 'thumb_' . $args['thumb'] . '_' . $attachment_name;
					$target_file_thumb = Util :: getAttachmentFilePath($project_record->getId(), $attachment_name_thumb, $attachment_folder);
					if (!file_exists($target_file_thumb)) {
						$attachment_name_thumb = 'thumb_' . $attachment_name;
						$target_file_thumb = Util :: getAttachmentFilePath($project_record->getId(), $attachment_name_thumb, $attachment_folder);
						if (file_exists($target_file_thumb)) {
							$target_file = $target_file_thumb;
						}
					}
				}

				$parts = pathinfo($target_file);

				$content_type = isset ($parts['extension']) ? $parts['extension'] : 'text';

				$base_name = $parts['basename'];

				if (file_exists($target_file)) {
					Util :: downloadFile($target_file);
					return;
				} else {
					throw new Exception(' no such attachment exists!');
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

	function revoke($args) {
		$this->common($args);
		if (!isset ($args['message_id'])) {
			$this->appendErrorMessage('There was an error; Message id is not set!');
		}
		$config = $this->getConfig();
		if (!$this->isAdmin) {
			$this->appendErrorMessage('Can not revoke - You are not authorized! ');
		}
		$type_of_message = "";
		$view_id = 0;

		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($config);
				$message_record = CommonRecord :: findById($db->getPrefix() . MessageRecord :: TABLE_NAME, $args['message_id'], new MessageRecord($db));
				if ($message_record == null) {
					throw new Exception(' no such dataset found!');
				}
				if (!$message_record->getIsDeleted()) {
					throw new Exception(' can not revoke - the message has not been deleted! ');
				}
				if (!$this->isAdmin) {
					throw new Exception(' can not revoke - You are not authorized! ');
				}
				$message_record->setStatus(Constants :: NONE);
				$message_record->store();
				$type_of_message = $message_record->getType();
				$view_id = $message_record->getTypeId();

			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		$new_args = array ();

		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
		}

		if ($type_of_message == Constants :: PROJECT_MESSAGE) {
			$new_args['id'] = $view_id;
			$this->callModuleMethod('project', 'view', $new_args);
		}
		if ($type_of_message == Constants :: TASK_MESSAGE) {
			$new_args['id'] = $view_id;
			if (isset ($args['page_index']) && ($args['page_index'] != '')) {
				$new_args['page_index'] = $args['page_index'];
			}
			$this->callModuleMethod('task', 'view', $new_args);
		}
		$this->callModuleMethod('project', 'index', $new_args);

	}
	function deleteInbox($args) {
		$this->common($args);

		$ids_for_delete = array ();
		if (isset ($args['message_board_id']) && $args['message_board_id'] != '') {
			$ids_for_delete[] = $args['message_board_id'];
		}
		if (isset ($args['selected_id']) && !empty ($args['selected_id'])) {
			$ids_for_delete = array_merge($args['selected_id'], $ids_for_delete);
		}

		if (empty ($ids_for_delete)) {
			$this->appendErrorMessage('Error, you did not select any messages for deletion!');
		}

		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($this->getConfig());

				MessageBoardRecordPeer :: deleteMessageBoardRecords($db, $ids_for_delete);
			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}

		$new_args = array ();
		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);

		} else {
			$this->setFlashMessage('Message deleted');
		}

		$this->callModuleMethod('message', 'index', $new_args);

	}
	//todo - authentication required ???
	function delete($args) {

		$this->common($args);
		if (!isset ($args['message_id'])) {
			$this->appendErrorMessage('There was an error; Message id is not set!');
		}
		$config = $this->getConfig();
		//for project message redirection

		$type_of_message = "";
		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($config);
				$message_record = CommonRecord :: findById($db->getPrefix() . MessageRecord :: TABLE_NAME, $args['message_id'], new MessageRecord($db));

				if ($message_record == null) {
					throw new Exception(' no such dataset found!');
				}
				MessageBoardRecordPeer :: deleteUserMessageBoardRecord($config, $message_record, '');
				$type_of_message = $message_record->getType(); //GENERAL_MESSAGE or PROJECT_MESSAGE

				$type_id = $message_record->getTypeId();

				if ($type_of_message == Constants :: TASK_MESSAGE) {

					$task_record = TaskRecordPeer :: findByPK($db, $type_id);
					if ($task_record->getIsClosed()) {
						throw new Exception(' can not delete a comment when the task is closed.');
					}
				} else
					if ($type_of_message == Constants :: ISSUE_MESSAGE) {

						$issue_record = IssuePeer :: findByPK($db, $type_id);
						if ($issue_record->getIsClosed()) {
							throw new Exception(' can not delete a comment when the issue is closed.');
						}
					}

				$bookmarks = BookmarkRecordPeer :: getCategoryBookmarks($db, $message_record->getType(), $message_record->getId());
				if (!empty ($bookmarks)) {
					$ids_for_delete = array ();
					foreach ($bookmarks as $bookmark) {
						$ids_for_delete[] = $bookmark->getId();
					}
					BookmarkRecordPeer :: deleteBookmarks($db, $ids_for_delete);
				}

				if ($this->isAdmin) {
					//delete attachments

					$message_attachment_files = explode(':', $message_record->getAttachmentName());
					$deleted_files = array ();

					if (is_array($message_attachment_files) && !empty ($message_attachment_files)) {
						$deleted_files = Util :: deleteAttachments($type_of_message, $config, $message_attachment_files);
					}

					$table_name = $db->getPrefix() . MessageRecord :: TABLE_NAME;
					CommonRecord :: delete($db, $table_name, CommonRecord :: ID_COL, array (
						$args['message_id']
					));
				} else
					if ($message_record->getIsDeleted() && !$this->isAdmin) {
						throw new Exception(' can not delete - You are not authorized! ');
					} else
						if ($message_record->getFromId() == $this->user->getId()) {
							$message_record->setStatus(Constants :: DELETED_MESSAGE);
							$message_record->store();
						}

				//for project messages
				$type_id = $message_record->getTypeId();

			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		$new_args = array ();

		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
		}

		if ($type_of_message == Constants :: PROJECT_MESSAGE) {
			$new_args['id'] = $type_id;
			$this->callModuleMethod('project', 'view', $new_args);
		}
		if ($type_of_message == Constants :: TASK_MESSAGE) {
			$new_args['id'] = $type_id;
			if (isset ($args['page_index']) && ($args['page_index'] != '')) {
				$new_args['page_index'] = $args['page_index'];
			}
			$this->callModuleMethod('task', 'view', $new_args);
		}

		if ($type_of_message == Constants :: ISSUE_MESSAGE) {
			$new_args['id'] = $type_id;
			$this->callModuleMethod('issue', 'view', $new_args);
		}

		$this->callModuleMethod('message', 'index', $new_args);
	}

	function bookmarkMessage($args) {
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
				if ($message_record != null) {
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

				}
			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		$new_args = array ();
		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
		} else {
			if ($view_type == Constants :: PROJECT_MESSAGE) {
				$this->setFlashMessage('Description bookmarked');
			} else {
				$this->setFlashMessage('Comment bookmarked');
			}
		}

		//jl, we are passing from argument only from dahsboard page
		if ((!$this->has_error) && (isset ($args['from_page'])) && ($args['from_page'] == 'dashboard')) {
			$this->callModuleMethod('dashboard', 'index', $new_args);
		}

		if ((!$this->has_error) && (isset ($args['search_text'])) && ($args['search_text'] != '')) {
			$new_args['search_text'] = $args['search_text'];
			$this->callModuleMethod('search', 'index', $new_args);
		}
		if ($view_id != null) {
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

		} else {
			$this->callModuleMethod('project', 'index', $new_args);
		}
	}

}
?>