<?php

declare(strict_types=1);

use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\Driver\Cursor;
use MongoDB\InsertManyResult;
use MongoDB\InsertOneResult;
use MongoDB\Model\BSONDocument;
use MongoDB\UpdateResult;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Manage like a facade a single database with any number of collection/s
 *
 * Class RIOMongoDatabase
 */
class RIOMongoDatabase
{
    /**
     * instance
     *
     * @var null|RIOMongoDatabase
     */
    protected static ?RIOMongoDatabase $_instance = null;

    private Client $client;

    /**
     * Singleton
     * RIOMongoDatabase constructor.
     */
    private function __construct()
    {
        $this->client = $this->getClient();
    }

    protected function __clone(): void {}

    public static function getInstance(): self
    {
        if (null === self::$_instance)
        {
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    public function getName(): string
    {
        return $_ENV["DB_NAME"];
    }

    private function getClient(): Client|RedirectResponse
    {
        if(!isset($this->client)) {
            $client = new Client($_ENV["MONGODB"]);
            try {
                $databaseInfoIterator = $client->listDatabases();
            } catch (RIOConnectionFailed $connectionFailed) {
                if (RIOConfig::isInDebugMode()) {
                    throw new Error("The database connection could not be established.", 0, $connectionFailed);
                } else {
                    return RIORedirect::error(503);
                }
            }
            return $client;
        }
        return $this->client;
    }

    public function getDatabase(): Database
    {
        return $this->client->{$this->getName()};
    }

    public function getCollection(string $collection): Collection
    {
        return (new RIOMongoDatabaseCollection($this->getDatabase(), $collection))->getCollection();
    }

    public function createCollection(string $collection, array $options = []): array|object
    {
        return $this->getDatabase()->createCollection($collection, $options);
    }

    public function find(string $collection, $filter = [], array $options = []): Cursor
    {
        return $this->getCollection($collection)->find($filter, $options);
    }

    public function insertMany(string $collection, array $documents, array $options = []): InsertManyResult
    {
        return $this->getCollection($collection)->insertMany($documents, $options);
    }

    public function insertOne(string $collection, mixed $document, array $options = []): InsertOneResult
    {
        return $this->getCollection($collection)->insertOne($document, $options);
    }

    public function updateMany(string $collection, mixed $filter, mixed $update, array $options = []): UpdateResult
    {
        return $this->getCollection($collection)->updateMany($filter, $update, $options);
    }

    public function updateOne(string $collection, mixed $filter, mixed $update, array $options = []): UpdateResult
    {
        return $this->getCollection($collection)->updateOne($filter, $update, $options);
    }

    /**
     * @return BSONDocument[]
     */
    public function getActiveUsers(): array
    {
        return $this->getUsers(["time_record_started" => true]);
    }

    /**
     * @return BSONDocument[]
     */
    public function getInactiveUsers(): array
    {
        return $this->getUsers(["time_record_started" => false]);
    }

    /**
     * @param array $filter
     * @param array $options
     * @return BSONDocument[]
     */
    public function getUsers(array $filter = [], array $options = []): array
    {
        return $this->getUsersCollection()->find($filter)->toArray();
    }

    public function getWorkDaysCollectionByYearUser(string $year, string $username): Collection
    {
        return $this->getDatabase()->selectCollection($this->getWorkDaysCollectionByYearUserAsString($year,$username));
    }

    public function getWorkDaysCollectionByYearUserAsString(string $year, string $username): string
    {
        return "work_day_".$year.'_'.$username;
    }

    public function getUsersCollection(): Collection
    {
        return $this->getDatabase()->selectCollection("user");
    }

    public function getWorkDaysCollection(): Collection
    {
        return $this->getDatabase()->selectCollection("work_day");
    }
}