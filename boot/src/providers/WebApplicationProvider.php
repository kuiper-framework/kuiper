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
use Psr\Cache\CacheItemPoolInterface;
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
                FlashInterface::class => di\object(FlashSession::class),
                AuthInterface::class => di\object(Auth::class),
                SessionInterface::class => di\factory([$this, 'provideSession']),
                PermissionCheckerInterface::class => di\object(PermissionChecker::class),
            ]);
        }
    }

    public function provideSession()
    {
        $conf = $this->settings['app.session'];
        $cache = $this->app->get(CacheItemPoolInterface::class);
        $handler = new CacheSessionHandler($cache, $conf);
        if (isset($conf['built-in']) && $conf['built-in'] === false) {
            $session = new ManagedSession($handler, $conf);
        } else {
            session_set_save_handler($handler, true);

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

        $this->app->getEventDispatcher()->dispatch(Events::BOOT_WEB_APPLICATION, new Event($app));

        return $app;
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
}
