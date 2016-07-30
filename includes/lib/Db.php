<?php


/*
 * Created on December 21, 2008
 *
 * bispark software services
 * www.bispark.com
 */
final class Db {
	private $dsn;

	private $prefix = '';
	private $dbUser = '';
	private $password = '';
	private $host = '';
	private $databaseName = '';
	private $config;
	private $connection = null;

	private static $instance = null;

	public static function getInstance($config) {
		if (Db :: $instance == null) {
			Db :: $instance = new Db($config);
		}
		return Db :: $instance;
	}

	public function getConfig() {
		return $this->config;
	}
	public function getPrefix() {
		return $this->prefix;
	}
	public function query($sql) {
		$result = @ mysql_query($sql, $this->getConnection());
		if (!$result) {
			throw new Exception('Db::query - ' . mysql_error());
			//throw new Exception('Db::query - ' . $sql);

		}
		return $result;
	}
	public function getLastInsertId() {
		return mysql_insert_id($this->getConnection());
	}

	//following function take output of query method
	public function result($query, $row) {
		$result = @ mysql_result($query, $row);
		if (!$result) {
			throw new Exception('Db::result - ' . mysql_error());
		}
		return $result;

	}

	function rows($query) {
		$result = @ mysql_num_rows($query);
		return $result;
	}

	function dbarray($result) {
		$assoc_result = @ mysql_fetch_assoc($result);
		return $assoc_result;
	}
	
	function dbObject($result){
		$result = @ mysql_fetch_object($result);
		return $result;
	}
	
	function arraynum($query) {
		$result = @ mysql_fetch_row($query);
		if (!$result) {
			throw new Exception('Db::arraynum - ' . mysql_error());
		}
		return $result;
	}
	
	

	public function count($field, $table, $criteria = "") {
		$cond = ($criteria ? " WHERE " . $criteria : "");
		
		$result = @ mysql_query("SELECT Count(" . $field . ") FROM " .   $table . $cond, $this->getConnection());
		if (!$result) {
			throw new Exception('Db::count - ' . mysql_error());
			
		}
		$rows = mysql_result($result, 0);
		return $rows;

	}
	public function escapeString($str) {
		return mysql_real_escape_string(stripslashes ($str), $this->getConnection());
	}
	public function begin() {
		$result = @ mysql_query("BEGIN");
		if (!$result) {
			throw new Exception('Db::begin - ' . mysql_error());
			
		}

	}
	public function commit() {
		$result = @ mysql_query("COMMIT");
		if (!$result) {
			throw new Exception('Db::commit - ' . mysql_error());
			
		}

	}
	public function rollback() {
		$result = @ mysql_query("ROLLBACK");
		if (!$result) {
			throw new Exception('Db::rollback - ' . mysql_error());
			
		}

	}
	
	private function __construct($config) {
		$this->config = $config;
		$this->dsn = $config->getValue('DB', 'dsn');
		$this->prefix = $config->getValue('DB', 'prefix');

		list ($str1, $str2) = explode("@", $this->dsn);
		list ($left, $right) = explode("//", $str1);
		list ($this->dbUser, $this->password) = explode(":", $right);
		list ($this->host, $this->databaseName) = explode("/", $str2);

	}
	private function isConnected() {
		return ($this->connection != null && is_resource($this->connection));

	}

	private function getConnection() {
		if (!$this->isConnected()) {
			$this->connection = @ mysql_connect($this->host, $this->dbUser, $this->password);

			if (!$this->connection) {
				throw new Exception('Db::getConnection - could not connect to database' . mysql_error());
			}

			if (!mysql_select_db($this->databaseName, $this->connection)) {
				throw new Exception('Db::getConnection - Could not select database' . mysql_error());
			}

		}
		return $this->connection;
	}

	private function disconnect() {
		if ($this->isConnected()) {
			mysql_close($this->connection);
			$this->connection = null;

		}
	}

	function __destruct() {

		$this->disconnect();
	}
}
?>
