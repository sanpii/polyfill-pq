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
        $this->fetchType = self::FETCH_ARRAY;
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
            case 'errorMessage':
                return pg_result_error($this->results);
            break;
            case 'diag':
                return [
                    'severity' => pg_result_error_field($this->results, PGSQL_DIAG_SEVERITY),
                    'sqlstate' => pg_result_error_field($this->results, PGSQL_DIAG_SQLSTATE),
                    'message_primary' => pg_result_error_field($this->results, PGSQL_DIAG_MESSAGE_PRIMARY),
                    'message_detail' => pg_result_error_field($this->results, PGSQL_DIAG_MESSAGE_DETAIL),
                    'message_hint' => pg_result_error_field($this->results, PGSQL_DIAG_MESSAGE_HINT),
                    'statement_position' => pg_result_error_field($this->results, PGSQL_DIAG_STATEMENT_POSITION),
                    'internal_position' => pg_result_error_field($this->results, PGSQL_DIAG_INTERNAL_POSITION),
                    'internal_query' => pg_result_error_field($this->results, PGSQL_DIAG_INTERNAL_QUERY),
                    'context' => pg_result_error_field($this->results, PGSQL_DIAG_CONTEXT),
                    'source_file' => pg_result_error_field($this->results, PGSQL_DIAG_SOURCE_FILE),
                    'source_line' => pg_result_error_field($this->results, PGSQL_DIAG_SOURCE_LINE),
                    'source_function' => pg_result_error_field($this->results, PGSQL_DIAG_SOURCE_FUNCTION),
                ];
            break;
            case 'numRows':
                return $this->count();
            break;
            case 'numCols':
                if (!is_resource($this->results)) {
                    return 0;
                } else {
                    return pg_num_fields($this->results);
                }
            break;
            default:
                throw new \Exception("Invalid property pg\\Result::$name");
            break;
        }
    }

    public function __debugInfo()
    {
        return [
            'status' => $this->status,
            'errorMessage' => $this->errorMessage,
            'diag' => $this->diag,
            'numRows' => $this->numRows,
            'numCols' => $this->numCols,
        ];
    }

    public function count()
    {
        if (!is_resource($this->results)) {
            return 0;
        }

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
