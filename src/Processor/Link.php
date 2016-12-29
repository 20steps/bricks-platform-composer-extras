<?php

namespace BricksPlatformComposerExtras\Processor;

use BricksPlatformComposerExtras\Handler;
use Composer\IO\IOInterface;

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

    public function process(array $config)
    {
        $this->setConfig($config);
        $config = $this->getConfig();

        $realFile = $config['file'];

        $exists = is_file($realFile);

        $targetFile = $this->getTargetFile($realFile);
        
        if ($exists) {
	        if ($this->getIO()->askConfirmation(sprintf('Destination file %s already exists - link to %s (y/[n])? ',$realFile, $targetFile),false)) {
		        $this->getIO()->write(sprintf('<info>Relinking %s -> %s</info>', $realFile, $targetFile));
		        unlink($realFile);
		        symlink($targetFile, $realFile);
	        }
        } else {
	        $this->getIO()->write(sprintf('<info>Linking %s -> %s</info>', $realFile, $targetFile));
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
    
    protected function getTargetFile($realFile) {
	    $target = Handler\AbstractHandler::getTarget();
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
