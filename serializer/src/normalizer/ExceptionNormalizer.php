<?php

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
        $exception = unserialize(base64_decode($data));
        if ($exception === false) {
            return new \RuntimeException('Bad exception data: '.json_encode($data));
        }
        if ($exception instanceof \Exception) {
            return $exception;
        }
        if (is_array($exception) && isset($exception['class'], $exception['message'], $exception['code'])) {
            $className = $exception['class'];
            $class = new \ReflectionClass($className);
            $constructor = $class->getConstructor();
            if ($class->isSubClassOf(\Exception::class) && $constructor !== null) {
                $params = $constructor->getParameters();
                if (count($params) > 2) {
                    $requiredParams = 0;
                    foreach ($params as $param) {
                        if (!$param->isOptional()) {
                            ++$requiredParams;
                        }
                    }
                    if ($requiredParams <= 2) {
                        return new $className($exception['message'], $exception['code']);
                    }
                }
            }
        }

        return new \RuntimeException('Bad exception data: '.json_encode($exception));
    }
}
