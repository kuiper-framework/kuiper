<?php

declare(strict_types=1);

namespace kuiper\db\orm\serializer;

use kuiper\annotations\DocReaderInterface;
use kuiper\db\metadata\Column;
use kuiper\serializer\NormalizerInterface;

class JsonObjectSerializer implements Serializer
{
    /**
     * @var NormalizerInterface
     */
    private $normalizer;

    /**
     * @var DocReaderInterface
     */
    private $docReader;

    public function __construct(NormalizerInterface $normalizer, DocReaderInterface $docReader)
    {
        $this->normalizer = $normalizer;
        $this->docReader = $docReader;
    }

    public function serialize($value, Column $column)
    {
        return isset($value) ? json_encode($this->normalizer->normalize($value)) : '';
    }

    public function unserialize($data, Column $column)
    {
        if ($data) {
            $type = $this->docReader->getPropertyType($column->getProperty());

            return $this->normalizer->denormalize(json_decode($data, true), $type);
        } else {
            return null;
        }
    }
}
