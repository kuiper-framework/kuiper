<?php

declare(strict_types=1);

namespace kuiper\db\orm;

use kuiper\annotations\DocReaderInterface;
use kuiper\annotations\ReaderInterface;
use kuiper\db\orm\serializer\SerializerRegistry;
use kuiper\db\sharding\ClusterInterface;

class ShardingRepositoryFactory implements RepositoryFactoryInterface
{
    /**
     * @var ClusterInterface
     */
    private $connection;

    /**
     * @var SerializerRegistry
     */
    private $serializers;

    /**
     * @var TableMetadataFactory
     */
    private $tableMetadataFactory;

    /**
     * @var ReaderInterface
     */
    private $annotationReader;

    /**
     * @var DocReaderInterface
     */
    private $docReader;

    /**
     * @var string
     */
    private $tableNamePrefix;

    /**
     * @param string $tableNamePrefix
     */
    public function __construct(ClusterInterface $connection, ReaderInterface $annotationReader, DocReaderInterface $docReader, $tableNamePrefix = '')
    {
        $this->connection = $connection;
        $this->annotationReader = $annotationReader;
        $this->docReader = $docReader;
        $this->tableNamePrefix = $tableNamePrefix;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $modelClass)
    {
        $tableMetadata = $this->getTableMetadataFactory()->create($modelClass);
        $repositoryClass = $tableMetadata->getRepositoryClass() ?: ShardingRepository::class;

        return new $repositoryClass($this->connection, $tableMetadata, $this->getSerializers());
    }

    protected function getTableMetadataFactory()
    {
        if (!$this->tableMetadataFactory) {
            $this->tableMetadataFactory = new TableMetadataFactory($this->annotationReader, $this->docReader, $this->getSerializers(), $this->tableNamePrefix);
        }

        return $this->tableMetadataFactory;
    }

    protected function getSerializers()
    {
        if (!$this->serializers) {
            $this->setSerializers(new SerializerRegistry());
        }

        return $this->serializers;
    }

    public function setSerializers(SerializerRegistry $serializers)
    {
        $this->serializers = $serializers;
    }
}
