<?php

namespace pq;

class Connection
{
    private $handle;

    public function __construct($dsn, $flags = 0)
    {
        $this->handle = pg_connect($dsn);
    }

    public function exec($query)
    {
        $results = pg_query($this->handle, $query);

        return new Result($results);
    }
}
