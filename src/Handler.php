<?php


namespace BricksPlatformComposerExtras;
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
class Handler
{
	
	
	const EXTRAS_KEY = 'bricks-platform';
	const CONFIG_KEY = 'config';
	const SERVICE_KEY = 'service';
	
	/** @var IOInterface; */
	protected $io;

    /** @var ProcessorInterface[] */
    protected $processors = [];

    public function install(Event $event)
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
	    
        foreach ($configs as $config) {
            $type = isset($config['type']) ? $config['type'] : 'dist';
	        $class = __NAMESPACE__ . '\\Processor\\' . ucfirst($type);
            /** @var ProcessorInterface $processor */
            $processor = $this->getProcessorForClass($class, $event->getIO());
            $processor->process($config);
        }
    }
    
	public function reload(Event $event) {
		$this->io = $event->getIO();
		

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
		
		$stage = Handler::getStage();
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
    
    
    
    protected function setupTarget(Event $event) {
    	$io = $this->getIO();

	    $filename = 'etc/stage';
	    if (is_file($filename)) {
		    $update = $io->ask('Update stage setting (y,[n])?', false);
	    } else {
		    $update = true;
	    }
	    if ($update) {
	    	$default=Handler::getEnv('BRICKS_STAGE','dev');
		    $value = $io->ask(sprintf('Please enter the stage of your installation [%s] ', $default), $default);
		    file_put_contents($filename,$value);
		    $this->getIO()->write(sprintf('<info>Writing %s to the "%s" file</info>', $value, $filename));
	    }
	    $value=file_get_contents($filename);
	    Handler::putEnv('BRICKS_STAGE',$value);

    	$filename = 'etc/color';
	    if (is_file($filename)) {
		    $update = $io->ask('Update color setting (y,[n])?', false);
	    } else {
		    $update = true;
	    }
	    if ($update) {
		    $default=Handler::getEnv('BRICKS_COLOR','generic');
		    $value = $io->ask(sprintf('Please enter the color of your installation [%s] ',$default), $default);
		    file_put_contents($filename,$value);
		    $this->getIO()->write(sprintf('<info>Writing %s to the "%s" file</info>', $value, $filename));
	    }
	    $value=file_get_contents($filename);
	    Handler::putEnv('BRICKS_COLOR',$value);
	
	    $this->getIO()->write(sprintf('<info>Target was set/detected as %s</info>',Handler::getTarget()));
    }
    
    // helpers
	
	/** @return IOInterface\ */
	protected function getIO() {
		return $this->io;
	}
	
	public static function getEnv($varname,$default=FALSE) {
		$value = getenv($varname);
		if ($value === FALSE) {
			return $default;
		}
		return $value;
	}

	public static function putEnv($varname,$value) {
		putenv($varname.'='.$value);
	}
	
	public static function getTarget() {
		return Handler::getColor().'_'.Handler::getStage();
	}
	
	public static function getStage() {
		return Handler::getEnv('BRICKS_STAGE');
	}
	
	public static function getColor() {
		return Handler::getEnv('BRICKS_COLOR');
	}

}
