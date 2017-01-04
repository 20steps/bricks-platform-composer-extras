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
class Link implements ProcessorInterface
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

        $targetFile = $this->getTargetFile($realFile,$config);
        
        if ($exists) {
	        if ($this->getIO()->askConfirmation(sprintf('Destination file %s already exists - link to %s (y/[n])? ',$realFile, $targetFile),true)) {
		        if ($sudo) {
			        $this->getIO()->write(sprintf('<comment>Relinking with sudo %s -> %s</comment>', $realFile, $targetFile));
			        $command="sudo sh -c 'ln -sf ".$targetFile." ".$realFile."'";
			        $this->getIO()->write($command);
			        shell_exec($command);
					if ($mode) {
						$command="sudo sh -c 'chmod ".$mode." ".$realFile."'";
						shell_exec($command);
					}
		        } else {
			        $this->getIO()->write(sprintf('<comment>Relinking %s -> %s</comment>', $realFile, $targetFile));
			        unlink($realFile);
			        symlink($targetFile, $realFile);
					if ($mode) {
						$command="sh -c 'chmod ".$mode." ".$realFile."'";
						shell_exec($command);
					}
		        }
	        }
        } else {
	        if ($sudo) {
		        $this->getIO()->write(sprintf('<comment>Linking with sudo %s -> %s</comment>', $realFile, $targetFile));
		        $command="sudo sh -c 'ln -sf ".$targetFile." ".$realFile."'";
		        $this->getIO()->write($command);
		        shell_exec($command);
				if ($mode) {
					$command="sudo sh -c 'chmod ".$mode." ".$realFile."'";
					shell_exec($command);
				}
	        } else {
		        $this->getIO()->write(sprintf('<comment>Linking %s -> %s</comment>', $realFile, $targetFile));
		        try {
			        unlink($realFile);
		        } catch (\Exception $e) {
			        // try to unlink as $realFile might be a stale link
		        }
		        symlink($targetFile, $realFile);
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
    
    protected function getTargetFile($realFile,$config) {
    	if (isset($config['link-file'])) {
    		return $config['link-file'];
	    }
	    $target = Handler\AbstractHandler::getTarget();
	    if (isset($config['use-hostname'])) {
	    	$hostname=gethostname();
		    $target=$target.'_'.$hostname;
	    }
	    $realFileSegments = explode('/',$realFile);
	    $filename = array_pop($realFileSegments);
	    $filenameSegments = explode('.',$filename);
	    if (count($filenameSegments)==1) {
		    // regular files without suffix
		    return $filenameSegments[1].'.'.$target;
	    } else if (count($filenameSegments) == 2 && $filenameSegments[0]=='') {
	    	// dot-files without suffix
            return '.'.$filenameSegments[1].'.'.$target;
	    }
	    // regular files with suffix
	    $suffix = array_pop($filenameSegments);
	    return implode('.',$filenameSegments).'.'.$target.'.'.$suffix;
    }

}
