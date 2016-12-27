<?php


namespace BricksPlatformComposerExtras\Handler;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use BricksPlatformComposerExtras\Processor\ProcessorInterface;

/**
 * Abstract Handler
 *
 * @package BricksPlatformComposerExtras
 */
abstract class AbstractHandler
{

	const EXTRAS_KEY = 'bricks-platform';
	
	/** @var IOInterface; */
	protected $io;
	
	
	// public helpers
	
	public function loadTarget($quiet=true) {
		$value=trim(@file_get_contents('etc/stage'));
		self::putEnv('BRICKS_STAGE',$value);
		
		$value=trim(@file_get_contents('etc/color'));
		self::putEnv('BRICKS_COLOR',$value);
		
		if (!$quiet) {
			$this->getIO()->write(sprintf('<info>Target was set/detected as %s</info>',self::getTarget()));
		}
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
		return self::getColor().'_'.self::getStage();
	}
	
	public static function getStage() {
		return self::getEnv('BRICKS_STAGE');
	}
	
	public static function getColor() {
		return self::getEnv('BRICKS_COLOR');
	}
	
	
    // protected helpers
	
	/** @return IOInterface\ */
	protected function getIO() {
		return $this->io;
	}
	


}
