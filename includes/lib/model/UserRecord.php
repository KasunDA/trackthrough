<?php


/*
 * Created on April 12, 2009
 *
 * bispark software services
 * www.bispark.com
 */

require_once 'CommonRecord.php';
require_once 'Util.php';
require_once 'Constants.php';
class UserRecord extends  CommonRecord {
	
	const ID_COL='id'; 
	const SIGNIN_ID_COL = 'signin_id';
	const PASSWORD_COL = 'password';
	const IV_COL = 'iv';
	const FIRST_NAME_COL = 'first_name';
	const LAST_NAME_COL = 'last_name';
	const EMAIL_COL = 'email';
	const ICON_NAME_COL = 'icon_name';
	//const TYPE_COL = 'type';
	
	const CREATED_AT_COL = 'created_at';
	const SIGNEDIN_AT_COL = 'signedin_at';	
	
	const TABLE_NAME = 'user';
	
	protected $id;
	private $signinId;
	private $password;
	private $iv;
	private $iconName;
	private $firstName;
	private $lastName;
	
	private $email;
	
	
	//private $type;
	
	private $createdAt = '0000-00-00 00:00:00';
	private $signedinAt = '0000-00-00 00:00:00';
	
	
	function __construct($db) {
		parent::__construct($db);
		$this->createdAt = date('Y-m-d H:i:s');	
	}	
	
	
	public function getSigninId() {
		return $this->signinId;
	}
	public function getPassword() {
		return $this->password;
	}
	public function getIv() {
		return $this->iv;
	}
	public function getFirstName() {
		return $this->firstName;
	}
	public function getLastName() {
		return $this->lastName;
	}
	public function getEmail() {
		return $this->email;
	}
	public function getIconName() {
		return $this->iconName;
	}
	
	public function getCreatedAt() {
		return $this->createdAt;
	}
	public function getSignedinAt() {
		return $this->signedinAt;
	}
		
	public function setSigninId($signinId) {
		 $this->signinId = $signinId;
	}
	public function setPassword($password) {
		 $this->password = $password;
	}
	public function setIv($iv) {
		 $this->iv = $iv;
	}
	
	public function setFirstName($firstName) {
		 $this->firstName = $firstName;
	}
	public function setLastName($lastName) {
		 $this->lastName = $lastName;
	}
	public function setEmail($email) {
		 $this->email = $email;
	}
	/*public function setType($type) {
		 $this->type = $type;
	}*/
	
	public function setCreatedAt($createdAt) {
		$this->createdAt = $createdAt;
	}
	public function setIconName($iconName) {
		$this->iconName = $iconName;
	}
	
	
	public function setSignedinAt($signedinAt) {
		 $this->signedinAt = $signedinAt;
	}
	
	function getTableName()  {
		return self::TABLE_NAME;
	}
	
	function getActualPassword() {
		return trim(Util :: decrypt($this->password, $this->signinId, $this->iv));
	}
	
		
	function getHasFirstName() {
		return ($this->firstName == '')?  false : true;
	}
	
	//utility function
	private $n_completed_projects = 0;
	function setNumberOfCompletedProjects($n_completed_projects) {
		$this->n_completed_projects = $n_completed_projects;
	}
	function getNumberOfCompletedProjects() {
		return $this->n_completed_projects ;
	}
    
    private $user_permission;
    function setPermission($user_permission) {
		$this->permission = $user_permission;
	}
	function getPermission() {
		return $this->permission;
	}
	
	function isSelectedTeam($team,$selected){
		if($team->getId() == $selected){
			return true;
		}
		return false;
	}
	
	function isSelectedUser($user,$selected){
		if($user->getId() == $selected){
			return true;
		}
		return false;
	}
	
    function isLead($user_type){
    	if($user_type == Constants :: LEAD_PROJECT){
    		return true;
    	}
    	return false;
    }
    
    
	function getNameValueAssoc() {

		return array (
			self::SIGNIN_ID_COL => $this->signinId,
			self::PASSWORD_COL => $this->password,
			self::IV_COL => $this->iv,			
			self::FIRST_NAME_COL => $this->firstName,
			self::LAST_NAME_COL => $this->lastName,
			self::EMAIL_COL => $this->email,
			self :: ICON_NAME_COL => $this->iconName,
			self :: CREATED_AT_COL => $this->createdAt,
			self :: SIGNEDIN_AT_COL => $this->signedinAt,
			
		);

	}
 
}
?>