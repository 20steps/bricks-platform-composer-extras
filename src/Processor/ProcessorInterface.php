<?php


namespace BricksPlatformComposerExtras\Processor;

use Composer\IO\IOInterface;

/**
 * Interface ProcessorInterface
 * @package BricksPlatformComposerExtras
 */
interface ProcessorInterface {

    public function process(array $config);

    public function setConfig($config);
    public function getConfig();

    public function __construct(IOInterface $io);

}