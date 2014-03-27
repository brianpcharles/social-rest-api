<?php

/**
 * Portable DB functions 
 * 
 * @author bcharles
 *
 */
trait DBO {
	
	//default.
	protected $dbName;
	protected $table;
	
	/**
	 * DB Instance- available all the time.
	 * @var DB
	 */
	protected $db;
	
	/**
	 * Insert
	 * If this->table is set, save a new row.
	 * @return number - the instered id.
	 */
	protected function insert() {
		//no table, this won't work.
		if(empty($this->table)) return false;
		//
		$fields = $this->getFields(true);
		$this->created_at = date('Y-m-d H:i:s');
	
		$query = "INSERT INTO {$this->table} SET " . implode($fields, ', ') . ", created_at = '{$this->created_at}'";
			
		if(!$result = $this->db->writeQuery($query)) {
			throw new Exception($this->db->getError());
		}
		return $this->db->getID();
	}
	
	/**
	 * Update
	 * If this->table is set and we already have the primary, update the current row.
	 * @return bool
	 */
	protected function update() {
		if(empty($this->table)) return false;
	
		$fields = $this->getFields();
		$primaryKey = $this->db->getPrimaryKey($this->table);
		
		$query = "UPDATE {$this->table} SET " . implode($fields, ', ') . "
		WHERE {$primaryKey} = " . $this->db->formatDBString($this->map($primaryKey)) . "
		LIMIT 1";
	
		if(!$result = $this->db->writeQuery($query)) {
			throw new Exception($this->db->getError());
		}
	}
	
	/**
	* Get the field list from the map and create the insert/update query sets
	* @param bool $primary - if primary is set, skip it. insert only.
	* @return mixed
	*/
	protected function getFields($primary=false) {
		
		//the map is a list of db values and how they relate to class values.
		$fieldList = $this->getMap();
		
		$fields = array();
		//get the table primary key
		$primaryKey = $this->db->getPrimaryKey($this->table);
		//if primary (ignore primary actually), check for a value, or unset the value.
		if($primary) {
			if(is_numeric($fieldList[$primaryKey]) && $fieldList[$primaryKey] > 0) return $this->update();
			else unset($fieldList[$primaryKey]);
		}
		
		$columns = $this->db->getTableColumns($this->table);
		//loop and create :)
		foreach($fieldList as $key=>$value) {
			if(!empty($primaryKey) && $key == $primaryKey) continue;
				
			for($i = 0; $i < count($columns); $i++) {
				$column = (object)$columns[$i];
				if($column->name == $key) {
					//if the column is zero or a number, insert it plain.
					if($value == "0" || is_numeric($value)) $fields[] = "{$key} = '" . $value . "'";
					//otherwise, scrub and clean it.
					else $fields[] = "{$key} = " . $this->db->formatDBString($value);
				}
				continue;
			}
		}
	
		return $fields;
	}
	
	/**
	 * @return string $table - table name.
	 */
	public function getTable() {
		if(!empty($this->table)) return $this->table;
		return false;
	}
	
	/**
	* Get a list of rows based on simple table filters.
	* Text is matched on '%LIKE%' and numbers are exact matches.
	* @param strig $class - which class we want to go get (uses the assumed class table)
	* @param mixed (key=>value) column, value
	*/
	public static function getFilteredResults($class, $filter=array(), $order=NULL, $dbName=NULL) {
		$object = new $class();
		//get the table.
		$table = $object->getTable();
		//this always opens a new db connection.
		$db = DB::load($dbName, true);
		//get the primary key
		$key = $db->getPrimaryKey($table);
		
		//start the query by selecting the primary key.
		$query = "SELECT {$key} FROM {$table}";
		$where = array();
		
		if(!empty($filter)) {
			//loop through the filters t create a "where"
			foreach($filter as $key=>$value) {
				if($key === "0" || is_numeric($key)) $where[] = $value;
				else {
					if($key == "0" || is_numeric($value)) {
						$where[] = "{$key} = {$value}";
					}
					else {
							$where[] = "{$key} LIKE \"%{$value}%\"";
					}
				}
			}
		}
		//add any where to the query	
		if($where) $query .= " WHERE " . implode($where, ' AND ');
		//sort the query (if applicable)
		if($order) $query .= " ORDER BY " . $order;
		//go get the query, finally.
		$results = $db->fetchRowList($query);
	
		$set = array();
		if(!empty($results)) {
			//now try an create valid object instances and put them in a set array.
			foreach($results as $row) {
				//the object id (primary key value)
				$id = (int)$row[0];
				//start the class!!
				$obj = new $class($id);
				//add it to the array
				$set[] = $obj;
			}
		}
		//finally, return the array. 
		//(results may vary).
		return $set;
	}
}