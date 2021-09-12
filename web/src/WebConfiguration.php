<?php

declare(strict_types=1);

namespace kuiper\web;

use DI\Annotation\Inject;
use function DI\autowire;
use kuiper\annotations\AnnotationReaderInterface;
use kuiper\di\annotation\AllConditions;
use kuiper\di\annotation\AnyCondition;
use kuiper\di\annotation\Bean;
use kuiper\di\annotation\ConditionalOnClass;
use kuiper\di\annotation\ConditionalOnMissingClass;
use kuiper\di\annotation\ConditionalOnProperty;
use kuiper\di\ComponentCollection;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\logger\LoggerConfiguration;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\swoole\Application;
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

class WebConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        $this->addAccessLoggerConfig();

        return [
            ErrorRendererInterface::class => autowire(LogErrorRenderer::class),
            AclInterface::class => autowire(Acl::class),
            RequestLogFormatterInterface::class => autowire(LineRequestLogFormatter::class),
        ];
    }

    protected function addAccessLoggerConfig(): void
    {
        $config = Application::getInstance()->getConfig();
        $config->merge([
            'application' => [
                'web' => [
                    'middleware' => [
                        AccessLog::class,
                    ],
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

    /**
     * @Bean
     */
    public function slimApp(ContainerInterface $container): App
    {
        return SlimAppFactory::create($container);
    }

    /**
     * @Bean()
     */
    public function requestHandler(App $app, ContainerInterface $container, AnnotationProcessorInterface $annotationProcessor): RequestHandlerInterface
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

    /**
     * @Bean()
     * @Inject({"options": "application.web"})
     */
    public function annotationProcessor(
        ContainerInterface $container,
        AnnotationReaderInterface $annotationReader,
        App $app,
        ?array $options): AnnotationProcessorInterface
    {
        return new AnnotationProcessor(
            $container,
            $annotationReader,
            $app,
            $options['context_url'] ?? null,
            $options['namespace'] ?? null
        );
    }

    /**
     * @Bean()
     * @Inject({"options": "application.web.error"})
     */
    public function errorMiddleware(
        ContainerInterface $container,
        App $app,
        LoggerFactoryInterface $loggerFactory,
        ErrorHandlerInterface $defaultErrorHandler,
        ?array $options): ErrorMiddleware
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
        foreach (ComponentCollection::getAnnotations(annotation\ErrorHandler::class) as $annotation) {
            /** @var annotation\ErrorHandler $annotation */
            $errorHandler = $container->get($annotation->getComponentId());
            foreach ((array) $annotation->value as $type) {
                $errorMiddleware->setErrorHandler($type, $errorHandler);
            }
        }
        $errorMiddleware->setDefaultErrorHandler($defaultErrorHandler);

        return $errorMiddleware;
    }

    /**
     * @Bean("webErrorRenderers")
     */
    public function webErrorRenders(ContainerInterface $container): array
    {
        return [
            MediaType::APPLICATION_JSON => $container->get(JsonErrorRenderer::class),
            MediaType::APPLICATION_XML => $container->get(XmlErrorRenderer::class),
            MediaType::TEXT_XML => $container->get(XmlErrorRenderer::class),
            MediaType::TEXT_HTML => $container->get(HtmlErrorRenderer::class),
            MediaType::TEXT_PLAIN => $container->get(PlainTextErrorRenderer::class),
        ];
    }

    /**
     * @Bean()
     * @Inject({
     *     "errorRenderers": "webErrorRenderers",
     *     "includeStacktrace": "application.web.error.include_stacktrace"
     * })
     */
    public function defaultErrorHandler(
        ResponseFactoryInterface $responseFactory,
        LoggerFactoryInterface $loggerFactory,
        ErrorRendererInterface $logErrorRenderer,
        array $errorRenderers,
        ?string $includeStacktrace): ErrorHandlerInterface
    {
        $logger = $loggerFactory->create(ErrorHandler::class);
        $errorHandler = new ErrorHandler($responseFactory, $errorRenderers, $logErrorRenderer, $logger);
        $errorHandler->setIncludeStacktraceStrategy($includeStacktrace ?? IncludeStacktrace::NEVER);

        return $errorHandler;
    }

    /**
     * @Bean()
     * @Inject({"options": "application.web.view"})
     * @AnyCondition({
     *     @ConditionalOnMissingClass(Twig::class),
     *     @ConditionalOnProperty("application.web.view.engine", hasValue="php")
     * })
     */
    public function phpView(?array $options): ViewInterface
    {
        return new PhpView($options['path'] ?? getcwd(), $options['extension'] ?? '.php');
    }

    /**
     * @Bean()
     * @Inject({"options": "application.web.view"})
     * @AllConditions({
     *     @ConditionalOnClass(Twig::class),
     *     @ConditionalOnProperty("application.web.view.engine", hasValue="twig", matchIfMissing=true)
     * })
     */
    public function twigView(LoaderInterface $twigLoader, ?array $options): ViewInterface
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
    public function twigLoader(?array $options): LoaderInterface
    {
        $loader = new FilesystemLoader($options['path'] ?? getcwd());

        if (!empty($options['alias'])) {
            foreach ($options['alias'] as $alias => $path) {
                $loader->addPath($path, $alias);
            }
        }

        return $loader;
    }

    /**
     * @Bean()
     * @Inject({"sessionConfig": "application.web.session"})
     */
    public function sessionFactory(CacheItemPoolInterface $cache, ?array $sessionConfig): SessionFactoryInterface
    {
        $sessionConfig = ($sessionConfig ?? []) + [
                'auto_start' => true,
            ];

        return new CacheStoreSessionFactory(new CacheSessionHandler($cache, $sessionConfig), $sessionConfig);
    }

    /**
     * @Bean()
     * @Inject({"loginUrl": "application.web.login.url", "redirectParam": "application.web.login.redirect_param"})
     */
    public function loginUrlBuilder(?string $loginUrl, ?string $redirectParam): LoginUrlBuilderInterface
    {
        return new DefaultLoginUrlBuilder($loginUrl ?? '/login', $redirectParam ?? 'redirect');
    }
}
