<?php


namespace BricksPlatformComposerExtras\Handler;

use Composer\IO\IOInterface;
use Composer\Script\Event;

use BricksPlatformComposerExtras\Processor\ProcessorInterface;

/**
 * Class ReloadHandler
 *
 * @package BricksPlatformComposerExtras
 */
class RemoteConsoleHandler extends AbstractHandler
{
	
	const REMOTE_KEY = 'remote';
	
	public function console(Event $event) {
		$this->io = $event->getIO();
		
		$arguments = $event->getArguments();
		
		if (!(count($arguments)>=1)) {
			throw new \InvalidArgumentException('You have to specify a remote as the first argument');
		}
		
		$name = array_shift($arguments);
		
		if (count($arguments)==0) {
			$consoleArgs = "";
		} else {
			$consoleArgs = implode(' ',$arguments);
		}
		
		$remote = $this->findOneRemoteByName($event,$name);
		
		$url = $remote['url'];
		
		$commandLine = sprintf('ssh -p %d %s@%s \'cd %s; bin/console %s\'',
			parse_url($url,PHP_URL_PORT),
			parse_url($url,PHP_URL_USER),
			parse_url($url,PHP_URL_HOST),
			parse_url($url,PHP_URL_PATH),
			addslashes($consoleArgs)
		);
		
		$this->io->write(sprintf("<info>Executing %s</info>",$commandLine));
		
		$output = shell_exec($commandLine);
		
		$this->io->write($output);
	}
	
	/**
	 * @param Event $event
	 * @param $name
	 */
	protected function findOneRemoteByName(Event $event, $name) {
		$extras = $event->getComposer()->getPackage()->getExtra();
		
		if (!isset($extras[self::EXTRAS_KEY])) {
			throw new \InvalidArgumentException(sprintf('Bricks setup must be configured using the extra.%s setting.', self::EXTRAS_KEY));
		}
		
		$extras = $extras[self::EXTRAS_KEY];
		
		if (!is_array($extras)) {
			throw new \InvalidArgumentException(sprintf('The extra.%s setting must be an array or a configuration object.', self::EXTRAS_KEY));
		}
		
		if (!isset($extras[self::REMOTE_KEY])) {
			$this->getIO()->write('<info>No remotes configured, exiting ...</info>');
			exit;
		}
		
		$remotes = $extras[self::REMOTE_KEY];
		
		foreach ($remotes as $remote) {
			$remoteName = $remote['name'];
			if ($remoteName == $name) {
				return $remote;
			}
		}
		
		$this->getIO()->write('<info>Remote not configured, exiting ...</info>');
		exit;
		
	}

}
