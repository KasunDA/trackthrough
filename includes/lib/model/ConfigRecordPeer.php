<?php


/*
 * Created on May 14, 2009
 *
 * bispark software services
 * www.bispark.com
 */
require_once 'CommonRecord.php';
require_once 'ConfigRecord.php';
require_once 'Constants.php';
class ConfigRecordPeer {
	const CONFIG_CACHE = 'CONFIG_CACHE';
	public static function createIfNotExist($db, $keyName, $value, $type = Constants :: VALUE_TYPE) {
		$configRecord = self :: findByKeyName($db, $keyName);

		if ($configRecord == null) {

			$configRecord = new ConfigRecord($db);
		}
		
		$configRecord->setKeyName($keyName);
		$configRecord->setValue($value);
		$configRecord->setType($type);
		$configRecord->store();

		return $configRecord;
	}
	/*
	public static function update($db, $id, $keyName, $value, $type=Constants::VALUE_TYPE) {
		$table_name = $db->getPrefix() . ConfigRecord :: TABLE_NAME;	
		$sql = "update  " . $table_name . " set " . ConfigRecord :: KEY_NAME_COL . "='$keyName'," . ConfigRecord :: VALUE_COL . "='$value'," . ConfigRecord :: TYPE_COL . "='$type' where id='" . $id . "'";
		
		$db->query($sql);
	}	*/
	public static function getConfigRecords($db) {

		$where_cond = '';
		$table_name = $db->getPrefix() . ConfigRecord :: TABLE_NAME;
		return CommonRecord :: getObjects($table_name, $where_cond, ' key_name asc  ', '', '', new ConfigRecord($db));

	}
	public static function findByKeyName($db, $keyName) {

		$where_cond = ConfigRecord :: KEY_NAME_COL . "='$keyName' ";

		$table_name = $db->getPrefix() . ConfigRecord :: TABLE_NAME;
		$records = CommonRecord :: getObjects($table_name, $where_cond, '', '', 1, new ConfigRecord($db));
		if (is_array($records) && count($records) > 0) {
			return $records[0];
		}
		return $records;
	}

	public static function getCompanyName($action_obj) {

		return self :: getValue($action_obj, Constants :: COMPANY_NAME);

	}
	/*
	public static function getProjectsPerPage($action_obj) {
	
		return self :: getValue($action_obj ,Constants::PROJECTS_PER_PAGE);
				
	}
	public static function getTaskCommentsPerPage($action_obj) {
	
		return self :: getValue($action_obj ,Constants::TASK_COMMENTS_PER_PAGE);
				
	}*/
	public static function getCopyMailsToAdmin($action_obj) {

		return self :: getValue($action_obj, Constants :: COPY_MAILS_OF_MESSAGES_TO_ADMINISTRATOR) == 1 ? true : false;

	}
	public static function getFromEmailAddress($action_obj) {
		return self :: getValue($action_obj, Constants :: FROM_EMAIL_ADDRESS);
	}
	public static function getWebSiteName($action_obj) {
		return self :: getValue($action_obj, Constants :: WEBSITE_NAME);
	}
	public static function getAttachmentTypes($action_obj) {
		return self :: getValue($action_obj, Constants :: ATTACHMENT_TYPES);
	}

	private static function getValue($action_obj, $keyName) {
		$config_cache = $action_obj->getParameter(self :: CONFIG_CACHE);
		$config_cache = !$config_cache || !is_array($config_cache) ? array () : $config_cache;
		if (!isset ($cache[$keyName])) {
			$db = Db :: getInstance($action_obj->getConfig());
			$record = self :: findByKeyName($db, $keyName);
			$config_cache[$keyName] = ($record != null) ? $record->getValue() : '';
			//store for the future
			$action_obj->setParameter(self :: CONFIG_CACHE, $config_cache);
		}

		return isset ($config_cache[$keyName]) ? $config_cache[$keyName] : '';
	}
}
?>