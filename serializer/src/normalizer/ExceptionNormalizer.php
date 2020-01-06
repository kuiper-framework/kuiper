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
    public function denormalize($data, $type = null)
    {
        if (isset($data['class'], $data['attributes'])) {
            // 支持 hyperf 异常序列化格式
            if (is_string($data['attributes'])) {
                $exception = unserialize($data['attributes']);
            } else {
                $exception = array_merge($data['attributes'], ['class' => $data['class']]);
            }
        } else {
            $exception = unserialize(base64_decode($data));
            if (false === $exception) {
                return new \RuntimeException('Bad exception data: '.json_encode($data));
            }
        }
        $exception = unserialize(base64_decode($data));
        if (false === $exception) {
            return new \RuntimeException('Bad exception data: '.json_encode($data));
        }
        if ($exception instanceof \Exception) {
            return $exception;
        }
        if (is_array($exception) && isset($exception['class'], $exception['message'], $exception['code'])) {
            try {
                $e = $this->toException($exception);
                if ($e) {
                    return $e;
                }
            } catch (\ReflectionException $e) {
                return new \RuntimeException(sprintf(
                    'Bad exception %s: %s', $exception['class'], $exception['message']
                ), $exception['code']);
            } catch (\TypeError $e) {
                return new \RuntimeException(sprintf(
                    'Uncaught exception %s: %s', $exception['class'], $exception['message']
                ), $exception['code']);
            }
        }

        return new \RuntimeException('Bad exception data: '.json_encode($exception));
    }

    /**
     * @param array $exception
     *
     * @return \Exception|null
     *
     * @throws \ReflectionException
     */
    private function toException(array $exception)
    {
        $type = $exception['class'];
        $class = new \ReflectionClass($type);
        $constructor = $class->getConstructor();
        if ($class->isSubClassOf(\Exception::class) && null !== $constructor) {
            return new $type($exception['message'], $exception['code']);
        }
    }
}
