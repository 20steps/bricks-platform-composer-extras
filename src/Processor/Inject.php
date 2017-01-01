<?php

namespace BricksPlatformComposerExtras\Processor;

use BricksPlatformComposerExtras\Handler\SetupHandler;
use Composer\IO\IOInterface;
use Composer\Script\Event;

/**
 * Class Generic
 *
 * Loosely based in https://github.com/Incenteev/ParameterHandler/blob/master/Processor.php
 *
 * @package BricksPlatformComposerExtras
 */
class Inject implements ProcessorInterface
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

        $action = $exists ? 'Inject into' : 'Update injection into';
	
	    $injectFile = $config['inject-file'];
	    
	    $composerJson = json_decode(file_get_contents(getcwd().'/composer.json'),true);
	    $projectName=$composerJson['name'];
	    
	    $injectFileContent = file_get_contents($injectFile);

        if (!$exists) {
	        throw new \InvalidArgumentException('The file %s to inject %s into does not exist',$realFile,$injectFile);
        }
        

	    $realFileContent = file_get_contents($realFile);

        if (isset($config['comment-prefix'])) {
        	$commentPrefix=$config['comment-prefix'];
        } else {
        	$commentPrefix='# ';
        }
        
        $beginOfComment = $commentPrefix.'BRICKS-INJECT-START '.$projectName.' - '.$injectFile;
        $endOfComment =   $commentPrefix.'BRICKS-INJECT-END '.$projectName.' - '.$injectFile;
        
        $pattern = '/'.str_replace('/',"\\/",preg_quote($beginOfComment).'(.+)'.preg_quote($endOfComment)).'/s';
        $injectedPreviously = preg_match($pattern,$realFileContent,$matches);
	    if ($injectedPreviously) {
		    $this->getIO()->write(sprintf('<comment>Updating injection of %s into "%s"</comment>', $injectFile,$realFile));
		    $realFileContent=preg_replace($pattern,$beginOfComment."\n".$injectFileContent."\n".$endOfComment,$realFileContent);
	    } else {
		    $this->getIO()->write(sprintf('<comment>Injecting %s into "%s"</comment>', $injectFile,$realFile));
		    $realFileContent.="\n\n".$beginOfComment."\n";
		    $realFileContent.=$injectFileContent."\n";
		    $realFileContent.=$endOfComment."\n\n";
	    }
	    if (isset($config['sudo'])) {
		    $sudo=$config['sudo'];
	    } else {
		    $sudo=false;
	    }
	    if ($sudo) {
	    	$tmpFile = tempnam('/tmp','bricks');
	    	file_put_contents($tmpFile,$realFileContent);
	    	$command="sudo sh -c 'cat ".$tmpFile." > ".$realFile."'";
		    shell_exec($command);
	    } else {
	    	file_put_contents($realFile,$realFileContent);
	    }
        /*$contents = preg_replace_callback('/\{\{(.*)\}\}/', array($this, '_templateReplace'), $distTemplate);
        file_put_contents($realFile, $contents);*/

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

        if (empty($config['inject-file'])) {
            $config['inject-file'] = $config['file'].'.inject';
        }

        if (!is_file($config['inject-file'])) {
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
