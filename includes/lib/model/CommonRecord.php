<?php

/*
 * Created on April 11, 2009
 *
 * bispark software services
 * www.bispark.com
 */

abstract class CommonRecord {
	const ID_COL = 'id';
	protected $id;
	protected $db;

	function __construct(& $db) {
		$this->db = $db;

	}
	public function getId() {
		return $this->id;
	}
	public function setId($id) {
		$this->id = $id;
	}
	public function getDb() {
		return $this->db;
	}
	//exception
	public function store($column_names = null) {
		if (!isset ($this->id)) {
			$this->id = $this->createRecord($column_names);
		//	echo $this->id;exit();
		} else {
			$this->updateRecord($column_names);
		}
	}
	abstract protected function getNameValueAssoc();
	abstract protected function getTableName();

	public function __toString() {
		$data = $this->getNameValueAssoc();
		$str = "";
		if (is_array($data)) {
			$data[self :: ID_COL] = $this->id;
			foreach (array_keys($data) as $key) {
				$str .= "$key = " . $data[$key] . "\n";
			}
		}
		return $str;
	}

	private function createRecord($column_names = null) {
		$data = $this->getNameValueAssoc();
		//$data[self :: ID_COL] = null;  //error when you use strict mode
		$sql = "insert into " . $this->db->getPrefix() . $this->getTableName() . " (";
		$values = " values (";
		$keys = array_keys($data);
		$col_name_cnt = 0;
		for ($cnt = 0; $cnt < count($keys); $cnt++) {
			$key = $keys[$cnt];
			if ($column_names == null || in_array($key, $column_names)) {
				if ($column_names == null) {
					$comma = ($cnt < (count($keys) - 1)) ? "," : " ";
				} else {
					$comma = ($col_name_cnt < (count($column_names) - 1)) ? "," : " ";
				}
				$sql .= "$key$comma";
				//$values .= "'" . $data[$key] . "'$comma";

				$values .= "'" . $this->db->escapeString($data[$key]) . "'$comma";
				$col_name_cnt++;
			}
		}

		$sql .= ") " . $values . ")";

		$this->db->query($sql);
		$this->id = $this->db->getLastInsertId();
		
		return $this->id;

	}
	private function updateRecord($column_names = null) {
		$data = $this->getNameValueAssoc();
		//not required for update - $data[self :: ID_COL] = $this->id;
		$sql = "update  " . $this->db->getPrefix() . $this->getTableName() . " set ";

		$keys = array_keys($data);
		$col_name_cnt = 0;
		for ($cnt = 0; $cnt < count($keys); $cnt++) {
			$key = $keys[$cnt];
			if ($column_names == null || in_array($key, $column_names)) {
				if ($column_names == null) {

					$comma = ($cnt < (count($keys) - 1)) ? "," : " ";
				} else {
					
					$comma = ($col_name_cnt < (count($column_names) - 1)) ? "," : " ";
				}
				$sql .= "$key='" . $this->db->escapeString($data[$key]) . "'$comma";
				$col_name_cnt++;
			}
		}

		$sql .= " where id='" . $this->id . "'";
		//throw new Exception(" $sql");
		return $this->db->query($sql);
	}

	private static function convColumnNameToMethodName($col_name, $beginwith) {
		$tokens = explode("_", $col_name);
		$methodName = $beginwith;
		foreach ($tokens as $token) {
			$ch1 = substr($token, 0, 1);
			$rest = substr($token, 1);
			$methodName .= strtoupper($ch1) . $rest;
		}
		return $methodName;
	}
	public static function convResultsetToObjects($res, $model_object, $column_names_arr = array()) {
		$column_names =  empty($column_names_arr) ?  array_keys($model_object->getNameValueAssoc()) : $column_names_arr;

		$column_names[] = self :: ID_COL;

		$model_array = array ();

		while ($row = $model_object->getDb()->dbarray($res)) {
			$object = clone $model_object;
			foreach ($column_names as $column_name) {
				$method = self :: convColumnNameToMethodName($column_name, 'set');
				if (isset ($row[$column_name]) && method_exists($object, $method)) {

					call_user_func(array (
						& $object,
						$method
					), $row[$column_name]);
				}
			}

			$model_array[] = $object;
		}

		return $model_array;
	}
	public static function getObjects($table_name, $where_cond = '', $order_by = '', $offset = '', $limit = '', $model_object, $column_names=array()) {
		
		$where_cond = ($where_cond == '') ? '' : " where $where_cond ";
		$order_by = ($order_by == '') ? '' : " order by $order_by ";
		$offset = ($offset == '') ? '' : " offset $offset ";
		$limit = ($limit == '') ? '' : " limit $limit ";

		$column_name_str = empty($column_names)? ' * ' : implode (',', $column_names);
		
		$sql = "select $column_name_str from $table_name $where_cond  $order_by $limit $offset ";
		//var_dump($sql);
		$db = $model_object->getDb();
		$result = $db->query($sql);
		return self :: convResultsetToObjects($result, $model_object, $column_names);
	}
	
	
	public static function findById($table_name, $id, $model_object) {
		$where_cond = self :: ID_COL . "='" . $id . "' ";
		$records = self :: getObjects($table_name, $where_cond, '', '', 1, $model_object);
		if (is_array($records) && count($records) > 0) {
			return $records[0];
		}
		return null;
	}
	public static function delete($db, $table_name, $col, $value_arr) {
		
		$where_cond = $col . " IN ('" . implode("','", $value_arr) . "')";

		$sql = "DELETE FROM $table_name where $where_cond";
		return $db->query($sql);
	}
	

}
?>