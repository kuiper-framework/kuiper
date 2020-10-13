<?php

declare(strict_types=1);

namespace kuiper\http\client;

use kuiper\annotations\AnnotationReaderInterface;
use kuiper\http\client\annotation\HttpClient;
use kuiper\http\client\annotation\RequestHeader;
use kuiper\http\client\annotation\RequestMapping;
use kuiper\serializer\DocReaderInterface;
use kuiper\serializer\NormalizerInterface;

class MethodMetadataFactory
{
    /**
     * @var AnnotationReaderInterface
     */
    private $annotationReader;

    /**
     * @var DocReaderInterface
     */
    private $docReader;

    /**
     * @var NormalizerInterface
     */
    private $normalizer;

    /**
     * MethodMetadataFactory constructor.
     */
    public function __construct(AnnotationReaderInterface $annotationReader, DocReaderInterface $docReader, NormalizerInterface $normalizer)
    {
        $this->annotationReader = $annotationReader;
        $this->docReader = $docReader;
        $this->normalizer = $normalizer;
    }

    /**
     * @return array [method, uri, options]
     */
    public function create(string $clientClass, string $method, array $args): MethodMetadata
    {
        $reflectionMethod = new \ReflectionMethod($clientClass, $method);
        $methodMetadata = new MethodMetadata($reflectionMethod);
        /** @var HttpClient $classAnnotation */
        $classAnnotation = $this->annotationReader->getClassAnnotation($reflectionMethod->getDeclaringClass(), HttpClient::class);
        $parameters = [];
        foreach ($reflectionMethod->getParameters() as $i => $parameter) {
            $parameters[$parameter->getName()] = [
                'value' => $args[$i] ?? null,
                'parameter' => $parameter,
            ];
        }
        $replacePlaceholder = static function (array $matches) use (&$parameters, $clientClass, $method) {
            if (array_key_exists($matches[1], $parameters)) {
                $value = $parameters[$matches[1]];
                unset($parameters[$matches[1]]);

                return $value['value'];
            } else {
                throw new \InvalidArgumentException("$clientClass::$method should have parameter \${$matches[1]}");
            }
        };
        $placeholderRe = '/\{(\w+)(:.*)?\}/';

        $headers = [];
        foreach (array_merge($this->annotationReader->getClassAnnotations($reflectionMethod->getDeclaringClass()),
            $this->annotationReader->getMethodAnnotations($reflectionMethod)) as $annotation) {
            if ($annotation instanceof RequestHeader) {
                [$name, $value] = explode(':', preg_replace_callback($placeholderRe, $replacePlaceholder, $annotation->value));
                $headers[strtolower(trim($name))] = trim($value);
            }
        }

        /** @var RequestMapping $mapping */
        $mapping = $this->annotationReader->getMethodAnnotation($reflectionMethod, RequestMapping::class);
        $methodMetadata->setHttpMethod($mapping->method);

        $uri = preg_replace_callback($placeholderRe, $replacePlaceholder, $mapping->value);

        if (null !== $classAnnotation) {
            $uri = $classAnnotation->url.$classAnnotation->path.$uri;
        }
        $methodMetadata->setUri($uri);

        $options = [];
        if (!empty($headers)) {
            $options['headers'] = $headers;
        }
        if (!empty($parameters)) {
            $parameterOption = $this->getParameterOption($methodMetadata->getHttpMethod(), $this->getContentType($options['headers']), $parameters);
            $options = array_merge($options, $parameterOption);
        }
        $methodMetadata->setOptions($options);

        $methodMetadata->setReturnType($this->docReader->getReturnType($reflectionMethod));

        return $methodMetadata;
    }

    private function getParameterOption(string $method, string $contentType, array $parameters): array
    {
        $params = [];
        $hasResource = false;
        foreach ($parameters as $name => $parameter) {
            $value = $parameter['value'];
            if ($value instanceof Request) {
                return $value->getOptions();
            }
            if (is_resource($value) || $value instanceof File) {
                $hasResource = true;
            }
            if (is_object($value)) {
                $params = array_merge($params, $this->normalizer->normalize($value));
            } else {
                $params[$name] = $value;
            }
        }
        if ('GET' === $method) {
            return ['query' => $params];
        }

        if (false !== strpos($contentType, 'application/json')) {
            return ['json' => $params];
        }

        if ($hasResource || strpos($contentType, 'multipart/form-data')) {
            $multipart = [];
            foreach ($params as $name => $value) {
                $content = [
                    'name' => $name,
                ];
                if ($value instanceof File) {
                    $content['contents'] = fopen($value->getPath(), 'rb');
                    $content['filename'] = $value->getName();
                } else {
                    $content['contents'] = $value;
                }
                $multipart[] = $content;
            }

            return ['multipart' => $multipart];
        }

        return ['form_params' => $params];
    }

    private function getContentType(array $headers): string
    {
        return $headers['content-type'] ?? '';
    }
}
