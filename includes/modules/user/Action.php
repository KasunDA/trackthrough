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
		if ($this->getIsAuthorised()) {
			if ($this->user) {
				$this->callModuleMethod('project', 'index');
			}
		}
		$this->endSession($args);

		$this->loginBlock = true;
		$config = $this->getConfig();
		//$meta_data = Util :: getMetaData('index', $config);
		$this->title = "Index"; 		/* Abhilash 26-10-13 */

		return new FlexyView('landingPage.html', $this);

	}


  	function indexUserSettings($args) {  		
		$this->common($args);
		$config = $this->getConfig();
		//$meta_data = Util :: getMetaData('settings', $config);
		$this->title = "Settings";		/* Abhilash 26-10-13 */
		if (!$this->getIsAuthorised()) {
			$this->appendErrorMessage('Unknown access, authorization required! ');
			return new FlexyView('error.html', $this);
		}
		$this->updateSettings = true;
		$this->project_filter_records = array ();
		
		try {
			$db = Db :: getInstance($this->getConfig());
			if($this->isAdmin) {
				 $this->users_table_max_rows = PreferenceRecordPeer::getMaxUserTableRows($this, $this->user->getId());
			}
			
			$hidden_project_ids = array();
			$hidden_project_ids_str = PreferenceRecordPeer::getHiddenProjectIds($this, $this->user->getId());
			if($hidden_project_ids_str) {
				$hidden_project_ids =@explode(",",$hidden_project_ids_str );
			}
			$this->project_filter_records = array();
			if ($this->isAdmin) {
				$this->project_filter_records = ProjectRecordPeer :: getUserProjectRecords($db, '', '', true, '', array (), ProjectRecord :: NAME_COL, 'asc');
			} else {
				$permission_types = array (
					Constants :: ADD_ISSUE,
					Constants :: LEAD_PROJECT,
					Constants :: CAN_PERFORM_TASK
				);
				$this->project_filter_records = ProjectRecordPeer :: getUserProjectRecords($db, '', '', false, $this->user->getId(), $permission_types, ProjectRecord :: NAME_COL, 'asc');

			}
			
		
			if(!empty($this->project_filter_records)) {
				foreach ($this->project_filter_records as $record) {
					$record->is_hidden = in_array($record->getId(), $hidden_project_ids);
				}
			}
				
			
			
			$this->projects_per_page = PreferenceRecordPeer::getProjectsPerPage($this, $this->user->getId());
			
			$this->task_comments_per_page = PreferenceRecordPeer::getTaskCommentsPerPage($this, $this->user->getId());
			
			$this->max_messages_per_page = PreferenceRecordPeer::getMaxMessagesPerPage($this, $this->user->getId());
			
			$this->max_dashboard_block_items = PreferenceRecordPeer::getMaxDashboardItemsPerPage($this, $this->user->getId());
			
			$this->show_all_comments_of_open_issues = PreferenceRecordPeer::getShowAllCommentsOpenIssues($this, $this->user->getId());
			
			$this->show_closed_comments_of_closed_issues = PreferenceRecordPeer::getShowClosedCommentsClosedIssues($this, $this->user->getId());
			$this->show_attached_image_with_comments = PreferenceRecordPeer::getShowImageWithIssues($this, $this->user->getId());
			
			
			$this->close_task_when_issue_closed = PreferenceRecordPeer::getCloseTaskWhenIssueClosed($this, $this->user->getId());
			$this->close_issue_when_task_closed = PreferenceRecordPeer::getCloseIssueWhenTaskClosed($this, $this->user->getId());
			
			
			$this->search_project_details = PreferenceRecordPeer::getSearchProjectDetails($this, $this->user->getId());
			$this->search_task_details = PreferenceRecordPeer::getSearchTaskDetails($this, $this->user->getId());
			$this->search_issue_details = PreferenceRecordPeer::getSearchIssueDetails($this, $this->user->getId());
			$this->search_messages = PreferenceRecordPeer::getSearchMessages($this, $this->user->getId());
			
			
			$this->show_dashboard_unread_messages = PreferenceRecordPeer::getDashboardShowUnreadMessages($this, $this->user->getId());
			$this->show_dashboard_my_tasks = PreferenceRecordPeer::getDashboardShowMyTasks($this, $this->user->getId());
			$this->show_dashboard_others_tasks = PreferenceRecordPeer::getDashboardShowOthersTasks($this, $this->user->getId());
					
		} catch (Exception $exception) {
			$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
		}
		if($this->isAdmin){			
			return new FlexyView('user/admin/userSettings.html', $this);
		}
		else {
			return new FlexyView('user/userSettings.html', $this);
		}		
	}
	
	function updateUserSettings($args) {
		$this->common($args);
		if (!$this->getIsAuthorised()) {
			$this->appendErrorMessage('Unknown access, authorization required! ');
			return new FlexyView('error.html', $this);
		}
		try {
	    	$settings = array ();
			
			if ($this->isAdmin && isset ($args[Constants :: MAX_USER_TABLE_ROWS]) && ($args[Constants :: MAX_USER_TABLE_ROWS] != '')) {
				if ($args[Constants :: MAX_USER_TABLE_ROWS] <= 0) {
					$this->appendErrorMessage('Invalid value for user table rows! Value sholud be greater than zero.');
					$this->setFlashMessage($this->error_message, true);
					return $this->callModuleMethod('user', 'indexUserSettings', array ());
				} else {
					$settings[Constants :: MAX_USER_TABLE_ROWS] = $args[Constants :: MAX_USER_TABLE_ROWS];
				}
			}

			if (isset ($args[Constants :: PROJECTS_PER_PAGE]) && ($args[Constants :: PROJECTS_PER_PAGE] != '')) {
				if ($args[Constants :: PROJECTS_PER_PAGE] <= 0) {
					$this->appendErrorMessage('Invalid value for Projects per page! Value sholud be greater than zero.');
					$this->setFlashMessage($this->error_message, true);
					return $this->callModuleMethod('user', 'indexUserSettings', array ());
				} else {
					$settings[Constants :: PROJECTS_PER_PAGE] = $args[Constants :: PROJECTS_PER_PAGE];
				}
			}

			if (isset ($args[Constants :: TASK_COMMENTS_PER_PAGE]) && ($args[Constants :: TASK_COMMENTS_PER_PAGE] != '')) {
				if ($args[Constants :: TASK_COMMENTS_PER_PAGE] <= 0) {
					$this->appendErrorMessage('Invalid value for Task comments per page! Value sholud be greater than zero.');
					$this->setFlashMessage($this->error_message, true);
					return $this->callModuleMethod('user', 'indexUserSettings', array ());
				} else {
					$settings[Constants :: TASK_COMMENTS_PER_PAGE] = $args[Constants :: TASK_COMMENTS_PER_PAGE];
				}
			}
			if (isset ($args[Constants :: MAX_DASHBOARD_BLOCK_ITEMS]) && ($args[Constants :: MAX_DASHBOARD_BLOCK_ITEMS] != '')) {
				if ($args[Constants :: MAX_DASHBOARD_BLOCK_ITEMS] <= 0) {
					$this->appendErrorMessage('Invalid value for dashboard max!, value sholud be greater than zero.');
					$this->setFlashMessage($this->error_message, true);
					return $this->callModuleMethod('user', 'indexUserSettings', array ());
				} else {
					$settings[Constants :: MAX_DASHBOARD_BLOCK_ITEMS] = $args[Constants :: MAX_DASHBOARD_BLOCK_ITEMS];
				}
			}

			if (isset ($args[Constants :: MAX_MESSAGES_PER_PAGE]) && ($args[Constants :: MAX_MESSAGES_PER_PAGE] != '')) {
				if ($args[Constants :: MAX_MESSAGES_PER_PAGE] <= 0) {
					$this->appendErrorMessage('Invalid value for messages per page, value sholud be greater than zero.');
					$this->setFlashMessage($this->error_message, true);
					return $this->callModuleMethod('user', 'indexUserSettings', array ());
				} else {
					$settings[Constants :: MAX_MESSAGES_PER_PAGE] = $args[Constants :: MAX_MESSAGES_PER_PAGE];
				}
			}
			
			if (isset ($args[Constants :: CLOSE_TASK_WHEN_ISSUE_CLOSED]) && $args[Constants :: CLOSE_TASK_WHEN_ISSUE_CLOSED] == 'on') {
				$settings[Constants :: CLOSE_TASK_WHEN_ISSUE_CLOSED] = 1;
			} else {
				$settings[Constants :: CLOSE_TASK_WHEN_ISSUE_CLOSED] = 0;
			}
			if (isset ($args[Constants :: CLOSE_ISSUE_WHEN_TASK_CLOSED]) && $args[Constants :: CLOSE_ISSUE_WHEN_TASK_CLOSED] == 'on') {
				$settings[Constants :: CLOSE_ISSUE_WHEN_TASK_CLOSED] = 1;
			} else {
				$settings[Constants :: CLOSE_ISSUE_WHEN_TASK_CLOSED] = 0;
			}
			if (isset ($args[Constants :: SHOW_ALL_COMMENTS_OF_OPEN_ISSUES]) && $args[Constants :: SHOW_ALL_COMMENTS_OF_OPEN_ISSUES]== 'on') {
				$settings[Constants :: SHOW_ALL_COMMENTS_OF_OPEN_ISSUES] = 1;
			} else {
				$settings[Constants :: SHOW_ALL_COMMENTS_OF_OPEN_ISSUES] = 0;
			}
			if (isset ($args[Constants :: SHOW_CLOSED_COMMENTS_OF_CLOSED_ISSUES]) && $args[Constants :: SHOW_CLOSED_COMMENTS_OF_CLOSED_ISSUES] == 'on') {
				$settings[Constants :: SHOW_CLOSED_COMMENTS_OF_CLOSED_ISSUES] = 1;
			} else {
				$settings[Constants :: SHOW_CLOSED_COMMENTS_OF_CLOSED_ISSUES] = 0;
			}
			if (isset ($args[Constants :: SHOW_ATTACHED_IMAGE_WITH_COMMENTS]) && $args[Constants :: SHOW_ATTACHED_IMAGE_WITH_COMMENTS] == 'on') {
				$settings[Constants :: SHOW_ATTACHED_IMAGE_WITH_COMMENTS] = 1;
			} else {
				$settings[Constants :: SHOW_ATTACHED_IMAGE_WITH_COMMENTS] = 0;
			}			
			if (isset ($args[Constants :: SHOW_DASHBOARD_UNREAD_MESSAGES]) && $args[Constants :: SHOW_DASHBOARD_UNREAD_MESSAGES] == 'on') {
				$settings[Constants :: SHOW_DASHBOARD_UNREAD_MESSAGES] = 1;
			} else {
				$settings[Constants :: SHOW_DASHBOARD_UNREAD_MESSAGES] = 0;
			}			
			if (isset ($args[Constants :: SHOW_DASHBOARD_OTHERS_TASKS]) && $args[Constants :: SHOW_DASHBOARD_OTHERS_TASKS] == 'on') {
				$settings[Constants :: SHOW_DASHBOARD_OTHERS_TASKS] = 1;
			} else {
				$settings[Constants :: SHOW_DASHBOARD_OTHERS_TASKS] = 0;
			}			
			if (isset ($args[Constants :: SHOW_DASHBOARD_MY_TASKS]) && $args[Constants :: SHOW_DASHBOARD_MY_TASKS] == 'on') {
				$settings[Constants :: SHOW_DASHBOARD_MY_TASKS] = 1;
			} else {
				$settings[Constants :: SHOW_DASHBOARD_MY_TASKS] = 0;
			}		
			
			$settings[Constants :: SEARCH_PROJECT_DETAILS] = 0;
			$settings[Constants :: SEARCH_TASK_DETAILS] = 0;
			$settings[Constants :: SEARCH_ISSUE_DETAILS] = 0;
			$settings[Constants :: SEARCH_MESSAGESS] = 0;
			$settings[Constants :: HIDDEN_PROJECT_IDS] = '';
			if (isset ($args[Constants :: SEARCH_PROJECT_DETAILS]) && $args[Constants :: SEARCH_PROJECT_DETAILS]== 'on') {
				$settings[Constants :: SEARCH_PROJECT_DETAILS] = 1;
			} 
			if (isset ($args[Constants :: SEARCH_TASK_DETAILS]) && $args[Constants :: SEARCH_TASK_DETAILS] == 'on') {
				$settings[Constants :: SEARCH_TASK_DETAILS] = 1;
			} 
			if (isset ($args[Constants :: SEARCH_ISSUE_DETAILS]) && $args[Constants :: SEARCH_ISSUE_DETAILS]== 'on') {
				$settings[Constants :: SEARCH_ISSUE_DETAILS] = 1;
			} 
			if (isset ($args[Constants :: SEARCH_MESSAGESS]) && $args[Constants :: SEARCH_MESSAGESS] == 'on') {
				$settings[Constants :: SEARCH_MESSAGESS] = 1;
			} 
			
			if (isset ($args[Constants :: HIDDEN_PROJECT_IDS]) && !empty($args[Constants :: HIDDEN_PROJECT_IDS])) {
				$settings[Constants :: HIDDEN_PROJECT_IDS] = implode(",",$args[Constants :: HIDDEN_PROJECT_IDS]);
			} 			
			
			
			//var_dump($args['hidden_project_ids']);
			
			PreferenceRecordPeer :: setValues($this, $this->user->getId(), $settings);
		} catch (Exception $exception) {
			$this->appendErrorMessage('There was an error; ' . $exception->getMessage());			
			if($this->isAdmin) {			
				return new FlexyView('user/admin/userSettings.html', $this);
			}
			else {
				return new FlexyView('user/userSettings.html', $this);
			}			
		}
		$new_args = array ();
		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
		} else {
			$this->setFlashMessage('Settings updated');
		}
		return $this->callModuleMethod('user', 'indexUserSettings', $new_args);
	}
  	
	function indexSiteSettings($args) {
		$this->common($args);
		$config = $this->getConfig();
		//$meta_data = Util :: getMetaData('admin settings', $config);
		$this->title = "Configuration Settings";		/* Abhilash 26-10-13 */
		if (!$this->isAdmin) {
			$this->appendErrorMessage('Unknown access, authorization required! ');
			return new FlexyView('error.html', $this);
		}
		try {
			$db = Db :: getInstance($this->getConfig());

			$this->updateSiteSettings = true;
			$config_records = ConfigRecordPeer :: getConfigRecords($db);

			for ($cnt = 0; $cnt < count($config_records); $cnt++) {

				if ($config_records[$cnt]->getKeyName() == Constants :: COPY_MAILS_OF_MESSAGES_TO_ADMINISTRATOR) {
					$this->copy_mails_of_messages_to_administrator = $config_records[$cnt]->getIsTrue();
					continue;
				}
				if ($config_records[$cnt]->getKeyName() == Constants :: FROM_EMAIL_ADDRESS) {
					$this->from_email_id = $config_records[$cnt]->getValue();
					continue;
				}
				if ($config_records[$cnt]->getKeyName() == Constants :: WEBSITE_NAME) {
					$this->website_name = $config_records[$cnt]->getValue();
					continue;
				}
				if ($config_records[$cnt]->getKeyName() == Constants :: COMPANY_NAME) {
					$this->company_name = $config_records[$cnt]->getValue();
					continue;
				}
				if ($config_records[$cnt]->getKeyName() == Constants :: ATTACHMENT_TYPES) {
					$this->attachment_types = $config_records[$cnt]->getValue();
					continue;
				}			
			}
		} catch (Exception $exception) {
			$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
		}
		return new FlexyView('user/admin/siteSettings.html', $this);
	}
	
	function updateSiteSettings($args) {
		$this->common($args);
		
		if (!$this->isAdmin  ) {
			$this->appendErrorMessage('Unknown access, authorization required! ');
			return new FlexyView('error.html', $this);
		}
		$this->updateSiteSettings = true;
		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($this->getConfig());
				
				$config_records = ConfigRecordPeer :: getConfigRecords($db);
				
				for ($cnt = 0; $cnt < count($config_records); $cnt++) {
					$id = $config_records[$cnt]->getId();
					$key_name = $config_records[$cnt]->getKeyName();
					if ($key_name == Constants :: COPY_MAILS_OF_MESSAGES_TO_ADMINISTRATOR) {
						$value = (isset ($args[$key_name]) && $args[$key_name] == 'on') ? '1' : '0';
					} else {						
						$value = (isset ($args[$key_name])) ? $args[$key_name] : $config_records[$cnt]->getValue();
					}
					$type = $config_records[$cnt]->getType();
				
					ConfigRecordPeer :: createIfNotExist($db, $key_name, $value,$type);
				}
				
			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
			return new FlexyView('user/admin/configSettings.html', $this);
		}
		$this->setFlashMessage('Settings updated');

		$this->callModuleMethod('user', 'indexSiteSettings', array ());
	}	
	
	function show($args) {
		$this->common($args);
		if (!isset ($args['m'])) {
			$this->appendErrorMessage('Module not defined! ');
		}
		if (!isset ($args['a'])) {
			$this->appendErrorMessage('Action not defined! ');
		}
		if (!isset ($args['id'])) {
			$this->appendErrorMessage('Id not defined! ');
		}
		if (!isset ($args['u'])) {
			$this->appendErrorMessage('User not defined! ');
		}
		if (!$this->has_error) {
			$this->m = $args['m'];
			$this->a = $args['a'];
			$this->id = $args['id'];
			$this->u = $args['u'];
			if ($this->getIsAuthorised() && $this->user->getId() == $this->u) {
				$this->callModuleMethod($this->m, $this->a, array (
					'id' => $this->id
				));
				exit ();
			}
		}
		//signout
		$this->endSession($args);
		$this->isLanding = true;
		$this->callModuleMethod('user', 'index', array ());
	}

	function signout($args) {
		$this->common($args);
		$this->signout = true;
		$this->endSession($args);
		$this->callModuleMethod('user', 'index', array ());
	}

	function signin($args) {
		$this->common($args);
		$config = $this->getConfig();
		if (!isset ($args['signin_id']) || $args['signin_id'] == '' || !isset ($args['password']) || $args['password'] == '') {
			$this->appendErrorMessage('User name or password was incorrect, please try again!');
		}
		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($config);
				$this->user = UserRecordPeer :: findBySigninIdOrEmail($db, $args['signin_id'], $args['signin_id']);

				if ($this->user == null) {
					throw new Exception('User name or password was incorrect, please try again!');
				}
				if ($args['password'] != $this->user->getActualPassword()) {
					throw new Exception('User name or password was incorrect, please try again!');
				}
				$this->setIsAuthorised(true);
				$this->setParameter('USER_ID', $this->user->getId());
				
				$permission_types = UserPermissionPeer::getUserPermissionTypes($db, $this->user->getId());
				$this->setParameter('is_admin', in_array( Constants :: ADMINISTRATION, $permission_types));
				$this->setParameter('can_create_project', in_array( Constants :: LEAD_PROJECT, $permission_types));
				$this->setParameter('can_perform_task', in_array( Constants :: CAN_PERFORM_TASK, $permission_types));
						
				$theme_color = PreferenceRecordPeer :: getUserTheme($this, $this->user->getId());
				if ($theme_color == null) {
					$theme_color = $config->getValue('THEMES', 'default_theme');
				}
				$this->setParameter('THEME_COLOR', $theme_color);

				$this->user->setSignedinAt(date('Y-m-d H:i:s'));
				$this->user->store(array (
					UserRecord :: SIGNEDIN_AT_COL
				));

				$u_id = isset ($args['u']) ? $args['u'] : '';

				if ($u_id != $this->user->getId()) {
					/*if ($this->user->getId() != 1) {
						$this->callModuleMethod('dashboard', 'index', array ());
					} else {
						$this->callModuleMethod('project', 'index', array ());
					}*/
					$this->callModuleMethod('dashboard', 'index', array ());
				} else {
					$module = isset ($args['m']) ? $args['m'] : 'user';
					$action_name = isset ($args['a']) ? $args['a'] : 'index';
					$new_args = isset ($args['id']) ? array (
						'id' => $args['id']
					) : array ();
					$this->callModuleMethod($module, $action_name, $new_args);
				}
			} catch (Exception $exception) {

				$this->appendErrorMessage($exception->getMessage());
			}
		}
		$new_args = array ();
		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
		}
		$this->callModuleMethod('user', 'index', $new_args);
	}

	function register($args) {

		$this->common($args);
		$this->loginBlock = true;
		$config = $this->getConfig();
		//$meta_data = Util :: getMetaData('register', $config);
		$this->title = "Register User";		/* Abhilash 26-10-13 */
		if (!$this->isAdmin) {
			$this->appendErrorMessage('Unknown access, authorization required! ');
			return new FlexyView('error.html', $this);
		}
		
		$this->indexUsers = true;		
		
		$this->can_create_project = false;
		$this->can_perform_task = true;
				
		return new FlexyView('user/admin/register.html', $this);
	}

	function update($args) {
		$this->common($args);
		$this->loginBlock = true;
		$this->verifyRegisterArgs($args);
		$config = $this->getConfig();
		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($config);
				$user = new UserRecord($db);
				$signin_id = $args['signin_id'];
				$email = $args['email'];
				$password = $args['password'];
				$this->updateUserObj($user, $signin_id, $password, $email, $args['first_name'], $args['last_name']);

				//check whether email is duplicate
				$existing_user_record = UserRecordPeer :: findByEmail($db, $args['email']);
				if ($existing_user_record != null && $existing_user_record->getId() != $user->getId()) {
					throw new Exception(' email is already being used, please try again with different one!');
				}

				//new
				$signin_id = isset ($args['signin_id']) ? $args['signin_id'] : $user->getSigninId();

				//check whether signin_id is duplicate
				if (isset ($args['signin_id'])) {
					$existing_user_record = UserRecordPeer :: findBySigninId($db, $signin_id);
					if ($existing_user_record != null && $existing_user_record->getId() != $user->getId()) {
						throw new Exception(' the login name is already being used, please try again with different one!');
					}
				}

				$user->store();
				$create_project_permission = (isset ($args['create_project_permission']) && $args['create_project_permission'] != '') ? true : false ;
				$perform_task_permission = (isset ($args['perform_task_permission']) && $args['perform_task_permission'] != '') ? true : false ;
				
				if($create_project_permission) {					
					UserPermissionPeer :: setCreateProjectPermission($db, $user->getId());
				}
				else {
					UserPermissionPeer :: unsetCreateProjectPermission($db,$user->getId());
				}
				
				if($perform_task_permission) {
					UserPermissionPeer :: setPerformTaskPermission($db, $user->getId());				
				}
				else {
					UserPermissionPeer :: unsetPerformTaskPermission($db, $user->getId());
				}
			
				$this->fireRegistrationMail($db, $user, Constants :: REGISTRATION_MAIL_TEMPLATE);
			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		if ($this->has_error) {
			$new_args = array ();
			if ($this->isAdmin) {
				$this->setFlashMessage($this->error_message, true);
				$this->callModuleMethod('user', 'indexUsers', $new_args);
			} else {
				return new FlexyView('user/admin/register.html', $this);
			}
		}
		if ($this->isAdmin) {
			$new_args = array ();
			$this->setFlashMessage('User registered');
			$this->callModuleMethod('user', 'indexUsers', $new_args);
		}
		$this->callModuleMethod('user', 'response', $new_args);
	}
	
	function indexPermission($args) {

		$this->common($args);
		$this->loginBlock = true;
		$config = $this->getConfig();
		//$meta_data = Util :: getMetaData('register', $config);
		$this->title = "User Permission";	/* Abhilash 26-10-13 */
		if (!$this->isAdmin) {
			$this->appendErrorMessage('Unknown access, authorization required! ');
			return new FlexyView('error.html', $this);
		}
		if(!isset($args['id']) || $args['id'] == '') {
			$this->appendErrorMessage('Invalid user, dataset not found! ');
		}
		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($config);
				$this->tt_user = UserRecordPeer::findByPK($db, $args['id']);
				if($this->tt_user == null) {
					throw new Exception(' Invalid user, dataset not found!');
				}
				$this->can_create_project = UserPermissionPeer::canCreateProject($db, $this->tt_user->getId());
				$this->can_perform_task = UserPermissionPeer::canPerformTask($db, $this->tt_user->getId());
			}
			catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}		
		}
		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
			$this->callModuleMethod('user', 'indexUsers', array());			
		}		
		return new FlexyView('user/admin/editPermission.html', $this);
	}
	
	function updatePermission($args) {
		$this->common($args);
		$this->loginBlock = true;
		$config = $this->getConfig();
		if (!$this->isAdmin) {
			$this->appendErrorMessage('Unknown access, authorization required! ');
			return new FlexyView('error.html', $this);
		}
		if(!isset($args['tt_user_id']) || $args['tt_user_id'] == '') {
			$this->appendErrorMessage('Invalid user, dataset not found! ');
		}
		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($config);
				$this->tt_user = UserRecordPeer::findByPK($db, $args['tt_user_id']);
				
				if($this->tt_user == null) {
					throw new Exception(' Invalid user, dataset not found!');
				}
			
				$create_project_permission = (isset ($args['create_project_permission']) && $args['create_project_permission'] != '') ? true : false ;
				$perform_task_permission = (isset ($args['perform_task_permission']) && $args['perform_task_permission'] != '') ? true : false ;
						
				if($create_project_permission) {					
					UserPermissionPeer :: setCreateProjectPermission($db, $this->tt_user->getId());
				}
				else {
					UserPermissionPeer :: unsetCreateProjectPermission($db,$this->tt_user->getId());
				}
				
				if($perform_task_permission) {					
					UserPermissionPeer :: setPerformTaskPermission($db, $this->tt_user->getId());				
				}
				else {					
					UserPermissionPeer :: unsetPerformTaskPermission($db, $this->tt_user->getId());
				}	
				
			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);				
		}
		else {
			$this->setFlashMessage('Permission updated');
		}			
	   $this->callModuleMethod('user', 'indexUsers', array());		
	}	

	function setTheme($args) {
		$this->common($args);
		if (!$this->getIsAuthorised()) {
			$this->appendErrorMessage('Unknown access, authorization required! ');
			return new FlexyView('error.html', $this);
		}
		if (!isset ($args['name']) || $args['name'] == '') {
			$this->appendErrorMessage('Error, theme is not selected');
		}
		if (!$this->has_error) {
			try {
				$config = $this->getConfig();
				$db = Db :: getInstance($this->getConfig());

				$theme_default = $config->getValue('THEMES', 'default_theme');
				$themes = explode(",", $config->getValue('THEMES', 'names'));
				if(empty($themes)) {
					throw new Exception(' Could not find theme setup');
				}
				
				if(in_array($theme_default, $themes)) {
					$theme_default = $themes[0];
				}
				
				$theme_name = in_array($args['name'], $themes) ? $args['name'] : $theme_default;				

				PreferenceRecordPeer :: setUserTheme($this, $this->user->getId(), $theme_name);
				 $this->setParameter('THEME_COLOR', $theme_name);

			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		$new_args = array ();

		if (isset ($args['path'])) {
			$path_array = explode('-', $args['path']);
		}
		
		

		$cnt = count($path_array);
		if ($cnt >= 1) {
			$module = $path_array[0];
		}
		if ($cnt >= 2) {

			$method = $path_array[1];
		}
		if ($cnt >= 3) {
			for ($i = 2; $i < $cnt; $i++) {
				$even = ($i % 2) == 0;
				if ($even) {
					$new_args[$path_array[$i]] = '';
				} else {
					$new_args[$path_array[$i -1]] = $path_array[$i];
				}
			}
		}

		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);

		}
		//we can not capture args for now
		if($module == 'search' && $method == 'index') {
			$module = 'dashboard';
		}
		$this->callModuleMethod($module, $method, $new_args);
	}

	function updateProfile($args) {
		$this->common($args);
		if (!$this->getIsAuthorised()) {
			$this->appendErrorMessage('Unknown access, authorization required! ');
			return new FlexyView('error.html', $this);
		}
		$this->verifyProfileArgs($args);
		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($this->getConfig());
				$user_record = UserRecordPeer :: findByEmail($db, $this->user->getEmail());

				if ($user_record == null) {
					throw new Exception(' could not find the data set, please try again!');
				}
				//check whether email is duplicate
				$existing_user_record = UserRecordPeer :: findByEmail($db, $args['email']);
				if ($existing_user_record != null && $existing_user_record->getId() != $user_record->getId()) {

					throw new Exception(' email is already being used, please try again with different one!');
				}
				$old_signin_id = $user_record->getSigninId();
				//new
				$signin_id = isset ($args['signin_id']) ? $args['signin_id'] : $user_record->getSigninId();
				//check whether signin_id is duplicate
				if (isset ($args['signin_id'])) {
					$existing_user_record = UserRecordPeer :: findBySigninId($db, $signin_id);
					if ($existing_user_record != null && $existing_user_record->getId() != $user_record->getId()) {
						throw new Exception(' the login name is already being used, please try again with different one!');
					}
				}
				if (isset ($_FILES['uploaded_icon']) && $_FILES['uploaded_icon']['name'] != "") {
					$user_icon = $_FILES['uploaded_icon'];
					$isImage = Util :: isValidImageFile($_FILES['uploaded_icon']['tmp_name'], Util :: getPermittedImageTypes());
		
					if (!$isImage) {
					throw new Exception(' Invalid profile picture'); //megha 12.3.15

					}
				}
				
				$email = isset ($args['email']) ? $args['email'] : $user_record->getEmail();
				$first_name = isset ($args['first_name']) ? $args['first_name'] : $user_record->getFirstName();
				$last_name = isset ($args['last_name']) ? $args['last_name'] : $user_record->getLastName();
				
				
				
				$attachment_folder = Util :: getAttachmentFolderName($this->getConfig(), Constants :: USER);
				$new_icon_name = Util :: createIconHelper($user_record->getId(), $attachment_folder, 'avatar');
				$old_icon= $user_record->getIconName();
				

				if ($new_icon_name) {
					$user_record->setIconName($new_icon_name);
					
					if ($old_icon && $old_icon != $new_icon_name) {
						Util :: deleteAttachments(Constants :: USER, $this->getConfig(), array (
							$old_icon
						));
					}
				}
				
				
				$this->updateUserObj($user_record, $signin_id, $user_record->getActualPassword(), $email, $first_name, $last_name);
				$user_record->store();
				//update session record

			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		if ($this->has_error) {
			$new_args = array (
				'is_profile_tab' => 'true',				
			);
			$this->setFlashMessage($this->error_message, true);

			$this->callModuleMethod('user', 'profile', $new_args);
		} else {
			$this->setFlashMessage('Profile updated');
		}

		$this->callModuleMethod('project', 'index', array ());
	}

	function updatePassword($args) {
		$this->common($args);
		if (!$this->getIsAuthorised()) {
			$this->appendErrorMessage('Unknown access, authorization required! ');
			return new FlexyView('error.html', $this);
		}
		$this->verifyPasswordArgs($args);
		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($this->getConfig());

				$user_record = UserRecordPeer :: findByPK($db, $this->user->getId());
				if ($user_record == null) {
					throw new Exception(' could not find the data set, please try again!');
				}
				if ($args['password_old'] != $user_record->getActualPassword()) {
					throw new Exception(' invalid old password!');
				}
				$this->updateUserObj($user_record, $user_record->getSigninId(), $args['password_new'], $user_record->getEmail(), $user_record->getFirstName(), $user_record->getLastName());
				$user_record->store();
				//update session record

			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		if ($this->has_error) {
			$new_args = array (
				'is_password_tab' => 'true'
			);
			$this->setFlashMessage($this->error_message, true);
			$this->callModuleMethod('user', 'profile', array ());
			//return new FlexyView('user/profile.html', $this);
		}
		$this->setFlashMessage('Password  changed');
		$this->callModuleMethod('project', 'index', array ());
	}

	function retrievePassword($args) {
		$this->common($args);

		if (!isset ($args['email']) || $args['email'] == '') {
			$this->appendErrorMessage('Email address can not be blank!');
		}
		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($this->getConfig());
				$user_record = UserRecordPeer :: findByEmail($db, $args['email']);
				if ($user_record == null) {
					throw new Exception(' could not find data set, please try again!');
				}

				$this->firePasswordRetrievalMail($db, $user_record->getActualPassword(), $user_record->getEmail());

			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
			return $this->queryEmail($args);
		}
		$new_args = array (
			'password_retrieval_response' => 'true'
		);
		$this->callModuleMethod('user', 'response', $new_args);
	}

	function response($args) {
		$this->common($args);
		$this->loginBlock = true;
		$this->password_retrieval_response = false;
		$db = Db :: getInstance($this->getConfig());

		if (isset ($args['password_retrieval_response'])) {
			$this->password_retrieval_response = true;
		}
		return new FlexyView('user/general.html', $this);
	}

	function queryEmail($args) {
		$this->common($args);
		$config = $this->getConfig();
		//$meta_data = Util :: getMetaData('password', $config);
		$this->title = "Retrieve Password"; 	/* Abhilash 26-10-13 */
		$this->loginBlock = true;
		$db = Db :: getInstance($this->getConfig());
		return new FlexyView('user/queryEmail.html', $this);
	}

	function resetPassword($args) {
		$this->common($args);
		if (!$this->getIsAuthorised()) {
			$this->appendErrorMessage(' Unknown access, authorization required! ');
			return new FlexyView('error.html', $this);
		}
		if (!isset ($args['id'])) {
			$this->appendErrorMessage('Error, user id is not set!');
		} else
			if (!$this->isAdmin) {
				$this->appendErrorMessage('You can not access this property!');
			}
		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($this->getConfig());
				$user_record = UserRecordPeer :: findByPK($db, $args['id']);

				if ($user_record == null) {
					throw new Exception(' could not find data set, please try again!');
				}
				$new_password = Util::generatePassword($user_record->getSigninId()."".time(), true);

				$this->updateUserObj($user_record, $user_record->getSigninId(), $new_password, $user_record->getEmail(), $user_record->getFirstName(), $user_record->getLastName());
				$user_record->store();

				//update session record
				$this->firePasswordResetMail($db, $user_record->getActualPassword(), $user_record->getEmail());
			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
			$new_args = array ();
			if ($this->has_error) {
				$this->setFlashMessage($this->error_message, true);
				$this->callModuleMethod('user', 'indexUsers', $new_args);
			}
			$this->setFlashMessage('Password reset');
			$this->callModuleMethod('user', 'indexUsers', array ());

		}
	}

	function profile($args) {
		$this->common($args);
		$config = $this->getConfig();
		//$meta_data = Util :: getMetaData('profile', $config);
		$this->title = "Profile";			/* Abhilash 26-10-13 */
		if (!$this->getIsAuthorised()) {
			$this->appendErrorMessage('Unknown access, authorization required! ');
			return new FlexyView('error.html', $this);
		}
		$this->profile = true;
		if (isset ($args['is_password_tab'])) {
			$this->is_passowrd_tab = true;
		} else
			if (isset ($args['is_profile_tab'])) {
				$this->is_profile_tab = true;
			}
		return new FlexyView('user/profile.html', $this);
	}

	function indexUsers($args) {
		$this->common($args);
		$config = $this->getConfig();
		//$meta_data = Util :: getMetaData('users', $config);
		$this->title = "Users";			/* Abhilash 26-10-13 */
		if (!$this->getIsAuthorised()) {
			$this->appendErrorMessage('Unknown access, authorization required!');
			return new FlexyView('error.html', $this);
		}
		if (!$this->isAdmin) {
			$this->appendErrorMessage('Error, invalid role, authorization required!');
			return new FlexyView('error.html', $this);
		}
		$this->indexUsers = true;

		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($this->getConfig());
				
				$admin = UserRecordPeer :: getAdminUser($db);
				
				$offset = isset ($args['from']) ? $args['from'] : 0;
				$limit = PreferenceRecordPeer :: getMaxUserTableRows($this, $this->user->getId());
				$this->user_records = UserRecordPeer ::getUsers($db, $offset, $limit,  array($admin->getId()));
				$this->user_records = UserPermissionPeer::getUserRecordsWithPermissions($db, $this->user_records);
				$this->no_records = empty ($this->user_records) ? true : false;
				//pagination
				///////////////////////////				
				
				$total_records = UserRecordPeer :: getUsersCount($db, array($admin->getId()));

				
				$this->pc = new PageCollection($args, $limit, $total_records,$this->getAbsoluteURL('/user/indexUsers'));
				
				if (isset ($args['registration_error_message'])) {
					$this->has_error = true;
					$this->error_message = urldecode($args['registration_error_message']);
				}

			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		return new FlexyView('user/admin/indexUsers.html', $this);
	}

	function delete($args) {
		$this->common($args);
		if (!$this->getIsAuthorised()) {
			$this->appendErrorMessage('Unknown access, authorization required! ');
			return new FlexyView('error.html', $this);
		}
		if (!$this->isAdmin) {
			$this->appendErrorMessage(' Error, invalid role, authorization required! ');
		}
		if (!isset ($args['id'])) {
			$this->appendErrorMessage('Error, user id is not set!');
		} else
			if ($args['id'] == $this->user->getId()) {
				$this->appendErrorMessage('Error, invalid user id!');
			}
		$lead_deleted = false;
		if (!$this->has_error) {
			try {
				$db = Db :: getInstance($this->getConfig());
				$user = UserRecordPeer :: findByPK($db, $args['id']);

			    //check tasks assigned, leading ...
			    $n_tasks = TaskRecordPeer :: getUserTaskCountHavingPermissions($db, array(), $args['id'], array (
						Constants :: CAN_PERFORM_TASK,
						Constants :: CAN_VIEW_TASK,
						Constants :: LEAD_TASK
					));
					if($n_tasks > 0 ) {
						throw new Exception(' can not delete a user account when there are associated tasks ' );
					}

				//$this->deleteUserMessages($user, $user_permission);
				$bookmarks = BookmarkRecordPeer :: getBookmarks($db, $user->getId());
				if (!empty($bookmarks)) {
					$ids_for_delete = array();
					foreach ($bookmarks as $bookmark) {
						$ids_for_delete[] = $bookmark->getId();
					}
					BookmarkRecordPeer :: deleteBookmarks($db, $ids_for_delete);
				}

				$table_name = $db->getPrefix() . UserRecord :: TABLE_NAME;
				CommonRecord :: delete($db, $table_name, CommonRecord :: ID_COL, array (
					$args['id']
				));
			} catch (Exception $exception) {
				$this->appendErrorMessage('There was an error; ' . $exception->getMessage());
			}
		}
		$new_args = array ();
		if ($this->has_error) {
			$this->setFlashMessage($this->error_message, true);
		}
		$this->callModuleMethod('user', 'indexUsers', $new_args);
	}
	function icon($args) {
		$this->common($args);
		$config = $this->getConfig();
		$user_id = isset ($args['id']) ? $args['id'] : '';
		$icon_name = "";
		try {
			$db = Db :: getInstance($this->getConfig());
			if ($user_id) {
				$icon_name = UserRecordPeer :: getIconName($db, $user_id);
			}
		} catch (Exception $exception) {

		}
	
		
		$attachment_folder = Util :: getAttachmentFolderName($config, Constants :: USER);
		$icon_file = Util :: getAttachmentFilePath($user_id, $icon_name, $attachment_folder);
		if (!file_exists($icon_file) || !is_file($icon_file)) {
			$resources_folder = $config->getValue('FlexyView', 'resources_dir');
			$icon_file = ".".DIRECTORY_SEPARATOR.$resources_folder .DIRECTORY_SEPARATOR. 'images'.DIRECTORY_SEPARATOR . 'profile_avatar.png';
		}
		
		Util :: downloadFile($icon_file);

		exit ();

	}

	private function fireRegistrationMail($db, $user, $template_type) {
		$config = $this->getConfig();
		$from_email = ConfigRecordPeer :: getFromEmailAddress($this);
		$website_name = ConfigRecordPeer :: getWebSiteName($this);
		$admin_user_name = UserRecordPeer :: getAdminUserFirstName($db);
		if ($from_email != '') {
			list ($subject_template, $body_template) = Util :: getMailTemplateContents($config, $template_type);
			$login_url = $this->getAbsoluteURLWithoutSession('/user/index/');
			$subject = Util :: getSubstitutedRegistrationTemplate($subject_template, $user->getSigninId(), $user->getActualPassword(), $login_url, $admin_user_name, $website_name);
			$body = Util :: getSubstitutedRegistrationTemplate($body_template, $user->getSigninId(), $user->getActualPassword(), $login_url, $admin_user_name, $website_name);
			$st = Util :: sendTextMail($from_email, $user->getEmail(), $subject, $body);
		}
	}

	private function firePasswordRetrievalMail($db, $password, $email) {
		$config = $this->getConfig();
		$from_email = ConfigRecordPeer :: getFromEmailAddress($this);

		if ($from_email != '') {
			$subject = 'Account Details';
			$body = " Your password is $password ";
			$st = Util :: sendTextMail($from_email, $email, $subject, $body);
		}
	}

	private function firePasswordResetMail($db, $password, $email) {
		$config = $this->getConfig();
		$from_email = ConfigRecordPeer :: getFromEmailAddress($this);
		if ($from_email != '') {
			$subject = 'Account Details';
			$body = "Your password has been reset as $password by administrator";
			
			$st = Util :: sendTextMail($from_email, $email, $subject, $body);

		}
	}

	private function validEmail($address) {
		return (!preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $address)) ? FALSE : TRUE;
	}

	private function validName($subject) {
		return (!preg_match("/^([a-z0-9A-Z])+$/i", $subject)) ? FALSE : TRUE;
	}

	private function verifyRegisterArgs($args) {
		if (!isset ($args['email']) || $args['email'] == '') {
			$this->appendErrorMessage('Email address can not be blank! ');
		}
		if (!$args['email'] == '' && !$this->validEmail($args['email'])) {
			$this->appendErrorMessage('Email address not in correct format! ');
		}
		if (!isset ($args['first_name']) || $args['first_name'] == '') {
			$this->appendErrorMessage('First name can not be blank! ');
		}
		if (!$args['first_name'] == '' && !$this->validName($args['first_name'])) {
			$this->appendErrorMessage('First name should have alpha-numeric characters only! ');
		}
		if (!isset ($args['signin_id']) || $args['signin_id'] == '') {
			$this->appendErrorMessage('Login name can not be blank! ');
		}
		if (!$args['signin_id'] == '' && !$this->validName($args['signin_id'])) {
			$this->appendErrorMessage('Login name should have alpha-numeric characters only! ');
		}
		if (!$args['signin_id'] == '' && (strlen($args['signin_id']) < 3)) {
			$this->appendErrorMessage('Login name must be at least 3 characters in length! ');
		}
		if (!isset ($args['password']) || $args['password'] == '') {
			$this->appendErrorMessage('Password can not be blank! ');
		}
		if (!$args['password'] == '' && (strlen($args['password']) < 5)) {
			$this->appendErrorMessage('Password must be at least 5 characters in length! ');
		}
		if ($args['password'] !== $args['password_repeat']) {
			$this->appendErrorMessage('Passwords do not match, please try again!');
		}
	}

	private function verifyProfileArgs($args) {
		if (!isset ($args['email']) || $args['email'] == '') {
			$this->appendErrorMessage('Email address can not be blank! ');
		}
		if (!$args['email'] == '' && !$this->validEmail($args['email'])) {
			$this->appendErrorMessage(' Email address not in correct format! ');
		}
		if (!isset ($args['first_name']) || $args['first_name'] == '') {
			$this->appendErrorMessage(' First name can not be blank! ');
		}
		if (!$args['first_name'] == '' && !$this->validName($args['first_name'])) {
			$this->appendErrorMessage('First name should have alpha-numeric characters only! ');
		}
		if (!isset ($args['signin_id']) || $args['signin_id'] == '') {
			$this->appendErrorMessage(' Login name can not be blank! ');
		}
		if (!$args['signin_id'] == '' && !$this->validName($args['signin_id'])) {
			$this->appendErrorMessage('Login name should have alpha-numeric characters only! ');
		}
		if (!$args['signin_id'] == '' && (strlen($args['signin_id']) < 3)) {
			$this->appendErrorMessage(' Login name must be at least 3 characters in length! ');
		}
	}

	private function verifyPasswordArgs($args) {
		if (!isset ($args['password_old']) || $args['password_old'] == '') {
			$this->appendErrorMessage(' Old password can not be blank! ');
		}
		if (!isset ($args['password_new']) || $args['password_new'] == '') {
			$this->appendErrorMessage(' New password can not be blank! ');
		}
		if (!$args['password_new'] == '' && (strlen($args['password_new']) < 5)) {
			$this->appendErrorMessage(' New password must be at least 5 characters in length! ');
		}
		if ($args['password_new'] !== $args['password_new_repeat']) {
			$this->appendErrorMessage(' New passwords do not match, please try again! ');
		}
	}

	private function updateUserObj($user, $signinId, $password, $email, $firstName, $lastName) {
		$user->setSigninId($signinId);
		$user->setEmail($email);
		$user->setFirstName($firstName);
		$user->setLastName($lastName);
		$iv = Util :: create_iv();
		$user->setPassword(Util :: encrypt($password, $signinId, $iv));
		$user->setIv($iv);
	}
	
	private function endSession($args) {
		$this->setIsAuthorised(false);
		$this->unsetParameter('USER_ID');
		session_destroy();
		session_unset();
	}

	private function deleteUserMessages($user, $user_permission) {
		
		$user_id = $user->getId();
		$config = $this->getConfig();
		/*try {
			$db = Db :: getInstance($config);
			$table = $db->getPrefix() . MessageRecord :: TABLE_NAME;
			$where_cond = MessageRecord :: FROM_ID_COL . "='$user_id' ";
			$records = CommonRecord :: getObjects($table, $where_cond, ' id desc ', '', '', new MessageRecord($db));

			for ($cnt = 0; $cnt < count($records); $cnt++) {
				MessageBoardRecordPeer :: deleteUserMessageBoardRecord($config, $records[$cnt], '');

				if ($user_permission == Constants :: LEAD_PROJECT) {
					$project_id = $records[$cnt]->getTypeId();
					CommonRecord :: delete($db, $table, MessageRecord :: TYPE_ID_COL, array (
						$project_id
					));
				} else {
					$message_id = $records[$cnt]->getId();
					CommonRecord :: delete($db, $table, CommonRecord :: ID_COL, array (
						$message_id
					));
				}
			}
		} catch (Exception $exception) {
			throw new Exception('Error while retrieving # new messages; ' . $exception->getMessage());
		}*/
	}	
}
?>