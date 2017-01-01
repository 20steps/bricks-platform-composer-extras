<?php


namespace BricksPlatformComposerExtras\Processor;

use Composer\IO\IOInterface;
use Composer\Script\Event;

/**
 * Interface ProcessorInterface
 * @package BricksPlatformComposerExtras
 */
interface ProcessorInterface {

    public function process(array $config, Event $event);

    public function setConfig($config);
    public function getConfig();

    public function __construct(IOInterface $io);

}