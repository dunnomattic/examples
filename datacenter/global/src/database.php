<?php

class Database
{
    private $host, $user, $pass, $dbname;
    private $dbh;
    private $stmt;
    private $error;
    private $query;
    private $debug;


    public function __construct($debug = false)
    {
        if (defined('DB_HOST')) {
            $this->host = DB_HOST;
            $this->dbname = DB_NAME;
            $this->user = DB_USER;
            $this->pass = DB_PASS;
        }
        else {
            $this->host = $_SERVER["DB_HOST"];
            $this->dbname = $_SERVER["DB_NAME"];
            $this->user = $_SERVER["DB_USERNAME"];
            $this->pass = $_SERVER["DB_PASSWORD"];
        }


        // Set DSN
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname;
        //error_log($dsn . "; " . $this->user . "; " . $this->pass);
        // Set options
        $options = array(
            PDO::ATTR_PERSISTENT    => true,
            PDO::ATTR_ERRMODE       => PDO::ERRMODE_EXCEPTION
        );
        // Create a new PDO instanace
        try {
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        }
        // Catch any errors
        catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log('exception caught for newPDO');
        }
        $this->debug = $debug;
    }

    public function setDebug($debug) {
        $this->debug = $debug;
    }

    public function bind($param, $value, $type = null)
    {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    public function query(string $query)
    {
        //error_log($query);
        $this->query = $query;
        $this->stmt = $this->dbh->prepare($query);
    }

    public function execute($params = null) : bool
    {
        if ($this->debug) {
            for ($x = 0; $x < count($params); $x++) {
                $pos = strpos($this->query, "?");
                $this->query = substr_replace($this->query, "'" . $params[$x] . "'", $pos, 1);
            }
            error_log(str_replace("\n", "", $this->query));
        }
        if (!is_null($params) && is_array($params)) {
            return $this->stmt->execute($params);
        }
        else {
            return $this->stmt->execute();
        }
    }

    public function resultset($params = null) : array
    {
        $this->execute($params);
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function single($params = null)
    {
        $this->execute($params);
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function beginTransaction() : bool
    {
        return $this->dbh->beginTransaction();
    }

    public function endTransaction() : bool
    {
        return $this->dbh->commit();
    }

    public function lastInsertId() : string
    {
        return $this->dbh->lastInsertId();
    }

    public function cancelTransaction() : bool
    {
        return $this->dbh->rollBack();
    }
}
