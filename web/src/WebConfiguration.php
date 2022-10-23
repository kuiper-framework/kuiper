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
use kuiper\di\attribute\AnyCondition;
use kuiper\di\attribute\Bean;
use kuiper\di\attribute\ConditionalOnClass;
use kuiper\di\attribute\ConditionalOnMissingClass;
use kuiper\di\attribute\ConditionalOnProperty;
use kuiper\di\attribute\Configuration;
use kuiper\di\ComponentCollection;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\logger\LoggerConfiguration;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\swoole\Application;
use kuiper\swoole\config\ServerConfiguration;
use kuiper\swoole\event\RequestEvent;
use kuiper\swoole\listener\HttpRequestEventListener;
use kuiper\swoole\logger\LineRequestLogFormatter;
use kuiper\swoole\logger\RequestLogFormatterInterface;
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
use Slim\Middleware\ErrorMiddleware;
use Twig\Environment as Twig;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;

#[Configuration(dependOn: [ServerConfiguration::class])]
class WebConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        $this->addAccessLoggerConfig();
        Application::getInstance()->getConfig()->mergeIfNotExists([
            'application' => [
                'server' => [
                    'http_factory' => 'diactoros',
                ],
            ],
        ]);

        return [
            ErrorRendererInterface::class => autowire(LogErrorRenderer::class),
            AclInterface::class => autowire(Acl::class),
            RequestLogFormatterInterface::class => autowire(LineRequestLogFormatter::class),
        ];
    }

    protected function addAccessLoggerConfig(): void
    {
        $config = Application::getInstance()->getConfig();

        if (!in_array(AccessLog::class, $config->get('application.web.middleware', []), true)) {
            $config->merge([
                'application' => [
                    'web' => [
                        'middleware' => [
                            AccessLog::class,
                        ],
                    ],
                ],
            ]);
        }
        $config->mergeIfNotExists([
            'application' => [
                'listeners' => [
                    RequestEvent::class => HttpRequestEventListener::class,
                ],
                'logging' => [
                    'loggers' => [
                        'AccessLogLogger' => LoggerConfiguration::createAccessLogger(
                            $config->get('application.logging.access_log_file',
                                $config->get('application.logging.path').'/access.log')),
                    ],
                    'logger' => [
                        AccessLog::class => 'AccessLogLogger',
                    ],
                ],
            ],
        ]);
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
            foreach (array_reverse($middlewares) as $middleware) {
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
        $errorMiddleware = new ErrorMiddleware($app->getCallableResolver(),
            $app->getResponseFactory(),
            (bool) ($options['display_error'] ?? false),
            (bool) ($options['log_error'] ?? true),
            false,
            $loggerFactory->create(ErrorMiddleware::class));
        $errorHandlers = ($options['handlers'] ?? []) + [
                RedirectException::class => HttpRedirectHandler::class,
                UnauthorizedException::class => UnauthorizedErrorHandler::class,
                HttpUnauthorizedException::class => UnauthorizedErrorHandler::class,
            ];
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
    #[AnyCondition(
        new ConditionalOnMissingClass(Twig::class),
        new ConditionalOnProperty('application.web.view.engine', hasValue: 'php')
    )]
    public function phpView(#[Inject('application.web.view')] ?array $options): ViewInterface
    {
        return new PhpView(rtrim($options['path'] ?? getcwd(), '/'), $options['extension'] ?? '.php');
    }

    #[Bean]
    #[AnyCondition(
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

    /**
     * @Bean()
     * @Inject({"options": "application.web.view"})
     * @ConditionalOnClass(Twig::class)
     */
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
}
