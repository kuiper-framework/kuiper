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

namespace kuiper\tracing;

use DI\Attribute\Inject;
use kuiper\di\attribute\Bean;
use kuiper\di\attribute\Configuration;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;

use function kuiper\helper\env;

use kuiper\helper\Text;
use kuiper\http\client\HttpClientFactoryInterface;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\swoole\Application;
use kuiper\swoole\ServerConfig;
use kuiper\tars\server\TarsTcpReceiveEventListener;
use kuiper\tracing\listener\TraceDbQuery;
use kuiper\tracing\middleware\rpc\TraceServerRequest;
use kuiper\tracing\middleware\tars\TraceClientRequest;
use kuiper\tracing\middleware\web\TraceWebRequest;

#[Configuration]
class TracingConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        if (class_exists(Application::class) && Application::hasInstance()) {
            $config = Application::getInstance()->getConfig();
            $config->mergeIfNotExists([
                'application' => [
                    'tracing' => [
                        'reporting_url' => env('TRACING_REPORTING_URL'),
                    ],
                ],
            ]);
            $config->appendTo('application.listeners', TraceDbQuery::class);
            if (Text::isNotEmpty($config->get('application.tracing.reporting_url'))) {
                $config->appendTo('application.tars.client.middleware', TraceClientRequest::class);
                $config->appendTo('application.jsonrpc.client.middleware', middleware\rpc\TraceClientRequest::class);
                foreach ($config->get('application.server.ports', []) as $port) {
                    if (is_array($port)) {
                        if (isset($port['listener'])) {
                            if ('jsonRpcHttpRequestListener' === $port['listener']) {
                                $config->appendTo('application.jsonrpc.server.middleware', TraceServerRequest::class);
                            } elseif (TarsTcpReceiveEventListener::class === $port['listener']) {
                                $config->appendTo('application.tars.server.middleware', middleware\tars\TraceServerRequest::class);
                            }
                        }
                    } else {
                        $config->appendTo('application.web.middleware', TraceWebRequest::class);
                    }
                }
            }
        }

        return [];
    }

    #[Bean]
    public function config(
        #[Inject('application.tracing')] ?array $options,
        ServerConfig $serverConfig,
        LoggerFactoryInterface $loggerFactory,
        HttpClientFactoryInterface $httpClientFactory): Config
    {
        $options['ip'] = $serverConfig->getPort()->getHost();
        $ipv4Regex = '/^(\d+\.)*\d+$/';
        if (!preg_match($ipv4Regex, $options['ip'])) {
            $options['ip'] = gethostbyname($options['ip']);
            if (!preg_match($ipv4Regex, $options['ip'])) {
                $options['ip'] = null;
            }
        }
        $options['tags'][Constants::JAEGER_HOSTNAME_TAG_KEY] = gethostname();
        $config = new Config($options, $serverConfig->getServerName(), null, $httpClientFactory);
        $config->setLogger($loggerFactory->create(Tracer::class));
        Tracer::set([$config, 'createTracer']);

        return $config;
    }
}
