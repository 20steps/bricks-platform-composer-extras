<?php

namespace BricksPlatformComposerExtras;

use Composer\Script\Event;

use BricksPlatformComposerExtras\Handler\ReloadHandler;
use BricksPlatformComposerExtras\Handler\SetupHandler;
use BricksPlatformComposerExtras\Handler\DeployHandler;
use BricksPlatformComposerExtras\Handler\RemoteHandler;

/**
 * Class Bootstrap
 *
 * Entry point for composer events.
 *
 * @package BricksPlatformComposerExtras
 */
class Bootstrap {

    /**
     * Boostrap a SetupHandler() instance and call the setup method.
     * @param Event $event
     */
    static public function setup(Event $event) {
        $handler = new SetupHandler();
        $handler->setup($event);
    }
	
	/**
	 * Boostrap a SetupHandler() instance and call the setup method.
	 * @param Event $event
	 */
	static public function remotes(Event $event) {
		$handler = new RemoteHandler();
		$handler->remotes($event);
	}
	
	/**
	 * Boostrap a ReloadHandler() instance and call the reload method.
	 * @param Event $event
	 */
	static public function reload(Event $event) {
		$handler = new ReloadHandler();
		$handler->reload($event);
	}
	
	/**
	 * Boostrap a DeployHandler() instance and call the reload method.
	 * @param Event $event
	 */
	static public function deploy(Event $event) {
		$handler = new DeployHandler();
		$handler->deploy($event);
	}
	
	/**
	 * Boostrap a DeployHandler() instance and call the reload method.
	 * @param Event $event
	 */
	static public function switchColor(Event $event) {
		$handler = new SwitchColorHandler();
		$handler->switchColor($event);
	}

}