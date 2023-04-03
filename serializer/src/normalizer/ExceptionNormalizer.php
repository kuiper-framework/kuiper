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

use Exception;
use kuiper\reflection\ReflectionTypeInterface;
use kuiper\serializer\exception\UnexpectedValueException;
use kuiper\serializer\NormalizerInterface;
use ReflectionClass;
use Serializable;

class ExceptionNormalizer implements NormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function normalize(mixed $object): mixed
    {
        $exception = $object;
        /** @var Exception $exception */
        if ($exception instanceof Serializable) {
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
            throw new UnexpectedValueException('Bad exception data: '.json_encode($data));
        }
        if ($exception instanceof Exception) {
            return $exception;
        }
        if (is_array($exception)
            && isset($exception['class'], $exception['message'], $exception['code'])
            && class_exists($exception['class'])) {
            $exceptionClass = $exception['class'];
            $class = new ReflectionClass($exceptionClass);
            $constructor = $class->getConstructor();
            if (null !== $constructor && $class->isSubclassOf(Exception::class)) {
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

        throw new UnexpectedValueException('Bad exception data: '.json_encode($exception));
    }
}
