<?php

class Integration extends APIBase {
	
	use REST;
	
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
	private $socialID;
	
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
	private $status;
	
	/**
	 * Integration Original Create Date
	 * @var DateTime
	 */
	private $created_at;
	
}