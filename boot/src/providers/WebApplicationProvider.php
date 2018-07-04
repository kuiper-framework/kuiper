<?php

namespace kuiper\boot\providers;

use kuiper\boot\Events;
use kuiper\boot\Provider;
use kuiper\di;
use kuiper\web\ApplicationInterface;
use kuiper\web\FastRouteUrlResolver;
use kuiper\web\MicroApplication;
use kuiper\web\middlewares\Session as SessionMiddleware;
use kuiper\web\RouteCollector;
use kuiper\web\RouteRegistrar;
use kuiper\web\RouteRegistrarInterface;
use kuiper\web\security\Acl;
use kuiper\web\security\AclInterface;
use kuiper\web\security\Auth;
use kuiper\web\security\AuthInterface;
use kuiper\web\security\PermissionChecker;
use kuiper\web\security\PermissionCheckerInterface;
use kuiper\web\session\CacheSessionHandler;
use kuiper\web\session\FlashInterface;
use kuiper\web\session\FlashSession;
use kuiper\web\session\ManagedSession;
use kuiper\web\session\ManagedSessionInterface;
use kuiper\web\session\Session;
use kuiper\web\session\SessionInterface;
use kuiper\web\UrlResolverInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\EventDispatcher\GenericEvent as Event;
use Zend\Diactoros\ServerRequestFactory;

/**
 * Provides kuiper\web\ApplicationInterface.
 *
 * add entry in config/app.php :
 *  'base_path' => <base_path>,
 *  'base_uri' => <base_uri>,
 *  'session' => [
 *  ],
 *  'routes' => [
 *  ]
 *
 * Class WebApplicationProvider
 */
class WebApplicationProvider extends Provider
{
    public function register()
    {
        $settings = $this->settings;
        $this->services->addDefinitions([
            ApplicationInterface::class => di\factory([$this, 'provideWebApplication']),
            RouteRegistrarInterface::class => di\factory([$this, 'provideRouteRegistrar']),
            UrlResolverInterface::class => di\object(FastRouteUrlResolver::class)
            ->constructor(di\params([
                'baseUri' => $settings['app.base_uri'],
            ])),
            ServerRequestInterface::class => di\factory([ServerRequestFactory::class, 'fromGlobals']),
        ]);
        if ($settings['app.session']) {
            $this->services->addDefinitions([
                \SessionHandlerInterface::class => di\object(CacheSessionHandler::class)
                ->constructor(di\params([
                    'options' => $settings['app.session'],
                ])),
                FlashInterface::class => di\object(FlashSession::class),
                AuthInterface::class => di\object(Auth::class),
                SessionInterface::class => di\factory([$this, 'provideSession']),
                PermissionCheckerInterface::class => di\object(PermissionChecker::class),
                AclInterface::class => di\factory([$this, 'provideAcl']),
            ]);
        }
    }

    public function provideSession()
    {
        $conf = $this->settings['app.session'];
        if (isset($conf['built-in']) && $conf['built-in'] === false) {
            $session = new ManagedSession($this->app->get(\SessionHandlerInterface::class), $conf);
        } else {
            if (!isset($conf['handler']) || $conf['handler'] != 'file') {
                session_set_save_handler($this->app->get(\SessionHandlerInterface::class), true);
            }

            $session = new Session();
            $session->start();
        }

        return $session;
    }

    public function provideRouteRegistrar()
    {
        $routeRegistrar = new RouteRegistrar();
        $routeConfig = $this->settings['app.routes'];
        if ($routeConfig) {
            $this->addRoutesByAnnotation($routeRegistrar, $routeConfig);
        } else {
            $this->addRoutesByFile($routeRegistrar);
        }

        return $routeRegistrar;
    }

    public function provideWebApplication()
    {
        $app = new MicroApplication($this->app->getContainer());

        // auto add SessionMiddleware
        $conf = $this->settings['app.session'];
        if (isset($conf['built-in']) && $conf['built-in'] === false) {
            $session = $this->app->get(SessionInterface::class);
            if ($session instanceof ManagedSessionInterface) {
                $app->add(new SessionMiddleware($session), 'before:start', 'session');
            }
        }
        $this->addMiddlewares($app);

        $this->app->getEventDispatcher()->dispatch(Events::BOOT_WEB_APPLICATION, new Event($app));

        return $app;
    }

    private function addMiddlewares(ApplicationInterface $app)
    {
        $middlewares = $this->settings['app.middlewares'];
        if (is_array($middlewares) && !empty($middlewares)) {
            $container = $this->app->getContainer();
            foreach ($middlewares as $middleware) {
                $middleware = (array) $middleware;
                $app->add(
                    $container->get($middleware[0]),
                    $position = isset($middleware[1]) ? $middleware[1] : ApplicationInterface::ROUTE,
                    $id = isset($middleware[2]) ? $middleware[2] : null
                );
            }
        }
    }

    /**
     * @param RouteRegistrarInterface $app
     * @param array                   $routeConfig
     */
    private function addRoutesByAnnotation(RouteRegistrarInterface $app, $routeConfig)
    {
        /** @var RouteCollector $collector */
        $collector = $this->app->get(RouteCollector::class);
        $collector->setRouteRegistrar($app);
        foreach ($routeConfig as $namespace => $matcher) {
            if ($matcher) {
                $app->group($matcher, function () use ($collector, $namespace) {
                    $collector->addNamespace($namespace);
                });
            } else {
                $collector->addNamespace($namespace);
            }
        }
    }

    /**
     * @param RouteRegistrarInterface $app
     */
    private function addRoutesByFile(RouteRegistrarInterface $app)
    {
        foreach ($this->app->getModules() as $module) {
            if ($module->getBasePath()) {
                $file = $module->getBasePath().'/routes/web.php';
                if (file_exists($file)) {
                    if ($namespace = $module->getNamespace()) {
                        $app->group([
                            'namespace' => $namespace.'\\controllers',
                        ], function () use ($app, $file) {
                            /** @noinspection PhpIncludeInspection */
                            require_once $file;
                        });
                    } else {
                        /** @noinspection PhpIncludeInspection */
                        require_once $file;
                    }
                }
            }
        }
        if ($this->settings['app.base_path'] && file_exists($file = $this->settings['app.base_path'].'/routes/web.php')) {
            /** @noinspection PhpIncludeInspection */
            require_once $file;
        }
    }

    public function provideAcl()
    {
        $acl = new Acl();
        foreach ($this->settings['app.acl'] as $role => $resources) {
            foreach ($resources as $resource) {
                if (strpos($resource, ':') === false) {
                    throw new \InvalidArgumentException("Resource '$resource' for role '$role' is invalid, must be 'category:action'");
                }
                list($category, $action) = explode(':', $resource);
                $acl->allow($role, $category, $action);
            }
        }

        return $acl;
    }
}
