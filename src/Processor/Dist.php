<?php

namespace BricksPlatformComposerExtras\Processor;

use Composer\IO\IOInterface;
use Composer\Script\Event;

/**
 * Class Generic
 *
 * Loosely based in https://github.com/Incenteev/ParameterHandler/blob/master/Processor.php
 *
 * @package BricksPlatformComposerExtras
 */
class Dist implements ProcessorInterface
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

        $realFile = $config['file'];

        $exists = is_file($realFile);

        $action = $exists ? 'Updating' : 'Creating';
	
	    $distFile = $config['dist-file'];
	    
	    $distTemplate = file_get_contents($distFile);
	    
	    $updateByDefault = false;
	    if (isset($config['update-by-default'])) {
		    $updateByDefault = $config['update-by-default'];
	    }

        if ($exists) {
	    	if ($updateByDefault) {
	    		$message = 'Destination file %s already exists - update from %s ([y]/n)? ';
		    } else {
	    		$message = 'Destination file %s already exists - update from %s (y/[n])? ';
		    }
            if ($this->getIO()->askConfirmation(sprintf($message,$realFile, $distFile),$updateByDefault)) {
                $this->getIO()->write(sprintf('<comment>%s the "%s" file</comment>', $action, $realFile));
                $oldFile = $realFile . '.old';
                copy($realFile, $oldFile);
                $this->getIO()->write(sprintf('A copy of the old configuration file was saved to %s', $oldFile));
            } else {
                return false;
            }
        } else {
	        $this->getIO()->write(sprintf('<comment>%s the "%s" file</comment>', $action, $realFile));
            if (!is_dir($dir = dirname($realFile))) {
                mkdir($dir, 0755, true);
            }
        }

        $contents = preg_replace_callback('/\{\{(.*)\}\}/', array($this, '_templateReplace'), $distTemplate);
        file_put_contents($realFile, $contents);

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
            throw new \InvalidArgumentException('The extra.bricks-platform.dist-files.file setting is required.');
        }

        if (empty($config['dist-file'])) {
            $config['dist-file'] = $config['file'].'.dist';
        }

        if (!is_file($config['dist-file'])) {
            throw new \InvalidArgumentException(sprintf('The dist file "%s" does not exist. Check settings of extra.bricks-platform.config in your composer.json or create the file.', $config['dist-file']));
        }
        $this->config = $config;
    }

    /**
     * @return array
     */
    protected function _getEnvValue($variable)
    {
        if (isset($this->config['env-map'])) {
            $envMap = $this->config['env-map'];
            if (isset($envMap[$variable])) {
                $variable = $envMap[$variable];
            }
        }
        return getenv($variable);
    }

    /**
     * @param array $matches
     * @return string
     */
    protected function _templateReplace(array $matches)
    {
        $result = $matches[0];
        if (count($matches) > 1) {
            $explode = explode('|', $matches[1]);
            $question = $explode[0];
            $index = 0;
            do {
                $default = @$explode[++$index] ?: null;
                // if default syntax is =ENV[VARIABLE_NAME] then extract VARIABLE_NAME from the environment as default value
                if (strpos($default, '=ENV[') === 0) {
                    $envMatch = [];
                    preg_match('/^\=ENV\[(.*)\]$/', $default, $envMatch);
                    if (isset($envMatch[1])) {
                        $default = $this->_getEnvValue($envMatch[1]);
                    }
                }
            } while( empty($default) && $index < count($explode) );
            $question = str_replace('[]', "[$default]", $question);
            $result = $this->getIO()->ask(rtrim($question) . ' ', $default);
        }
        return $result;
    }
}
