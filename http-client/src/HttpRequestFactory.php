<?php

declare(strict_types=1);

namespace kuiper\http\client;

use GuzzleHttp\Psr7;
use GuzzleHttp\Utils;
use kuiper\annotations\AnnotationReaderInterface;
use kuiper\http\client\annotation\HttpClient;
use kuiper\http\client\annotation\RequestHeader;
use kuiper\http\client\annotation\RequestMapping;
use kuiper\http\client\request\File;
use kuiper\http\client\request\Request;
use kuiper\rpc\client\ProxyGenerator;
use kuiper\rpc\client\RequestFactoryInterface;
use kuiper\rpc\InvokingMethod;
use kuiper\rpc\RequestInterface;
use kuiper\rpc\RpcRequest;
use kuiper\serializer\NormalizerInterface;
use Psr\Http\Message\RequestInterface as HttpRequestInterface;

class HttpRequestFactory implements RequestFactoryInterface
{
    /**
     * @var AnnotationReaderInterface
     */
    private $annotationReader;

    /**
     * @var NormalizerInterface
     */
    private $normalizer;

    public function __construct(AnnotationReaderInterface $annotationReader, NormalizerInterface $normalizer)
    {
        $this->annotationReader = $annotationReader;
        $this->normalizer = $normalizer;
    }

    public function createRequest(object $proxy, string $method, array $args): RequestInterface
    {
        $invokingMethod = new InvokingMethod($proxy, $method, $args);
        $reflectionMethod = new \ReflectionMethod(ProxyGenerator::getInterfaceName($invokingMethod->getTargetClass()), $method);
        /** @var HttpClient|null $classAnnotation */
        $classAnnotation = $this->annotationReader->getClassAnnotation($reflectionMethod->getDeclaringClass(), HttpClient::class);
        $parameters = [];
        foreach ($reflectionMethod->getParameters() as $i => $parameter) {
            $parameters[$parameter->getName()] = [
                'value' => $args[$i] ?? null,
                'parameter' => $parameter,
            ];
        }
        $replacePlaceholder = static function (array $matches) use (&$parameters, $invokingMethod) {
            if (array_key_exists($matches[1], $parameters)) {
                $value = $parameters[$matches[1]];
                unset($parameters[$matches[1]]);

                return $value['value'];
            } else {
                throw new \InvalidArgumentException($invokingMethod->getFullMethodName()." should have parameter \${$matches[1]}");
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

        $uri = preg_replace_callback($placeholderRe, $replacePlaceholder, $mapping->value);

        if (null !== $classAnnotation) {
            $uri = $classAnnotation->url.$classAnnotation->path.$uri;
        }
        $request = new Psr7\Request($method, $uri, $headers);
        if (!empty($parameters)) {
            $options = $this->getRequestOptions($request, $parameters);
            $request = $this->applyOptions($request, $options);
        }

        return new RpcRequest($request, $invokingMethod);
    }

    private function getRequestOptions(HttpRequestInterface $request, array $parameters): array
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
        if ('GET' === $request->getMethod()) {
            return ['query' => $params];
        }

        if (false !== strpos($request->getHeaderLine('content-type'), 'application/json')) {
            return ['json' => $params];
        }

        if ($hasResource || strpos($request->getHeaderLine('content-type'), 'multipart/form-data')) {
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

    /**
     * Copy from \Guzzle\Client.
     */
    private function applyOptions(HttpRequestInterface $request, array $options): HttpRequestInterface
    {
        $modify = [
            'set_headers' => [],
        ];

        if (isset($options['headers'])) {
            $modify['set_headers'] = $options['headers'];
            unset($options['headers']);
        }

        if (isset($options['form_params'])) {
            if (isset($options['multipart'])) {
                throw new \InvalidArgumentException('You cannot use '.'form_params and multipart at the same time. Use the '.'form_params option if you want to send application/'.'x-www-form-urlencoded requests, and the multipart '.'option to send multipart/form-data requests.');
            }
            $options['body'] = \http_build_query($options['form_params'], '', '&');
            unset($options['form_params']);
            // Ensure that we don't have the header in different case and set the new value.
            $options['_conditional'] = Psr7\Utils::caselessRemove(['Content-Type'], $options['_conditional']);
            $options['_conditional']['Content-Type'] = 'application/x-www-form-urlencoded';
        }

        if (isset($options['multipart'])) {
            $options['body'] = new Psr7\MultipartStream($options['multipart']);
            unset($options['multipart']);
        }

        if (isset($options['json'])) {
            $options['body'] = Utils::jsonEncode($options['json']);
            unset($options['json']);
            // Ensure that we don't have the header in different case and set the new value.
            $options['_conditional'] = Psr7\Utils::caselessRemove(['Content-Type'], $options['_conditional']);
            $options['_conditional']['Content-Type'] = 'application/json';
        }

        if (!empty($options['decode_content'])
            && true !== $options['decode_content']
        ) {
            // Ensure that we don't have the header in different case and set the new value.
            $options['_conditional'] = Psr7\Utils::caselessRemove(['Accept-Encoding'], $options['_conditional']);
            $modify['set_headers']['Accept-Encoding'] = $options['decode_content'];
        }

        if (isset($options['body'])) {
            if (\is_array($options['body'])) {
                throw new \InvalidArgumentException('Passing in the "body" request '.'option as an array to send a request is not supported. '.'Please use the "form_params" request option to send a '.'application/x-www-form-urlencoded request, or the "multipart" '.'request option to send a multipart/form-data request.');
            }
            $modify['body'] = Psr7\Utils::streamFor($options['body']);
            unset($options['body']);
        }

        if (!empty($options['auth']) && \is_array($options['auth'])) {
            $value = $options['auth'];
            $type = isset($value[2]) ? \strtolower($value[2]) : 'basic';
            switch ($type) {
                case 'basic':
                    // Ensure that we don't have the header in different case and set the new value.
                    $modify['set_headers'] = Psr7\Utils::caselessRemove(['Authorization'], $modify['set_headers']);
                    $modify['set_headers']['Authorization'] = 'Basic '
                        .\base64_encode("$value[0]:$value[1]");
                    break;
                case 'digest':
                    // @todo: Do not rely on curl
                    $options['curl'][\CURLOPT_HTTPAUTH] = \CURLAUTH_DIGEST;
                    $options['curl'][\CURLOPT_USERPWD] = "$value[0]:$value[1]";
                    break;
                case 'ntlm':
                    $options['curl'][\CURLOPT_HTTPAUTH] = \CURLAUTH_NTLM;
                    $options['curl'][\CURLOPT_USERPWD] = "$value[0]:$value[1]";
                    break;
            }
        }

        if (isset($options['query'])) {
            $value = $options['query'];
            if (\is_array($value)) {
                $value = \http_build_query($value, '', '&', \PHP_QUERY_RFC3986);
            }
            if (!\is_string($value)) {
                throw new \InvalidArgumentException('query must be a string or array');
            }
            $modify['query'] = $value;
            unset($options['query']);
        }

        // Ensure that sink is not an invalid value.
        if (isset($options['sink'])) {
            // TODO: Add more sink validation?
            if (\is_bool($options['sink'])) {
                throw new \InvalidArgumentException('sink must not be a boolean');
            }
        }

        $request = Psr7\Utils::modifyRequest($request, $modify);
        if ($request->getBody() instanceof Psr7\MultipartStream) {
            // Use a multipart/form-data POST if a Content-Type is not set.
            // Ensure that we don't have the header in different case and set the new value.
            $options['_conditional'] = Psr7\Utils::caselessRemove(['Content-Type'], $options['_conditional']);
            $options['_conditional']['Content-Type'] = 'multipart/form-data; boundary='
                .$request->getBody()->getBoundary();
        }

        // Merge in conditional headers if they are not present.
        if (isset($options['_conditional'])) {
            // Build up the changes so it's in a single clone of the message.
            $modify = [];
            foreach ($options['_conditional'] as $k => $v) {
                if (!$request->hasHeader($k)) {
                    $modify['set_headers'][$k] = $v;
                }
            }
            $request = Psr7\Utils::modifyRequest($request, $modify);
            // Don't pass this internal value along to middleware/handlers.
            unset($options['_conditional']);
        }

        return $request;
    }
}
