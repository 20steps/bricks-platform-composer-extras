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
class SwitchColorHandler extends AbstractHandler
{
    public function switchColor(Event $event) {
	    $this->io = $event->getIO();
	
	    $this->updateColor($event);
	
    }
	 
   // helpers
	
    protected function updateColor(Event $event) {
		    $io = $this->getIO();
		
		    $filename = 'etc/color';
		    if (is_file($filename)) {
			    $update = $io->ask('Update color setting ([y],n)?', true);
		    } else {
			    $update = true;
		    }
		    if ($update) {
			    $default = self::getEnv('BRICKS_COLOR', 'generic');
			    $value = $io->ask(sprintf('Please enter the color of your installation [%s] ', $default), $default);
			    file_put_contents($filename, $value);
			    $this->getIO()->write(sprintf('<info>Writing %s to the "%s" file</info>', $value, $filename));
		    }
		    $this->loadTarget(false);
	    }
    }