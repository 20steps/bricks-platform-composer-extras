<?php

namespace BricksPlatformComposerExtras\Processor;

use BricksPlatformComposerExtras\Handler;
use Composer\IO\IOInterface;
use Composer\Script\Event;

/**
 * Class Generic
 *
 * Loosely based in https://github.com/Incenteev/ParameterHandler/blob/master/Processor.php
 *
 * @package BricksPlatformComposerExtras
 */
class Copy implements ProcessorInterface
{

    protected $io;
    protected $config;

    public function __construct(IOInterface $io)
    {
        $this->setIO($io);
    }

    public function process(array $config, Event $event)
    {
        $this->setConfig($config);
        $config = $this->getConfig();

        $realFile = $config['file'];
        if (isset($config['sudo'])) {
        	$sudo = $config['sudo'];
        } else {
        	$sudo = false;
        }

		if (isset($config['mode'])) {
			$mode=$config['mode'];
		} else {
			$mode=null;
		}
		
        $exists = file_exists($realFile);
        
        $sourceFile = $this->getSourceFile($realFile,$config);
        
        if ($exists) {
	        if ($this->getIO()->askConfirmation(sprintf('Destination file %s already exists - copy from %s ([y]/n)? ',$realFile, $sourceFile),true)) {
		        if ($sudo) {
			        $this->getIO()->write(sprintf('<comment>Copying with sudo %s -> %s</comment>', $sourceFile, $realFile));
			        $command="sudo sh -c 'cp -f ".$sourceFile." ".$realFile."'";
			        $this->getIO()->write($command);
			        shell_exec($command);
					if ($mode) {
						$command="sudo sh -c 'chmod ".$mode." ".$realFile."'";
						shell_exec($command);
					}
		        } else {
			        $this->getIO()->write(sprintf('<comment>Copying %s -> %s</comment>', $sourceFile, $realFile));
			        copy($sourceFile, $realFile);
					if ($mode) {
						$command="sh -c 'chmod ".$mode." ".$realFile."'";
						shell_exec($command);
					}
		        }
	        }
        } else {
	        if ($sudo) {
		        $this->getIO()->write(sprintf('<comment>Copying with sudo %s -> %s</comment>', $sourceFile, $realFile));
		        $command="sudo sh -c 'cp -f ".$sourceFile." ".$realFile."'";
		        $this->getIO()->write($command);
		        shell_exec($command);
				if ($mode) {
					$command="sudo sh -c 'chmod ".$mode." ".$realFile."'";
					shell_exec($command);
				}
	        } else {
		        $this->getIO()->write(sprintf('<comment>Copying %s -> %s</comment>', $sourceFile, $realFile));
		        copy($sourceFile, $realFile);
				if ($mode) {
					$command="sh -c 'chmod ".$mode." ".$realFile."'";
					shell_exec($command);
				}
	        }
        }

        return true;
    }

    /**
     * @return IOInterface
     */
    public function getIO()
    {
        return $this->io;
    }

    /**
     * @param IOInterface $io
     */
    public function setIO(IOInterface $io)
    {
        $this->io = $io;
    }

    /**
     * @return mixed
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param mixed $config
     */
    public function setConfig($config)
    {
        if (empty($config['file'])) {
            throw new \InvalidArgumentException('The extra.bricks-platform.config.file setting is required.');
        }

        $this->config = $config;
    }
    
    protected function getSourceFile($realFile,$config) {
    	
    	if (isset($config['source-file'])) {
    		return $config['source-file'];
	    }
	    
	    if (isset($config['color-only']) && $config['color-only']) {
		    $source= Handler\AbstractHandler::getColor();
	    } else {
		    $source = Handler\AbstractHandler::getTarget();
	    }
	    
	    if (isset($config['use-hostname'])) {
	    	$hostname=gethostname();
		    $source=$source.'_'.$hostname;
	    }
	
	
	    $realFileSegments = explode('/',$realFile);
	    $filename = array_pop($realFileSegments);
	    $filenameSegments = explode('.',$filename);

	    if (count($filenameSegments)==1) {
		    // regular files without suffix
		    return $filenameSegments[0].'.'.$source;
	    } else if (count($filenameSegments) == 2 && $filenameSegments[0]=='') {
	    	// dot-files without suffix
            return '.'.$filenameSegments[1].'.'.$source;
	    }
	    // regular files with suffix
	    $suffix = array_pop($filenameSegments);
	    return implode('.',$filenameSegments).'.'.$source.'.'.$suffix;
    }

}
