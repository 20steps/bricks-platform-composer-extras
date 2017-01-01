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
class Directory implements ProcessorInterface
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

        $directoryName = $config['directory'];
        if (isset($config['mode'])) {
        	$mode = $config['mode'];
        } else {
        	$mode= null;
        }
	    if (isset($config['sudo'])) {
		    $sudo = $config['sudo'];
	    } else {
		    $sudo= false;
	    }
        
        if ($mode) {
	        $this->getIO()->write(sprintf('<comment>Making sure directory %s exists with mode %s</comment>', $directoryName, $mode));
        	
        } else {
	        $this->getIO()->write(sprintf('<comment>Making sure directory %s exists</comment>', $directoryName));
        	
        }

        if ($sudo) {
	        $command="sudo sh -c 'mkdir -p ".$directoryName."'";
	        shell_exec($command);
	        if ($mode) {
	        	shell_exec("sudo sh -c 'chmod -R ".$mode." ".$directoryName."'");
	        }
        } else {
	        mkdir($directoryName,$mode,true);
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
        if (empty($config['directory'])) {
            throw new \InvalidArgumentException('The extra.bricks-platform.config.directory setting is required.');
        }

        $this->config = $config;
    }
    
}
