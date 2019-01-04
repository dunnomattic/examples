<?php

class MSSQLConnection
{

    private $serverName;
    private $databaseName;
    private $UID;
    private $PWD;
    private $conn;

    function __construct()
    {
        $this->serverName = "XXXX";
        $this->databaseName = "XXXX";
        $this->UID = "XXXX";
        $this->PWD = "XXXX";

        $this->connect();
    }

    public function connect()
    {
        $connectionInfo = [
            "Database" => $this->databaseName,
            "UID" => $this->UID,
            "PWD" => $this->PWD
        ];

        $this->conn = sqlsrv_connect($this->serverName, $connectionInfo);
    }

    public function open()
    {
        if (!$this->conn) {
            echo "Connection could not be established.\n";
            die(print_r(sqlsrv_errors(), true));
        }

        return $this->conn;
    }

    public function getServerName(): string
    {
        return $this->serverName;
    }

    public function getTableColumns($table)
    {
        $sql = "USE {$this->databaseName};
                SELECT * FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_NAME='{$table}';";

        return $this->fetchAll($sql);
    }

    public function getTableColumnsFromCSV($table)
    {

    }

    public function setServerName(string $serverName)
    {
        $this->serverName = $serverName;
        $this->connect();
    }

    public function fetchAll($sql) {
        $query_output = [];
        $getData = sqlsrv_query($this->conn, $sql);
        while($row = sqlsrv_fetch_array($getData, SQLSRV_FETCH_ASSOC))
        {
            $query_output[] = $row;
        }

        sqlsrv_free_stmt($getData);
        return $query_output;
    }

    public function fetchRecord($sql) {
        $getData = sqlsrv_query($this->conn, $sql);
        $row = sqlsrv_fetch_array($getData, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($getData);
        return $row;
    }
}
