<?php

declare(strict_types=1);

namespace kuiper\tars\server;

use kuiper\helper\Properties;
use kuiper\helper\Text;
use kuiper\tars\core\EndpointParser;
use kuiper\tars\core\TarsProtocol;
use kuiper\tars\exception\ConfigException;

class Config
{
    /**
     * @throws ConfigException
     */
    public static function parseFile(string $fileName): Properties
    {
        $content = file_get_contents($fileName);
        if (false === $content) {
            throw new ConfigException("cannot read config file '{$fileName}'");
        }

        return self::parse($content);
    }

    /**
     * @throws ConfigException
     */
    public static function parse(string $content): Properties
    {
        $stack = [];
        $current = $config = Properties::create();
        foreach (explode("\n", $content) as $lineNum => $line) {
            $line = trim($line);
            if (empty($line) || 0 === strpos($line, '#')) {
                continue;
            }
            if (preg_match("/<(\/?)(\S+)>/", $line, $matches)) {
                if ($matches[1]) {
                    if (empty($stack)) {
                        throw new ConfigException("Unexpect close tag '{$line}' at line {$lineNum}");
                    }
                    $current = array_pop($stack);
                } else {
                    $stack[] = $current;
                    $current = $current[$matches[2]] = Properties::create();
                }
            } else {
                $parts = array_map('trim', explode('=', $line, 2));
                if (1 === count($parts)) {
                    $current[$parts[0]] = true;
                } else {
                    $current[$parts[0]] = $parts[1];
                }
            }
        }
        $arr = $config->get('tars.application');
        $ports = [];
        if (isset($arr['server'])) {
            $adapters = [];
            foreach ($arr['server'] as $key => $value) {
                if (false !== strpos($key, '.') && Text::endsWith($key, 'Adapter')) {
                    $adapter = $arr['server'][$key];
                    if (isset($adapter['endpoint'])) {
                        $endpoint = EndpointParser::parse($adapter['endpoint']);
                        $ports[$endpoint->getAddress()] = TarsProtocol::fromValue($adapter['protocol'])->serverType;
                    }
                    $adapters[] = $adapter;
                    unset($arr['server'][$key]);
                }
            }
            $arr['server']['adapters'] = $adapters;
        }

        return Properties::create([
            'application' => [
                'tars' => $arr,
                'swoole' => [
                    'ports' => $ports,
                ],
            ],
        ]);
    }
}
