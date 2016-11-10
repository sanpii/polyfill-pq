<?php

namespace pq;

class Statement
{
    private $connection;
    private $name;
    private $query;
    private $types;

    public function __construct(Connection $connection, $name, $query, $types = null, $async = false)
    {
        $this->connection = $connection;
        $this->name = $name;
        $this->query = $query;

        $this->types = [];
        foreach ($types as $type) {
            $this->types[] = $this->getType($type);
        }
    }

    public function exec($params = null)
    {
        static $statement = null;

        if ($statement === null) {
            foreach ($this->types as $index => $type) {
                $index++;
                $this->query = str_replace("\$$index", "\$$index::$type", $this->query);
            }
            pg_prepare($this->connection->handle, $this->name, $this->query);
        }

        $results = pg_execute($this->connection->handle, $this->name, $params);

        return new Result($results);
    }

    private function getType($oid)
    {
        $results = $this->connection->execParams(
            "SELECT typname FROM pg_type WHERE oid = $1",
            [$oid]
        );

        return $results->current()['typname'];
    }
}
