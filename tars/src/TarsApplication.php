<?php

declare(strict_types=1);

namespace kuiper\tars;

use kuiper\swoole\Application;

class TarsApplication extends Application
{
    protected function getConfigLoader(): ConfigLoaderInterface
    {
        if (null === $this->configLoader) {
            $this->configLoader = new ConfigLoader();
        }

        return $this->configLoader;
    }

    private function parseArgv(): array
    {
        $configFile = null;
        $properties = [];
        $commandName = null;
        $argv = $_SERVER['argv'];
        $rest = [];
        while (null !== $token = array_shift($argv)) {
            if ('--' === $token) {
                $rest[] = $token;
                break;
            }
            if (0 === strpos($token, '--')) {
                $name = substr($token, 2);
                $pos = strpos($name, '=');
                if (false !== $pos) {
                    $value = substr($name, $pos + 1);
                    $name = substr($name, 0, $pos);
                }
                if ('config' === $name) {
                    $configFile = $value ?? array_shift($argv);
                } elseif ('define' === $name) {
                    $properties[] = $value ?? array_shift($argv);
                } else {
                    $rest[] = $token;
                }
            } elseif ('-' === $token[0] && 2 === strlen($token) && 'D' === $token[1]) {
                $properties[] = array_shift($argv);
            } else {
                $rest[] = $token;
            }
        }
        $_SERVER['argv'] = array_merge($rest, $argv);

        return [$configFile, $properties];
    }

    public function setConfigLoader(ConfigLoaderInterface $configLoader): void
    {
        $this->configLoader = $configLoader;
    }
}
