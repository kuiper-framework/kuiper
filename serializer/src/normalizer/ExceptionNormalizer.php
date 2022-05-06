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

namespace kuiper\serializer\normalizer;

use kuiper\reflection\ReflectionTypeInterface;
use kuiper\serializer\NormalizerInterface;
use ReflectionClass;

class ExceptionNormalizer implements NormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function normalize(mixed $object): array|string
    {
        /** @var \Exception $exception */
        if ($exception instanceof \Serializable) {
            $data = $exception;
        } else {
            $data = [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ];
        }

        return base64_encode(serialize($data));
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize(mixed $data, string|ReflectionTypeInterface $className): mixed
    {
        $exception = unserialize(base64_decode($data, true), ['allowed_classes' => true]);
        if (false === $exception) {
            return new \RuntimeException('Bad exception data: '.json_encode($data));
        }
        if ($exception instanceof \Exception) {
            return $exception;
        }
        if (is_array($exception) && isset($exception['class'], $exception['message'], $exception['code'])) {
            $exceptionClass = $exception['class'];
            $class = new ReflectionClass($exceptionClass);
            $constructor = $class->getConstructor();
            if (null !== $constructor && $class->isSubclassOf(\Exception::class)) {
                $params = $constructor->getParameters();
                $paramNames = [];
                foreach ($params as $param) {
                    $paramNames[$param->getName()] = true;
                }
                if (isset($paramNames['message'], $paramNames['code'])) {
                    return new $exceptionClass($exception['message'], $exception['code']);
                }
            }
        }

        return new \RuntimeException('Bad exception data: '.json_encode($exception));
    }
}
