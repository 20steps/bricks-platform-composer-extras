<?php


namespace BricksPlatformComposerExtras\Handler;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use BricksPlatformComposerExtras\Processor\ProcessorInterface;

/**
 * Class Handler
 *
 * Loosely based on https://github.com/Incenteev/ParameterHandler/blob/master/ScriptHandler.php
 *
 * @package BricksPlatformComposerExtras
 */
class SetupHandler extends AbstractHandler
{
	const CONFIG_KEY = 'config';
	
    /** @var ProcessorInterface[] */
    protected $processors = [];

    public function setup(Event $event)
    {
    	$this->io = $event->getIO();
	
	    $this->setupTarget($event);
	    
	    $extras = $event->getComposer()->getPackage()->getExtra();
        
        if (!isset($extras[self::EXTRAS_KEY])) {
            throw new \InvalidArgumentException(sprintf('Bricks setup must be configured using the extra.%s setting.', self::EXTRAS_KEY));
        }

        $extras = $extras[self::EXTRAS_KEY];

        if (!is_array($extras)) {
            throw new \InvalidArgumentException(sprintf('The extra.%s setting must be an array or a configuration object.', self::EXTRAS_KEY));
        }
	    
	    if (!isset($extras[self::CONFIG_KEY])) {
		    throw new \InvalidArgumentException(sprintf('The %s key is missing in extra.%s.', self::CONFIG_KEY, self::EXTRAS_KEY));
	    }
	
	    $configs = $extras[self::CONFIG_KEY];
	
	    $stage = self::getStage();
        foreach ($configs as $config) {
        	if (isset($config['stage'])) {
		        $stages = $config['stage'];
		        if (!in_array($stage,$stages)) {
			        // $this->getIO()->write(sprintf('<comment>Skipping as stage does not match</comment>'));
			        continue;
		        }
	        }
            $type = isset($config['type']) ? $config['type'] : 'dist';
	        $class = 'BricksPlatformComposerExtras\\Processor\\' . ucfirst($type);
            /** @var ProcessorInterface $processor */
            $processor = $this->getProcessorForClass($class, $event->getIO());
            $processor->process($config,$event);
        }
    }
    
    // helpers
	
    protected function setupTarget(Event $event) {
    	$io = $this->getIO();

	    $filename = 'etc/stage';
	    if (is_file($filename)) {
		    $update = $io->ask('Update stage setting (y,[n])?', false);
	    } else {
		    $update = true;
	    }
	    if ($update) {
	    	$default=self::getEnv('BRICKS_STAGE','dev');
		    $value = $io->ask(sprintf('Please enter the stage of your installation [%s] ', $default), $default);
		    file_put_contents($filename,$value);
		    $this->getIO()->write(sprintf('<info>Writing %s to the "%s" file</info>', $value, $filename));
	    }

    	$filename = 'etc/color';
	    if (is_file($filename)) {
		    $update = $io->ask('Update color setting (y,[n])?', false);
	    } else {
		    $update = true;
	    }
	    if ($update) {
		    $default=self::getEnv('BRICKS_COLOR','generic');
		    $value = $io->ask(sprintf('Please enter the color of your installation [%s] ',$default), $default);
		    file_put_contents($filename,$value);
		    $this->getIO()->write(sprintf('<info>Writing %s to the "%s" file</info>', $value, $filename));
	    }
	    $this->loadTarget(false);
    }
	
	/**
	 * @param $type
	 * @param $io
	 * @return ProcessorInterface
	 */
	protected function getProcessorForClass($class, IOInterface $io) {
		if (!isset($this->processors[$class])) {
			if (!class_exists($class)) {
				throw new \InvalidArgumentException(sprintf('Could not find class %s. Please specify a valid class as the config file\'s "type" parameter.', $class));
			}
			$this->processors[$class] = new $class($io);
		}
		return $this->processors[$class];
	}
	
	
}
