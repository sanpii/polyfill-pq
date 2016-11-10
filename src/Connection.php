<?php

namespace pq;

class Connection
{
    public $user;
    public $pass;

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

    public $handle;
    private $dsn;
    private $flags;

    public function __construct($dsn, $flags = 0)
    {
        $this->dsn = $dsn;
        $this->flags = $flags;

        $this->connect();
    }

    private function connect()
    {
        if ($this->flags & self::PERSISTENT) {
            $this->handle = pg_pconnect($this->dsn);
        } else {
            if ($this->flags & self::ASYNC) {
                $flags = PGSQL_CONNECT_ASYNC;
            } else {
                $flags = 0;
            }
            $this->handle = pg_connect($this->dsn, $flags);
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

    public function resetAsync()
    {
        pg_close($this->handle);
        $this->connect();
    }

    public function __get($name)
    {
        switch ($name) {
            case 'db':
                return pg_dbname($this->handle);
            break;
            case 'host':
                return pg_host($this->handle);
            break;
            case 'port':
                return pg_port($this->handle);
            break;
            case 'options':
                return pg_options($this->handle);
            break;
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
