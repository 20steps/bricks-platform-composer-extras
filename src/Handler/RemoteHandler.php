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
class RemoteHandler extends AbstractHandler
{
	
	const REMOTE_KEY = 'remote';
	
	public function remotes(Event $event) {
		$this->io = $event->getIO();
		
		$this->loadTarget();
		
		$extras = $event->getComposer()->getPackage()->getExtra();
		
		if (!isset($extras[self::EXTRAS_KEY])) {
			throw new \InvalidArgumentException(sprintf('Bricks setup must be configured using the extra.%s setting.', self::EXTRAS_KEY));
		}
		
		$extras = $extras[self::EXTRAS_KEY];
		
		if (!is_array($extras)) {
			throw new \InvalidArgumentException(sprintf('The extra.%s setting must be an array or a configuration object.', self::EXTRAS_KEY));
		}
		
		if (!isset($extras[self::REMOTE_KEY])) {
			$this->getIO()->write('<info>No remptes configured, skipping ...</info>');
			return;
		}
		
		$remotes = $extras[self::REMOTE_KEY];
		
		$this->getIO()->write('<info>Updating remotes</info>');
		
		$stage = self::getStage();
		$color = self::getColor();
		foreach ($remotes as $remote) {
			$name = $remote['name'];
			if (array_key_exists('color',$remote)) {
				if (!is_string($remote['color'])) {
					$this->getIO()->write(sprintf('<error>Element color must be a string in the definition of a remote</error>'));
					die;
				}
				if ($remote['color']!=$color) {
					continue;
				}
			}
			$url = $remote['url'];
			$command = 'git remote rm '.$name.'; git remote add '.$name.' '.$url;
			$this->getIO()->write(sprintf('<comment>Executing %s </comment>',$command));
			shell_exec($command);
		}
		
		$this->getIO()->write('<info>Updated remotes.</info>');
		
	}

}
