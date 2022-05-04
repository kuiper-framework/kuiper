<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\tars\server;

use kuiper\helper\Properties;
use kuiper\helper\Text;
use kuiper\reflection\ReflectionType;
use kuiper\swoole\constants\ServerSetting;
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
                // 统一把 - 改为 _
                $name = str_replace('-', '_', $parts[0]);
                if (1 === count($parts)) {
                    $current[$name] = true;
                } else {
                    $current[$name] = $parts[1];
                }
            }
        }
        $arr = $config->get('tars.application');
        $ports = [];
        $serverSettings = [];
        if (isset($arr['server'])) {
            $adapters = [];
            foreach ($arr['server'] as $key => $value) {
                if (false !== strpos($key, '.') && Text::endsWith($key, 'Adapter')) {
                    $adapter = $arr['server'][$key];
                    if (isset($adapter['endpoint'])) {
                        $endpoint = EndpointParser::parse($adapter['endpoint']);
                        $ports[$endpoint->getPort()] = [
                            'protocol' => TarsProtocol::fromValue($adapter['protocol'] ?? 'tars')->serverType,
                            'host' => $endpoint->getHost(),
                        ];
                    }
                    $adapters[] = $adapter;
                    unset($arr['server'][$key]);
                } elseif (is_string($key) && ServerSetting::has($key)) {
                    $serverSettings[$key] = ReflectionType::forName(ServerSetting::type($key))->sanitize($value);
                }
            }
            if (empty($serverSettings[ServerSetting::WORKER_NUM])) {
                $threads = (int) $adapters[0]['threads'];
                if ($threads > 0) {
                    $serverSettings[ServerSetting::WORKER_NUM] = $threads;
                }
            }
            $arr['server']['adapters'] = $adapters;
            $arr['server']['server_settings'] = $serverSettings;
        }

        return Properties::create([
            'application' => [
                'tars' => $arr,
                'server' => [
                    'ports' => $ports,
                ],
                'swoole' => $serverSettings,
            ],
        ]);
    }
}
