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
class ReloadHandler extends AbstractHandler
{
	
	const SERVICE_KEY = 'service';
	
	public function reload(Event $event) {
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
		
		if (!isset($extras[self::SERVICE_KEY])) {
			$this->getIO()->write('<info>No services configured, skipping ...</info>');
			return;
		}
		
		$services = $extras[self::SERVICE_KEY];
		
		$this->getIO()->write('<info>Reloading services</info>');
		
		$stage = self::getStage();
		foreach ($services as $service) {
			$name = $service['name'];
			$stages = $service['stage'];
			$sudo = false;
			if (isset($service['sudo'])) {
				$sudo = $service['sudo'];
			}
			if (!in_array($stage,$stages)) {
				$this->getIO()->write(sprintf('<comment>Skipping %s as stage does not match</comment>',$name));
				continue;
			}
			if (isset($service['script'])) {
				$script = $service['script'];
			} else {
				$script = 'service '.$name.' reload';
			}
			if ($sudo) {
				$script = 'sudo '.$script;
			}
			$this->getIO()->write(sprintf('<info>Reloading %s</info>',$name));
			shell_exec($script);
		}
		
		$this->getIO()->write('<info>Reloaded services.</info>');
		
	}

}
