<?php


/*
 * Created on April 22, 2009
 *
 * bispark software services
 * www.bispark.com
 */

require_once 'CommonRecord.php';
require_once 'Constants.php';

class MessageBoardRecord extends CommonRecord {

	const MESSAGE_ID_COL = 'message_id';
	const USER_ID_COL = 'user_id';
	const STATUS_COL = 'status';
	const TABLE_NAME = 'message_board';

	private $messageId;
	private $userId;
	private $status;

	function __construct($db) {
		parent :: __construct($db);
		$this->quantity = 0;
		$this->primaryKeywordCount = 0;
		$this->status = Constants :: UNREAD_MESSAGE;
	}

	public function getMessageId() {
		return $this->messageId;
	}

	public function getUserId() {
		return $this->userId;
	}
	public function getStatus() {
		return $this->status;
	}

	public function setMessageId($messageId) {
		$this->messageId = $messageId;
	}

	public function setUserId($userId) {
		$this->userId = $userId;
	}
	public function setStatus($status) {
		$this->status = $status;
	}

	function getTableName() {
		return self :: TABLE_NAME;
	}

	function getIsUnread() {
		return $this->status == Constants :: UNREAD_MESSAGE;
	}

	function getNameValueAssoc() {

		return array (
			self :: MESSAGE_ID_COL => $this->messageId,
			self :: USER_ID_COL => $this->userId,
			self :: STATUS_COL => $this->status
		);

	}

}
?>