<?php

declare(strict_types=1);

namespace kuiper\web;

use DI\Annotation\Inject;
use function DI\get;
use kuiper\di\annotation\Bean;
use kuiper\di\annotation\ConditionalOnProperty;
use kuiper\di\annotation\Configuration;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\web\handler\DefaultLoginUrlBuilder;
use kuiper\web\handler\ErrorHandler;
use kuiper\web\handler\IncludeStacktrace;
use kuiper\web\handler\LoginUrlBuilderInterface;
use kuiper\web\handler\UnauthorizedErrorHandler;
use kuiper\web\http\MediaType;
use kuiper\web\session\CacheSessionHandler;
use kuiper\web\session\CacheStoreSessionFactory;
use kuiper\web\session\SessionFactoryInterface;
use kuiper\web\view\PhpView;
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
use Slim\Middleware\ErrorMiddleware;

/**
 * @Configuration()
 */
class WebConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        return [
            'webErrorHandlers' => [
                HttpUnauthorizedException::class => get(UnauthorizedErrorHandler::class),
            ],
            'webErrorRenderers' => [
                MediaType::APPLICATION_JSON => get(JsonErrorRenderer::class),
                MediaType::APPLICATION_XML => get(XmlErrorRenderer::class),
                MediaType::TEXT_XML => get(XmlErrorRenderer::class),
                MediaType::TEXT_HTML => get(HtmlErrorRenderer::class),
                MediaType::TEXT_PLAIN => get(PlainTextErrorRenderer::class),
            ],
        ];
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
    public function requestHandler(App $app, ContainerInterface $container): RequestHandlerInterface
    {
        $middlewares = $container->get('application.web.middleware');
        if (is_array($middlewares)) {
            foreach ($middlewares as $middleware) {
                $app->add($container->get($middleware));
            }
        }

        return $app;
    }

    /**
     * @Bean()
     * @Inject({"webConfig": "application.web", "errorHandlers": "webErrorHandlers"})
     */
    public function errorMiddleware(
        App $app,
        LoggerFactoryInterface $loggerFactory,
        ErrorHandlerInterface $defaultErrorHandler,
        array $errorHandlers,
        array $webConfig): ErrorMiddleware
    {
        $errorMiddleware = new ErrorMiddleware($app->getCallableResolver(),
            $app->getResponseFactory(),
            (bool) ($webConfig['display-error'] ?? false),
            (bool) ($webConfig['log-error'] ?? false),
            false,
            $loggerFactory->create(ErrorMiddleware::class));
        foreach ($errorHandlers as $type => $errorHandler) {
            $errorMiddleware->setErrorHandler($type, $errorHandler);
        }
        $errorMiddleware->setDefaultErrorHandler($defaultErrorHandler);

        return $errorMiddleware;
    }

    /**
     * @Bean()
     * @Inject({"errorRenderers": "webErrorRenderers", "includeStacktrace": "application.web.include-stacktrace"})
     */
    public function defaultErrorHandler(
        ResponseFactoryInterface $responseFactory,
        LoggerFactoryInterface $loggerFactory,
        array $errorRenderers,
        ?string $includeStacktrace): ErrorHandlerInterface
    {
        $logger = $loggerFactory->create(ErrorHandler::class);
        $errorHandler = new ErrorHandler($responseFactory, $errorRenderers, $logger);
        $errorHandler->setIncludeStacktraceStrategy($includeStacktrace ?? IncludeStacktrace::NEVER);

        return $errorHandler;
    }

    /**
     * @Bean()
     * @ConditionalOnProperty("application.web.view.path")
     * @Inject({"viewPath": "application.web.view.path"})
     */
    public function phpView(string $viewPath): ViewInterface
    {
        return new PhpView($viewPath);
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
     * @Inject({"loginUrl": "application.web.login-url", "redirectParam": "application.web.redirect-param"})
     */
    public function loginUrlBuilder(?string $loginUrl, ?string $redirectParam): LoginUrlBuilderInterface
    {
        return new DefaultLoginUrlBuilder($loginUrl ?? '/login', $redirectParam ?? 'redirect');
    }
}
