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
class TargetHandler extends AbstractHandler
{
	public function printTarget(Event $event) {
		$this->io = $event->getIO();
		
		$this->loadTarget(false);
		
	}
	
    public function switchColor(Event $event) {
	    $this->io = $event->getIO();
	
	    $this->updateColor($event);
	
    }
	
	public function switchStage(Event $event) {
		$this->io = $event->getIO();
		
		$this->updateStage($event);
		
	}
	
	 
   // helpers
	
    protected function updateColor(Event $event) {
	    $io = $this->getIO();
	    
	    $color = null;
	    $arguments = $event->getArguments();
	    if ($arguments && is_array($arguments) && count($arguments)==1) {
	    	$color = trim($arguments[0]);
	    }
	
	    $filename = 'etc/color';
	    
	    if (!$color) {
		    if (is_file($filename)) {
			    $update = $io->ask('Update color setting ([y],n)?', true);
		    } else {
			    $update = true;
		    }
	    } else {
	    	$update = true;
	    }
	    if ($update) {
		    $default = self::getEnv('BRICKS_COLOR', 'generic');
		    if (!$color) {
			    $value = $io->ask(sprintf('Please enter the color of your installation [%s] ', $default), $default);
		    } else {
		    	$value = $color;
		    }
		    file_put_contents($filename, $value);
		    $this->getIO()->write(sprintf('<info>Writing %s to the "%s" file</info>', $value, $filename));
	    }
	    $this->loadTarget(false);
    }
    
    public function updateStage(Event $event) {
	    $io = $this->getIO();
	
	    $stage = null;
	    $arguments = $event->getArguments();
	    if ($arguments && is_array($arguments) && count($arguments)==1) {
		    $stage = trim($arguments[0]);
	    }
	    
		$filename = 'etc/stage';
	    if (!$stage) {
		    if (is_file($filename)) {
			    $update = $io->ask('Update stage setting ([y],n)?', true);
		    } else {
			    $update = true;
		    }
	    } else {
	    	$update = true;
	    }
		if ($update) {
			$default = self::getEnv('BRICKS_STAGE', 'dev');
			if (!$stage) {
				$value = $io->ask(sprintf('Please enter the stage of your installation [%s] ', $default), $default);
			} else {
				$value = $stage;
			}
			file_put_contents($filename, $value);
			$this->getIO()->write(sprintf('<info>Writing %s to the "%s" file</info>', $value, $filename));
		}
	}
	
}