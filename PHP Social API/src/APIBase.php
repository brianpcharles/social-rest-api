<?php
/**
 * Abstract Class
 * Contains class definitions/requirements
 * 
 * @author bcharles
 *
 */
abstract class APIBase {
	
	use DBO;
	
	const STATUS_ACTIVE = 1;
	const STATUS_INACTIVE = 0;
	
	public function __construct() {
		$this->db = DB::get();
		
		if(empty($this->table)) throw new Exception('No table provided in '. __CLASS__ .' definition.');
	}
	
	protected abstract function getRow();
	
	protected abstract function initFromRow($row);
	
	protected abstract function save();
	
}