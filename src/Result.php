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

    public function __construct($results)
    {
        $this->results = $results;
        $this->resultsIndex = 0;
    }

    public function fetchCol(&$ref, $col = 0)
    {
        $results = pg_fetch_all_columns($this->results, $col);

        if ($results !== false) {
            $ref = $results[0];
            return true;
        } else {
            return NULL;
        }
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
        switch($this->fetchType) {
            case self::FETCH_ARRAY:
                return pg_fetch_array($this->results, $this->resultsIndex);
            break;
            case self::FETCH_ASSOC:
                return pg_fetch_assoc($this->results, $this->resultsIndex);
            break;
            case self::FETCH_OBJECT:
                return pg_fetch_object($this->results, $this->resultsIndex);
            break;
        }
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