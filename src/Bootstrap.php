<?php

namespace BricksPlatformComposerExtras;


use Composer\Script\Event;

/**
 * Class Bootstrap
 *
 * Entry point for composer events.
 *
 * @package BricksPlatformComposerExtras
 */
class Bootstrap {

    /**
     * Boostrap a Handler() instance and call the install method.
     * @param Event $event
     */
    static public function install(Event $event) {
        $handler = new Handler();
        $handler->install($event);
    }

}