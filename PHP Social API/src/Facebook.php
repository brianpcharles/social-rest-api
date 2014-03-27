<?php

class Facebook extends Integration {
	
	/**
	 * Facebook Public App ID.
	 * @var string
	 */
	protected $appID;
	
	/**
	 * Facebook Private App Secret
	 * @var string
	 */
	protected $appSecret;
	
	protected static $graphURL = 'https://graph.facebook.com/';
	
	protected static $dialogURL = 'https://www.facebook.com/dialog/oauth/';
	
	protected static $oauthURL = 'oauth/access_token';
	
	/**
	 * State
	 * 	Usage: Make sure the request that comes back
	 * 	matches the request that went out.  In a 1-to-1 model
	 * 	this is pretty typical anyway.
	 * 
	 * Appended to intial auth call.
	 * @var string
	 */
	protected static $state = 'bR1@N';
	
	/**
	 * The permissions needed to market to facebook on a profrssional level
	 * Creating events, publishing to pages, updating page data, accessing photos
	 * 
	 * Appended to initial auth call.
	 * @var string
	 */
	protected static $scope = "user_status,create_event,publish_stream,read_stream,manage_pages,
								photo_upload,user_photos,read_insights";
	
	
	protected static $fields = "id,username,name,picture,likes,location,
									phone,about,category,link,hours";
	
	/**
	 * Type of integration.
	 * @var number
	 */
	const SOCIAL_TYPE_ID = 1; //table row id, integration types.
	
	/**
	 * Set up the parent, if possible using the integration id and user.
	 * 
	 * @param number $integrationID
	 * @param number $userID
	 */
	public function __construct($integrationID=0, $userID=0) {
		parent::__construct($integrationID, $userID);
		
		$this->appID = Config::FACEBOOK_APP_ID;
		$this->appSecret = Config::FACEBOOK_APP_SECRET;
	}
	
	public function activate($code = null) {
		
	}
	
	public function post(Message $message) {
		
	}
	
	private function photo($params) {
		
	}
	
	private function status($params) {
		
	}
	
	private function event($params) { 
		
	}
	
	private function batch() {
		
	}
	
	private function tab() {
		
	}
	
	private function page() {
		
	}
	
	private function subscribe() {
		
	}
}
