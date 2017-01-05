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
	const REMOTE_KEY = 'remote';
	
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
		
		if (!isset($extras[self::REMOTE_KEY])) {
			$this->getIO()->write('<info>No remptes configured, skipping ...</info>');
			return;
		}
		
		$remotesConfig = $extras[self::REMOTE_KEY];
		
		$foundTarget=false;
		foreach ($targets as $target) {
			$name=$target['name'];
			if ($name!=$deployTargetName) {
				continue;
			}
			$this->getIO()->write(sprintf('<info>Deploying to target %s</info>',$name));
			$remotes=$target['remote'];
			if (isset($target['branch'])) {
				$branch = $target['branch'];
			} else {
				$branch = 'master';
			}
			foreach ($remotes as $remote) {
				if (self::getStage()=='dev') {
					// (re)setup remote
					foreach ($remotesConfig as $remoteConfig) {
						if ($remoteConfig['name']==$remote) {
							$color=$remoteConfig['color'];
							$stage=$remoteConfig['stage'];
							$command = sprintf('bricks-deploy setup -r "%s" --color %s --stage %s',$remote,$color,$stage);
							$this->getIO()->write(sprintf('<comment>Executing command %s</comment>',$command));
							shell_exec($command);
						}
					}
				}
				// deploy to remote
				$this->getIO()->write(sprintf('<comment>Deploying branch %s to remote %s</comment>',$branch,$remote));
				shell_exec(sprintf('git push %s HEAD:%s',$remote, $branch));
			}
			$foundTarget=true;
			break;
		}
		
		if (!$foundTarget) {
			$this->getIO()->write(sprintf('<error>No target [%s] defined in composer.json</error>',$name));
		}
		
	}

}
