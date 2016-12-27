<?php


namespace BricksPlatformComposerExtras\Handler;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use BricksPlatformComposerExtras\Processor\ProcessorInterface;

/**
 * Class DeployHandler
 *
 * @package BricksPlatformComposerExtras
 */
class DeployHandler extends AbstractHandler
{
	
	const TARGETS_KEY = 'target';
	
	public function deploy(Event $event) {
		$this->io = $event->getIO();
		
		$arguments = $event->getArguments();
		
		if (!(count($arguments)==1)) {
			throw new \InvalidArgumentException('You have to specify a deployment target as the single argument');
		}
		
		$deployTargetName = $arguments[0];
		
		$this->loadTarget();
		
		$extras = $event->getComposer()->getPackage()->getExtra();
		
		if (!isset($extras[self::EXTRAS_KEY])) {
			throw new \InvalidArgumentException(sprintf('Bricks setup must be configured using the extra.%s setting.', self::EXTRAS_KEY));
		}
		
		$extras = $extras[self::EXTRAS_KEY];
		
		if (!is_array($extras)) {
			throw new \InvalidArgumentException(sprintf('The extra.%s setting must be an array or a configuration object.', self::EXTRAS_KEY));
		}
		
		if (!isset($extras[self::TARGETS_KEY])) {
			$this->getIO()->write('<info>No targets configured, skipping ...</info>');
			return;
		}
		
		$targets = $extras[self::TARGETS_KEY];
		
		$foundTarget=false;
		foreach ($targets as $target) {
			$name=$target['name'];
			if ($name!=$deployTargetName) {
				continue;
			}
			$this->getIO()->write(sprintf('<info>Deploying to target %s</info>',$name));
			$remotes=$target['remote'];
			foreach ($remotes as $remote) {
				$this->getIO()->write(sprintf('<info>Deploying to remote %s</info>',$remote));
				shell_exec(sprintf('git push %s',$remote));
			}
			$foundTarget=true;
			break;
		}
		
		if (!$foundTarget) {
			$this->getIO()->write(sprintf('<error>No target [%s] defined in composer.json</error>',$name));
		}
		
	}

}
