<?php
/*
 * Created on April 17, 2009
 *
 * bispark software services
 * www.bispark.com
 */
require_once 'CommonRecord.php';
require_once 'UserRecord.php';
require_once 'Constants.php';
require_once 'BookmarkRecord.php';
require_once 'ProjectRecordPeer.php';
require_once 'TaskRecordPeer.php';
require_once 'MessageRecordPeer.php';
require_once 'IssuePeer.php';


class BookmarkRecordPeer {
	
	public static function getIsBookmarked($db, $user_id, $category, $category_id){
		
		
		$table_name = $db->getPrefix() . BookmarkRecord :: TABLE_NAME;
		$user_cond = BookmarkRecord :: USER_ID_COL ."= '$user_id' ";
		$category_cond = BookmarkRecord :: CATEGORY_COL ."= '$category' ";
		$category_id_cond = BookmarkRecord :: CATEGORY_ID_COL ."= '$category_id' ";
		$where_cond = "$user_cond AND $category_cond AND $category_id_cond " ;
		
		
		return $db->count(BookmarkRecord :: ID_COL, $table_name, $where_cond) > 0 ? true : false;
		
		/*
		$records = CommonRecord :: getObjects($table_name, $where_cond, '', '','',new BookmarkRecord($db));
		if(count($records) > 0){ 
			return $records;
		}
		return null;*/
	}
	
	public static function bookmark($db,$user_id,$category,$category_id){
		$is_bookmarked = self :: getIsBookmarked($db, $user_id,  $category, $category_id);
		if(!$is_bookmarked ){  
			$bookmark = new BookmarkRecord($db);
			$bookmark->setUserId($user_id);
			$bookmark->setCategory($category);
			$bookmark->setCategoryId($category_id);
			$bookmark->store();
		} 
		return;
	} 
	
	public static function  getBookmarks($db, $user_id){
		
		$table_name = $db->getPrefix() . BookmarkRecord :: TABLE_NAME;	 
		
		$where_cond = BookmarkRecord :: USER_ID_COL ."= '$user_id' ";
		$records = CommonRecord :: getObjects($table_name, $where_cond, ' id desc','','',new BookmarkRecord($db));
		if (is_array($records) && count($records) > 0) {
			return $records;
		}
		return null;
		
	}
	
	public static function getBookmarksWithCategory($db,$config,$bookmarks){
		$user_bookmarks = null;
		$record_flag = false;
		foreach($bookmarks as $bookmark){
			
			if($bookmark->getCategory() == Constants :: PROJECT){
				$record = ProjectRecordPeer :: findByPK($db,$bookmark->getCategoryId());
				if($record != null){ 
					$bookmark->setBookmarkDescription($record->getName());
					$bookmark->setBookmarkCategoryLabel('Project');
					$record_flag = true;
				}
			}
			else if($bookmark->getCategory() == Constants :: TASK){
			
			
				$record = TaskRecordPeer :: findByPK($db,$bookmark->getCategoryId());
				
				if($record != null){ 
					$project_record = ProjectRecordPeer :: findByPK($db,$record->getParentProjectId());
					$bookmark->setBookmarkDescription($record->getName());
					$bookmark = self :: getBookmarkProjectDetails($config, $bookmark, $project_record);					
					$bookmark->setBookmarkCategoryLabel('Task');
					$record_flag = true;
				}
			}
			else if($bookmark->getCategory() == Constants :: ISSUE){
				$record = IssuePeer :: findByPK($db,$bookmark->getCategoryId());				
				if($record != null){					 
					$bookmark->setBookmarkDescription($record->getTitle());
					$project_record = ProjectRecordPeer :: findByPK($db,$record->getProjectId());
					$bookmark = self :: getBookmarkProjectDetails($config, $bookmark, $project_record);	
					$bookmark->setBookmarkCategoryLabel('Issue');
					$record_flag = true;
				}
			}
			else if($bookmark->getCategory() == Constants :: PROJECT_MESSAGE){
				$record = MessageRecordPeer :: findByPK($db,$bookmark->getCategoryId());
				if($record != null){	 
					$project_record = ProjectRecordPeer :: findByPK($db,$record->getTypeId());
					$bookmark->setBookmarkDescription($record->getCont());
					$bookmark = self :: getBookmarkProjectDetails($config, $bookmark, $project_record);
					$bookmark->setBookmarkCategoryLabel('Project Message');
					$bookmark->setMessageProjectId($record->getTypeId());
					$record_flag = true;
				}
			}
			else if($bookmark->getCategory() == Constants :: TASK_MESSAGE){
				$record = MessageRecordPeer :: findByPK($db,$bookmark->getCategoryId());			
				if($record != null){ 
					$task_record = TaskRecordPeer :: findByPK($db,$record->getTypeId());
					$bookmark->setBookmarkDescription($record->getCont());
					$project_record = ProjectRecordPeer :: findByPK($db,$task_record->getParentProjectId());
					$bookmark = self :: getBookmarkProjectDetails($config, $bookmark, $project_record);
					$bookmark->setBookmarkCategoryLabel('Task Message');
					$bookmark->setMessageTaskId($record->getTypeId());
					$record_flag = true;
				}
			}
			else if($bookmark->getCategory() == Constants :: ISSUE_MESSAGE){
				$record = MessageRecordPeer :: findByPK($db,$bookmark->getCategoryId());	
				if($record != null){ 
					$issue_record = IssuePeer :: findByPK($db,$record->getTypeId());					
					$bookmark->setBookmarkDescription($record->getCont());
					$project_record = ProjectRecordPeer :: findByPK($db,$issue_record->getProjectId());
					$bookmark = self :: getBookmarkProjectDetails($config, $bookmark, $project_record);
					$bookmark->setBookmarkCategoryLabel('Issue Message');
					$bookmark->setMessageIssueId($record->getTypeId());
					$record_flag = true;
				}
			}
		}
		if($record_flag ){ 
			$user_bookmarks = $bookmarks;
		}
		return $user_bookmarks;
	} 
	
	
	public static function  getCategoryBookmarks($db,  $category, $category_id){
		$table_name = $db->getPrefix() . BookmarkRecord :: TABLE_NAME;
		$where_cond = BookmarkRecord :: CATEGORY_ID_COL ."= '$category_id' AND " . BookmarkRecord :: CATEGORY_COL . "= '$category' ";
		$records = CommonRecord :: getObjects($table_name, $where_cond, '', '','',new BookmarkRecord($db));
		if(count($records) > 0){ 
			return $records;
		}
		return null;
	}
	
	
	public static function deleteBookmarks($db, $bookmark_ids) {
		$table_name = $db->getPrefix() . BookmarkRecord :: TABLE_NAME;
		CommonRecord :: delete($db, $table_name, BookmarkRecord :: ID_COL, $bookmark_ids);

	}
	
	
	
	
	
	
	private static function getBookmarkProjectDetails($config, $bookmark, $project_record){
		    $bookmark->project_name = '';
			$bookmark->project_id = '';
			$bookmark->project_icon = '';
			if ($project_record != null) {
				$view_url = $config->getValue('FW', 'base_url').'/project/view/id/' . $project_record->getId();
				$name_more_link = '<a href="' . $view_url . '" class="more_link">...</a>';
				//$bookmark->project_short_name =  Util :: truncate($project_record->getName(), 140, $name_more_link); /* abhilash 16-10-13 */
				$bookmark->project_short_name =  Util :: truncate($project_record->getName(), 100, $name_more_link); /* megha 11-3-15 */
				
				$bookmark->project_name = $project_record->getName();
				$bookmark->project_id = $project_record->getId();
				$project_icons_folder = Util :: getAttachmentFolderName($config, $project_record->getType());
				if($project_record->getIconName() != null){
					$project_icon_name  = $project_record->getIconName();
					$bookmark->project_icon = $config->getValue('FW', 'base_url'). '/' .$project_icons_folder . $project_record->getId() . '/'.$project_icon_name;
				}
				else{
					$project_icon_name  = 'default.png';
					$bookmark->project_icon = $config->getValue('FW', 'base_url'). '/resources/images/'.$project_icon_name;
				}
				
			}
			return $bookmark;
	}
	
	
	
}
?>