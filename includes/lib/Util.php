<?php


/*
 * Created on April 17, 2009
 *
 * bispark software services
 * www.bispark.com
 */

require_once 'UserPermissionPeer.php';
class Util {
	const MY_CONST = 'bispark.com';

	public static function getRegKey($str) {
		$str .= self :: MY_CONST;
		return md5($str);
	}

	public static function getMetaData($controller, $config) {
		$meta_data = array ();
		$meta_data['title'] = $config->getValue($controller, 'title');
		return $meta_data;
	}

	public static function getVersion() {
		$version = '1.4.4';
		return $version;
	}

	public static function getNewSession($prefix = 'wf') {
		for ($i = 0; $i <= 99; $i++) {
			$session_name = md5($prefix . $i);
			if (!array_key_exists($session_name, $_COOKIE)) {
				break;
			}
		}
		return $session_name;
	}

	public static function generatePassword($str, $hashed_password = false) {
		if ($hashed_password) {
			$str = md5($str);
		}
		if (strlen($str) < 5) {
			return $str;
		}
		return substr($str, 0, 5);
	}

	public static function encrypt($src, $key, $iv) {
		if ($src == '' || $key == '') {
			return '';
		}
		$my_key = $key . self :: MY_CONST;
		$my_key = substr($my_key, 0, 5);
		$my_key = md5($my_key);
		// Encryption Algorithm
		$cipher_alg = MCRYPT_RIJNDAEL_128;

		// Encrypt $string
		return base64_encode(mcrypt_encrypt($cipher_alg, $my_key, $src, MCRYPT_MODE_CBC, base64_decode($iv)));
		//return $src;

	}
	public static function decrypt($encrypted_string, $key, $iv) {
		if ($encrypted_string == '') {
			return '';
		}
		$my_key = $key . self :: MY_CONST;
		$my_key = substr($my_key, 0, 5);
		$my_key = md5($my_key);
		// Encryption Algorithm
		$cipher_alg = MCRYPT_RIJNDAEL_128;
		return mcrypt_decrypt($cipher_alg, $my_key, base64_decode($encrypted_string), MCRYPT_MODE_CBC, base64_decode($iv));
		//return $encrypted_string;
	}

	public static function create_iv() {
		srand();
		$iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC), MCRYPT_RAND);
		return base64_encode($iv);
	}

	public static function sendTextMail($sendmail_from, $to, $subject, $content, $target_file = '') {
		require_once ('Mail.php');
		require_once ('Mail/mime.php');

		$hdrs = array (
			'From' => $sendmail_from,
			'Subject' => $subject
		);
		$crlf = "\n";
		$mime = new Mail_mime($crlf);
		$mime->setTXTBody($content);
		if ($target_file != '') {
			//no more attachments required!!
			//$mime->addAttachment($target_file, 'text/plain');
		}

		//do not ever try to call these lines in reverse order
		$body = $mime->get();
		$hdrs = $mime->headers($hdrs);

		$mail = Mail :: factory('mail');

		return @ $mail->send($to, $hdrs, $body);
	}
	public static function getAttachmentFilePath($project_id, $source_file_name, $attachment_folder_path) {
		return $attachment_folder_path . DIRECTORY_SEPARATOR . $project_id . DIRECTORY_SEPARATOR . $source_file_name;
	}

	public static function getFileExtension($file_path) {
		$parts = pathinfo($file_path);
		return $parts['extension'];

	}

	public static function getAttachmentFolderName($config, $const_type) {
		if($const_type == Constants :: USER) {
			return $config->getValue('ETC', 'uploads_folder').DIRECTORY_SEPARATOR.'user'.DIRECTORY_SEPARATOR;
		}
		
		return $config->getValue('ETC', 'uploads_folder').DIRECTORY_SEPARATOR.'project'.DIRECTORY_SEPARATOR;
		
		/*
		if ($message_type == Constants :: GENERAL_MESSAGE) {
			return $config->getValue('ETC', 'message_attachment_folder'); //this variable no more exists
		} else
			if ($message_type == Constants :: PROJECT_MESSAGE || Constants :: TASK_MESSAGE || Constants :: ISSUE_MESSAGE) {
				return $config->getValue('ETC', 'project_attachment_folder');
			}

		return $config->getValue('ETC', 'message_attachment_folder'); //this variable no more exists
		*/

	}

	public static function getAttachmentNamePrefix($attachable_record) {
		$prefix = "";
		switch ($attachable_record->getType()) {
			case Constants :: PROJECT :
				$prefix = "p_";
				break;
			case Constants :: PROJECT_MESSAGE :
				$prefix = "pm_";
				break;
			case Constants :: GENERAL_MESSAGE :
				$prefix = "gm_";
				break;
			case Constants :: TASK :
				$prefix = "t_";
				break;
			case Constants :: TASK_MESSAGE :
				$prefix = "tm_";
				break;
			case Constants :: ISSUE :
				$prefix = "i_";
				break;
			case Constants :: ISSUE_MESSAGE :
				$prefix = "im_";
				break;
			default :
				break;
		}

		$prefix = $prefix . $attachable_record->getId() . "_";
		return $prefix;
	}

	//returns a valid message on upload success
	public static function createAttachmentHelper($attachable_record, $attachment_folder, $project_id) {
		$attach_log_msg = "";
		$attached_files = array ();
		for ($cnt = 0; $cnt < 10; $cnt++) {
			$upload_file_key = 'uploadedfile' . "_$cnt";

			if (!isset ($_FILES[$upload_file_key])) {
				continue;
			}

			$uploaded_file_name = isset ($_FILES[$upload_file_key]['name']) ? basename($_FILES[$upload_file_key]['name']) : '';

			if ($uploaded_file_name == '') {
				continue;
			}
			$uploaded_file_name_without_space = str_replace(' ', '_', $_FILES[$upload_file_key]['name']);
			$uploaded_file_name_without_space = str_replace('\s', '_', $uploaded_file_name_without_space);
			$uploaded_file_name_without_space = str_replace(',', '_', $uploaded_file_name_without_space);

			$prefix = self :: getAttachmentNamePrefix($attachable_record);

			$attachment_file_name = $prefix . $uploaded_file_name_without_space;

			$target_file = self :: getAttachmentFilePath($project_id, $attachment_file_name, $attachment_folder);

			if (file_exists($target_file)) {
				unlink($target_file);
			} else {
				//check whether folder exist

				$user_folder = dirname($target_file);

				if (!file_exists($user_folder)) {
					mkdir($user_folder, 0700, true);
				}
			}

			//create file
			touch($target_file);

			if (!move_uploaded_file($_FILES[$upload_file_key]['tmp_name'], $target_file)) {
				throw new Exception('Error while uploading the file, please try again!');
			}
			
			if (file_exists($target_file)) {
				require_once 'MessageRecord.php';
				$attached_files[] = basename($target_file);
				//create thumbnail if appropriate 
				self::createThumbnail($target_file);

				$attach_log_msg .= "Attachment : $target_file uploaded ";

			}
		}

		if (!empty ($attached_files)) {
			$attachable_record->setAttachmentName(implode(':', $attached_files));
			$attachable_record->store(array (
				MessageRecord :: ATTACHMENT_NAME_COL
			)); //same as ProjectRecord::ATTACHMENT_NAME_COL
		}

		return $attach_log_msg;
	}
	public static function getMailTemplateContents($config, $template_type) {
		$template_base_folder = $config->getValue('ETC', 'mail_template_folder');
		if ($template_type == Constants :: MESSAGE_MAIL_TEMPLATE) {
			$template_folder = $template_base_folder . 'message';

		} else
			if ($template_type == Constants :: REGISTRATION_MAIL_TEMPLATE) {
				$template_folder = $template_base_folder . 'registration';

			} else
				if ($template_type == Constants :: NEW_PROJECT_MAIL_TEMPLATE) {
					$template_folder = $template_base_folder . 'new_project';
				} else
					if ($template_type == Constants :: NEW_TASK_MAIL_TEMPLATE) {
						$template_folder = $template_base_folder . 'new_task';
					}

		$subject_cont = $template_folder . DIRECTORY_SEPARATOR . 'subject.txt';
		$body_cont = $template_folder . DIRECTORY_SEPARATOR . 'body.txt';

		return array (
			file_get_contents($subject_cont),
			file_get_contents($body_cont)
		);
	}
	//*
	public static function getSubstitutedProjectTemplate($template_cont, $lead, $to_user, $message_url, $website_name, $project_record) {

		$replacable_keys = array (
			'[[to_user.first_name]]',
			'[[website_name]]',
			'[[message_url]]',
			'[[project_name]]',
			'[[lead.first_name]]'
		);
		$replace_with = array (
			$to_user->getFirstName(),
			$website_name,
			$message_url,
			$project_record->getName(),
			$lead->getFirstName()
		);

		return str_replace($replacable_keys, $replace_with, $template_cont);

	}

	public static function getSubstitutedTaskTemplate($template_cont, $from_user, $to_user, $message_url, $website_name, $task_record) {

		$replacable_keys = array (
			'[[to_user.first_name]]',
			'[[website_name]]',
			'[[message_url]]',
			'[[task_name]]',
			'[[lead.first_name]]'
		);
		$replace_with = array (
			$to_user->getFirstName(),
			$website_name,
			$message_url,
			$task_record->getName(),
			$from_user->getFirstName()
		);

		return str_replace($replacable_keys, $replace_with, $template_cont);

	}

	public static function getSubstitutedMessageTemplate($template_cont, $from_user, $to_user, $subject, $message, $message_url, $website_name) {
		$replacable_keys = array (
			'[[from_user.first_name]]',
			'[[to_user.first_name]]',
			'[[subject]]',
			'[[comments]]',
			'[[message_url]]',
			'[[website_name]]'
		);
		$replace_with = array (
			$from_user->getFirstName(),
			$to_user->getFirstName(),
			$subject,
			$message,
			$message_url,
			$website_name
		);

		return str_replace($replacable_keys, $replace_with, $template_cont);

	}
	public static function getSubstitutedRegistrationTemplate($template_cont, $signinId, $password, $login_url, $admin_user_name, $website_name) {
		$replacable_keys = array (
			'[[username]]',
			'[[password]]',
			'[[login_url]]',
			'[[website_name]]',
			'[[admin.first_name]]'
		);
		$replace_with = array (
			$signinId,
			$password,
			$login_url,
			$website_name,
			$admin_user_name
		);

		return str_replace($replacable_keys, $replace_with, $template_cont);

	}
	public static function getSubstitutedTaskMailTemplate($template_cont, $to_user, $task_record, $task_url, $admin_user_name, $website_name) {
		$replacable_keys = array (

			'[[to_user.first_name]]',
			'[[task_type]]',
			'[[task_name]]',
			'[[task_description]]',
			'[[task_url]]',
			'[[website_name]]',
			'[[admin.first_name]]'
		);
		$replace_with = array (
			$to_user->getFirstName(),
			$task_record->getTypeLabel(),
			$task_record->getName(),
			$task_record->getDescription(),
			$task_url,
			$website_name,
			$admin_user_name
		);

		return str_replace($replacable_keys, $replace_with, $template_cont);

	}

	public static function getSubstitutedTaskReviewMailTemplate($db, $template_cont, $to_user, $task_record, $task_url, $admin_user_name, $website_name) {
		$replacable_keys = array (

			'[[to_user.first_name]]',
			'[[task_type]]',
			'[[task_name]]',
			'[[task_description]]',
			'[[task_url]]',
			'[[website_name]]',
			'[[admin.first_name]]'
		);
		$to_user = UserRecordPeer :: findByPK($db, $to_user);
		$replace_with = array (
			$to_user->getFirstName(),
			$task_record->getTypeLabel(),
			$task_record->getName(),
			$task_record->getDescription(),
			$task_url,
			$website_name,
			$admin_user_name
		);

		return str_replace($replacable_keys, $replace_with, $template_cont);

	}

	public static function getSubstitutedAdminTaskMailTemplate($template_cont, $to_user, $task_record, $task_url, $admin_user_name, $website_name) {
		$replacable_keys = array (

			'[[to_user.first_name]]',
			'[[task_name]]',
			'[[task_url]]',
			'[[website_name]]',
			'[[lead.first_name]]',
			'[[team.first_name]]',
			'[[admin.first_name]]'
		);
		$replace_with = array (
			$to_user->getFirstName(),
			$task_record->getName(),
			$task_url,
			$website_name,
			$task_record->getLeadSigninId(),
			$task_record->getTeamSigninId(),
			$admin_user_name
		);

		return str_replace($replacable_keys, $replace_with, $template_cont);

	}

	public static function getUploadErrorMessage() {
		if (isset ($_FILES["uploadedfile"]["error"])) {
			$error = $_FILES["uploadedfile"]["error"];
			if ($error == UPLOAD_ERR_INI_SIZE) {
				return 'The uploaded file exceeds the max size!';
			}
			if ($error == UPLOAD_ERR_FORM_SIZE) {
				return 'The uploaded file exceeds the MAX size!';
			}
			if ($error == UPLOAD_ERR_PARTIAL) {
				return 'The uploaded file was only partially uploaded!';
			}
			if ($error == UPLOAD_ERR_NO_FILE) {
				return 'No file was uploaded!';
			}
			if ($error == UPLOAD_ERR_NO_TMP_DIR) {
				return 'Missing a temporary folder!';
			}
			if ($error == UPLOAD_ERR_CANT_WRITE) {
				return 'Failed to write file to disk!';
			}
			return 'Unknown upload error';

		}
		return '';
	}
	//to take out projectid and underscore
	public static function extractAttachmentName($arg) {
		$arr = explode('_', $arg);
		if (count($arr) > 2) {
			$arr = array_slice($arr, 2, count($arr) - 1);
		}
		return implode('_', $arr);
	}

	public static function deleteAttachments($type, $config, $attachment_files) {
		
		$thumbs_included = array();
		foreach ($attachment_files as $attachment_file) {
			$thumbs_included[] = $attachment_file;
			$thumbs_included[] = "thumb_".$attachment_file;
		}

		$attachment_folder = Util :: getAttachmentFolderName($config, $type);

		if (!file_exists($attachment_folder)) {
			return false;
		}

		$files = self :: listFiles($attachment_folder);

		$deleted_files = array ();

		foreach ($files as $file) {
			$file_name = basename($file);
			if (in_array($file_name, $thumbs_included) && file_exists($file)) { //file_exists check redundant ??

				if (file_exists($file)) {
					unlink($file);
					$deleted_files[] = basename($file);

				}
				

			}
		}
		return $deleted_files;

	}
	public static function listFiles($path, $files = array ()) {

		$ignore = array (

			'.',
			'..'
		);

		$dh = @ opendir($path);

		while (false !== ($file = readdir($dh))) {

			if (!in_array($file, $ignore)) {

				if (is_dir("$path/$file")) {

					$files = self :: listFiles("$path/$file", $files);

				} else {

					$files[] = "$path/$file";

				}

			}

		}

		closedir($dh);

		return $files;
	}
	public static function deleteTaskAttachments($config, $task_record) {
		$db = Db :: getInstance($config);
		$task_attachment_files = array ();

		$task_attachment_files = explode(':', $task_record->getAttachmentName());

		if (is_array($task_attachment_files) && !empty ($task_attachment_files)) {

			self :: deleteAttachments(Constants :: TASK_MESSAGE, $config, $task_attachment_files);
		}

		//$task_message_records = MessageRecordPeer :: getTaskMessageRecords($db, '', '',$task_record->getParentProjectId(), $task_record->getId());
		$task_message_records = MessageRecordPeer :: getMessageRecords($db, null, '', Constants :: TASK_MESSAGE, $task_record->getId());

		$task_message_attachment_files = array ();
		foreach ($task_message_records as $task_message_record) {
			$task_message_attachment_files = array_merge($task_message_attachment_files, explode(':', $task_message_record->getAttachmentName()));
		}

		if (is_array($task_message_attachment_files) && !empty ($task_message_attachment_files)) {
			self :: deleteAttachments(Constants :: TASK_MESSAGE, $config, $task_message_attachment_files);
		}
	}
	public static function deleteProjectAttachments($config, $project_record) {

		$db = Db :: getInstance($config);

		$project_attachment_files = array ();

		$project_attachment_files = explode(':', $project_record->getAttachmentName());

		//echo empty ($project_attachment_files);exit();
		if (is_array($project_attachment_files) && !empty ($project_attachment_files)) {

			Util :: deleteAttachments(Constants :: PROJECT_MESSAGE, $config, $project_attachment_files);

		}

		//$project_message_records = MessageRecordPeer :: getProjectMessageRecords($db,'', $project_record->getId());
		$project_message_records = MessageRecordPeer :: getMessageRecords($db, null, '', Constants :: PROJECT_MESSAGE, $project_record->getId());

		$project_message_attachment_files = array ();
		foreach ($project_message_records as $project_message_record) {
			$project_message_attachment_files = array_merge($project_message_attachment_files, explode(':', $project_message_record->getAttachmentName()));
		}

		if (is_array($project_message_attachment_files) && !empty ($project_message_attachment_files)) {
			Util :: deleteAttachments(Constants :: PROJECT_MESSAGE, $config, $project_message_attachment_files);
		}

	}

	public static function truncate($text, $length = 300, $ending = '..', $exact = true, $considerHtml = false) {

		if (is_array($ending)) {
			extract($ending);
		}

		if ($considerHtml) {
			if (mb_strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
				return $text;
			}
			$totalLength = mb_strlen($ending);
			$openTags = array ();
			$truncate = '';
			preg_match_all('/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER);

			foreach ($tags as $tag) {
				if (!preg_match('/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s', $tag[2])) {
					if (preg_match('/<[\w]+[^>]*>/s', $tag[0])) {
						array_unshift($openTags, $tag[2]);
					} else
						if (preg_match('/<\/([\w]+)[^>]*>/s', $tag[0], $closeTag)) {
							$pos = array_search($closeTag[1], $openTags);
							if ($pos !== false) {
								array_splice($openTags, $pos, 1);
							}
						}
				}
				$truncate .= $tag[1];
				$contentLength = mb_strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $tag[3]));
				if ($contentLength + $totalLength > $length) {
					$left = $length - $totalLength;
					$entitiesLength = 0;
					if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $tag[3], $entities, PREG_OFFSET_CAPTURE)) {
						foreach ($entities[0] as $entity) {
							if ($entity[1] + 1 - $entitiesLength <= $left) {
								$left--;
								$entitiesLength += mb_strlen($entity[0]);
							} else {
								break;
							}
						}
					}
					$truncate .= mb_substr($tag[3], 0, $left + $entitiesLength);
					break;
				} else {
					$truncate .= $tag[3];
					$totalLength += $contentLength;
				}
				if ($totalLength >= $length) {
					break;
				}
			}
		} else {

			if (mb_strlen($text) <= $length) {
				return $text;
			} else {
				$truncate = mb_substr($text, 0, $length);
			}
		}
		if (!$exact) {
			$spacepos = mb_strrpos($truncate, ' ');
			if (isset ($spacepos)) {
				if ($considerHtml) {
					$bits = mb_substr($truncate, $spacepos);
					preg_match_all('/<\/([a-z]+)>/', $bits, $droppedTags, PREG_SET_ORDER);
					if (!empty ($droppedTags)) {
						foreach ($droppedTags as $closingTag) {
							if (!in_array($closingTag[1], $openTags)) {
								array_unshift($openTags, $closingTag[1]);
							}
						}
					}
				}
				$truncate = mb_substr($truncate, 0, $spacepos);
			}
		}
		$truncate .= $ending;
		if ($considerHtml) {
			foreach ($openTags as $tag) {
				$truncate .= '</' . $tag . '>';
			}
		}
		return $truncate;
	}

	public static function getExistingAttachments($config, $attchment_names, $id, $attachment_type) {

		for ($cnt = 0; $cnt < count($attchment_names); $cnt++) {
			$attachment_folder = self :: getAttachmentFolderName($config, $attachment_type);
			$target_file = self :: getAttachmentFilePath($id, $attchment_names[$cnt], $attachment_folder);
			if (!file_exists($target_file)) {
				$existing_attachment_names[$cnt] = null;
			} else {
				$existing_attachment_names[$cnt] = self :: extractAttachmentName($attchment_names[$cnt]);
			}
		}
		return $existing_attachment_names;
	}

	public static function getAttachmentIcon($config, $attachment_name) {
		if ($attachment_name != '' && $attachment_name != null) {
			$type = substr(strrchr($attachment_name, "."), 1);
			$type = strtolower($type);
			$attachment_icon_name = $config->getValue('Attachment_icon', $type);
			if ($attachment_icon_name == null) {
				$attachment_icon_name = $config->getValue('Attachment_icon', 'default');
			}
		}
		return $attachment_icon_name;
	}

	public static function get_temp_folder() {
		$tmp = ini_get('upload_tmp_dir');
		if(!$tmp || !file_exists ($tmp)) {
			$tmp =  sys_get_temp_dir();
		}
		return $tmp.DIRECTORY_SEPARATOR."tt";
	}

	public static function getAttachmentURL($config, $project_id, $attachment_name) {
		$base_url = $config->getValue('FW', 'base_url');
		$project_attachment_folder = $config->getValue('ETC', 'uploads_folder').'/projects/';
		$image_path = $base_url . '/' . $project_attachment_folder . $project_id . '/' . $attachment_name;
		return $image_path;
	}

	public static function createAttachmentCloneHelper($config, $attachable_record, $attchment_names, $attachment_folder, $old_project_id, $new_project_id) {
		$attach_log_msg = "";
		$attachments = array ();
		foreach ($attchment_names as $attachment_name) {
			$attachment_folder = self :: getAttachmentFolderName($config, $attachable_record->getType());
			$old_file = self :: getAttachmentFilePath($old_project_id, $attachment_name, $attachment_folder);

			$attachment_file_name = self :: extractAttachmentName($attachment_name);
			$prefix = self :: getAttachmentNamePrefix($attachable_record);
			$attachment_new_file_name = $prefix . $attachment_file_name;
			$target_file = self :: getAttachmentFilePath($new_project_id, $attachment_new_file_name, $attachment_folder);
			$attached_files[] = $attachment_new_file_name;
			if (!file_exists($old_file)) {
				continue;
			} else {
				self :: createFile($target_file, $old_file);

			}
		}

		if (!empty ($attached_files)) {
			$attached_file_names = (implode(':', $attached_files));
		}
		return $attached_file_names;
	}

	public static function createFile($target_file, $old_file) {
		if (file_exists($target_file)) {
			unlink($target_file);
		} else {
			//check whether folder exist
			$user_folder = dirname($target_file);

			if (!file_exists($user_folder)) {
				mkdir($user_folder, 0700, true);
			}
			$target_cont = file_get_contents($old_file);
			//write
			$attachment_file = $target_file;
			$fp = fopen($attachment_file, 'w');
			if (!$fp) {
				throw new Exception('Error while cloning the uploaded file!');
			}
			fputs($fp, $target_cont);
			fclose($fp);
		}
		return;
	}

	public static function createIssueAttachmentCloneHelper($config, $attachable_record, $attchment_names, $attachment_folder, $project_record) {
		$attach_log_msg = "";
		$attachments = array ();
		foreach ($attchment_names as $attachment_name) {
			$attachment_folder = self :: getAttachmentFolderName($config, Constants :: ISSUE);
			$old_file = self :: getAttachmentFilePath($project_record->getId(), $attachment_name, $attachment_folder);

			$attachment_file_name = self :: extractAttachmentName($attachment_name);
			$prefix = self :: getAttachmentNamePrefix($attachable_record);
			$attachment_new_file_name = $prefix . $attachment_file_name;
			$target_file = self :: getAttachmentFilePath($project_record->getId(), $attachment_new_file_name, $attachment_folder);
			$attached_files[] = $attachment_new_file_name;
			if (!file_exists($old_file)) {
				continue;
			} else {
				self :: createFile($target_file, $old_file);

			}
		}

		if (!empty ($attached_files)) {
			$attached_file_names = (implode(':', $attached_files));
		}
		return $attached_file_names;
	}
	
	
	

	public static function createIconHelper($id, $icons_folder, $unique_prefix="") {
		$attached_file = null;

		$upload_file_key = 'uploaded_icon';


		if (!isset ($_FILES[$upload_file_key])) {
			return null;
		}

		$uploaded_file_name = isset ($_FILES[$upload_file_key]['name']) ? basename($_FILES[$upload_file_key]['name']) : '';

		if ($uploaded_file_name == '') {
			return null;
		}
		$uploaded_file_name_without_space = str_replace(' ', '_', $_FILES[$upload_file_key]['name']);
		$uploaded_file_name_without_space = str_replace('\s', '_', $uploaded_file_name_without_space);
		$uploaded_file_name_without_space = str_replace(',', '_', $uploaded_file_name_without_space);
		
		if(!$unique_prefix) {
			$unique_prefix = $id;
		}
		$attachment_file_name = $unique_prefix . '_' . $uploaded_file_name_without_space;
		$target_file = self :: getAttachmentFilePath($id, $attachment_file_name, $icons_folder);


		if (file_exists($target_file)) {
			unlink($target_file);
		} else {
			//check whether folder exist

			$user_folder = dirname($target_file);

			if (!file_exists($user_folder)) {
				mkdir($user_folder, 0700, true);
			}
		}

		//create file
		touch($target_file);

		if (!move_uploaded_file($_FILES[$upload_file_key]['tmp_name'], $target_file)) {
			throw new Exception('Error while uploading the file, please try again!');
		}
		//throw new Exception('KKKKK');
		if (file_exists($target_file)) {
			$attached_file = basename($target_file);
		}

		return $attached_file;
	}

	public static function downloadFile($target_file) {
		$parts = pathinfo($target_file);
		$content_type = isset ($parts['extension']) ? $parts['extension'] : 'text';
		$base_name = $parts['basename'];
		if (file_exists($target_file)) {
			header("Cache-Control: public");
			header("Content-Description: File Transfer");
			header('Content-disposition: attachment; filename=' . $base_name);
			header("Content-type: application/$content_type");
			header("Content-Transfer-Encoding: binary");
			header('Content-Length: ' . filesize($target_file));
			//echo file_get_contents($target_file);
			$file = fopen($target_file, "r");
			while (!feof($file)) {
				echo fread($file, 8192);
			}
			fclose($file);
		}
		return;
	}

	public static function deleteIssueAttachments($config, $issue_record) {
		$db = Db :: getInstance($config);
		$issue_attachment_files = array ();

		$issue_attachment_files = explode(':', $issue_record->getAttachmentName());

		if (is_array($issue_attachment_files) && !empty ($issue_attachment_files)) {
			self :: deleteAttachments(Constants :: ISSUE_MESSAGE, $config, $issue_attachment_files);
		}
		$issue_message_records = MessageRecordPeer :: getMessageRecords($db, null, '', Constants :: ISSUE_MESSAGE, $issue_record->getId());
		$issue_message_attachment_files = array ();
		foreach ($issue_message_records as $issue_message_record) {
			$issue_message_attachment_files = array_merge($issue_message_attachment_files, explode(':', $issue_message_record->getAttachmentName()));
		}
		if (is_array($issue_message_attachment_files) && !empty ($issue_message_attachment_files)) {
			self :: deleteAttachments(Constants :: ISSUE_MESSAGE, $config, $issue_message_attachment_files);
		}
	}

	//courtesy - http://stackoverflow.com/questions/14241257/
	public static function getFormatedDate($time) {
		if ($time >= strtotime("today 00:00")) {
			return date("g:i A", $time);
		}
		elseif ($time >= strtotime("yesterday 00:00")) {
			return "Yesterday at " . date("g:i A", $time);
		}
		elseif ($time >= strtotime("-6 day 00:00")) {
			return date("l \\a\\t g:i A", $time);
		} else {
			return date("M d, Y", $time);
		
		}
	}
	
	public static function getChartFilePath ($user_id, $file_name) {
		
		$chart_folder = self::get_temp_folder().DIRECTORY_SEPARATOR.$user_id;
			if (!file_exists($chart_folder)) {
				@mkdir($chart_folder, 0700, true);
			}
		
		return $chart_folder.DIRECTORY_SEPARATOR.$file_name;
		
	}
	
	//returns original one if none exists
	public static function getAttachmentThumbFile ($sz_wh,$attachment_name, $attachment_folder, $default_file, $project_record ) {
		$attachment_name_thumb = 'thumb_' . $sz_wh . '_' . $attachment_name;
					$target_file_thumb = self :: getAttachmentFilePath($project_record->getId(), $attachment_name_thumb, $attachment_folder);
					if (!file_exists($target_file_thumb)) {
						$attachment_name_thumb = 'thumb_' . $attachment_name;
						$target_file_thumb = self :: getAttachmentFilePath($project_record->getId(), $attachment_name_thumb, $attachment_folder);
						if (file_exists($target_file_thumb)) {
							return $target_file_thumb;
						}
					}
					return $default_file;
	}

	//$image_types array
	//$image_file 
	public static function isValidImageFile($image_file, $image_types) {
		$size = array ();
		if (file_exists($image_file)) {
			$size = getimagesize($image_file);
		}

		if (!isset ($size["mime"])) {
			return false;
		}
		$mime_type = strtolower($size["mime"]);

		foreach ($image_types as $image_type) {
			$pos = stripos($mime_type, strtolower($image_type));
			if ($pos !== false) {
				return true;
			}
		}

		return false;

	}

	//only used for project icon and   issue report export
	public static function getPermittedImageTypes() {
		return array (
			'png',
			'jpg','jpeg',
			'gif'
		);
	}
	
	/* Abhilash 3.7.2014 */
	private static function createThumbnail($target_file) {
		
	
		$type = '';
		list($width, $height, $type, $attr) = @getimagesize($target_file); 
		if(!in_array($type, array(IMAGETYPE_GIF,IMAGETYPE_JPEG ,IMAGETYPE_PNG))) {
			return;
		}
		
		 $file_name = basename($target_file); //file name from absolute file path
	 $attachment_folder_path = dirname($target_file); //folder of target_file
		
			
					$thumb_width = 150;
					$thumb_height = 120;
					
					//create the thumbnail
					$thumb = imagecreatetruecolor($thumb_width, $thumb_height);
					if($type == IMAGETYPE_PNG) { 
					$image = imagecreatefrompng($target_file);
					}
					if($type == IMAGETYPE_JPEG) {
						$image = imagecreatefromjpeg($target_file);
					}
					if($type == IMAGETYPE_GIF) {
						$image = imagecreatefromgif($target_file);
					}
					imagecopyresampled($thumb, $image, 0, 0, 0, 0, $thumb_width,$thumb_height,$width, $height);
					
					$thumb_file = $attachment_folder_path . DIRECTORY_SEPARATOR . 'thumb_'. $file_name;
					
					imagejpeg($thumb, $thumb_file, 100);
					imagedestroy($thumb);
				
	}
	
	
	
  
	
	
}
?>