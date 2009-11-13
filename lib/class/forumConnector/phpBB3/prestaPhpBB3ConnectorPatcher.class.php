<?php
/**
 * prestaPhpBB3Connector is the forum connector for PhpBB3
 * @author ylybliamay
 *
 */
class prestaPhpBB3ConnectorPatcher extends prestaPhpBB3Connector implements prestaForumConnectorPatcherInterface
{
	

	/**
	 * This method allow us to patch the forum in order to install the prerequist
	 * for the plugin uses
	 * @author	ylybliamay
	 * @version	1.0 - 2009-10-27 - ylybliamay
	 * @since	1.0 - 2009-10-27 - ylybliamay
	 */
	public function patchForum( sfBaseTask $sfTask )
	{
		// Set general config
		$this->patchGeneralConfig( $sfTask );
		
		// Add the custom data in order to create a link between project and forum database
		$this->patchAddCustomField( $sfTask );
		// Disable user profile edition (can't change email or password)
		$this->patchDisableUserProfileEdition( $sfTask );
		
		$this->patchDisableRegistration( $sfTask );
		
		// Delete links and form for log in from the forum
		$this->patchDisableLogin( $sfTask );
		
		$sfTask->logSection( "Clear file cache", null, null, $this->clearCache() ? 'INFO' : 'ERROR' );
	}
	
	/**
	 * Add a custom field
	 * @author	ylybliamay
	 * @version	1.0 - 2009-10-30 - ylybliamay
	 * @since	1.0 - 2009-10-30 - ylybliamay
	 * @return 	boolean
	 */
	public function patchAddCustomField( sfBaseTask $sfTask )
	{
		$field	= $this->params['forumFieldProjectUserId'];
		
		// Check if this field already exist
		$sql = "SELECT `field_id` FROM `".$this->dbprefix."profile_fields` WHERE `field_name` = '". $field ."'";
		$result = $this->sqlExec($sql);
		$exist	= mysql_num_rows($result);
		if(!$exist)
		{
			$sql = "INSERT INTO `".$this->dbprefix."profile_fields` VALUES( NULL, '". $field ."', 1, '". $field ."', '10', '0', '0', '0', '0', '', 0, 0, 0, 1, 1, 1, 1)";
			$succeed	= $this->sqlExec($sql);
		}
		$sfTask->logSection( 'Database', 'Add custom field - part 1', null, $exist || $succeed ? 'INFO' : 'ERROR' );
		
		// Check if the field already create in the profile_fields_data table
		$sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '". $this->dbprefix ."profile_fields_data' AND column_name = 'pf_". $field ."'";
		$result = $this->sqlExec($sql);
		$exist	= mysql_num_rows($result);
		if(!$exist)
		{
			$sql = "ALTER TABLE `".$this->dbprefix."profile_fields_data` ADD `pf_". $field ."` bigint(20)";
			$succeed	= $this->sqlExec($sql);
		}
		$sfTask->logSection( 'Database', 'Add custom field - part 2', null, $exist || $succeed ? 'INFO' : 'ERROR' );
	}
	
	/**
	 * Deactivate the forum registration
	 * @author	ylybliamay
	 * @version	1.0 - 2009-10-30 - ylybliamay
	 * @since	1.0 - 2009-10-30 - ylybliamay
	 */
	protected function patchDisableRegistration( sfBaseTask $sfTask )
	{
		$sql = "UPDATE `". $this->dbprefix ."config` SET `config_value` = 3 WHERE `config_name` = 'require_activation'";
		$sfTask->logSection( 'Database', 'Disable registration', null, $this->sqlExec($sql) ? 'SUCCEED' : 'FAILURE' );
	}
	
	/**
	 * 
	 * 
	 * @author	Christophe Dolivet <cdolivet@prestaconcept.net>
	 * @version	1.0 - 6 nov. 2009 - Christophe Dolivet <cdolivet@prestaconcept.net>
	 * @since	6 nov. 2009 - Christophe Dolivet <cdolivet@prestaconcept.net>
	 * @param	sfBaseTask $sfTask
	 */
	protected function patchDisableUserProfileEdition( sfBaseTask $sfTask )
	{
		$sql = "UPDATE `". $this->dbprefix ."modules` SET `module_enabled` = '0' WHERE `module_langname` = 'UCP_PROFILE_REG_DETAILS' LIMIT 1 ;";
		$sfTask->logSection( 'Database', 'Disable user profile edition', null, $this->sqlExec($sql) ? 'SUCCEED' : 'FAILURE' );
	}
	
	/**
	 * Deactivate login in the forum
	 * @author	ylybliamay
	 * @version	1.0 - 2009-10-30 - ylybliamay
	 * @since	1.0 - 2009-10-30 - ylybliamay
	 * @return	boolean
	 */
	protected function patchDisableLogin( sfBaseTask $sfTask )
	{
		
		// *************
		// *** Disable logout link
		// *************
		
		$this->searchAndReplace(
			'<li class="icon-logout"><a href="{U_LOGIN_LOGOUT}" title="{L_LOGIN_LOGOUT}" accesskey="l">{L_LOGIN_LOGOUT}</a></li>',
			'<!-- IF S_USER_LOGGED_IN  --><li class="icon-logout">{L_LOGIN_LOGOUT}</li><!-- ENDIF -->',
			$this->phpbb_root_path.'styles/prosilver/template/overall_header.html', $sfTask );
		
		// *************
		
			
		// *************
		// *** Disable login form
		// *************
		
		$search	= <<<EOF
<!-- IF not S_USER_LOGGED_IN and not S_IS_BOT -->

		<form action="{S_LOGIN_ACTION}" method="post">

		<div class="panel">
			<div class="inner"><span class="corners-top"><span></span></span>

			<div class="content">
				<h3><a href="{U_LOGIN_LOGOUT}">{L_LOGIN_LOGOUT}</a><!-- IF S_REGISTER_ENABLED -->&nbsp; &bull; &nbsp;<a href="{U_REGISTER}">{L_REGISTER}</a><!-- ENDIF --></h3>

				<fieldset class="fields1">
				<dl>
					<dt><label for="username">{L_USERNAME}:</label></dt>
					<dd><input type="text" tabindex="1" name="username" id="username" size="25" value="{USERNAME}" class="inputbox autowidth" /></dd>
				</dl>
				<dl>
					<dt><label for="password">{L_PASSWORD}:</label></dt>
					<dd><input type="password" tabindex="2" id="password" name="password" size="25" class="inputbox autowidth" /></dd>
					<!-- IF S_AUTOLOGIN_ENABLED --><dd><label for="autologin"><input type="checkbox" name="autologin" id="autologin" tabindex="3" /> {L_LOG_ME_IN}</label></dd><!-- ENDIF -->
					<dd><label for="viewonline"><input type="checkbox" name="viewonline" id="viewonline" tabindex="4" /> {L_HIDE_ME}</label></dd>
				</dl>
				<dl>
					<dt>&nbsp;</dt>
					<dd><input type="submit" name="login" tabindex="5" value="{L_LOGIN}" class="button1" /></dd>
				</dl>
				</fieldset>
			</div>

			<span class="corners-bottom"><span></span></span></div>
		</div>

		</form>

	<!-- ENDIF -->
EOF;
		$this->searchAndReplace( $search, '<!-- /* form removed */ -->', $this->phpbb_root_path.'styles/prosilver/template/viewforum_body.html', $sfTask );
		
		// *************
		
		
		// *************
		// *** disable login form
		// *************
		
		$search	= <<<EOF
<!-- IF not S_USER_LOGGED_IN and not S_IS_BOT -->
	<form method="post" action="{S_LOGIN_ACTION}" class="headerspace">
	<h3><a href="{U_LOGIN_LOGOUT}">{L_LOGIN_LOGOUT}</a><!-- IF S_REGISTER_ENABLED -->&nbsp; &bull; &nbsp;<a href="{U_REGISTER}">{L_REGISTER}</a><!-- ENDIF --></h3>
		<fieldset class="quick-login">
			<label for="username">{L_USERNAME}:</label>&nbsp;<input type="text" name="username" id="username" size="10" class="inputbox" title="{L_USERNAME}" />  
			<label for="password">{L_PASSWORD}:</label>&nbsp;<input type="password" name="password" id="password" size="10" class="inputbox" title="{L_PASSWORD}" />
			<!-- IF S_AUTOLOGIN_ENABLED -->
				| <label for="autologin">{L_LOG_ME_IN} <input type="checkbox" name="autologin" id="autologin" /></label>
			<!-- ENDIF -->
			<input type="submit" name="login" value="{L_LOGIN}" class="button2" />
		</fieldset>
	</form>
<!-- ENDIF -->
EOF;
		$this->searchAndReplace( $search, '<!-- /* form removed */ -->', $this->phpbb_root_path.'styles/prosilver/template/index_body.html', $sfTask );
		
		// *************
		
		
		// *************
		// *** disable login body page
		// *************
		
		$search	= <<<EOF
		<fieldset <!-- IF not S_CONFIRM_CODE -->class="fields1"<!-- ELSE -->class="fields2"<!-- ENDIF -->>
		<!-- IF LOGIN_ERROR --><div class="error">{LOGIN_ERROR}</div><!-- ENDIF -->
		<dl>
			<dt><label for="{USERNAME_CREDENTIAL}">{L_USERNAME}:</label></dt>
			<dd><input type="text" tabindex="1" name="{USERNAME_CREDENTIAL}" id="{USERNAME_CREDENTIAL}" size="25" value="{USERNAME}" class="inputbox autowidth" /></dd>
		</dl>
		<dl>
			<dt><label for="{PASSWORD_CREDENTIAL}">{L_PASSWORD}:</label></dt>
			<dd><input type="password" tabindex="2" id="{PASSWORD_CREDENTIAL}" name="{PASSWORD_CREDENTIAL}" size="25" class="inputbox autowidth" /></dd>
			<!-- IF S_DISPLAY_FULL_LOGIN and (U_SEND_PASSWORD or U_RESEND_ACTIVATION) -->
				<!-- IF U_SEND_PASSWORD --><dd><a href="{U_SEND_PASSWORD}">{L_FORGOT_PASS}</a></dd><!-- ENDIF -->
				<!-- IF U_RESEND_ACTIVATION --><dd><a href="{U_RESEND_ACTIVATION}">{L_RESEND_ACTIVATION}</a></dd><!-- ENDIF -->
			<!-- ENDIF -->
		</dl>
		
		<!-- IF S_CONFIRM_CODE -->
		<dl>
			<dt><label for="confirm_code">{L_CONFIRM_CODE}:</label><br /><span>{L_CONFIRM_CODE_EXPLAIN}</span></dt>
				<dd><input type="hidden" name="confirm_id" value="{CONFIRM_ID}" />{CONFIRM_IMAGE}</dd>
				<dd><input type="text" name="confirm_code" id="confirm_code" size="8" maxlength="8" tabindex="3" class="inputbox narrow" title="{L_CONFIRM_CODE}" /></dd>
		</dl>
		<!-- ENDIF -->
		
		<!-- IF S_DISPLAY_FULL_LOGIN -->
		<dl>
			<!-- IF S_AUTOLOGIN_ENABLED --><dd><label for="autologin"><input type="checkbox" name="autologin" id="autologin" tabindex="4" /> {L_LOG_ME_IN}</label></dd><!-- ENDIF -->
			<dd><label for="viewonline"><input type="checkbox" name="viewonline" id="viewonline" tabindex="5" /> {L_HIDE_ME}</label></dd>
		</dl>
		<!-- ENDIF -->
		<dl>
			<dt>&nbsp;</dt>
			<dd>{S_HIDDEN_FIELDS}<input type="submit" name="login" tabindex="6" value="{L_LOGIN}" class="button1" /></dd>
		</dl>
	
		</fieldset>
EOF;
		$this->searchAndReplace( $search, '<!-- /* form removed */ -->', $this->phpbb_root_path.'styles/prosilver/template/login_body.html', $sfTask );
		
		// *************		
		
		
		// *************
		// *** disable login and logout actions
		// *************
		
		$search	= <<<EOF
// Basic "global" modes
switch (\$mode)
{
EOF;
		$replace	= <<<EOF
// login and logout are disabled
if( \$mode == 'login' || \$mode == 'logout')
{
	die;
}

// Fixed Basic "global" modes
switch(\$mode)
{
EOF;
		$this->searchAndReplace( $search, $replace, $this->phpbb_root_path.'ucp.php', $sfTask );
		
		// *************
	
		// *************
		// *** synch session with website one
		// *************
		
		$search		= <<<EOF
		// Is session_id is set or session_id is set and matches the url param if required
		if (!empty(\$this->session_id) && (!defined('NEED_SID') || (isset(\$_GET['sid']) && \$this->session_id === \$_GET['sid'])))
		{
			\$sql = 'SELECT u.*, s.*
				FROM ' . SESSIONS_TABLE . ' s, ' . USERS_TABLE . " u
				WHERE s.session_id = '" . \$db->sql_escape(\$this->session_id) . "'
					AND u.user_id = s.session_user_id";
			\$result = \$db->sql_query(\$sql);
			\$this->data = \$db->sql_fetchrow(\$result);
			\$db->sql_freeresult(\$result);

			// Did the session exist in the DB?
EOF;

		$replace	= <<<EOF
		if( class_exists( 'sfConfig' ) )
		{
			\$projectUserId	= sfConfig::get('projectUserId');
		}
		
		// Is session_id is set or session_id is set and matches the url param if required
		if ( ( !empty(\$this->session_id) || !empty(\$projectUserId) ) && (!defined('NEED_SID') || (isset(\$_GET['sid']) && \$this->session_id === \$_GET['sid'])))
		{
			\$sql = 'SELECT u.*, s.*
				FROM ' . SESSIONS_TABLE . ' s, ' . USERS_TABLE . " u
				WHERE s.session_id = '" . \$db->sql_escape(\$this->session_id) . "'
					AND u.user_id = s.session_user_id";
			\$result = \$db->sql_query(\$sql);
			\$this->data = \$db->sql_fetchrow(\$result);
			\$db->sql_freeresult(\$result);
			
			if( class_exists( 'sfConfig' ) )
			{
				\$projectUserId	= sfConfig::get('projectUserId');
				\$forumUserId	= is_array( \$this->data ) && array_key_exists('user_id',\$this->data) ? \$this->data['user_id'] : 1;
				if(!empty(\$projectUserId) && ( empty(\$forumUserId) || \$forumUserId == 1 ) )
				{
					prestaForumFactory::getForumConnectorInstance()->signIn( \$projectUserId );
					header('Location: '.\$_SERVER['REQUEST_URI']);die;
				}
				else if(empty(\$projectUserId) && ( !empty(\$forumUserId) && \$forumUserId != 1 ) )
				{
					prestaForumFactory::getForumConnectorInstance()->signOut( prestaForumFactory::getForumConnectorInstance()->getProjectUserIdFromForumUserId( \$forumUserId ) );
					header('Location: '.\$_SERVER['REQUEST_URI']);die;
				}
			}
			
			// Did the session exist in the DB?
EOF;
		$this->searchAndReplace( $search, $replace, $this->phpbb_root_path.'includes/session.php', $sfTask );
		
		// ************* 
	}
	
	/**
	 * Set the general config for the forum
	 * @author	ylybliamay
	 * @version	1.0 - 2009-10-30 - ylybliamay
	 * @since	1.0 - 2009-10-30 - ylybliamay
	 */
	protected function patchGeneralConfig( sfBaseTask $sfTask )
	{
		$search		= null;
		$replace	= <<<EOF
<?php
/**
 * Set PHPBB3 config value according to the environment
 * @author	ylybliamay
 * @return	array
 */
function getConfigEnvironment()
{
	\$result['server_name']	= array_key_exists('HTTP_HOST',\$_SERVER)	? \$_SERVER['HTTP_HOST'] : '';
	\$result['script_path']	= array_key_exists('PHP_SELF',\$_SERVER) 	? substr(\$_SERVER['PHP_SELF'],0,strpos(\$_SERVER['PHP_SELF'],'phpBB3') + 6) : '';
	\$result['cookie_domain']= array_key_exists('HTTP_HOST',\$_SERVER) 	? \$_SERVER['HTTP_HOST'] : '';
	return \$result;
}

/**
 * In order to get Symfony sf_user, whe should get the sf_user from the instance.
 * But according to the referer application (symfony or forum), you should create or only get the instance.
 */

@define('SYMFONY_FORUM', true);

require dirname(__FILE__).'/../index.php';

\$instanceCreated	= false;

if( !sfContext::hasInstance() )
{
	\$instanceCreated	= true;
	\$instance			= sfContext::createInstance(\$configuration);
	// notify a special event for possible customization
	\$instance->getEventDispatcher()->notify( new sfEvent( \$instance, 'prestaForumConnector.initContextInstanceFromForum' ) );
}

\$sf_user	= sfContext::getInstance()->getUser();
\$sf_user_id =  method_exists( \$sf_user, 'getUserId' ) ? \$sf_user->getUserId() : 0;
if(\$sf_user_id > 0)
{
	sfConfig::set('projectUserId', \$sf_user_id );
}
if( \$instanceCreated )
{
	\$instance->shutdown();
}

\$databaseManager	= new sfDatabaseManager( \$configuration );
\$sfPropelDatabase 	= \$databaseManager->getDatabase( sfConfig::get('app_prestaForumConnector_forumDatabaseId' ) );

\$dsn = \$sfPropelDatabase->getParameter('dsn');
\$dsn = explode(':',\$dsn);
// phpBB 3.0.x auto-generated configuration file
// Do not change anything in this file!
\$dbms	= \$dsn[0];
\$dsn	= explode(';',\$dsn[1]);
\$dsn_dbname	= explode('=',\$dsn[0]);
\$dsn_dbhost	= explode('=',\$dsn[1]);

\$dbhost 			= \$dsn_dbhost[1];
\$dbport 			= '';
\$dbname 			= \$dsn_dbname[1];
\$dbuser 			= \$sfPropelDatabase->getParameter('username');
\$dbpasswd 			= \$sfPropelDatabase->getParameter('password');
\$table_prefix 		= '$this->dbprefix';
\$acm_type 			= 'file';
\$load_extensions	= '';

@define('PHPBB_INSTALLED', true);
		
EOF;
		$this->searchAndReplace( $search, $replace, $this->phpbb_root_path.'config.php', $sfTask );
		
		// *************
		// *** acm_file.php
		// *************
		
		$search	= <<<EOF
		if (\$fp = @fopen(\$this->cache_dir . 'data_global.' . \$phpEx, 'wb'))
		{
			@flock(\$fp, LOCK_EX);
EOF;
		$replace	= <<<EOF
		if (\$fp = @fopen(\$this->cache_dir . 'data_global.' . \$phpEx, 'wb'))
		{
			\$this->vars = array_merge(\$this->vars,getConfigEnvironment());
			@flock(\$fp, LOCK_EX);
EOF;
		$this->searchAndReplace( $search, $replace, $this->phpbb_root_path.'includes/acm/acm_file.php', $sfTask );
		
		// *************


		// *************
		// *** cache.php
		// *************
		
		$search		= <<<EOF
		}

		return \$config;
EOF;
		$replace	= <<<EOF
		}
		
		\$config = array_merge(\$config,getConfigEnvironment());

		return \$config;
EOF;
		$this->searchAndReplace( $search, $replace, $this->phpbb_root_path.'includes/cache.php', $sfTask );
		
		// *************
	}
}