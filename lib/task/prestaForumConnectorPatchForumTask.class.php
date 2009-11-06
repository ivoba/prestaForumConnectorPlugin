<?php
/**
 * Synchronize user task allow to synchronize project user and forum user
 * @author ylybliamay
 *
 */
class prestaForumConnectorPatchForumTask extends sfBaseTask
{
	protected function configure()
	{
		$this->namespace	= 'prestaForumConnectorPlugin';
		$this->name			= 'patchForum';
		$this->briefDescription		= 'Patch forum database and file in order to activate correct setup';
		$this->detailedDescription	= 'Patch forum database and file in order to activate correct setup';
		
		$this->addArgument('application', sfCommandArgument::REQUIRED, 'Application');
   		$this->addOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'Environnement' );
	}
	
	/**
	 * It uses the forum connector method for synchronize user
	 * @author	ylybliamay
	 * @version	1.0 - 2009-10-30 - ylybliamay
	 * @since	1.0 - 2009-10-30 - ylybliamay
	 */
	protected function execute($arguments = array(), $options = array())
	{
		$start_time	= microtime( true );
		$this->logSection( 'patchForum', 'Start '.date('Y-m-d H:i:s') );

		// launch patch process
		prestaForumFactory::getForumConnectorInstance()->patchForum( $this );
		
		$this->logSection( 'patchForum', 'End '.date('Y-m-d H:i:s') .' ( Total: '. round( ( microtime(true) - $start_time ), 2) .' s )' );
	}
}