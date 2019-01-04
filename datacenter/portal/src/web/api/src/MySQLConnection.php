<?php

class MySQLConnection {

    private $serverName;
    private $databaseName;
    private $UID;
    private $PWD;
    private $conn;

    function __construct()
    {
        $this->serverName = "1.2.3.4";
        $this->databaseName = "XXX";
        $this->UID = "XXX";
        $this->PWD = "XXX";

        $this->connect();
    }

    public function connect()
    {
        $this->conn = new mysqli($this->serverName, $this->UID, $this->PWD, $this->databaseName);
    }

    public function open() : mysqli
    {
        if ($this->conn->connect_errno) {
            printf("Connect failed: %s\n", $this->conn->connect_error);
            exit();
        }
        else
            return $this->conn;
    }

    public function getServerName() : string
    {
        return $this->serverName;
    }

    public function setServerName(string $serverName)
    {
        $this->serverName = $serverName;
        $this->connect();
    }

    function fetchFirst($sql) {
        $mysqli = $this->open();
        $row = [];

        if ($result = $mysqli->query($sql))
            $row = mysqli_fetch_all($result, MYSQLI_ASSOC);

        return $row;
    }

    function delete($sql) {
        $mysqli = $this->open();

        if ($mysqli->query($sql) === TRUE) {
//            echo "Record deleted successfully";
        } else {
            echo "Error deleting record: " . $mysqli->error;
        }

        $mysqli->close();
    }

    function insert($sql) {
        $mysqli = $this->open();

        if ($mysqli->query($sql) === TRUE) {
//            echo "Record inserted successfully";
        } else {
            echo "Error inserting record: " . $mysqli->error;
        }

        $mysqli->close();
    }

    function update($sql) {
        $mysqli = $this->open();

        if ($mysqli->query($sql) === TRUE) {
//            echo "Record updated successfully";
        } else {
            echo "Error updating record: " . $mysqli->error;
        }

        $mysqli->close();
    }

    public function fetchAll($sql) {
        $mysqli = $this->open();
        $query_output = [];

        if ($result = $mysqli->query($sql)) {
            $query_output = mysqli_fetch_all($result, MYSQLI_ASSOC);

            mysqli_free_result($result);
            mysqli_close($mysqli);
        }

        return $query_output;
    }

}
