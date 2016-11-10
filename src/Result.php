<?php

namespace pq;

class Result implements \Iterator, \Countable
{
    const TUPLES_OK = 2;

    const FETCH_ARRAY = 0;
    const FETCH_ASSOC = 1;
    const FETCH_OBJECT = 2;

    public $fetchType;

    private $results;
    private $resultsIndex;
    private $bounds;

    public function __construct($results)
    {
        $this->results = $results;
        $this->resultsIndex = 0;
        $this->bounds = [];
    }

    public function bind($col, &$var)
    {
        $this->bounds[$col] = &$var;
    }

    public function fetchBound()
    {
        if (!$this->valid()) {
            return null;
        }

        $results = [];

        foreach ($this->bounds as $col => &$var) {
            $this->fetchCol($var, $col);
            $results[$col] = $var;
        }

        $this->resultsIndex++;
        return $results;
    }

    public function fetchCol(&$ref, $col = 0)
    {
        $results = pg_fetch_all_columns($this->results, $col);

        if ($results !== false) {
            $type = pg_field_type($this->results, $col);
            $ref = $this->convert($results[$this->resultsIndex], $type);
            return true;
        } else {
            return NULL;
        }
    }

    private function convert($row, $type)
    {
        switch ($type) {
            case 'int4':
                return (int)$row;
            break;
            default:
                return $row;
            break;
        }
    }

    public function fetchRow($fetchType = null)
    {
        if (!$this->valid()) {
            return null;
        }

        if ($fetchType === null) {
            $fetchType = $this->fetchType;
        }

        switch($fetchType) {
            case self::FETCH_ARRAY:
                $result = pg_fetch_array($this->results, $this->resultsIndex++, PGSQL_NUM);
            break;
            case self::FETCH_ASSOC:
                $result = pg_fetch_assoc($this->results, $this->resultsIndex++);
            break;
            case self::FETCH_OBJECT:
                $result = pg_fetch_object($this->results, $this->resultsIndex++);
            break;
        }

        $col = 0;
        foreach ($result as &$row) {
            $type = pg_field_type($this->results, $col++);
            $row = $this->convert($row, $type);
        }

        return $result;
    }

    public function fetchAll()
    {
        $index = 0;
        $results = [];

        while ($result = $this->fetchRow(self::FETCH_ARRAY)) {
            $results[$index++] = $result;
        }
        return $results;
    }

    public function __get($name)
    {
        switch($name) {
            case 'status':
                return pg_result_status($this->results);
            break;
            case 'numRows':
                return $this->count();
            break;
            case 'numCols':
                return pg_num_fields($this->results);
            break;
        }
    }

    public function count()
    {
        return pg_num_rows($this->results);
    }

    public function current()
    {
        return $this->fetchRow($this->fetchType);
    }

    public function key()
    {
        return $this->resultsIndex;
    }

    public function next()
    {
        $this->resultsIndex++;
    }

    public function rewind()
    {
        $this->resultsIndex = 0;
    }

    public function valid()
    {
        return ($this->resultsIndex < $this->count());
    }
}
