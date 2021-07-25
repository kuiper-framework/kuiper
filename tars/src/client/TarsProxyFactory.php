<?php

declare(strict_types=1);

namespace kuiper\tars\client;

use GuzzleHttp\Psr7\HttpFactory;
use kuiper\annotations\AnnotationReader;
use kuiper\annotations\AnnotationReaderInterface;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\rpc\MiddlewareInterface;
use kuiper\rpc\transporter\LoadBalanceAlgorithm;
use kuiper\rpc\transporter\LoadBalanceHolder;
use kuiper\rpc\transporter\PooledTransporter;
use kuiper\rpc\transporter\ServiceResolverInterface;
use kuiper\rpc\transporter\SwooleCoroutineTcpTransporter;
use kuiper\rpc\transporter\SwooleTcpTransporter;
use kuiper\rpc\transporter\TransporterInterface;
use kuiper\swoole\coroutine\Coroutine;
use kuiper\swoole\pool\PoolFactoryInterface;
use kuiper\tars\annotation\TarsClient;
use kuiper\tars\core\TarsMethodFactory;
use Psr\Log\NullLogger;

class TarsProxyFactory
{
    /**
     * @var AnnotationReaderInterface
     */
    private $annotationReader;
    /**
     * @var ServiceResolverInterface|null
     */
    private $serviceResolver;

    /**
     * @var PoolFactoryInterface|null
     */
    private $poolFactory;

    /**
     * @var LoggerFactoryInterface|null
     */
    private $loggerFactory;

    /**
     * @var MiddlewareInterface[]
     */
    private $middlewares = [];

    /**
     * TarsProxyFactory constructor.
     *
     * @param AnnotationReaderInterface|null $annotationReader
     * @param ServiceResolverInterface|null  $serviceResolver
     */
    public function __construct(?ServiceResolverInterface $serviceResolver = null, ?AnnotationReaderInterface $annotationReader = null)
    {
        $this->serviceResolver = $serviceResolver;
        $this->annotationReader = $annotationReader ?? AnnotationReader::getInstance();
    }

    /**
     * @return AnnotationReaderInterface
     */
    public function getAnnotationReader(): AnnotationReaderInterface
    {
        return $this->annotationReader;
    }

    /**
     * @return ServiceResolverInterface|null
     */
    public function getServiceResolver(): ?ServiceResolverInterface
    {
        return $this->serviceResolver;
    }

    /**
     * @return PoolFactoryInterface|null
     */
    public function getPoolFactory(): ?PoolFactoryInterface
    {
        return $this->poolFactory;
    }

    /**
     * @param PoolFactoryInterface $poolFactory
     */
    public function setPoolFactory(PoolFactoryInterface $poolFactory): void
    {
        $this->poolFactory = $poolFactory;
    }

    /**
     * @return LoggerFactoryInterface|null
     */
    public function getLoggerFactory(): ?LoggerFactoryInterface
    {
        return $this->loggerFactory;
    }

    /**
     * @param LoggerFactoryInterface $loggerFactory
     */
    public function setLoggerFactory(LoggerFactoryInterface $loggerFactory): void
    {
        $this->loggerFactory = $loggerFactory;
    }

    /**
     * @return MiddlewareInterface[]
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * @param MiddlewareInterface[] $middlewares
     */
    public function setMiddlewares(array $middlewares): void
    {
        $this->middlewares = $middlewares;
    }

    public function create(string $className, array $options = [])
    {
        /** @var TarsClient|null $tarsClientAnnotation */
        $class = new \ReflectionClass($className);
        $tarsClientAnnotation = $this->getAnnotationReader()->getClassAnnotation($class, TarsClient::class);
        if (null === $tarsClientAnnotation) {
            throw new \InvalidArgumentException("class $className has no @TarsClient annotation");
        }

        $servantName = $tarsClientAnnotation->value;
        if ($class->isInterface()) {
            $proxyGenerator = new TarsProxyGenerator();
            $proxyClass = $proxyGenerator->generate($className, $options);
            $proxyClass->eval();
            $className = $proxyClass->getClassName();
        }
        $httpFactory = new HttpFactory();
        $transporterFactory = function (int $connId) use ($httpFactory, $className, $servantName, $options): TransporterInterface {
            if (null !== $this->getServiceResolver()) {
                $endpointHolder = new LoadBalanceHolder($this->getServiceResolver(), $servantName, LoadBalanceAlgorithm::ROUND_ROBIN);
            } else {
                $endpointHolder = null;
            }
            if (Coroutine::isEnabled()) {
                $transporter = new SwooleCoroutineTcpTransporter($httpFactory, $endpointHolder, $options);
            } else {
                $transporter = new SwooleTcpTransporter($httpFactory, $endpointHolder, $options);
            }
            $logger = null !== $this->getLoggerFactory()
                ? $this->getLoggerFactory()->create($className)
                : new NullLogger();
            $logger->info("[$className] create connection $connId", ['class' => get_class($transporter)]);
            $transporter->setLogger($logger);

            return $transporter;
        };
        $transporter = null !== $this->getPoolFactory()
            ? new PooledTransporter($this->getPoolFactory()->create($servantName, $transporterFactory))
            : $transporterFactory(0);
        $requestFactory = new TarsRequestFactory($httpFactory, $httpFactory, new TarsMethodFactory());
        $tarsClient = new \kuiper\tars\client\TarsClient($transporter, $requestFactory, new TarsResponseFactory(), $this->getMiddlewares());

        return new $className($tarsClient);
    }
}
