<?php
/**
 * Abstract Class
 * Contains class definitions/requirements
 * 
 * @author bcharles
 *
 */
abstract class APIBase {
	
	//Trait Containing Insert, Update, FilteredResults, etc.
	use DBO;
	
	/**
	 * Original Create Date
	 * @var DateTime
	 */
	private $created_at;
	
	const STATUS_ACTIVE = 1;
	const STATUS_INACTIVE = 0;
	
	public function __construct() {
		$this->db = DB::load();
		
		if(empty($this->table)) throw new Exception('No table provided in '. __CLASS__ .' definition.');
	}
	
	protected abstract function getRow();
	
	protected abstract function initFromRow($row);
	
	public function getMap();
	
	protected abstract function save();
	
	/**
	 * Accept one param and if param exists, return the mapped value.
	 * @param string $key - the param being asked for.
	 * @return string - the value being asked for.
	 */
	protected function map($key) {
		$map = $this->getMap();
		return $map[$key];
	}
	
	/**
	 * Get the created on date.
	 * @return string - date row was created.
	 */
	public function getCreateDate() {
		return date('m/d/Y h:i:sa', strtotime($this->created_at));
	}
	
	/**
	 * Necessary to auto output classes as JSON data for
	 * Rest API and Client Side needs. Requires that a map be set.
	 * @return mixed - class variable mappings as json.
	 */
	public function jsonSerialize() {
		return $this->getMap();
	}
}