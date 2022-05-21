<?php
/**
 * @name Database class
 * @author Miguel Fermín
 * @copyright 2013
 * @license MIT
 * @license http://opensource.org/licenses/MIT
 */
class DataBase {
	
	public $conn;
	
	public $queryCount = 0;
	
	public $queries = array();
	
	static private $instance;
	
	public function __construct()
	{
		// todo: shouldn't use root in web
		$this->conn = mysqli_connect('<botdbserver>', '<dbuser>', '<dbpwd>');
		mysqli_select_db($this->conn, '<botdbname>');
		mysqli_set_charset($this->conn, 'utf8');
	}
	
	static public function instance()
	{
		if (!isset(self::$instance)) {
			$name = __CLASS__;
			self::$instance = new $name;
		}
		return self::$instance;
	}
	
	public function esc($str)
	{  
		if (is_null($str)) return null; 
		return mysqli_real_escape_string($this->conn, $str); 
	}

	public function escape($str)
	{ 
		if (is_null($str)) return null; 
		return mysqli_real_escape_string($this->conn, $str); 
	} 
	
	public function escapeArray($arr)
	{
        foreach ($arr as $k => $v) {
        	if (!is_null($v)) {
            	$arr[$k] = $this->escape($v);
            }
        }
        return $arr;
	}
	
	public function lastError() {
		return mysqli_error($this->conn);
	}

	public function query($query)
	{
		$this->queryCount++;
		$this->queries[] = $query;
	
        
		$result = mysqli_query($this->conn, $query);
		
		if ($result === false) {
			$this->debug($query);
		}
		
		if (mysqli_num_rows($result) == 0) {
			return array();
		}
		
		$salida = array();
		while ($row = mysqli_fetch_assoc($result)) {
			$salida[] = $row;
		}

		mysqli_free_result($result);
		
		return $salida;
	}

	public function count($table, $opts = array())
	{
		$where = "";
		if (!empty($opts['where'])) {
			$where = $this->where($opts['where']);
		}
		
		$query = "SELECT COUNT(*) AS result FROM $table $where";
		
		$row = $this->queryOne($query);
		
		return (int)$row['result'];
	}
	
	public function lastId()
	{
		return mysqli_insert_id($this->conn);
	}
	
	protected function debug($query)
	{
		//echo "<br>Error in the sentence: ". $query . "<br>";
		//echo mysqli_error();
		//$e = new Exception();
		//pr($e->getTraceAsString());
	}
	
	public function insert($table, $data)
	{
		$this->queryCount++;

		$fields = $this->escapeArray(array_keys($data));
		$values = $this->escapeArray(array_values($data));
		
		foreach ($values as $k => $val) {
			if (is_null($val)) {
				$values[$k] = 'NULL';
			} else {
				$values[$k] = "'$val'";
			}
		}
		
		$query = "INSERT INTO $table(`".join("`,`",$fields)."`) VALUES(".join(",", $values).")";

		$this->queries[] = $query;
		
		return mysqli_query($this->conn, $query);
	}	
	
	public function execute($query)
	{
		$this->queryCount++;
		$this->queries[] = $query;
		
		return mysqli_query($this->conn, $query);
	}

	public function multiExecute($multiQuery)
	{
		$this->queryCount++;
		$this->queries[] = $multiQuery;
		
		return mysqli_multi_query($this->conn, $multiQuery);
	}
	
	public function getAffectedRows()
	{
		return mysqli_affected_rows($this->conn);
	}
	
	public function select($table, $opts = array())
	{
		$fields = "*";
		$where = '';
		$order = '';
		
		if (!empty($opts['fields'])) {
			if (is_array($opts['fields'])) {
				$fields = join(",", $opts['fields']);
			} else {
				$fields = $opts['fields'];
			}
		}
		
		if (!empty($opts['where'])) {
			$where = $this->where($opts['where']);
		}
		
		if (!empty($opts['order'])) {
			$order = "ORDER BY " . $opts['order'];
		}
		
		$query = "SELECT $fields FROM $table $where $order";
	
		if (!empty($opts['limit'])) {
			if ($opts['limit'] === 1 || $opts['limit'] == '1') {
				return $this->queryOne($query." LIMIT 1");
			}
			
			$query .= " LIMIT ".$opts['limit'];
		}

//echo $query;

		return $this->query($query);
	}
	
	public function selectOne($table, $opts = array())
	{
		$opts['limit'] = 1;
		return $this->select($table, $opts);
	}
	
	public function update($table, $data, $opts = array())
	{
		$where = "";
		if (!empty($opts['where'])) {
			$where = $this->where($opts['where']);
		}
		
		$update = array();
		foreach ($data as $field => $value) {

			if (is_null($value)) {
				$update[] = "$field = NULL";
			} else {
				$update[] = "$field = '".$this->esc($value)."'";
			}
		}
		
		$query = "UPDATE $table SET ".join(" , ", $update)." $where";
		
		return $this->execute($query);
	}
	
	public function where($conditions)
	{
		$where = "";
		if (!empty($conditions) && is_array($conditions)) {
			$where = array();
			foreach ($conditions as $field => $value) {
				if (is_numeric($field) || empty($field)) {
					$where[] = " $value ";
				} else if (is_null($value)) {
					$where[] = " $field is null ";
				} else {
					$where[] = " $field = '".$this->escape($value)."' ";
				}
			}
			if (!empty($where)) {
				$where = " WHERE " . join(" AND ", $where);
			}
		} else if (!empty($conditions)) {
			$where = " WHERE " . $conditions;
		}
		return $where;
	}
	
	public function getById($table, $id, $fields = null)
	{
		if (!empty($fields)) {
			$query = "SELECT $fields FROM $table WHERE ID = '".(int)$id."'";
		} else {
			$query = "SELECT * FROM $table WHERE ID = '".(int)$id."'";
		}
		return $this->queryOne($query);
	}
	
	public function queryOne($query)
	{
		$this->queryCount++;
		$this->queries[] = $query;
		
		$result = mysqli_query($this->conn, $query);
		if (!$result) {
			return false;
		}
		
		if (mysqli_num_rows($result) == 0) {
			return false;
		}

		$row = mysqli_fetch_assoc($result);
		
		mysqli_free_result($result);
		
		return $row;
	}
	
	public function begin()
	{ 
		return $this->execute("START TRANSACTION;"); 
	}

	public function rollback()
	{ 
		return $this->execute("ROLLBACK;"); 
	}

	public function commit()
	{ 
		return $this->execute("COMMIT;"); 
	}

}