<?php

namespace pq;

class Cancel
{
    public $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function cancel()
    {
        pg_cancel_query($this->connection->handle);
    }
}
