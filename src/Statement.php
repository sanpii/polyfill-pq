<?php

namespace pq;

class Statement
{
    private $connection;
    private $name;
    private $query;
    private $types;
    private $bounds;

    public function __construct(Connection $connection, $name, $query, $types = [], $async = false)
    {
        $this->connection = $connection;
        $this->name = $name;
        $this->query = $query;

        $this->types = [];
        foreach ($types as $type) {
            $this->types[] = $this->getType($type);
        }

        $this->bounds = [];
    }

    public function bind($col, &$var)
    {
        $this->bounds[$col] = &$var;
    }

    public function exec($params = null)
    {
        static $statement = null;

        if ($statement === null) {
            $query = $this->query;

            foreach ($this->types as $index => $type) {
                $index++;
                $query = str_replace("\$$index", "\$$index::$type", $query);
            }

            $statement = pg_prepare($this->connection->handle, $this->name, $query);
        }

        if ($params === null) {
            $params = $this->bounds;
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
