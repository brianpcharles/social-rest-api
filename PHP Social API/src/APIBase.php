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
	
	/**
	 * Accepts raw data from a posted form (or any array)
	 * and applies any registered keys to thier class values.
	 * 
	 * 	Usage: $this->is_a_database_column = $data['isADatabaseColumn'];
	 * 
	 * @return void
	 */
	public function form($data = array()) {
		if(empty($data)) return;
	}
	
	/**
	 * Use a list of provided arguments or a data array to set
	 * the values for the keys.
	 * 
	 * 	Usage: $class->set(array('prop1' => $val1, 'prop2' => $val2))
	 * 	Usage: $class->set($prop1, $prop2)
	 */
	public function set() {
		
		$reflector = new ReflectionClass(__CLASS__);
		$parameters = $reflector->getMethod(__FUNCTION__)->getParameters();
		
		if(count($parameters) == 1 && is_array($parameters[0])) {
			$params = reset($parameters);
			foreach($params as $key=>$value) {
				if(property_exists(__CLASS__, $key)) {
					$this->$key = $value;
				}
			}
		}
		elseif(count($parameters) > 1) {
			foreach($parameters as $key => $value) {
				if(property_exists(__CLASS__, $key)) {
					$this->$key = $value;
				}
			}
		}
	}
	
	public abstract function getMap();
	
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
	 * 
	 * 	Usage: die(print_r(json_decode($object))) 
	 * 
	 * @return mixed - class variable mappings as json.
	 */
	public function jsonSerialize() {
		return $this->getMap();
	}
}