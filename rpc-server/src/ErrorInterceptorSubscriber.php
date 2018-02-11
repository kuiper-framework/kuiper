<?php

namespace kuiper\rpc\server;

use kuiper\annotations\ReaderInterface;
use kuiper\di\ContainerAwareInterface;
use kuiper\di\ContainerAwareTrait;
use kuiper\di\event\Events;
use kuiper\di\event\ResolveEvent;
use kuiper\rpc\server\annotation\ErrorInterceptor;
use ProxyManager\Factory\AccessInterceptorValueHolderFactory as Factory;
use ProxyManager\Proxy\ValueHolderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Catch exception.
 *
 * Class ErrorInterceptorSubscriber
 */
class ErrorInterceptorSubscriber implements EventSubscriberInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var ReaderInterface
     */
    private $annotationReader;

    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var ErrorInterceptor[]
     */
    private $errorInterceptors;

    /**
     * @var \Closure
     */
    private $interceptor;

    public static function getSubscribedEvents()
    {
        return [
            Events::AFTER_RESOLVE => 'afterResolve',
        ];
    }

    public function __construct(ReaderInterface $reader)
    {
        $this->annotationReader = $reader;
        $this->interceptor = function (/* @noinspection PhpUnusedParameterInspection */
            $proxy, $instance, $method, $params, &$returnEarly) {
            $returnEarly = true;
            try {
                return call_user_func_array([$instance, $method], $params);
            } catch (\Exception $e) {
                $class = get_class($instance);
                if (isset($this->errorInterceptors[$class])) {
                    $errorInterceptor = $this->errorInterceptors[$class]->class;
                    $error = new ErrorContext($instance, $method, $params, $e);

                    return $this->container->get($errorInterceptor)->handle($error);
                } else {
                    throw $e;
                }
            }
        };
    }

    public function afterResolve(ResolveEvent $event)
    {
        $value = $event->getValue();
        if (!is_object($value)) {
            return;
        }
        $realValue = $value;
        while ($realValue instanceof ValueHolderInterface) {
            $realValue = $realValue->getWrappedValueHolderValue();
        }
        $class = new \ReflectionClass($realValue);
        /** @var ErrorInterceptor $annotation */
        $annotation = $this->annotationReader->getClassAnnotation($class, ErrorInterceptor::class);
        if (!$annotation) {
            return;
        }
        if (!is_subclass_of($annotation->class, ErrorInterceptorInterface::class)) {
            trigger_error(sprintf("Parameter 'class' of @ErrorInterceptor on %s should implements %s",
                $class->getName(), ErrorInterceptorInterface::class));

            return;
        }
        $this->errorInterceptors[$class->getName()] = $annotation;
        $proxy = $this->getFactory()->createProxy($value);
        foreach ($class->getMethods() as $method) {
            if ($method->isPublic() && !$method->isStatic()) {
                $proxy->setMethodPrefixInterceptor($method->getName(), $this->interceptor);
            }
        }
        $event->setValue($proxy);
    }

    public function getFactory()
    {
        if ($this->factory === null) {
            $this->factory = new Factory();
        }

        return $this->factory;
    }

    public function setFactory(Factory $factory)
    {
        $this->factory = $factory;

        return $this;
    }
}
