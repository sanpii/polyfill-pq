<?php

namespace pq;

class Types implements \ArrayAccess
{
    private $connection;
    private $namespaces;

    public function __construct(Connection $connection, $namespaces = NULL)
    {
        $this->connection = $connection;
        $this->namespaces = $namespaces;
    }

    public function offsetExists($offset)
    {
    }

    public function offsetGet($offset)
    {
        if ($offset === 'connection') {
            return $this->connection;
        } else {
            $results = $this->connection->execParams(
                "SELECT oid, * FROM pg_type WHERE typname = $1",
                [$offset]
            );

            $results->fetchType = Result::FETCH_OBJECT;
            return $results->current();
        }
    }

    public function offsetSet($offset, $value)
    {
        throw new \LogicException("Read only property");
    }

    public function offsetUnset($offset)
    {
        throw new \LogicException("Read only property");
    }
}
