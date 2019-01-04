<?php

$serverName = "1.2.3.4\\ION";
$databaseName = "XXXXXX";
$UID = "XXXXXX";
$PWD = "XXXXXX";

//$this->serverName = "1.2.3.4";
//$this->databaseName = "ACCESSCONTROL";
//$this->UID = "reports";
//$this->PWD = "R3p0r+$";

$connectionInfo = [
    "Database" => $databaseName,
    "UID" => $UID,
    "PWD" => $PWD
];

$conn = sqlsrv_connect($serverName, $connectionInfo);

//$dbh = new PDO("sqlsrv:Server=1.2.3.4\\ION,1691;Database=ION_DATA", $UID , $PWD);

if (!$conn) {
    echo "Connection could not be established.\n";
    die(print_r(sqlsrv_errors(), true));
}

echo "Connection fine";
