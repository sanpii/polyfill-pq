<?php

namespace pq;

class Connection
{
    public $handle;

    public $db;
    public $user;
    public $pass;
    public $host;
    public $port;
    public $options;

    const OK = 0;
    const BAD = 1;
    const STARTED = 2;
    const MADE = 3;
    const AWAITING_RESPONSE = 4;
    const AUTH_OK = 5;
    const SSL_STARTUP = 6;
    const SETENV = 7;

    const ASYNC = 1;
    const PERSISTENT = 2;

    const POLLING_FAILED = 0;
    const POLLING_READING = 1;
    const POLLING_WRITING = 2;
    const POLLING_OK = 3;

    public function __construct($dsn, $flags = 0)
    {
        if ($flags & self::PERSISTENT) {
            $this->handle = pg_pconnect($dsn);
        } else {
            if ($flags & self::ASYNC) {
                $flags = PGSQL_CONNECT_ASYNC;
            }
            $this->handle = pg_connect($dsn, $flags);
        }
    }

    public function prepare($name, $query, $types = null)
    {
        return new Statement($this, $name, $query, $types);
    }

    public function exec($query)
    {
        $results = pg_query($this->handle, $query);

        return new Result($results);
    }

    public function execParams($query, $params, $types = null)
    {
        $results = pg_query_params($this->handle, $query, $params);

        return new Result($results);
    }

    public function poll()
    {
        return pg_connect_poll($this->handle);
    }

    public function __get($name)
    {
        switch ($name) {
            case 'status':
                return pg_connection_status($this->handle);
            break;
            case 'socket':
                return pg_socket($this->handle);
            break;
            default:
                throw new \Exception("Invalid property pg\\Connection::$name");
            break;
        }
    }
}
