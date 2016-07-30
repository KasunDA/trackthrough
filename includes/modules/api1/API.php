<?php
require_once 'BaseController.php';
require_once 'ActionHelper.php';
require_once 'Constants.php';
require_once 'PreferenceRecordPeer.php';
require_once 'UserPermissionPeer.php';
require_once 'Db.php';
require_once 'Constants.php';
require_once 'ConfigRecord.php';

class API extends FW_BaseController {

	function project_list($args) {
		$config = $this->getConfig();
		$data = array ();
		$data['result'] = FALSE;
		$data['error_message'] = 'unauthorized access';
		$args = $this->get_args($args);

		$this->logi("project_list: " . print_r($args, true));

		$data['result'] = array ();

		if (isset ($args['signature'])) {
			$secret = "trackthrough";
			$signature = ActionHelper :: generateSignature($config, '/api1/project-list', $secret, ActionHelper :: getSignableParams($args, array (
				'user_id'
			)));

			if ($signature == $args['signature']) {
				$data['error_message'] = '';

				try {
					$this->setupAuthorizedAPISession($args['user_id']);
					$projects = $this->execNewModuleMethod('project', 'index', $args);
					if (!empty ($projects)) {
						foreach ($projects as $p) {
							$data['result'][] = array (
								'id' => $p->getId(),
								'title' => $p->getName(),
								'desc' => $p->getDescription(),
								'icon_url' => ActionHelper :: getProjectIconURL($this, $p),
								'lead_uid' => $p->getLeadId(),
								'lead_uname' => $p->getLeadSigninId(),
								'progress' => $p->getProgress()
							);
						}
					}

				} catch (Exception $exception) {

					$data['error_message'] = $exception->getMessage();
				}

			}

		}

		ActionHelper :: jsonResponse($data);
	}

	function project_spec($args) {
		$config = $this->getConfig();
		$data = array ();
		$data['result'] = FALSE;
		$data['error_message'] = 'unauthorized access';
		$this->logi("project_spec: " . print_r($args, true));
		if (isset ($args['signature'])) {
			$secret = "trackthrough";
			$signature = ActionHelper :: generateSignature($config, '/api1/project-spec', $secret, ActionHelper :: getSignableParams($args, array (
				'id',
				'user_id'
			)));
		

			if ($signature == $args['signature']) {
				$data['error_message'] = '';

				try {
					$this->setupAuthorizedAPISession($args['user_id']);
					$o = $this->execNewModuleMethod('project', 'view', $args);
					if (isset($o->project_record)) {
						$data['result']['project'] = array (
							'id' => $o->project_record->getId(),
							'title' => $o->project_record->getName(),
							'desc' => $o->project_record->getDescription(),
							'icon_url' => ActionHelper :: getProjectIconURL($this, $o->project_record),
							'lead_uid' => $o->project_record->getLeadId(),
							'lead_uname' => $o->project_record->getLeadSigninId(),
							'progress' => $o->project_record->getProgress(),
							'attachments' => array ()
						);
						if ($o->project_record->attachments && !empty ($o->project_record->attachments)) {
							foreach ($o->project_record->attachments as $attachment) {
								$data['result']['project']['attachments'][] = array (
									'file_name' => $attachment->attachment_name,
									'type' => 'doc',
									'sz' => '123456'
								);

							}
						}
					}
					

				} catch (Exception $exception) {

					$data['error_message'] = $exception->getMessage();
				}

				
			}
		}
		


		ActionHelper :: jsonResponse($data);
	}
	function project_comment_list($args) {
		$config = $this->getConfig();
		$data = array ();
		$data['result'] = FALSE;
		$data['error_message'] = 'unauthorized access';
		$this->logi("project_comment_list: " . print_r($args, true));
		if (isset ($args['signature'])) {
			$secret = "trackthrough";
			$signature = ActionHelper :: generateSignature($config, '/api1/project-comment-list', $secret, ActionHelper :: getSignableParams($args, array (
				'project_id',
				'user_id'
			)));
		

			if ($signature == $args['signature']) {
				$data['error_message'] = '';

				try {
					$this->setupAuthorizedAPISession($args['user_id']);
					$args['id'] = $args['project_id'];
					$o = $this->execNewModuleMethod('project', 'view', $args);
					if (isset($o->message_records) && !empty($o->message_records)) {
						foreach ($o->message_records as $m) {
							$data['result'][] = $data['result'][] = array (
								'id' => $m->m_record->getId(),
								'from_uname' => $m->m_record->getFromName(), 
								'from_uid' => $m->m_record->getFromId(), 
								'date_formatted' => $m->m_record->getDateFormatted(),
								'desc' => $m->m_record->getCont(),
								'has_attachments' => $m->m_attachments && !empty ($m->m_attachments)
							);
							
							
						}
					}
					
					

				} catch (Exception $exception) {

					$data['error_message'] = $exception->getMessage();
				}

				
			}
		}
		


		ActionHelper :: jsonResponse($data);
	}
	
	function task_list($args) {
		$config = $this->getConfig();
		$data = array ();
		$data['result'] = FALSE;
		$data['error_message'] = 'unauthorized access';
		$this->logi("task_list: " . print_r($args, true));
		if (isset ($args['signature'])) {
			$secret = "trackthrough";
			$signature = ActionHelper :: generateSignature($config, '/api1/task-list', $secret, ActionHelper :: getSignableParams($args, array (
				'project_id',
				'user_id'
			)));
		

			if ($signature == $args['signature']) {
				$data['error_message'] = '';

				try {
					$this->setupAuthorizedAPISession($args['user_id']);
					$args['id'] = $args['project_id'];
					$o = $this->execNewModuleMethod('project', 'view', $args);
					if (isset($o->task_records) && !empty($o->task_records)) {
						foreach ($o->task_records as $t) {
							$data['result'][] = array (
								'id' => $t->getId(),
								'project_id' =>$t->getParentProjectId(),
								'title' => $t->getName(),
								'desc' => $t->getDescription(),
								'status' => $t->getStatus(),
								'lead_uid' => $t->getLeadId(),
								'lead_uname' => $t->getLeadSigninId(),
								'team_uids' => $t->getAssignedUids(),
								'team_unames' => $t->getTeamSigninId(),
								'view_only_for_self' => $t->view_only_for_self,
								'view_only' => $t->getIsViewOnly(),
								'progress' => $t->getProgress()
							);
						}
					}
					

				} catch (Exception $exception) {

					$data['error_message'] = $exception->getMessage();
				}

				
			}
		}
		


		ActionHelper :: jsonResponse($data);
	}
	function issue_list($args) {
		$config = $this->getConfig();
		$data = array ();
		$data['result'] = FALSE;
		$data['error_message'] = 'unauthorized access';
		$this->logi("issue_list: " . print_r($args, true));
		if (isset ($args['signature'])) {
			$secret = "trackthrough";
			$signature = ActionHelper :: generateSignature($config, '/api1/issue-list', $secret, ActionHelper :: getSignableParams($args, array (
				'project_id',
				'user_id'
			)));
		

			if ($signature == $args['signature']) {
				$data['error_message'] = '';

				try {
					$this->setupAuthorizedAPISession($args['user_id']);
					$args['id'] = $args['project_id'];
					$o = $this->execNewModuleMethod('project', 'view', $args);
					if (isset($o->issue_records) && !empty($o->issue_records)) {
						foreach ($o->issue_records as $i) {
							$data['result'][] = array (
								'id' => $i->getId(),
								'project_id' =>$i->getProjectId(),
								'title' => $i->getTitle(),
								'desc' => $i->getDescription(),
								'status' => $i->getStatus(),
								'user_id' => $i->getUserId(),
								'user_name' => $i->getUserSigninId(),
								'priority' => $i->getPriority(),
							);
						}
					}
					

				} catch (Exception $exception) {

					$data['error_message'] = $exception->getMessage();
				}

				
			}
		}
		


		ActionHelper :: jsonResponse($data);
	}
	
	

	function message_post($args) {
		$config = $this->getConfig();
		$data = array ();
		$data['result'] = FALSE;
		$data['error_message'] = 'unauthorized access';
		$this->logi("message_post FILES " . print_r($_FILES, true));
		$this->logi("message_post " . print_r($args, true));
		if (isset ($args['signature'])) {
			$secret = "trackthrough";
			$signature = ActionHelper :: generateSignature($config, '/api1/message-post', $secret, ActionHelper :: getSignableParams($args, array (
				'project_id',
				'message',
				'user_id'
			)));

			if ($signature == $args['signature']) {
				$data['error_message'] = '';
				try {
					$this->setupAuthorizedAPISession($args['user_id']);
					$args['description'] = $args['message'];
					$message_id = $this->execNewModuleMethod('project', 'message', $args);
					$data['result'] = array (
						'message_id' => $message_id
					);
				} catch (Exception $exception) {

					$data['error_message'] = $exception->getMessage();
				}

			}
		}

		ActionHelper :: jsonResponse($data);
	}
	//throws exception
	private function setupAuthorizedAPISession($user_id) {

		$db = Db :: getInstance($this->getConfig());
		$this->setIsAuthorised(true);
		$this->setParameter('USER_ID', $user_id);

		$permission_types = UserPermissionPeer :: getUserPermissionTypes($db, $user_id);
		$this->setParameter('is_admin', in_array(Constants :: ADMINISTRATION, $permission_types));
		$this->setParameter('can_create_project', in_array(Constants :: LEAD_PROJECT, $permission_types));
		$this->setParameter('can_perform_task', in_array(Constants :: CAN_PERFORM_TASK, $permission_types));

		$this->setParameter('call_from_api', TRUE);
	}
	private function execNewModuleMethod($module, $method, $args) {
		$config = $this->getConfig();

		$new_class_name = "Action";
		$new_class_file = $this->config->getValue('FW', 'module_dir') . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . $new_class_name . ".php";
		if (!file_exists($new_class_file)) {
			throw new Exception('Error: Could not find the action class for module ' . $module);

		}
		require ($new_class_file);

		$new_action_controller = new $new_class_name;

		if (isset ($new_action_controller)) {
			$new_action_controller->setConfig($config);
			if (method_exists($new_action_controller, $method)) {
				$val = call_user_func(array (
					$new_action_controller,
					$method
				), $args);
				//var_dump($view);
				return $val;
			}

		}
		return false;
	}
	//when args in query string format, not in well formed url format -:)
	private function get_args($args) {
		if (count(array_keys($args)) == 1) {
			foreach ($args as $k => $v) {
				if (!$v) {
					$query_str = $k; //hence k is a query string
					$args = parse_str($query_str);
				}
				break;
			}
		}
		return $args;
	}
	function logi($str) {
		$log_file = './api_log_' . date("y-m-d") . ".txt";

		$text = '[' . date('d/m/y h:m:s') . '] - ' . $str;

		// Write to log
		$fp = fopen($log_file, 'a');
		fwrite($fp, $text . "\n\n");

		fclose($fp); // close file

	}

}
?>