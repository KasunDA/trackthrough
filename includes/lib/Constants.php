<?php
/*
 * Created on April 12, 2009
 *
 * bispark software services
 * www.bispark.com
 */
final class Constants {
	const PROJECT = 1;
	const TASK = 2;	
	const USER = 11;
	const ISSUE = 101;
		
	const TASK_OPEN = 3;
	const TASK_INPROGRESS = 4;
	const TASK_CLOSED = 5;
	const TASK_REVIEW_PENDING = 6;
	
	const ISSUE_OPEN = 103;
	const ISSUE_CLOSED = 104;
	
	//types
	const PROJECT_MESSAGE = 5;
	const TASK_MESSAGE = 6 ;	
	const GENERAL_MESSAGE = 7;	
	
	
	
	const ISSUE_MESSAGE = 10 ;	
	
	//statuses
	const NONE =  100;  //active messages (in message record), read messages (in message board)
	const UNREAD_MESSAGE = 101;
	const DELETED_MESSAGE = 9;	
	//const NEW_MESSAGE = 8;
	
	
	
	const ADMINISTRATION = 1;
	const LEAD_PROJECT = 2; // can create project
	const LEAD_TASK = 3;
	const CAN_PERFORM_TASK = 4; // when assigned
	const CAN_ADD_TASK =  5;
	const DENY_ALL_PERMISSIONS = 6;
	const ADD_ISSUE = 7; //can add issue	
	
	const CAN_VIEW_TASK = 8; // when assigned
	
	
	const VALUE_TYPE = 13;
	const BOOLEAN_TYPE = 14;	
	
	const MESSAGE_MAIL_TEMPLATE = 15;
	const PROJECT_MAIL_TEMPLATE = 16;	
	const NEW_PROJECT_MAIL_TEMPLATE = 17;
	
	//const TASK_ASSIGNMENT_MAIL_TEMPLATE = 18;	
	const NEW_TASK_MAIL_TEMPLATE = 19;
	
	// no more used const ADMIN_MESSAGE_MAIL_TEMPLATE = 20;
	// no more used  const ADMIN_PROJECT_MAIL_TEMPLATE = 21;		
	//const ADMIN_TASK_ASSIGNMENT_MAIL_TEMPLATE = 22;	
	//const ADMIN_NEW_TASK_MAIL_TEMPLATE = 23;
	
	
	
	const REGISTRATION_MAIL_TEMPLATE = 25;
	
	
	
	
	
	const PROJECTS_PER_PAGE = "ppp";
	const TASK_COMMENTS_PER_PAGE = "tcpp";
	const MAX_DASHBOARD_BLOCK_ITEMS = "mdbi";
	const MAX_MESSAGES_PER_PAGE = "mmpp";
	
	
	const CLOSE_TASK_WHEN_ISSUE_CLOSED = "cltic";
	const CLOSE_ISSUE_WHEN_TASK_CLOSED = "clitc";

	const SHOW_ALL_COMMENTS_OF_OPEN_ISSUES = "sacoi";
	const SHOW_CLOSED_COMMENTS_OF_CLOSED_ISSUES	= "sccci";
	const SHOW_ATTACHED_IMAGE_WITH_COMMENTS = "saiwc";
	
	
	const SHOW_DASHBOARD_UNREAD_MESSAGES = "dum";
	const SHOW_DASHBOARD_OTHERS_TASKS = "dot";
	const SHOW_DASHBOARD_MY_TASKS = "dmt";
	
	const SEARCH_PROJECT_DETAILS = "spd";
	const SEARCH_TASK_DETAILS = "std";
	const SEARCH_ISSUE_DETAILS = "sid";
	const SEARCH_MESSAGESS = "sm";
	
	const HIDDEN_PROJECT_IDS = "hpids";
	
	//admin only
	const MAX_USER_TABLE_ROWS = "utmr";
	
	const WEBSITE_NAME = "website_name";
	const FROM_EMAIL_ADDRESS = "from_email_address";
	const COMPANY_NAME ="company_name";
	const ATTACHMENT_TYPES = "attachment_types";
	const COPY_MAILS_OF_MESSAGES_TO_ADMINISTRATOR ="copy_mails_of_messages_to_administrator";	
	
	
	
	const ALL_TYPE = "All";
	const ANY_TYPE = "Any";
	
	
	
	//priorities
	
	
	const LOW_PRIORITY = 110;
	const NORMAL_PRIORITY = 113;
	const HIGH_PRIORITY = 115;
	
	
	public static function getLabel($const) {
		$label =  "Undefined";
		switch ($const) {			
			case self::PROJECT:
			$label =  "Project";
			break;			
			case self::TASK:
			$label =  "Task";
			break;			
			case self::TASK_OPEN:
			$label =  "Open";
			break;			
			case self::TASK_INPROGRESS:
			$label =  "In progress";
			break;	
			case self::TASK_REVIEW_PENDING:
			$label =  "Review Pending";
			break;
			case self::TASK_CLOSED:
			$label =  "Closed";
			break;	
			case self::ISSUE_OPEN:
			$label =  "Open";
			break;			
			case self::ISSUE_CLOSED:
			$label =  "Closed";
			break;		
			
			case self::TOP_PRIORITY:
			$label =  "Top";
			break;
			case self::NORMAL_PRIORITY:
			$label =  "Normal";
			break;
			case self::LOW_PRIORITY:
			$label =  "Low";
			break;			
			default:
			break;
		}
		return $label;
	}
}


?>