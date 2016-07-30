<?php
// | FW is a frame work designed and developed by bispark software        |
// | (www.bispark.com), Dharwad, India. Fw derives its concepts from other|
// |  softwares like  Symfony and CodeIgnitor. Also, it is to be noted    |
// | that CodeIgnitor &  Symfony are much more versatile and have many    |        
// | more features than Fw.                                                                  |
// | This LICENSE is in the BSD license style.                            |
// |                                                               		  |
// | Redistribution and use in source and binary forms, with or without   |
// | modification, are permitted provided that the following conditions   |
// | are met:                                                             |
// |                                                                      |
// | Redistributions of source code must retain the above copyright       |
// | notice, this list of conditions and the following disclaimer.        |
// |                                                                      |
// | Redistributions in binary form must reproduce the above copyright    |
// | notice, this list of conditions and the following disclaimer in the  |
// | documentation and/or other materials provided with the distribution. |
// |                                                                      |
// | Neither the name of bispark software nor the names of other          |
// | contributors may be used to endorse or promote products derived from |       							 |
// | this software without specific prior| written permission.   
/*
 * Created on May 21, 2007
 *
 * bispark software services
 * www.bispark.com
 */
require_once 'BaseController.php';
require_once 'ErrorView.php';
require_once 'FlexyView.php';
require_once 'Db.php';
require_once 'Constants.php';
require_once 'ProjectRecord.php';
require_once 'ProjectRecordPeer.php';
require_once 'TaskRecord.php';
require_once 'TaskRecordPeer.php';
require_once 'MessageRecordPeer.php';
require_once 'MessageBoardRecordPeer.php';
require_once 'UserRecord.php';
require_once 'UserRecordPeer.php';
require_once 'ConfigRecord.php';
require_once 'ConfigRecordPeer.php';
require_once 'ActionHelper.php';
require_once 'AppLogPeer.php';
require_once 'BookmarkRecord.php';
require_once 'BookmarkRecordPeer.php';
require_once 'UserPermissionPeer.php';
require_once 'Issue.php';
require_once 'IssuePeer.php';
require_once 'IssueTask.php';
require_once 'IssuePdf.php';

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
	}
	
	function index($args) {
		$this->common($args);
		$this->title = "Error";
		$this->error_message = $args['error_message'];
		require_once 'FlexyView.php';
		return new FlexyView('error.html', $this);
	}
}


?>
