<?php

declare(strict_types=1);

namespace kuiper\http\client;

use GuzzleHttp\Exception\RequestException;
use kuiper\serializer\NormalizerInterface;
use Psr\Http\Message\ResponseInterface;

class DefaultResponseParser implements ResponseParser
{
    /**
     * @var NormalizerInterface
     */
    private $normalizer;

    /**
     * DefaultResponseParser constructor.
     */
    public function __construct(NormalizerInterface $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function parse(MethodMetadata $metadata, ResponseInterface $response)
    {
        if ($metadata->hasReturnType()) {
            $contentType = $response->getHeaderLine('content-type');
            if (false !== stripos($contentType, 'application/json')) {
                $data = json_decode((string) $response->getBody(), true);
                if ($metadata->getReturnType()->isUnknown()) {
                    return $data;
                }

                return $this->normalizer->denormalize($data, $metadata->getReturnType());
            }

            throw new \InvalidArgumentException("Response has no content-type, {$metadata->getMethodName()} should not declare return type");
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function handleError(MethodMetadata $metadata, RequestException $e)
    {
        throw $e;
    }
}
