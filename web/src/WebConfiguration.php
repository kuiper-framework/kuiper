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

namespace kuiper\web;

use DI\Attribute\Inject;

use function DI\autowire;

use kuiper\di\attribute\AllConditions;
use kuiper\di\attribute\Bean;
use kuiper\di\attribute\ConditionalOnClass;
use kuiper\di\attribute\ConditionalOnProperty;
use kuiper\di\attribute\Configuration;
use kuiper\di\ComponentCollection;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;

use function kuiper\helper\env;

use kuiper\helper\Properties;
use kuiper\logger\LoggerConfiguration;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\swoole\Application;
use kuiper\swoole\attribute\BootstrapConfiguration;
use kuiper\swoole\config\ServerConfiguration;
use kuiper\swoole\logger\RequestLogFormatterInterface;
use kuiper\swoole\logger\RequestLogTextFormatter;
use kuiper\web\exception\RedirectException;
use kuiper\web\exception\UnauthorizedException;
use kuiper\web\handler\DefaultLoginUrlBuilder;
use kuiper\web\handler\ErrorHandler;
use kuiper\web\handler\HttpRedirectHandler;
use kuiper\web\handler\IncludeStacktrace;
use kuiper\web\handler\LogErrorRenderer;
use kuiper\web\handler\LoginUrlBuilderInterface;
use kuiper\web\handler\UnauthorizedErrorHandler;
use kuiper\web\http\MediaType;
use kuiper\web\middleware\AccessLog;
use kuiper\web\middleware\HealthyStatus;
use kuiper\web\middleware\Session;
use kuiper\web\security\Acl;
use kuiper\web\security\AclInterface;
use kuiper\web\session\CacheSessionHandler;
use kuiper\web\session\CacheStoreSessionFactory;
use kuiper\web\session\SessionFactoryInterface;
use kuiper\web\view\PhpView;
use kuiper\web\view\TwigView;
use kuiper\web\view\ViewInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\App;
use Slim\Error\Renderers\HtmlErrorRenderer;
use Slim\Error\Renderers\JsonErrorRenderer;
use Slim\Error\Renderers\PlainTextErrorRenderer;
use Slim\Error\Renderers\XmlErrorRenderer;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Interfaces\ErrorHandlerInterface;
use Slim\Interfaces\ErrorRendererInterface;
use Slim\Middleware\BodyParsingMiddleware;
use Slim\Middleware\ErrorMiddleware;
use Twig\Environment as Twig;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;

#[Configuration(dependOn: [ServerConfiguration::class])]
#[BootstrapConfiguration]
class WebConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        $config = Application::getInstance()->getConfig();
        $config->mergeIfNotExists([
            'application' => [
                'web' => [
                    'log_file' => env('WEB_LOG_FILE', '{application.logging.path}/access.log'),
                    'log_post_body' => 'true' === env('WEB_LOG_POST_BODY'),
                    'log_sample_rate' => (float) env('WEB_LOG_SAMPLE_RATE', '1.0'),
                    'namespace' => env('WEB_NAMESPACE'),
                    'context_url' => env('WEB_CONTEXT_URL', ''),
                    'health_check_enabled' => 'true' === env('WEB_HEALTH_CHECK_ENABLED'),
                    'error' => [
                        'display' => 'true' === env('WEB_ERROR_DISPLAY'),
                        'logging' => 'true' === env('WEB_ERROR_LOGGING', 'true'),
                        'include_stacktrace' => env('WEB_ERROR_INCLUDE_STACKTRACE', 'on_trace_param'),
                        'handlers' => [
                            RedirectException::class => HttpRedirectHandler::class,
                            UnauthorizedException::class => UnauthorizedErrorHandler::class,
                            HttpUnauthorizedException::class => UnauthorizedErrorHandler::class,
                        ],
                    ],
                    'view' => [
                        'engine' => env('WEB_VIEW_ENGINE'),
                        'path' => env('WEB_VIEW_PATH', '{application.base_path}/resources/views'),
                    ],
                    'session' => [
                        'enabled' => 'true' === env('WEB_SESSION_ENABLED'),
                        'prefix' => env('WEB_SESSION_PREFIX'),
                        'cookie_name' => env('WEB_SESSION_COOKIE_NAME'),
                        'cookie_lifetime' => env('WEB_SESSION_COOKIE_LIFETIME'),
                    ],
                ],
                'logging' => [
                    'loggers' => [
                        'AccessLogLogger' => LoggerConfiguration::createAccessLogger('{application.web.log_file}'),
                    ],
                    'logger' => [
                        AccessLog::class => 'AccessLogLogger',
                    ],
                ],
            ],
        ]);
        $this->addMiddleware($config);

        return [
            ErrorRendererInterface::class => autowire(LogErrorRenderer::class),
            AclInterface::class => autowire(Acl::class),
        ];
    }

    #[Bean]
    public function accessLog(RequestLogFormatterInterface $requestLogFormatter,
                              LoggerFactoryInterface $loggerFactory,
                              #[Inject('application.web.log_sample_rate')] float $sampleRate): AccessLog
    {
        $log = new AccessLog($requestLogFormatter, null, $sampleRate);
        $log->setLogger($loggerFactory->create(AccessLog::class));

        return $log;
    }

    #[Bean]
    public function requestLogFormatter(#[Inject('application.web')] array $config): RequestLogFormatterInterface
    {
        return new RequestLogTextFormatter(extra: !empty($config['log_post_body']) ? ['query', 'body'] : ['query']);
    }

    #[Bean]
    public function slimApp(ContainerInterface $container): App
    {
        return SlimAppFactory::create($container);
    }

    #[Bean]
    public function requestHandler(App $app, ContainerInterface $container, AttributeProcessorInterface $annotationProcessor): RequestHandlerInterface
    {
        $annotationProcessor->process();
        $middlewares = $container->get('application.web.middleware');
        if (is_array($middlewares)) {
            // 数组前面的先运行
            ksort($middlewares);
            $middlewares = array_reverse($middlewares);
            foreach ($middlewares as $middleware) {
                $app->add(is_string($middleware) ? $container->get($middleware) : $middleware);
            }
        }

        return $app;
    }

    #[Bean]
    public function annotationProcessor(
        ContainerInterface $container,
        App $app,
        #[Inject('application.web')] ?array $options): AttributeProcessorInterface
    {
        return new AttributeProcessor(
            $container,
            $app,
            $options['context_url'] ?? null,
            $options['namespace'] ?? null
        );
    }

    #[Bean]
    public function errorMiddleware(
        ContainerInterface $container,
        App $app,
        LoggerFactoryInterface $loggerFactory,
        ErrorHandlerInterface $defaultErrorHandler,
        #[Inject('application.web.error')] ?array $options): ErrorMiddleware
    {
        $errorMiddleware = new ErrorMiddleware(
            $app->getCallableResolver(),
            $app->getResponseFactory(),
            (bool) ($options['display'] ?? $options['display_error'] ?? false),
            (bool) ($options['logging'] ?? $options['log_error'] ?? true),
            false,
            $loggerFactory->create(ErrorMiddleware::class)
        );
        $errorHandlers = $options['handlers'] ?? [];
        foreach ($errorHandlers as $type => $errorHandler) {
            $errorMiddleware->setErrorHandler($type, $container->get($errorHandler));
        }
        foreach (ComponentCollection::getComponents(attribute\ErrorHandler::class) as $attribute) {
            /** @var attribute\ErrorHandler $attribute */
            $errorHandler = $container->get($attribute->getComponentId());
            foreach ($attribute->getExceptions() as $type) {
                $errorMiddleware->setErrorHandler($type, $errorHandler);
            }
        }
        $errorMiddleware->setDefaultErrorHandler($defaultErrorHandler);

        return $errorMiddleware;
    }

    #[Bean('webErrorRenderers')]
    public function webErrorRenderers(ContainerInterface $container): array
    {
        return [
            MediaType::APPLICATION_JSON => $container->get(JsonErrorRenderer::class),
            MediaType::APPLICATION_XML => $container->get(XmlErrorRenderer::class),
            MediaType::TEXT_XML => $container->get(XmlErrorRenderer::class),
            MediaType::TEXT_HTML => $container->get(HtmlErrorRenderer::class),
            MediaType::TEXT_PLAIN => $container->get(PlainTextErrorRenderer::class),
        ];
    }

    #[Bean]
    public function defaultErrorHandler(
        ResponseFactoryInterface $responseFactory,
        LoggerFactoryInterface $loggerFactory,
        ErrorRendererInterface $logErrorRenderer,
        #[Inject('webErrorRenderers')] array $errorRenderers,
        #[Inject('application.web.error.include_stacktrace')] ?string $includeStacktrace): ErrorHandlerInterface
    {
        $logger = $loggerFactory->create(ErrorHandler::class);

        return new ErrorHandler(
            $responseFactory, $errorRenderers, $logErrorRenderer, $logger,
            includeStacktraceStrategy: $includeStacktrace ?? IncludeStacktrace::NEVER);
    }

    #[Bean]
    #[ConditionalOnProperty('application.web.view.engine', hasValue: 'php', matchIfMissing: true)]
    public function phpView(#[Inject('application.web.view')] ?array $options): ViewInterface
    {
        return new PhpView(rtrim($options['path'] ?? getcwd(), '/'), $options['extension'] ?? '.php');
    }

    #[Bean]
    #[AllConditions(
        new ConditionalOnClass(Twig::class),
        new ConditionalOnProperty('application.web.view.engine', hasValue: 'twig', matchIfMissing: true)
    )]
    public function twigView(LoaderInterface $twigLoader, #[Inject('application.web.view')] ?array $options): ViewInterface
    {
        $twig = new Twig($twigLoader, $options ?? []);
        if (!empty($options['globals'])) {
            foreach ($options['globals'] as $name => $value) {
                $twig->addGlobal($name, $value);
            }
        }

        return new TwigView($twig, $options['extension'] ?? null);
    }

    #[Bean]
    #[ConditionalOnClass(Twig::class)]
    public function twigLoader(#[Inject('application.web.view')] ?array $options): LoaderInterface
    {
        $loader = new FilesystemLoader($options['path'] ?? getcwd());

        if (!empty($options['alias'])) {
            foreach ($options['alias'] as $alias => $path) {
                $loader->addPath($path, $alias);
            }
        }

        return $loader;
    }

    #[Bean]
    public function sessionFactory(
        CacheItemPoolInterface $cache,
        #[Inject('application.web.session')] ?array $sessionConfig): SessionFactoryInterface
    {
        $sessionConfig = ($sessionConfig ?? []) + [
                'auto_start' => true,
            ];

        return new CacheStoreSessionFactory(new CacheSessionHandler($cache, $sessionConfig), $sessionConfig);
    }

    #[Bean]
    public function loginUrlBuilder(
        #[Inject('application.web.login.url')] ?string $loginUrl,
        #[Inject('application.web.login.redirect_param')] ?string $redirectParam): LoginUrlBuilderInterface
    {
        return new DefaultLoginUrlBuilder($loginUrl ?? '/login', $redirectParam ?? 'redirect');
    }

    /**
     * @param Properties $config
     */
    private function addMiddleware(Properties $config): void
    {
        $middlewares = $config->get('application.web.middleware', []);
        if ($config->getBool('application.web.health_check_enabled')
            && !in_array(HealthyStatus::class, $middlewares, true)) {
            $middlewares[] = HealthyStatus::class;
        }
        if (!in_array(ErrorMiddleware::class, $middlewares, true)) {
            $middlewares[] = ErrorMiddleware::class;
        }
        if (!in_array(AccessLog::class, $middlewares, true)) {
            if (in_array(HealthyStatus::class, $middlewares, true)) {
                $pos = array_search(HealthyStatus::class, $middlewares, true);
                array_splice($middlewares, $pos + 1, 0, AccessLog::class);
            } else {
                $pos = array_search(ErrorMiddleware::class, $middlewares, true);
                array_splice($middlewares, $pos, 0, AccessLog::class);
            }
        }
        if (!in_array(BodyParsingMiddleware::class, $middlewares, true)) {
            $middlewares[] = BodyParsingMiddleware::class;
        }
        if ($config->getBool('application.web.session.enabled')
            && !in_array(Session::class, $middlewares, true)) {
            $middlewares[] = Session::class;
        }
        $config->set('application.web.middleware', $middlewares);
    }
}
