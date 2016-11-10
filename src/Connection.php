<?php

namespace pq;

class Connection
{
    public $socket;

    public function __construct($dsn, $flags = 0)
    {
        $this->socket = pg_connect($dsn);
    }

    public function prepare($name, $query, $types = null)
    {
        return new Statement($this, $name, $query, $types);
    }

    public function exec($query)
    {
        $results = pg_query($this->socket, $query);

        return new Result($results);
    }

    public function execParams($query, $params, $types = null)
    {
        $results = pg_query_params($this->socket, $query, $params);

        return new Result($results);
    }
}
