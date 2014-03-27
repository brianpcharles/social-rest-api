<?php
/**
 * Create a DB Wrapper using
 * the MYSQLi functionality.
 * 
 * This allows for rows, rowlists, injection scrubbing
 * 
 * @author bcharles
 *
 */
class DB {
	
	/**
	 * MysqlI Instance.
	 * @var MySQLi
	 */
	protected $dbObject;
	
	/**
	 * Current Open Connection Name
	 * @var string
	 */
	protected static $con;
	
	/**
	 * Static DB Resource
	 */
	protected static $db;
	
	/**
	 * DB Class Instance Constructor
	 * Provide the db name to use in connecting.
	 * 
	 * Don't call this manually, use static function load()
	 * 
	 * @param string $dbServer (optional)
	 * @param string $dbUser (optional)
	 * @param string $dbPass (optional)
	 * @param string $dbName (optional)
	 */
	public function __construct($dbServer=null, $dbUser=null, $dbPass=null, $dbName=null) {
		//if this is a named or a new connection
		//start it.
		
		if(!empty($dbName)) {
			$this->dbObject = mysqli_init();
			mysqli_options($dbObject, MYSQLI_OPT_CONNECT_TIMEOUT, 100);
			$this->dbObject = self::setDBConnection($dbServer, $dbUser, $dbPass, $dbName, $dbObject);
		}
	}
	
	/**
	 * Check the current connection and set a new one if needed.
	 * @param string $dbServer (optional)
	 * @param string $dbUser (optional)
	 * @param string $dbPass (optional)
	 * @param string $dbName (optional)
	 * @param unknown $dbObject
	 */
	public static function setDBConnection($dbServer=null, $dbUser=null, $dbPass=null, $dbName=null, $dbObject=null) {
		
		//if the current connection is different the provided db name, switch it up.
		//Use config class to set anything not set by the args.
		if(self::$con !== $dbName) {
			//set the db server if not sepcified.
			$dbServer = empty($dbServer) ? Config::DBSERVER : $dbServer;
			//set the db user if not specified.
			$dbUser = empty($dbUser) ? Config::DBUSER : $dbUser;
			//set the db password if not specified.
			$dbPass = empty($dbPass) ? Config::DBPASS : $dbPass;
			//set the db name if not specified.
			$dbName = empty($dbName) ? Config::DBNAME : $dbName;
			//now connect to the new connection.
			return self::connect($dbServer, $dbUser, $dbPass, $dbName, $dbObject);
		}
	}
	
	/**
	 * Accept, reset and connect to a server/db
	 * @param string $dbServer
	 * @param string $dbUser
	 * @param string $dbPass
	 * @param string $dbName
	 * @param string $dbName
	 * @return MySQLi Resource Object
	 */
	private static function connect($dbServer=null, $dbUser=null, $dbPass=null, $dbName=null, $dbObject=null) {
		//if this is a new connection, establish the host server instance.
		if(!empty($dbObject)) {
			//connect
			$dbObject->real_connect($dbServer, $dbUser, $dbPass, $dbName);
			//return mysqli resource object
			return $dbObject;
		}
		
		//do a make a connection.
		self::$db = mysqli_init();
		self::$db->real_connect($dbServer, $dbUser, $dbPass, $dbName);
		//set the connection name for future reference.
		self::$con = $dbName;
	}
	
	/**
	 * Detect the current resource and either get a new one, 
	 * or return a DB instance using the existing thread id.
	 * 
	 * @param string $dbName (optional)
	 * @param bool $newInstance - whether to load this is a new sideloaded db or not.
	 * @return DB
	 */
	public static function load($dbName=null, $newInstance=false) {
		//this is a new instance.  Sideload it instead of using the static.
		if($newInstance) {
			$db = new DB(null, null, null, $dbName);
			return $db;
		}
	
		self::setDBConnection($dbName);
	
		$db = new DB();
		return $db;
	}
	
	/**
	 * Return the current db name or false if empty.
	 * @return boolean|string
	 */
	public function getCurrentConnection() {
		if(!empty(self::$conn)) return self::$conn;
		
		return false;
	}
	
	/**
	 * Get the current static db resource or false.
	 * @return MySQLi|boolean
	 */
	public function getDBResource() {
		if(!empty(self::$db)) return self::$db;
		
		return false;
	}
	
	/**
	 * Get the non-static db resource.
	 * @return MySQLi
	 */
	public function getDBObject() {
		//get the current resource.
		$db = self::getDBResource();
		if(isset($this->dbObject)) {
			$db = $this->dbObject;
		}
		//oh no, no connection.  get a new one silly.
		if(empty($db) && !empty(self::$con)) {
			self::setDBConnection(self::$con);
			return $this->getDBObject();
		}
		return $db;
	}
	
	/**
	 * Return the most recent mysqli error
	 * @return string
	 */
	public function getError() {
		if($this->dbObject) return $this->dbObject->error;
		return self::$db->error;
	}
	
	/**
	 * Return the most recent db error number
	 * @return number
	 */
	public function getErrorNo() {
		if($this->dbObject) return $this->dbObject->errno;
		return self::$db->errno;
	}
	
	/**
	 * Format the error for storing in log.
	 * @param string $lineNumber
	 * @param string $errorNumber
	 * @param string $errorString
	 * @return string
	 */
	public function getFormatError($lineNumber, $errorNumber, $errorString) {
		return "MYSQL Error " . $_SERVER['PHP_SELF']." on line {$lineNumber}: #{$errorNumber} {$errorString}";
	}
	
	/**
	 * Save the error in an error log.
	 * return boolean
	 */
	public function saveError($lineNumber) {
		$error = $this->getFormatError($lineNumber, $this->getErrorNo(), $this->getError());
		error_log($error);
		
		return true;
	}
	
	/**
	 * Fetches a single row based on $strSQL if there are no errors, or FALSE otherwise.
	 * @param string $query SQL query to execute
	 * @return array $row db result row
	 */
	public function fetchRow($query) {
		//get a db instance.
		$db = $this->getDBObject();
		try
		{
			$db->real_query($query);
			if($db->error || $db->errno) {
				throw new Exception ($this->getFormatError(__LINE__, $db->errorno, $db->error));
			}
			$results = $db->store_result();
			//get the row of data from the result.
			if($row = $results->fetch_array() ) {
				$results->free();
				return $row;
			}
		}
		catch(Exception $e) {
			$error = $this->getFormatError($e->getLine(), $e->getCode(), $e->getMessage());
			error_log($error);
		}
		return false;
	}
	
	/**
	 * Fetches an array of rows based on $strSQL if there are no errors, or FALSE otherwise.
	 * @param string $query
	 * @return array $rowList
	 */
	public function fetchRowList($query) {
		//get a db instance.
		$db = $this->getDBObject();
		//start an empty container.
		$rowList = array();
		
		try
		{
			//query and throw appropriate errors.
			$db->real_query($query);
			if($db->error || $db->errno) {
				throw new Exception ($this->formatError(__LINE__, $db->errorno, $db->error));
			}
			$results = $db->store_result();
			//if there are rows of results, loop through them and create the
			//rowList to return.
			while($row = $results->fetch_array() ) {
				$rowList[] = $row;
			}
			
			$results->free();
			return $rowList;
		}
		catch(Exception $e) {
			$error = $this->getFormatError($e->getLine(), $e->getCode(), $e->getMessage());
			error_log($error);
		}
		return false;
	}
	
	/**
	 * Save data without needing any back.
	 * @param string $query
	 * @throws Exception
	 * @return boolean
	 */
	public function writeQuery($query) {
		//get a db instance.
		$db = $this->getDBObject();
		
		try
		{
			//query and throw appropriate errors.
			$db->real_query($query);
			if($db->error || $db->errno) {
				throw new Exception ($this->formatError(__LINE__, $db->errorno, $db->error));
			}
			return true;
		}
		catch(Exception $e) {
			$error = $this->getFormatError($e->getLine(), $e->getCode(), $e->getMessage());
			error_log($error);
		}
		return false;
	}
	
	/**
	 * Return the last inserted row id.
	 * @return integer
	 */
	public function getID() {
		$db = $this->getDBObject();
		return $db->insert_id;
	}
	
	/**
	 * get the affected row count from the most recent sql query
	 * @return integer
	 */
	public function rowsAffected() {
		$db = $this->getDBObject();
		return $db->affected_rows;
	}
	
	/**
	 * Scrub and Clean the input string.
	 * @param string $query
	 * @param boolean $allownull
	 */
	public function escapeString($query, $allownull = false) {
		
		$db = $this->getDBObject();	
		if(empty($query)) {
			$query = $allownull ? 'NULL' : '';
		}
		else {
			$query = $this->scrub($query);
			$query = $db->real_escape_string($query);
		}
		//return the quoted and cleaned string.
		return '"' . $query . '"';
	}
	
	/**
	 * Injection prevention
	 * @param string $query
	 */
	private function scrub($query) {
		return preg_replace('/(SELECT *|DELETE FROM|)/', '', $query);
	}
	
	/**
	 * Get the primary key field name.
	 * @param string $table - table name.
	 * @return string|boolean - return the key or false if empty
	 */
	function getPrimaryKey($table) {
		$query = "SHOW INDEX FROM $table WHERE Key_name = 'PRIMARY'";
		if($result = $this->fetchRow($query)) {
			return $result['Column_name'];
		}
		return FALSE;
	
	}
	
	/**
	 * Get a list of the fields matching the table provided
	 * @param string $table
	 * @return mixed mysql field information
	 */
	public function getTableColumns($table=null) {
		if(empty($table)) return array();
		//get the actual mysqli object
		$db = $this->getDBObject();
		//select a single row from the table.
		$query = "SELECT * FROM {$table} LIMIT 1";
		//fetch only the colun names
		if($result = $db->query($query)) {
			return $result->fetch_fields();
		}
		return array();
	}
	
	/**
	 * Get the database name of the current connection instance.
	 * @return string db name
	 */
	function getDBName() {
		//return the static if that's this.
		if(self::$con) return self::$con;
	
		$query = "SELECT database() as dbName";
		if($result = $this->fetchRow($query)) {
			return $result[0];
		}
		return FALSE;
	}
}