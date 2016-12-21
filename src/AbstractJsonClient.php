<?php
namespace kuiper\rpc\client;

use ProxyManager\Factory\RemoteObject\AdapterInterface;
use ReflectionClass;
use InvalidArgumentException;
use Exception;
use kuiper\rpc\client\exception\RpcException;
use kuiper\serializer\NormalizerInterface;
use kuiper\serializer\exception\SerializeException;
use kuiper\annotations\DocReaderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class AbstractJsonClient implements AdapterInterface
{
    /**
     * @var array
     */
    private static $TYPES = [];

    /**
     * @var NormalizerInterface
     */
    private $normalizer;

    /**
     * @var DocReaderInterface
     */
    private $docReader;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var int
     */
    private $id = 1;

    public function __construct(NormalizerInterface $normalizer, DocReaderInterface $docReader, array $map = [])
    {
        $this->normalizer = $normalizer;
        $this->docReader = $docReader;
        $this->map = $map;
    }
    
    /**
     * @inheritDoc
     */
    public function call($wrappedClass, $method, array $params = [])
    {
        $serviceName = $wrappedClass . '.' . $method;

        if (isset($this->map[$serviceName])) {
            $serviceName = $this->map[$serviceName];
        }
        try {
            $requestBody = json_encode($this->normalizer->toArray([
                'method' => $serviceName,
                'id' => $this->id++,
                'params' => $params
            ]));
        } catch (SerializeException $e) {
            throw new InvalidArgumentException($e->getMessage(), 0, $e);
        }
        try {
            $response = $this->sendRequest($requestBody);
        } catch (\Exception $e) {
            if ($this->eventDispatcher) {
                $this->eventDispatcher->dispatch(Events::REQUEST_ERROR, $event = new RequestEvent($this, $requestBody));
                if ($event->hasResponse()) {
                    $response = $event->getResponse();
                } else {
                    throw $e;
                }
            } else {
                throw $e;
            }
        }
        $result = json_decode($response, true);
        if (isset($result['error'])) {
            $this->handleError($result['error']);
        }
        $result = $result['result'];
        if (is_array($result)) {
            $returnType = $this->getReturnType($wrappedClass, $method);
            return $this->normalizer->fromArray($result, $returnType);
        } else {
            return $result;
        }
    }

    /**
     * sends request to rpc server
     *
     * @param string $requestBody
     * @return string
     */
    abstract public function sendRequest($requestBody);

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }

    /**
     * @param string $className
     * @param string $method
     * @return \kuiper\reflection\ReflectionType
     */
    protected function getReturnType($className, $method)
    {
        $key = $className.'.'.$method;
        if (array_key_exists($key, self::$TYPES)) {
            return self::$TYPES[$key];
        }
        $class = new ReflectionClass($className);
        $method = $class->getMethod($method);
        return self::$TYPES[$key] = $this->docReader->getReturnType($method);
    }

    private function handleError($error)
    {
        if (isset($error['data'])) {
            $data = unserialize(base64_decode($error['data']));
            if ($data !== false) {
                if (is_array($data) && isset($data['class'], $data['message'], $data['code'])) {
                    $this->tryThrowException($data);
                } elseif ($data instanceof Exception) {
                    throw $data;
                }
            }
        }
        throw new RpcException($error['message'], $error['code']);
    }

    private function tryThrowException($data)
    {
        $className = $data['class'];
        $class = new ReflectionClass($className);
        $constructor = $class->getConstructor();
        if ($class->isSubClassOf(Exception::class) && $constructor !== null) {
            $params = $constructor->getParameters();
            if (count($params) > 2) {
                $requiredParams = 0;
                foreach ($params as $param) {
                    if (!$param->isOptional()) {
                        $requiredParams++;
                    }
                }
                if ($requiredParams <= 2) {
                    throw new $className($data['message'], $data['code']);
                }
            }
        }
    }
}
