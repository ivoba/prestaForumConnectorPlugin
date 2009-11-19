<?php
/**
 * prestaPhpBB3Connector is the forum connector for PhpBB3
 * @author ylybliamay
 *
 */
class prestaPhpBB3Connector extends prestaAbstractForumConnector
{
	public $db;
	public $dbprefix;
	public $phpbb_root_path;
	
	/**
	 * Get the database connection from the phpBB3 forum
	 * @author	ylybliamay
	 * @version	1.0 - 2009-10-26 - ylybliamay
	 * @since	1.0 - 2009-10-26 - ylybliamay
	 */
	public function setup()
	{
		global $db, $table_prefix, $phpbb_root_path, $phpEx;
		
		// define default values
		$this->params	= array_merge( array(
			'forumFieldProjectUserId'		=> 'project_user_id',
		), $this->params );
		
		
		// *************
		// *** Init phpBB3
		// *************
		
		if( !defined( 'IN_PHPBB' ) )
		{
			define('IN_PHPBB', true);
		}
		$this->phpbb_root_path = sfConfig::get( 'app_prestaForumConnector_forumWebDir' );
		$phpbb_root_path = $this->phpbb_root_path;
		$phpEx = substr(strrchr(__FILE__, '.'), 1);
		require_once $this->phpbb_root_path.'common.php';

		$this->db		= $db;
		$this->dbprefix	= $table_prefix;
		
		// *************
	}

	/**
	 * Sign in
	 * @var		$projectUserId
	 * @author	ylybliamay
	 * @version	1.0 - 2009-10-26 - ylybliamay
	 * @since	1.0 - 2009-10-26 - ylybliamay
	 * @return	boolean
	 */
	public function signIn($projectUserId, $sessionId = false)
	{
		if(!$sessionId)
		{
			$sessionId = md5(uniqid('something'.rand(), true));
		}
		$sessionKey 		= '';
		$phpbbCookieName	= self::getConfigVal('cookie_name');
		
		$this->synchUser( $projectUserId );
		
		$user_id 			= $this->getForumUserIdFromProjectUserId($projectUserId);
		$this->insertDbSession($sessionId, $sessionKey, $user_id);
		$this->setCookies($phpbbCookieName, $sessionId, $sessionKey, $user_id);
		$this->updateUserFields($user_id, array('lastvisit' => time()));
	}

	/**
	 * Sign out
	 * @author	ylybliamay
	 * @version	1.0 - 2009-10-26 - ylybliamay
	 * @since	1.0 - 2009-10-26 - ylybliamay
	 */
	public function signOut($projectUserId)
	{
		$phpbbCookieName 	= self::getConfigVal('cookie_name');
		$user_id 			= $this->getForumUserIdFromProjectUserId($projectUserId);

		$this->updateUserFields($user_id, array('lastvisit' => time()));
		$this->deleteDbSession($user_id);
		$this->unsetCookies($phpbbCookieName);
	}

	/**
	 * @author	ylybliamay
	 * @version	1.0 - 2009-10-27 - ylybliamay
	 * @since	1.0 - 2009-10-27 - ylybliamay
	 */
	public function enableForumUser($projectUserId)
	{
		$forum_user_id = $this->getForumUserIdFromProjectUserId($projectUserId);
		$sql	= "UPDATE `". $this->dbprefix ."users`"
				. " SET `user_type` = 0, `user_inactive_reason` = 0 "
				. " WHERE `user_id` = ".$forum_user_id;
		$this->sqlExec($sql);
	}

	/**
	 * @author	ylybliamay
	 * @version	1.0 - 2009-10-27 - ylybliamay
	 * @since	1.0 - 2009-10-27 - ylybliamay
	 */
	public function disableForumUser($projectUserId)
	{
		$forum_user_id = $this->getForumUserIdFromProjectUserId($projectUserId);
		$sql	= "UPDATE `". $this->dbprefix ."users`"
				. " SET `user_type` = 1, `user_inactive_reason` = 3 "
				. " WHERE `user_id` = ".$forum_user_id;
		$this->sqlExec($sql);
	}
	
	/**
	 * @author	ylybliamay
	 * @version	1.0 - 2009-10-29 - ylybliamay
	 * @since	1.0 - 2009-10-29 - ylybliamay
	 */
	public function deleteForumUser($projectUserId)
	{
		$this->disableForumUser($projectUserId);
		$sql	= "UPDATE ".$this->dbprefix."profile_fields_data "
				. "SET pf_".$this->params['forumFieldProjectUserId']." = NULL "
				. "WHERE pf_".$this->params['forumFieldProjectUserId']." = ".$projectUserId;
		$this->sqlExec($sql);
	}

	/**
	 * @author	ylybliamay
	 * @version	1.0 - 2009-10-26 - ylybliamay
	 * @since	1.0 - 2009-10-26 - ylybliamay
	 * @return	Return the projectUserId or false
	 */
	public function getProjectUserIdFromForumUserId($forumUserId)
	{
		$sql	= "SELECT pf_".$this->params['forumFieldProjectUserId']." FROM `". $this->dbprefix ."profile_fields_data` "
				. " WHERE user_id = ".$forumUserId;
		$result = $this->sqlExec($sql);
		$ar 	= $this->db->sql_fetchrow($result);
		if(is_array($ar) && array_key_exists('pf_project_user_id',$ar))
		{
			return $ar['pf_project_user_id'];
		}
		return false;
	}

	/**
	 * @author	ylybliamay
	 * @version	1.0 - 2009-10-26 - ylybliamay
	 * @since	1.0 - 2009-10-26 - ylybliamay
	 * @return	Return the forumUserId or false
	 */
	public function getForumUserIdFromProjectUserId($projectUserId)
	{
		$sql	= "SELECT user_id FROM `". $this->dbprefix ."profile_fields_data` "
				. " WHERE pf_".$this->params['forumFieldProjectUserId']." = ".$projectUserId;
		$result = $this->sqlExec($sql);
		$ar 	= $this->db->sql_fetchrow($result);
		if(is_array($ar) && array_key_exists('user_id',$ar))
		{
			return $ar['user_id'];
		}
		return false;
	}

	/**
	 * @author	ylybliamay
	 * @version	1.0 - 2009-10-26 - ylybliamay
	 * @since	1.0 - 2009-10-26 - ylybliamay
	 * @return	Return the nickname or false
	 */
	public function getUserNickName($projectUserId)
	{
		$sql	= "SELECT username FROM `". $this->dbprefix ."users` u,"
				. "`". $this->dbprefix ."profile_fields_data` d"
				. " WHERE d.pf_".$this->params['forumFieldProjectUserId']." = ".$projectUserId
				. " AND u.user_id = d.user_id";
		$result = $this->sqlExec($sql);
		$ar 	= $this->db->sql_fetchrow($result);
		if(is_array($ar) && array_key_exists('username',$ar))
		{
			return $ar['username'];
		}
		return false;
	}

	/**
	 * Check if the project user already exist
	 * @var		$projectUserId
	 * @author	ylybliamay
	 * @version	1.0 - 2009-10-26 - ylybliamay
	 * @since	1.0 - 2009-10-26 - ylybliamay
	 * @return	boolean
	 */
	protected function projectUserExist($projectUserId)
	{
		$sql	= "SELECT user_id FROM `". $this->dbprefix ."profile_fields_data`"
				. " WHERE pf_".$this->params['forumFieldProjectUserId']." = ".$projectUserId;
		$result = $this->sqlExec($sql);
		$exist 	= mysql_num_rows($result);
		if($exist)
		{
			return true;
		}
		return false;
	}

	/**
	 * Synchronize project user and forum user
	 * @param 	$projectUserId
	 * @return 	boolean
	 * @author	ylybliamay
	 * @version	1.0 - 2009-10-26 - ylybliamay
	 * @since	1.0 - 2009-10-26 - ylybliamay
	 */
	public function synchUser($projectUserId)
	{
		if(!$this->projectUserExist($projectUserId))
		{
			$this->createUser($projectUserId);
		}
		else
		{
			$this->updateUser($projectUserId);
		}
	}

	/**
	 * @author	ylybliamay
	 * @version	1.0 - 2009-10-26 - ylybliamay
	 * @since	1.0 - 2009-10-26 - ylybliamay
	 */
	protected function createUser($projectUserId, $group_id = 2)
	{
		$nickname	= $this->convertToForumNickName(prestaForumFactory::getUserConnectorInstance()->getUserNickName($projectUserId),$projectUserId);
		$remote_ip	= sfContext::getInstance()->getRequest()->getHttpHeader('addr', 'remote');
		
		$userCulture	= prestaForumFactory::getUserConnectorInstance()->getUserCulture($projectUserId);
		$userCulture	= empty( $userCulture ) ? sfConfig::get('sf_default_culture') : substr( $userCulture, 0, 2 );

		$sql	= "INSERT INTO `". $this->dbprefix ."users` "
				. " (group_id,user_permissions,user_sig,user_occ,user_interests,user_ip,user_regdate,username,username_clean,user_email,user_lang)"
				. " VALUES (".$group_id.",'','','','','".$remote_ip."',".time().",'".$nickname."','".$nickname."','".prestaForumFactory::getUserConnectorInstance()->getUserEmail($projectUserId)."','". $userCulture ."')";
		$this->sqlExec($sql);

		$user_id = $this->db->sql_nextid();

		$sql	= "INSERT INTO `". $this->dbprefix ."user_group` "
				. " (group_id,user_id,user_pending)"
				. " VALUES (".$group_id.",".$user_id.",0)";
		$this->sqlExec($sql);

		$sql	= "INSERT INTO `". $this->dbprefix ."profile_fields_data` "
				. " (user_id,pf_".$this->params['forumFieldProjectUserId'].")"
				. " VALUES (".$user_id.",".$projectUserId.")";
		$this->sqlExec($sql);

		$this->setConfigVal('newest_user_id', $user_id, true);
		$this->setConfigVal('newest_username', $nickname, true);
		$this->setConfigCount('num_users', 1, true);

		$sql = 'SELECT group_colour
				FROM ' . $this->dbprefix . 'groups
				WHERE group_id = ' . (int) $group_id;
		
		$result = $this->sqlExec($sql);
		$row = $this->db->sql_fetchrow($result);
		if(array_key_exists(0,$row))
		{
			$this->setConfigVal('newest_user_colour', $row[0]['group_colour'], true);
		}
		
		if(!prestaForumFactory::getUserConnectorInstance()->isUserEnabled($projectUserId))
		{
			$this->disableForumUser($projectUserId);
		}
	}

	/**
	 * @author	ylybliamay
	 * @version	1.0 - 2009-10-27 - ylybliamay
	 * @since	1.0 - 2009-10-27 - ylybliamay
	 */
	protected function updateUser($projectUserId)
	{
		$forum_user_id	= $this->getForumUserIdFromProjectUserId($projectUserId);
		$nickname 		= $this->convertToForumNickName(prestaForumFactory::getUserConnectorInstance()->getUserNickName($projectUserId),$projectUserId ,$forum_user_id);
		$email			= prestaForumFactory::getUserConnectorInstance()->getUserEmail($projectUserId);
		
		$userCulture	= prestaForumFactory::getUserConnectorInstance()->getUserCulture($projectUserId);
		$userCulture	= empty( $userCulture ) ? sfConfig::get('sf_default_culture') : substr( $userCulture, 0, 2 );

		$sql	= "UPDATE `". $this->dbprefix ."users` "
				. "SET ";

		if(prestaForumFactory::getUserConnectorInstance()->getUserNickName($projectUserId) != $nickname || $nickname != $this->getUserNickName($projectUserId))
		{
			$sql .= "`username` = '". $nickname ."', `username_clean` = '".$nickname."', ";
		}
		$sql	.= "`user_email` = '". $email ."', `user_lang`='". $userCulture ."'"
				. "WHERE `user_id` = ". $forum_user_id;
		$this->sqlExec($sql);

		$enabled = prestaForumFactory::getUserConnectorInstance()->isUserEnabled($projectUserId);
		if($enabled)
		{
			$this->enableForumUser($projectUserId);
		}
		else
		{
			$this->disableForumUser($projectUserId);
		}
	}

	/**
	 * @author	ylybliamay
	 * @version	1.0 - 2009-10-26 - ylybliamay
	 * @since	1.0 - 2009-10-26 - ylybliamay
	 */
	public function convertToForumNickName($projectNickName, $projectUserId, $forumUserId = 0)
	{
		// Convert the projectNickName if it's an address
		$projectNickName = $this->convertMailAddressToNickName($projectNickName);
		
		$min_length = $this->getConfigVal('min_name_chars');
		$max_length = $this->getConfigVal('max_name_chars');
		
		if( strlen( $projectNickName ) > $max_length )
		{
			$projectNickName	= substr( $projectNickName, 0, $max_length );	
		}
		if( strlen( $projectNickName ) < $min_length )
		{
			$projectNickName	= str_pad( $projectNickName, $min_length, "0" );	
		}
		
		$baseProjectNickname	= $projectNickName;

		$cpt	= 0;
		while( $this->nickNameAlreadyUse($projectNickName, $forumUserId) )
		{
			$cpt++;
			$projectNickName	= substr( $baseProjectNickname, 0, $max_length - strlen( $cpt ) ) . $cpt;
		}
		return $projectNickName;
	}

	/**
	 * Check if the nickname is already use
	 * @param	$nickname
	 * @return	boolean
	 * @author	ylybliamay
	 * @version	1.0 - 2009-10-26 - ylybliamay
	 * @since	1.0 - 2009-10-26 - ylybliamay
	 */
	protected function nickNameAlreadyUse($nickname, $forumUserId = 0)
	{
		$sql	= "SELECT username FROM `". $this->dbprefix ."users`"
				. " WHERE username = '". $nickname ."' AND `user_id` != ". $forumUserId;
		$result = $this->sqlExec($sql);
		$exist 	= mysql_num_rows($result);
		if($exist > 0)
		{
			return true;
		}
		return false;
	}

	/**
	 * Insert a user session
	 * @param 	$sessionId
	 * @param 	$sessionKey
	 * @param 	$user_id
	 * @author	ylybliamay
	 * @version	1.0 - 2009-10-26 - ylybliamay
	 * @since	1.0 - 2009-10-26 - ylybliamay
	 */
	protected function insertDbSession($sessionId, $sessionKey, $user_id)
	{
		$remote_ip = sfContext::getInstance()->getRequest()->getHttpHeader('addr', 'remote');
		$now = time();

		$sql	= "REPLACE INTO `". $this->dbprefix ."sessions_keys` (`key_id` ,`user_id` ,`last_ip` ,`last_login`)"
				. "VALUES ('$sessionKey', '$user_id', '$remote_ip', '$now');";
		$this->sqlExec($sql);
		
		$browser = '';
		if(is_array($_SERVER) && array_key_exists('HTTP_USER_AGENT', $_SERVER))
		{
			$browser = $_SERVER['HTTP_USER_AGENT'];
		}
			
		$sql	= "REPLACE INTO `". $this->dbprefix ."sessions` (`session_id` ,`session_user_id` ,`session_forum_id` ,"
				. "`session_last_visit` ,`session_start` ,`session_time` ,`session_ip` ,`session_browser` ,"
				. "`session_forwarded_for` ,`session_page` ,`session_viewonline` ,`session_autologin` ,"
				. "`session_admin`) VALUES ("
				. "'$sessionId', '$user_id', '0', '$now', '$now', '$now', '$remote_ip', '$browser', '', '', '1', '1', '0');";
		$this->sqlExec($sql);
	}

	/**
	 * Delete user's session(s) from phpBB's database
	 * @param integer $user_id
	 * @author	ylybliamay
	 * @version	1.0 - 2009-10-26 - ylybliamay
	 * @since	1.0 - 2009-10-26 - ylybliamay
	 */
	protected function deleteDbSession($user_id)
	{
		if (intval($user_id) < 1)
		{
			return false;
			# an extra check since DELETE without LIMIT is about to be performed
			#throw new Exception('PhpbbIntegration::deleteDbSession() got invalid user');
		}

		$sql = "DELETE FROM `". $this->dbprefix ."sessions_keys` WHERE `user_id`='$user_id'";
		$this->sqlExec($sql);

		$sql = "DELETE FROM `". $this->dbprefix ."sessions` WHERE `session_user_id`='$user_id'";
		$this->sqlExec($sql);
	}

	/**
	 * Set a field in the users table
	 * @param integer $user_id
	 * @param array of string $field_name => $field_value - without the user_ prefix, for example: email
	 * @param string $field_value- new field value
	 * @author	ylybliamay
	 * @version	1.0 - 2009-10-26 - ylybliamay
	 * @since	1.0 - 2009-10-26 - ylybliamay
	 */
	public function updateUserFields($user_id, $new_values)
	{
		if (empty($new_values) || !is_array($new_values))
		{
			throw new Exception('setUser() got invalid $new_values');
		}
			
		$sSqlExtra = '';

		foreach($new_values as $field_name => $field_value)
		{
			$sSqlExtra .= ",`user_$field_name`='$field_value'";
		}

		$sSqlExtra = substr($sSqlExtra, 1);
		$sql = "UPDATE ". $this->dbprefix ."users SET $sSqlExtra WHERE `user_id`='$user_id' LIMIT 1";
		$this->sqlExec($sql);
	}

	/**
	 * Set client cookies to allow auto-login to phpBB
	 * @param	$phpbbCookieName
	 * @param 	$sessionId
	 * @param 	$sessionKey
	 * @param 	$user_id
	 * @author	ylybliamay
	 * @version	1.0 - 2009-10-26 - ylybliamay
	 * @since	1.0 - 2009-10-26 - ylybliamay
	 */
	protected function setCookies($phpbbCookieName, $sessionId, $sessionKey, $user_id)
	{
		$domain 	= $this->getConfigVal('cookie_domain');
		$expiry 	= time() + 1209600; # two weeks should be ample
		setcookie($phpbbCookieName.'_k', $sessionKey, $expiry, '/', $domain);
		setcookie($phpbbCookieName.'_u', $user_id, $expiry, '/', $domain);
		setcookie($phpbbCookieName.'_sid', $sessionId, $expiry, '/', $domain);
	}

	/**
	 * Unset client cookies
	 * @param string $phpbbCookieName
	 * @author	ylybliamay
	 * @version	1.0 - 2009-10-26 - ylybliamay
	 * @since	1.0 - 2009-10-26 - ylybliamay
	 */
	protected function unsetCookies($phpbbCookieName)
	{
		$domain = self::getConfigVal('cookie_domain');
		$expiry = time() - 86400; # set to expired yesterday
		setcookie($phpbbCookieName.'_k', '', $expiry, '/', $domain);
		setcookie($phpbbCookieName.'_u', 0, $expiry, '/', $domain);
		setcookie($phpbbCookieName.'_sid', '', $expiry, '/', $domain);
	}

	/**
	 * Get a configuration value from phpBB's database
	 * @param	$name - value name
	 * @return 	string
	 * @author	ylybliamay
	 * @version	1.0 - 2009-10-26 - ylybliamay
	 * @since	1.0 - 2009-10-26 - ylybliamay
	 */
	protected function getConfigVal($name)
	{
		$fixedConfig	= function_exists('getConfigEnvironment') ? getConfigEnvironment() : array();
		$result	= null;
		if( array_key_exists( $name, $fixedConfig ) )
		{
			$result		= $fixedConfig[ $name ];
		}
		else
		{
			$sql 		= "SELECT `config_value` FROM `". $this->dbprefix ."config` WHERE `config_name` LIKE '$name'";
			$result 	= $this->sqlExec($sql);
			$ar 		= $this->db->sql_fetchrow($result);
			$result		= $ar['config_value'];
		}
		
		return $result;
	}

	/**
	 * Set a configuration value from phpBB's database
	 * @param 	$name
	 * @param 	$value
	 * @author	ylybliamay
	 * @version	1.0 - 2009-10-27 - ylybliamay
	 * @since	1.0 - 2009-10-27 - ylybliamay
	 * @return 	boolean
	 */
	protected function setConfigVal($name, $value)
	{
		$sql	= "UPDATE `".$this->dbprefix."config` "
				. "SET`config_value` = '". $value ."' "
				. "WHERE `config_name` = '". $name ."'";
		$this->sqlExec($sql);
	}
	
	/**
	 * Increment a counter for config value
	 * @param $config_name
	 * @param $increment
	 * @param $is_dynamic
	 * @return unknown_type
	 */
	protected function setConfigCount($config_name, $increment, $is_dynamic = false)
	{
		$sql = 'config_value + ' . (int) $increment;
		$this->sqlExec('UPDATE `' . $this->dbprefix . 'config` SET `config_value` = ' . $sql . " WHERE `config_name` = '" . $config_name . "'");
	}

	/**
	 * Execute sql query
	 * @param 	$sql - SQL Query
	 * @returns array
	 * @author	ylybliamay
	 * @version	1.0 - 2009-10-26 - ylybliamay
	 * @since	1.0 - 2009-10-26 - ylybliamay
	 */
	protected function sqlExec($sql)
	{
		return $this->db->sql_query($sql);
	}
	
	/**
	 * Clear forum cache
	 * 
	 * @author	Christophe Dolivet <cdolivet@prestaconcept.net>
	 * @version	1.0 - 6 nov. 2009 - Christophe Dolivet <cdolivet@prestaconcept.net>
	 * @since	6 nov. 2009 - Christophe Dolivet <cdolivet@prestaconcept.net>
	 * @see prestaForumConnectorPlugin/lib/class/forumConnector/prestaAbstractForumConnector#clearCache()
	 * 
	 * @return Boolean
	 */
	public function clearCache()
	{
		foreach( glob( $this->phpbb_root_path.'cache/*.php') as $filepath )
		{
			unlink( $filepath );
		}
		
		return true;
	}
	
	/**
	 * Promote a user as a forum admin
	 * 
	 * @author	Christophe Dolivet <cdolivet@prestaconcept.net>
	 * @version	1.0 - 6 nov. 2009 - Christophe Dolivet <cdolivet@prestaconcept.net>
	 * @since	6 nov. 2009 - Christophe Dolivet <cdolivet@prestaconcept.net>
	 * @see prestaForumConnectorPlugin/lib/class/forumConnector/prestaAbstractForumConnector#promoteUserAsAdmin($projectUserId)
	 */
	public function promoteUserAsAdmin( sfBaseTask $sfTask, $projectUserId )
	{
		$succeed		= false;
		
		$password		= $sfTask->ask( "Please enter user's password" );
		
		$isFoundator	= $sfTask->askConfirmation( "Is this the foundator user ? (Y/N)", 'QUESTION', false );
		
		// ensute the user is synched
		$this->synchUser( $projectUserId );
		$forumUserId	= $this->getForumUserIdFromProjectUserId( $projectUserId );
		
		$result = $this->sqlExec( "SELECT group_id FROM `". $this->dbprefix ."groups` WHERE group_name = 'ADMINISTRATORS'" );
		$ar 	= $this->db->sql_fetchrow($result);
		if( is_array($ar) && array_key_exists('group_id', $ar ) )
		{
			$groupId		= $ar['group_id'];
			$newUserType	= $isFoundator ? ", user_type='3' " : "";
			// update password and define user as fondator
			if( $this->sqlExec( "UPDATE `". $this->dbprefix ."users` SET group_id = '". $groupId ."' ". $newUserType .", user_password='". phpbb_hash( $password ) ."' WHERE user_id = '". $forumUserId ."'") )
			{
				// is the user is the new foundator, be sure that nobody else is the foundator (phpBB doesn't like to have multiple foundator)
				if( $isFoundator )
				{
					$this->sqlExec( "UPDATE `". $this->dbprefix ."users` SET user_type=0 WHERE user_type = 3 AND user_id != '". $forumUserId ."'" );
				}
				$result = $this->sqlExec( "SELECT * FROM `". $this->dbprefix ."user_group` WHERE group_id = '". $groupId ."' AND user_id = '". $forumUserId ."'" );
				$ar 	= $this->db->sql_fetchrow($result);
				if( !empty( $ar ) )
				{
					$succeed	= true;
				}
				elseif( $this->sqlExec( "INSERT IGNORE INTO `". $this->dbprefix ."user_group` (group_id, user_id, group_leader, user_pending) VALUES ('". $groupId ."','". $forumUserId ."',0,0)") )
				{
					$succeed	= true;
				}
			}
		}
		
		// clear forum cache
		$this->clearCache();
		
		return $succeed;
	}
	

	
	/**
	 * @author	ylybliamay
	 * @version	1.0 - 2009-10-29 - ylybliamay
	 * @since	1.0	- 2009-10-29 - ylybliamay
	 */
	public function forumUserIsEnabled($forumUserId)
	{
		$sql 	= 'SELECT user_id FROM '.$this->dbprefix.'users WHERE user_type = 0 AND user_inactive_reason = 0 AND user_id = '.$forumUserId;
		$result	= $this->sqlExec($sql);
		$exist 	= mysql_num_rows($result);
		if($exist)
		{
			return true;
		}
		return false;
	}
	
	/**
	 * @author	ylybliamay
	 * @version	1.0 - 2009-10-29 - ylybliamay
	 * @since	1.0	- 2009-10-29 - ylybliamay
	 */
	public function isSignedIn($forumUserId)
	{
		$sql 	= 'SELECT user_id FROM '.$this->dbprefix.'sessions_keys WHERE user_id = '.$forumUserId;
		$result	= $this->sqlExec($sql);
		$existSessionsKeys 	= mysql_num_rows($result);
		
		$sql 	= 'SELECT session_user_id FROM '.$this->dbprefix.'sessions WHERE session_user_id = '.$forumUserId;
		$result	= $this->sqlExec($sql);
		$existSessions 	= mysql_num_rows($result);
		
		if($existSessionsKeys && $existSessions)
		{
			return true;
		}
		return false;
	}
}