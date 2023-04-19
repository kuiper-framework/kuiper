<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\core;

use InvalidArgumentException;
use kuiper\reflection\ReflectionTypeInterface;
use kuiper\serializer\NormalizerInterface;

class BinaryStringNormalizer implements NormalizerInterface
{
    public function normalize(mixed $object): string
    {
        if ($object instanceof BinaryString) {
            return $object->jsonSerialize();
        }

        throw new InvalidArgumentException('Expected BinaryString object, got '.gettype($object));
    }

    public function denormalize(mixed $data, string|ReflectionTypeInterface $className): mixed
    {
        if (!is_string($data)) {
            throw new InvalidArgumentException('Expected string, got '.gettype($data));
        }

        return new BinaryString(base64_decode($data, true));
    }
}
