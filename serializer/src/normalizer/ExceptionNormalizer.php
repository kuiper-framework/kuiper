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

use kuiper\serializer\NormalizerInterface;

class ExceptionNormalizer implements NormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function normalize($exception)
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
    public function denormalize($data, $className)
    {
        $exception = unserialize(base64_decode($data, true));
        if (false === $exception) {
            return new \RuntimeException('Bad exception data: '.json_encode($data));
        }
        if ($exception instanceof \Exception) {
            return $exception;
        }
        if (is_array($exception) && isset($exception['class'], $exception['message'], $exception['code'])) {
            $exceptionClass = $exception['class'];
            $class = new \ReflectionClass($exceptionClass);
            $constructor = $class->getConstructor();
            if ($class->isSubclassOf(\Exception::class) && null !== $constructor) {
                $params = $constructor->getParameters();
                $paramNames = [];
                foreach ($params as $param) {
                    $paramNames[$param->getName()] = true;
                }
                if (isset($paramNames['message']) && isset($paramNames['code'])) {
                    return new $exceptionClass($exception['message'], $exception['code']);
                }
            }
        }

        return new \RuntimeException('Bad exception data: '.json_encode($exception));
    }
}
