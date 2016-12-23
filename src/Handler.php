<?php


namespace BricksPlatformComposerExtras;
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
class Handler
{

    const EXTRAS_KEY = 'dist-installer-params';

    /** @var ProcessorInterface[] */
    protected $processors = [];

    public function install(Event $event)
    {
        $extras = $event->getComposer()->getPackage()->getExtra();

        if (!isset($extras[self::EXTRAS_KEY])) {
            throw new \InvalidArgumentException(sprintf('The parameter handler needs to be configured through the extra.%s setting.', self::EXTRAS_KEY));
        }

        $configs = $extras[self::EXTRAS_KEY];

        if (!is_array($configs)) {
            throw new \InvalidArgumentException(sprintf('The extra.%s setting must be an array or a configuration object.', self::EXTRAS_KEY));
        }

        if (array_keys($configs) !== range(0, count($configs) - 1)) {
            $configs = [$configs];
        }

        foreach ($configs as $config) {
            $processorType = isset($config['type']) ? $config['type'] : __NAMESPACE__ . '\\Processor\\Generic';
            /** @var ProcessorInterface $processor */
            $processor = $this->_getProcessorForType($processorType, $event->getIO());
            $processor->process($config);
        }
    }

    /**
     * @param $type
     * @param $io
     * @return ProcessorInterface
     */
    protected function _getProcessorForType($type, IOInterface $io) {
        if (!isset($this->processors[$type])) {
            if (!class_exists($type)) {
                throw new \InvalidArgumentException(sprintf('Could not find class %s. Please specify a valid class as the config file\'s "type" parameter.', $type));
            }
            $this->processors[$type] = new $type($io);
        }
        return $this->processors[$type];
    }

}
