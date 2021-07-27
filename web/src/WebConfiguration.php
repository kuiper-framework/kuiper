<?php

declare(strict_types=1);

namespace kuiper\web;

use DI\Annotation\Inject;
use kuiper\annotations\AnnotationReaderInterface;
use kuiper\di\annotation\Bean;
use kuiper\di\annotation\ConditionalOnClass;
use kuiper\di\annotation\ConditionalOnMissingClass;
use kuiper\di\annotation\Configuration;
use kuiper\di\ComponentCollection;
use kuiper\logger\LoggerFactoryInterface;
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

/**
 * @Configuration()
 * @ConditionalOnClass(App::class)
 */
class WebConfiguration
{
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
                $app->add($container->get($middleware));
            }
        }

        return $app;
    }

    /**
     * @Bean()
     * @Inject({"contextUrl": "application.web.context-url", "namespace": "application.web.namespace"})
     */
    public function annotationProcessor(ContainerInterface $container, AnnotationReaderInterface $annotationReader, App $app, ?string $contextUrl, ?string $namespace): AnnotationProcessorInterface
    {
        return new AnnotationProcessor($container, $annotationReader, $app, $contextUrl, $namespace);
    }

    /**
     * @Bean()
     * @Inject({"errorConfig": "application.web.error"})
     */
    public function errorMiddleware(
        ContainerInterface $container,
        App $app,
        LoggerFactoryInterface $loggerFactory,
        ErrorHandlerInterface $defaultErrorHandler,
        ?array $errorConfig): ErrorMiddleware
    {
        $errorMiddleware = new ErrorMiddleware($app->getCallableResolver(),
            $app->getResponseFactory(),
            (bool) ($errorConfig['display-error'] ?? false),
            (bool) ($errorConfig['log-error'] ?? true),
            false,
            $loggerFactory->create(ErrorMiddleware::class));
        $errorHandlers = ($errorConfig['handlers'] ?? []) + [
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
     *     "includeStacktrace": "application.web.error.include-stacktrace"
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
     */
    public function logErrorRenderer(): ErrorRendererInterface
    {
        return new LogErrorRenderer();
    }

    /**
     * @Bean()
     * @Inject({"options": "application.web.view"})
     * @ConditionalOnMissingClass(Twig::class)
     */
    public function phpView(?array $options): ViewInterface
    {
        return new PhpView($options['path'] ?? getcwd(), $options['extension'] ?? '.php');
    }

    /**
     * @Bean()
     * @Inject({"options": "application.web.view"})
     * @ConditionalOnClass(Twig::class)
     */
    public function twigView(LoaderInterface $twigLoader, ?array $options): ViewInterface
    {
        $twig = new Twig($twigLoader, $options);
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
     * @Inject({"loginUrl": "application.web.login.url", "redirectParam": "application.web.login.redirect-param"})
     */
    public function loginUrlBuilder(?string $loginUrl, ?string $redirectParam): LoginUrlBuilderInterface
    {
        return new DefaultLoginUrlBuilder($loginUrl ?? '/login', $redirectParam ?? 'redirect');
    }
}
