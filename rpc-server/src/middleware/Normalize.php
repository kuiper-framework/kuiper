<?php

namespace kuiper\rpc\server\middleware;

use InvalidArgumentException;
use kuiper\annotations\DocReaderInterface;
use kuiper\rpc\MiddlewareInterface;
use kuiper\rpc\RequestInterface;
use kuiper\rpc\ResponseInterface;
use kuiper\rpc\server\ServiceResolverInterface;
use kuiper\serializer\NormalizerInterface;
use ReflectionClass;

class Normalize implements MiddlewareInterface
{
    /**
     * @var ServiceResolverInterface
     */
    private $resolver;

    /**
     * @var NormalizerInterface
     */
    private $normalizer;

    /**
     * @var DocReaderInterface
     */
    private $docReader;

    /**
     * @var array
     */
    private $cache;

    public function __construct(ServiceResolverInterface $resolver, NormalizerInterface $normalizer, DocReaderInterface $docReader)
    {
        $this->resolver = $resolver;
        $this->normalizer = $normalizer;
        $this->docReader = $docReader;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        $method = $this->resolver->resolve($request->getMethod());
        $callable = $method->getCallable();
        if (is_array($callable)) {
            $parameters = $this->normalizeParameters($callable, $request->getParameters());
            $request = $request->withParameters($parameters);
        }
        /** @var ResponseInterface $response */
        $response = $next($request, $response);
        if (0 == $response->getBody()->getSize()) {
            $result = $response->getResult();
            if (is_array($result) || is_object($result)) {
                return $response->withResult($this->normalizer->normalize($result));
            }
        }

        return $response;
    }

    /**
     * @param callable $callable
     * @param array    $parameters
     *
     * @return mixed
     */
    protected function normalizeParameters($callable, array $parameters)
    {
        if (empty($parameters)) {
            return $parameters;
        }
        if (!is_array($callable)) {
            return $parameters;
        }
        $types = $this->getParameterTypes($callable);
        if (count($parameters) > ($nofParams = count($types))) {
            throw new InvalidArgumentException(sprintf(
                'Parameters count not match, expects %d params, got %d', $nofParams, count($parameters)
            ));
        }
        $args = [];
        foreach ($parameters as $i => $value) {
            $index = 0;
            if (isset($types[$i])) {
                $index = $types[$i]['index'];
                $type = $types[$i]['type'];
            } else {
                foreach ($types as $paramType) {
                    if ($paramType['name'] == $i) {
                        $index = $paramType['index'];
                        $type = $paramType['type'];
                        break;
                    }
                }
                if (!isset($type)) {
                    throw new InvalidArgumentException("Unknown parameter '$i'");
                }
            }
            $value = $this->normalizer->denormalize($value, $type);
            $args[$index] = $value;
        }
        ksort($args, SORT_NUMERIC);

        return $args;
    }

    protected function callableToString($callable)
    {
        return sprintf('%s::%s', is_string($callable[0]) ? $callable[0] : get_class($callable[0]), $callable[1]);
    }

    protected function getParameterTypes($callable)
    {
        $key = $this->callableToString($callable);
        if (!isset($this->cache[$key])) {
            $class = new ReflectionClass($callable[0]);
            $method = $class->getMethod($callable[1]);
            $parameters = [];
            $paramTypes = $this->docReader->getParameterTypes($method);
            foreach ($method->getParameters() as $i => $parameter) {
                $parameters[$i] = [
                    'index' => $i,
                    'name' => $parameter->getName(),
                    'type' => $paramTypes[$parameter->getName()],
                ];
            }
            $this->cache[$key] = $parameters;
        }

        return $this->cache[$key];
    }
}
