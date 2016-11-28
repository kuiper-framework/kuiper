<?php
namespace kuiper\boot;

abstract class Provider implements ProviderInterface
{
    /**
     * @var Application
     */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function __get($name)
    {
        if (in_array($name, ['settings', 'loader', 'services', 'containerBuilder'])) {
            $method = 'get' . $name;
            return $this->app->$method();
        } else {
            throw new \LogicException("Property '$name' is undefined");
        }
    }

    public function template($expression)
    {
        $settings = $this->app->getSettings();
        $container = $this->app->getContainer();
        return preg_replace_callback('#\{([^\{\}]+)\}#', function(array $matches) use ($settings, $container) {
            return isset($settings[$matches[1]]) ? $settings[$matches[1]] : $container->get($matches[1]);
        }, $expression);
    }
}
