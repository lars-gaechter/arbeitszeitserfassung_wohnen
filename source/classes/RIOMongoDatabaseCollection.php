<?php

declare(strict_types=1);

use MongoDB\Collection;
use MongoDB\Driver\Cursor;
use MongoDB\InsertManyResult;
use MongoDB\InsertOneResult;
use MongoDB\UpdateResult;
use MongoDB\Database;

class RIOMongoDatabaseCollection
{
    private Database $database;

    private string $collection;

    /**
     * RIOMongoDatabaseCollection constructor.
     * @param Database $database
     * @param string $collection
     */
    public function __construct(Database $database, string $collection)
    {
        $this->database = $database;
        $this->collection = $collection;
    }

    public function getCollection(): Collection
    {
        return $this->database->{$this->getName()};
    }

    public function getName(): string
    {
        return $this->collection;
    }

    public function find($filter = [], array $options = []): Cursor
    {
        return $this->getCollection()->find($filter, $options);
    }

    public function insertMany(array $documents, array $options = []): InsertManyResult
    {
        return $this->getCollection()->insertMany($documents, $options);
    }

    public function insertOne(array|object $document, array $options = []): InsertOneResult
    {
        return $$this->getCollection()->insertOne($document, $options);
    }

    public function updateMany(array|object $filter, array|object $update, array $options = []): UpdateResult
    {
        return $this->getCollection()->updateMany($filter, $update, $options);
    }

    public function updateOne(array|object $filter, array|object $update, array $options = []): UpdateResult
    {
        return $this->getCollection()->updateOne($filter, $update, $options);
    }


}