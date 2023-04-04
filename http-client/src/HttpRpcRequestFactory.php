<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\http\client;

use GuzzleHttp\Psr7;
use GuzzleHttp\Utils;
use InvalidArgumentException;
use kuiper\http\client\attribute\HttpClient;
use kuiper\http\client\attribute\HttpHeader;
use kuiper\http\client\attribute\QueryParam;
use kuiper\http\client\attribute\RequestMapping;
use kuiper\http\client\request\File;
use kuiper\http\client\request\Request;
use kuiper\rpc\client\ProxyGenerator;
use kuiper\rpc\client\RpcRequestFactoryInterface;
use kuiper\rpc\RpcMethodFactoryInterface;
use kuiper\rpc\RpcRequest;
use kuiper\rpc\RpcRequestInterface;
use kuiper\serializer\NormalizerInterface;
use Psr\Http\Message\RequestInterface as HttpRequestInterface;
use ReflectionAttribute;
use ReflectionMethod;
use Reflector;

class HttpRpcRequestFactory implements RpcRequestFactoryInterface
{
    public function __construct(
        private readonly NormalizerInterface $normalizer,
        private readonly RpcMethodFactoryInterface $rpcMethodFactory)
    {
    }

    /**
     * @template T
     *
     * @param Reflector       $reflector
     * @param class-string<T> $name
     * @param int             $flags
     *
     * @return T|null
     */
    private function getAttribute(Reflector $reflector, string $name, int $flags = 0)
    {
        $attributes = $reflector->getAttributes($name, $flags);
        if (count($attributes) > 0) {
            return $attributes[0]->newInstance();
        }

        return null;
    }

    public function createRequest(object $proxy, string $method, array $args): RpcRequestInterface
    {
        $invokingMethod = $this->rpcMethodFactory->create($proxy, $method, $args);
        $reflectionMethod = new ReflectionMethod(ProxyGenerator::getInterfaceName(get_class($proxy)), $method);
        $headers = [];
        $parameters = [];
        foreach ($reflectionMethod->getParameters() as $i => $parameter) {
            $parameters[$parameter->getName()] = [
                'value' => $args[$i] ?? null,
                'parameter' => $parameter,
            ];
            $headerAttribute = $this->getAttribute($parameter, HttpHeader::class);
            if (null !== $headerAttribute) {
                $headers[$headerAttribute->getName()] = $args[$i] ?? null;
            }
        }
        $replacePlaceholder = static function (array $matches) use (&$parameters, $invokingMethod): string {
            if (array_key_exists($matches[1], $parameters)) {
                $value = $parameters[$matches[1]];
                unset($parameters[$matches[1]]);

                return urlencode($value['value']);
            }

            throw new InvalidArgumentException($invokingMethod." should have parameter \${$matches[1]}");
        };
        $placeholderRe = '/\{(\w+)(:.*)?\}/';

        foreach (array_merge($reflectionMethod->getDeclaringClass()->getAttributes(HttpHeader::class),
            $reflectionMethod->getAttributes(HttpHeader::class)) as $attribute) {
            /** @var HttpHeader $headerAttribute */
            $headerAttribute = $attribute->newInstance();
            $headers[strtolower($headerAttribute->getName())] = preg_replace_callback($placeholderRe, $replacePlaceholder, $headerAttribute->getValue());
        }

        $mapping = $this->getAttribute($reflectionMethod, RequestMapping::class, ReflectionAttribute::IS_INSTANCEOF);
        $uri = preg_replace_callback($placeholderRe, $replacePlaceholder, $mapping->getPath());

        $httpClientAttribute = $this->getAttribute($reflectionMethod->getDeclaringClass(), HttpClient::class);
        if (null !== $httpClientAttribute) {
            $uri = $httpClientAttribute->getUrl().$httpClientAttribute->getPath().$uri;
        }
        $request = new Psr7\Request($mapping->getMethod(), $uri, $headers);
        if (!empty($parameters)) {
            $options = $this->getRequestOptions($request, $mapping, $parameters);
            $request = $this->applyOptions($request, $options);
        }

        return new RpcRequest($request, $invokingMethod);
    }

    private function getRequestOptions(HttpRequestInterface &$request, RequestMapping $mapping, array $parameters): array
    {
        $params = [];
        $query = [];
        $hasResource = false;
        foreach ($parameters as $name => $parameter) {
            $value = $parameter['value'];
            if ($value instanceof Request) {
                return $value->getOptions();
            }
            if (is_resource($value) || $value instanceof File) {
                $hasResource = true;
                $params[$name] = $value;
                continue;
            }
            $queryParam = $this->getAttribute($parameter['parameter'], QueryParam::class);
            if (null !== $queryParam) {
                if (is_object($value)) {
                    $query[$queryParam->getName()] = $this->normalizer->normalize($value);
                } else {
                    $query[$queryParam->getName()] = $value;
                }
                continue;
            }
            if (is_object($value)) {
                $params += $this->normalizer->normalize($value);
            } else {
                $params[$name] = $value;
            }
        }
        if ('GET' === $request->getMethod()) {
            return ['query' => array_merge($params, $query)];
        }

        if ($hasResource || str_contains($request->getHeaderLine('content-type'), 'multipart/form-data')) {
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
            $request = $request->withoutHeader('content-type');

            return ['multipart' => $multipart, 'query' => $query];
        }

        if (str_contains($request->getHeaderLine('content-type'), 'application/json')) {
            return ['json' => $params, 'query' => $query];
        }

        return ['form_params' => $params, 'query' => $query];
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
            $options['_conditional'] = $options['headers'];
            unset($options['headers']);
        } else {
            $options['_conditional'] = [];
        }

        if (isset($options['form_params'])) {
            if (isset($options['multipart'])) {
                throw new InvalidArgumentException('You cannot use form_params and multipart at the same time. Use the form_params option if you want to send application/x-www-form-urlencoded requests, and the multipart option to send multipart/form-data requests.');
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
                throw new InvalidArgumentException('Passing in the "body" request option as an array to send a request is not supported. Please use the "form_params" request option to send a application/x-www-form-urlencoded request, or the "multipart" request option to send a multipart/form-data request.');
            }
            $modify['body'] = Psr7\Utils::streamFor($options['body']);
            unset($options['body']);
        }

        if (!empty($options['auth']) && \is_array($options['auth'])) {
            $value = $options['auth'];
            $type = isset($value[2]) ? \strtolower($value[2]) : 'basic';
            // Ensure that we don't have the header in different case and set the new value.
            if ('basic' === $type) {
                $modify['set_headers'] = Psr7\Utils::caselessRemove(['Authorization'], $modify['set_headers']);
                $modify['set_headers']['Authorization'] = 'Basic '
                    .\base64_encode("$value[0]:$value[1]");
            }
        }

        if (isset($options['query'])) {
            $value = $options['query'];
            if (\is_array($value)) {
                $value = \http_build_query($value, '', '&', \PHP_QUERY_RFC3986);
            }
            if (!\is_string($value)) {
                throw new InvalidArgumentException('query must be a string or array');
            }
            $modify['query'] = $value;
            unset($options['query']);
        }

        // Ensure that sink is not an invalid value.
        if (isset($options['sink'])) {
            // TODO: Add more sink validation?
            if (\is_bool($options['sink'])) {
                throw new InvalidArgumentException('sink must not be a boolean');
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
