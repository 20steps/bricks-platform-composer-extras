<?php

namespace BricksPlatformComposerExtras\Processor;

use Composer\IO\IOInterface;

/**
 * Class Generic
 *
 * Loosely based in https://github.com/Incenteev/ParameterHandler/blob/master/Processor.php
 *
 * @package BricksPlatformComposerExtras
 */
class Generic implements ProcessorInterface
{

    protected $io;
    protected $config;

    public function __construct(IOInterface $io)
    {
        $this->setIO($io);
    }

    public function process(array $config)
    {
        $this->setConfig($config);
        $config = $this->getConfig();

        $realFile = $config['file'];

        $exists = is_file($realFile);

        $action = $exists ? 'Rewriting' : 'Creating';

        if ($exists) {
            if ($this->getIO()->askConfirmation('Destination file already exists, overwrite (y/n)? ')) {
                $this->getIO()->write(sprintf('<info>%s the "%s" file</info>', $action, $realFile));
                $oldFile = $realFile . '.old';
                copy($realFile, $oldFile);
                $this->getIO()->write(sprintf('A copy of the old configuration file was saved to %s', $oldFile));
            } else {
                return false;
            }
        } else {
            if (!is_dir($dir = dirname($realFile))) {
                mkdir($dir, 0755, true);
            }
        }

        $template = file_get_contents($config['dist-file']);
        $contents = preg_replace_callback('/\{\{(.*)\}\}/', array($this, '_templateReplace'), $template);
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
            throw new \InvalidArgumentException('The extra.dist-installer-params.file setting is required.');
        }

        if (empty($config['dist-file'])) {
            $config['dist-file'] = $config['file'].'.dist';
        }

        if (!is_file($config['dist-file'])) {
            throw new \InvalidArgumentException(sprintf('The dist file "%s" does not exist. Check your dist-file config or create it.', $config['dist-file']));
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
