<?php
namespace kuiper\rpc\server;

use kuiper\rpc\server\request\RequestInterface;
use kuiper\rpc\server\request\JsonRequest;
use kuiper\rpc\server\response\Response;
use kuiper\rpc\server\response\Error;
use kuiper\rpc\server\response\JsonResponse;
use kuiper\rpc\server\exception\MalformedJsonException;
use kuiper\annotations\DocReaderInterface;
use kuiper\serializer\JsonSerializerInterface as SerializerInterface;
use kuiper\serializer\NormalizerInterface;
use Interop\Container\ContainerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use InvalidArgumentException;
use ReflectionClass;
use Exception;
use Throwable;

class JsonServer implements ServerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array
     */
    private $methods;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var MethodFactory
     */
    private $methodFactory;
    
    public function __construct(
        ContainerInterface $container,
        SerializerInterface $serializer = null,
        NormalizerInterface $normalizer = null,
        DocReaderInterface $docReader = null
    ) {
        $this->container = $container;
        $this->serializer = $serializer ?: $this->container->get(SerializerInterface::class);
        if ($normalizer === null) {
            $normalizer = $this->serializer instanceof NormalizerInterface
                        ? $this->serializer
                        : $this->container->get(NormalizerInterface::class);
        }
        if ($docReader === null) {
            $docReader = $this->container->get(DocReaderInterface::class);
        }
        $this->methodFactory = new MethodFactory($normalizer, $docReader);
    }

    /**
     * @inheritDoc
     */
    public function add($service, $name = null)
    {
        if ($name === null) {
            $name = is_object($service) ? get_class($service) : $service;
        }
        $class = new ReflectionClass($service);
        if (!is_object($service)) {
            $service = $this->container->get($service);
        }
        foreach ($class->getMethods() as $method) {
            if (!$method->isPublic() || $method->isStatic()) {
                continue;
            }
            $this->methods[$name.'.'.$method->getName()] = $this->methodFactory->create($service, $method);
        }
    }

    /**
     * @inheritDoc
     */
    public function handle(RequestInterface $rawRequest)
    {
        $request = new JsonRequest;
        $response = new JsonResponse($this->serializer);
        try {
            $request->loadJson($rawRequest->getBody());
            if (isset($this->methods[$request->getMethod()])) {
                $method = $this->methods[$request->getMethod()];
                try {
                    $result = $method->call($request->getParams());
                    $response->setResult($result);
                } catch (InvalidArgumentException $e) {
                    $this->fault($response, 'Invalid params', Error::ERROR_INVALID_PARAMS, $e);
                } catch (Exception $e) {
                    $this->fault($response, $e->getMessage(), $e->getCode(), $e);
                } catch (Throwable $e) {
                    $this->fault($response, $e->getMessage(), $e->getCode(), $e);
                }
            } else {
                $this->fault($response, sprintf('Method %s not found', $request->getMethod()), Error::ERROR_INVALID_METHOD);
            }
        } catch (MalformedJsonException $e) {
            $this->fault($response, 'Parse error', Error::ERROR_PARSE);
        } catch (InvalidArgumentException $e) {
            $this->fault($response, 'Invalid Request', Error::ERROR_INVALID_REQUEST, $e);
        }
        if (null !== ($id = $request->getId())) {
            $response->setId($id);
        }
        $response->setVersion($request->getVersion());
        $rawResponse = new Response;
        if (!$response->isError() && (null === $response->getId())) {
            return $rawResponse;
        }
        $rawResponse->setBody($response->toJson());
        return $rawResponse;
    }

    /**
     * Indicate fault response.
     *
     * @param  string $fault
     * @param  int $code
     * @param  mixed $data
     * @return Error
     */
    private function fault($response, $fault = null, $code = 404, $data = null)
    {
        if (($code >= 0 && $data instanceof \Exception) || (class_exists('Error') && $data instanceof \Error)) {
            $this->logger && $this->logger->error(sprintf("[JsonServer] Uncaught exception %s: %s", get_class($data), $data->getMessage()), ['trace' => $data->getTraceAsString()]);
        }
        $error = new Error($fault, $code, $data);
        $response->setError($error);
    }

    protected function getContainer()
    {
        return $this->container;
    }

    protected function getSerializer()
    {
        return $this->serializer;
    }
}
