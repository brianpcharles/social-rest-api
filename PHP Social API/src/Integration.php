<?php

/**
 * Base class for all integrations.
 * Handles DB Values, etc.
 * 
 * The posting, pulling of each integration is handled in
 * its child.
 * 
 * @author bcharles
 *
 */
class Integration extends APIBase {
	
	//include the REST API calls.
	use REST;
	
	protected $table = 'integrations';
	
	/**
	 * Integration UID
	 * @var number
	 */
	protected $integrationID = 0;
	
	/**
	 * User Object Instance
	 * @var User
	 */
	private $user;
	
	/**
	 * Integration Type
	 *  see: self::getTypeList()
	 * @var number
	 */
	private $type;
	
	/**
	 * The unique username to display.
	 *   - Defaults to UID if none exists.
	 * @var string
	 */
	private $username;
	
	/**
	 * Social Account ID (facebook id, or twitter id, etc).
	 * @var number
	 */
	private $socialID = 0;
	
	/**
	 * Link to profile page
	 * @var string
	 */
	private $link;
	
	/**
	 * Link to avatar image (null by default)
	 * @var string
	 */
	private $avatar;
	
	/**
	 * OAuth 2 Token
	 * @var string
	 */
	private $access_token;
	
	/**
	 * Oauth2 Secret
	 * @var string
	 */
	private $access_secret;
	
	/**
	 * Token Expiration (time())
	 * @var number
	 */
	private $expires_in;
	
	/**
	 * Last Auth Date
	 * @var DateTime
	 */
	private $auth_at;
	
	/**
	 * Integration Status
	 * @var number
	 */
	private $status = 0;
	
	/**
	 * Load one-time list of types of integrations.
	 */
	private static $types = array();
	
	/**
	 * Construct the integration object
	 *   Load the integration if the table id exists.
	 * @param number integration id.
	 * @param number user id.
	 */
	public function __construct($integrationID = 0, $userID = 0) {
		parent::__construct();
		//load or use the array of integration types.
		self::setTypes();
		
		if(!empty($userID)) $this->user = new User($userID);
		
		if(!empty($integrationID) )  {
			$this->integrationID = (int)$integrationID;
			$this->initFromRow($this->getRow());
		}
	}
	
	/**
	 * Get the table row corresponding to this integration UID.
	 * @return mixed|boolean - array from db result or false.
	 */
	protected function getRow() {
		if(empty($this->integrationID)) return false;
		
		$query = "SELECT integration_id, user_id, type, username, social_id,
					link, avatar, access_token, access_secret, expires_in, 
					auth_at, status, created_at FROM {$this->table} 
				WHERE integration_id = " . (int)$this->integrationID . " LIMIT 1";
		
		return $this->db->fetchRow($query);
	}
	
	/**
	 * Use the table row to set each of the values for this instance 
	 */
	protected function initFromRow($row) {
		if(empty($row)) return false;
		
		if(!empty($row['user_id'])) {
			$this->user = new User((int)$row['user_id']);
		}
		$this->type = (int)$row['type'];
		$this->username = $row['username'];
		$this->link = $row['link'];
		$this->avatar = $row['avatar'];
		$this->access_token = $row['access_token'];
		$this->access_secret = $row['access_secret'];
		$this->expires_in = $row['expires_in'];		
		$this->auth_at = new DateTime($row['auth_at']);
		$this->status = (int)$row['status'];
		
		if(!empty($this->expires_in) && $this->status == self::STATUS_ACTIVE) { 
			$last_auth = $this->auth_at->getTimestamp();
			$next_auth = $this->expires_in + $last_auth; 
			if($next_auth > time()) {
				$this->status = self::STATUS_INACTIVE;
			}
		}
	}
	
	/**
	 * Get the list of field - value matches to save.
	 * @return array
	 */
	public function getMap() {
		return array(
					'integration_id' => (int)$this->integrationID,
					'user_id' => (int)$this->user->getUserID(),
					'type' => (int)$this->type,
					'username' => $this->username,
					'social_id' => (int)$this->socialID,
					'link' => $this->link,
					'avatar' => $this->avatar,
					'status' => (int)$this->status
				);
	}
	
	/**
	 * Insert or Update new integrations.
	 * @throws Exception
	 * @return boolean - success or failure.
	 */
	public function save() {
		//Determine whether to insert new or update
		try {
			if(empty($this->integrationID)) { //empty integration id means insert new
				$this->integrationID = $this->insert();
			}
			else { //otherwise, update existing.
				$this->update();
			}
		}
		catch(Exception $e) {
			throw $e;
		}
		return true;
	}
	
	/**
	 * 
	 * Seperate save function from the
	 * standard because of the getMap().
	 * <p>
	 * Basically the current save function uses a public mapping
	 * to expose the table columns and values.  The Json Serializor
	 * also uses the values to return data to a potential rest server (coming soon).
	 * As a result, this function is seperate so as to keep the auth secret.
	 * </p>
	 * 
	 * @param $data - array of oauth data used to validate a user.
	 * @return boolean - success or failure.
	 */
	public function saveOAuthValues($data) {
		
		try {
			//first validate that the integration id exists.  If it doesn't, go get one!
			if(empty($this->integrationID)) $this->save();
			
			if(!empty($data['access_token'])) $this->access_token = $data['access_token'];
			if(!empty($data['access_secret'])) $this->access_secret = $data['access_secret'];
			if(!empty($data['expires_in'])) $this->expires_in = $data['expires_in'];
			$this->auth_at = new DateTime("now");
			
			//use the values set (or not set) above to save oauth data
			$query = "UPDATE {$this->table} SET " . 
						 "access_token = " . $this->db->formatDBString($this->access_token, true) . ", " . 
						 "access_secret = " . $this->db->formatDBString($this->access_secret, true) . ", " . 
						 "expires_in = " . $this->db->formatDBString($this->expires_in, true) . ", " . 
						 "auth_at = " . $this->formatDBString( $this->auth_at->format('Y-m-d H:i:s') ) . "
					 WHERE integration_id = " . (int)$this->integrationID . " LIMIT 1";
			return $this->db->rwosAffected();
		}
		catch(Exception $e) {
			throw $e;
		}
	}
	
	/**
	 * Load the integration types available.
	 * @param boolean $reload - Whether to reload the array or use the existing.
	 */
	public static function setTypes($reload=false) {
		//if reload is off and there is already a list, use the existing.
		if(!empty(self::$types) && !$reload) return false;
		//otherwise, load a static db and get the list as it sits in the table.
		$db = DB::load();
		$query = "SELECT integration_type_id, integration_type_name FROM integration_type";
		
		$results = $db->fetchRowList();
		if(!empty($results)) {
			foreach($results as $row) {
				$type[$row[0]] = $row[1];
			}
		}
	}
	
	/**
	 * Get the current list of possible integration types.
	 * @return mixed - array of key value integration types.
	 */
	public static function getTypes() {
		if(empty(self::$types)) self::setTypes(); 
	}
	
	/**
	 * Build the redirect uri when none has been provided
	 * Always takes protocol into consideration.
	 * @return string
	 */
	public static function buildRedirectURI() {
		$url = 'http';
		if(!empty($_SERVER['HTTPS'])) {
			if(in_array($_SERVER['HTTPS'], array('1', 'On'))) {
				$url .= 's';
			}
		}
		$url .= '://' . $_SERVER['HTTP_HOST'] . '/' . $_SERVER['PHP_SELF'];
	
		return $url;
	}

}