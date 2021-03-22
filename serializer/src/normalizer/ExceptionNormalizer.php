<?php

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
            $className = $exception['class'];
            $class = new \ReflectionClass($className);
            $constructor = $class->getConstructor();
            if ($class->isSubClassOf(\Exception::class) && null !== $constructor) {
                $params = $constructor->getParameters();
                if (count($params) > 2) {
                    $requiredParams = [];
                    foreach ($params as $param) {
                        if (!$param->isOptional()) {
                            $requiredParams[] = $param->getName();
                        }
                    }
                    if (2 === count($requiredParams) && $requiredParams == ['message', 'code']) {
                        return new $className($exception['message'], $exception['code']);
                    }
                }
            }
        }

        return new \RuntimeException('Bad exception data: '.json_encode($exception));
    }
}
