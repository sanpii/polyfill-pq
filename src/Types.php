<?php

namespace pq;

class Types implements \ArrayAccess
{
    public $connection;
    private $namespaces;

    public function __construct(Connection $connection, $namespaces = NULL)
    {
        $this->connection = $connection;
        $this->namespaces = $namespaces;
    }

    public function offsetExists($offset)
    {
        $results = $this->getType($offset);

        return ($results->count() > 0);
    }

    public function offsetGet($offset)
    {
        $results = $this->getType($offset);

        $results->fetchType = Result::FETCH_OBJECT;
        return $results->current();
    }

    public function offsetSet($offset, $value)
    {
        throw new \LogicException("Read only property");
    }

    public function offsetUnset($offset)
    {
        throw new \LogicException("Read only property");
    }

    private function getType($offset)
    {
        if (is_numeric($offset)) {
            return $this->connection->execParams(
                "SELECT oid, * FROM pg_type WHERE oid = $1",
                [$offset]
            );
        } else {
            return $this->connection->execParams(
                "SELECT oid, * FROM pg_type WHERE typname = $1",
                [$offset]
            );
        }
    }
}
