<?php

class QueryBuilder {
    public static $SORT_TYPE_DESC = "DESC";
    public static $SORT_TYPE_ASC = "ASC";

    public static $DB_TYPE_MYSQL = "mysql";
    public static $DB_TYPE_PGSQL = "pgsql";
    public static $DB_TYPE_SQLITE = "sqlite";
    public static $DB_TYPE_OCI = "oci";
    public static $DB_TYPE_SQLSRV = "sqlsrv";

    private $db;
    private $query;
    private $queryParams;

    public function getQueryParams() {
        print_r($this->queryParams);
    }

    private $dataSourceName;

    function __construct($config) {
        $this->queryParams = array();
        $this->prepareDataSourceName($config);
        $this->db = new PDO($this->dataSourceName, $config["username"], $config["password"]);
    }

    public function clear() {
        $this->query = "";
        $this->queryParams = array();
    }

    public function select($columns) {
        $this->query = "SELECT " . implode(",", $columns);
        return $this;
    }

    public function insertInto($table, $columns = []) {
        $this->query = "INSERT INTO " . $table;
        if (count($columns) > 0) {
            $columns = array_map(function($item) {
            return "`$item`";
        }, $columns);
            $this->query = $this->query . " (" .  implode(",", $columns) . ")";
        }
        return $this;
    }

    public function update($table) {
        $this->query = "UPDATE " . $table;
        return $this;
    }

    public function delete() {
        $this->query = "DELETE";
        return $this;
    }

    public function set($arguments) {
        $queryParamIndex = count($this->queryParams);

        $settableArguments;
        foreach($arguments as $key=>$value) {
            $queryParamString = ":query_param_" . $queryParamIndex++;
            $this->queryParams[$queryParamString] = $value;
            $settableArguments[] = "$key=$queryParamString";
        }

        $this->query = $this->query . " SET " . implode(",", $settableArguments);
        return $this;
    }

    public function values($values) {
        $queryParamIndex = count($this->queryParams);
        // $values = array_map(function($item) {
        //     return "'$item'";
        // }, $values);
        $settableArguments = [];
        foreach($values as $value) {
            $queryParamString = ":query_param_" . $queryParamIndex++;
            $this->queryParams[$queryParamString] = $value;
            $settableArguments[] = "$queryParamString";
        }

        $this->query = $this->query . " VALUES (" .  implode(",", $settableArguments) . ")";
        return $this;
    }

    public function from($table) {
        $this->query = $this->query . " FROM {$table}";
        return $this;
    }

    public function where($leftValue, $operation, $rightValue) {
        $this->query = $this->query . sprintf(" WHERE %s %s '%s'", $leftValue, $operation, $rightValue);
        return $this;
    }

    public function sort($by, $sortType = "ASC") {
        if ($sortType !== static::$SORT_TYPE_ASC && $sortType !== static::$SORT_TYPE_DESC) {
            throw new Exception("Uncorrect sort type: " . $sortType);
        }

        $this->query = $this->query . " ORDER BY {$by} {$sortType}";
        return $this;
    }

    public function limit($count, $from = 0) {
        if (!is_numeric($count)) {
            throw new Exception("Uncorrect count: " . $count);
        }

        if (!is_numeric($from)) {
            throw new Exception("Uncorrect from: " . $from);
        }

        $this->query = $this->query . " LIMIT {$from}, {$count}";
        return $this;
    }

    public function getQuery() {
        return $this->query;
    }

    public function execute() {
        $statement = $this->db->prepare($this->query);
        $statement->execute($this->queryParams);
        return $statement;
    }

    public function and($leftValue, $operation, $rightValue) {
        $this->query = $this->query . sprintf(" AND %s %s '%s'", $leftValue, $operation, $rightValue);
        return $this;
    }

    public function or($leftValue, $operation, $rightValue) {
        $this->query = $this->query . sprintf(" OR %s %s '%s'", $leftValue, $operation, $rightValue);
        return $this;
    }

    private function prepareDataSourceName($config) {
        $this->dataSourceName = sprintf("%s:dbname=%s;host=%s'", $config['dbtype'], $config["dbname"], $config["host"]);
    }
}
