<?php

namespace kuiper\rpc\client\middleware;

use kuiper\annotations\DocReaderInterface;
use kuiper\rpc\MiddlewareInterface;
use kuiper\rpc\RequestInterface;
use kuiper\rpc\ResponseInterface;
use kuiper\serializer\exception\SerializeException;
use kuiper\serializer\NormalizerInterface;

class Normalize implements MiddlewareInterface
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

    public function __construct(NormalizerInterface $normalizer, DocReaderInterface $docReader)
    {
        $this->normalizer = $normalizer;
        $this->docReader = $docReader;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        try {
            $request = $request->withParameters($this->normalizer->toArray($request->getParameters()));
        } catch (SerializeException $e) {
            throw new \InvalidArgumentException($e->getMessage(), 0, $e);
        }
        $response = $next($request, $response);
        $result = $response->getResult();
        if (is_array($result)) {
            $returnType = $this->getReturnType($request->getClass(), $request->getMethod());
            $response = $response->withResult($this->normalizer->fromArray($result, $returnType));
        }

        return $response;
    }

    /**
     * @param string $className
     * @param string $method
     *
     * @return \kuiper\reflection\ReflectionType
     */
    protected function getReturnType($className, $method)
    {
        $key = $className.'.'.$method;
        if (!isset(self::$TYPES[$key])) {
            self::$TYPES[$key] = $this->docReader->getReturnType(new \ReflectionMethod($className, $method));
        }

        return self::$TYPES[$key];
    }
}
