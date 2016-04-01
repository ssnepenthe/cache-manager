<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks
{
    public function versionBump( $version ) {
		$this->taskReplaceInFile( __DIR__ . '/cache-manager.php' )
			->regex( '/Version:.*$/m' )
			->to( sprintf( 'Version: %s', $version ) )
			->run();
	}
}
