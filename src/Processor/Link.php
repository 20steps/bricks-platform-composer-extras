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

        $exists = file_exists($realFile);

        $targetFile = $this->getTargetFile($realFile,$config);
        
        if ($exists) {
	        if ($this->getIO()->askConfirmation(sprintf('Destination file %s already exists - link to %s (y/[n])? ',$realFile, $targetFile),true)) {
		        $this->getIO()->write(sprintf('<comment>Relinking %s -> %s</comment>', $realFile, $targetFile));
		        unlink($realFile);
		        symlink($targetFile, $realFile);
	        }
        } else {
	        $this->getIO()->write(sprintf('<comment>Linking %s -> %s</comment>', $realFile, $targetFile));
	        symlink($targetFile,$realFile);
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
