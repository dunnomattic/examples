<?php

class Leasing {

    public function fetchAll($entity){
        $sql =  "SELECT * FROM leasing." . $entity .
            " LIMIT 100;";

        $mysql = new MySQLConnection();
        $records = $mysql->fetchAll($sql);

        return $records;
    }

    public function fetchCustomers(){
        $sql = "SELECT * from leasing.customers WHERE id NOT IN (4, 6, 51, 52) order by name;";

        $mysql = new MySQLConnection();
        $customer = $mysql->fetchAll($sql);

        return $customer;
    }

    public function fetchDatacenter($name){
        $sql = "SELECT * from leasing.datacenters WHERE commonName = '" . $name . "' LIMIT 1;";

        $mysql = new MySQLConnection();
        $datacenter = $mysql->fetchAll($sql);

        return $datacenter;
    }

    public function fetchCustomer($name){
        $sql = "SELECT * from leasing.customers WHERE name = '" . $name . "' LIMIT 1;";

        $mysql = new MySQLConnection();
        $customer = $mysql->fetchAll($sql);

        return $customer;
    }

    public function addRecord($entity, $fields){
        $sql =  "INSERT INTO leasing." . $entity . " (";

        foreach($fields as $key => $value)
        {
            if(!empty($value))
                $sql .= $key . ", ";
        }

        $sql = substr($sql, 0, -2);
        $sql .= ") VALUES ('";

        // need to account for numeric value types: mysqli::real_escape_string
        foreach($fields as $key => $value)
        {
            if(!empty($value))
                $sql .= $value . "', '";
        }

        $sql = substr($sql, 0, -3);
        $sql .= ")";

        $mysql = new MySQLConnection();
        $mysql->insert($sql);
    }

    public function updateRecord($entity, $id, $fields){
        $sql = "UPDATE leasing." . $entity . " SET ";

        foreach($fields as $key => $value)
        {
            if(!empty($value))
                $sql .= $key . " = '" . $value . "', ";
        }

        $sql = substr($sql, 0, -2);
        $sql .= " WHERE id = " . $id . ";";

        $mysql = new MySQLConnection();
        $mysql->update($sql);
    }

    public function deleteRecord($entity, $id){
        $sql =  "DELETE FROM leasing." . $entity .
                " WHERE id = " . $id . ";";

        $mysql = new MySQLConnection();
        $mysql->delete($sql);
    }

    public function deleteReaderFromRoom($room_id, $panel_id, $reader_id){
        $sql =  "DELETE FROM leasing.rooms_readers" .
                " WHERE room_id = " . $room_id .
                " AND panel_id = " . $panel_id .
                " AND reader_id = " . $reader_id . ";";

        $mysql = new MySQLConnection();
        $mysql->delete($sql);
    }

    public function fetchRecord($entity, $id){
        $sql =  "SELECT * FROM leasing." . $entity .
                " WHERE id = " . $id . " LIMIT 1;";

        $mysql = new MySQLConnection();
        $record = $mysql->fetchAll($sql);

        return $record;
    }

    public function fetchLatestRecord($entity){
        $sql =  "SELECT * FROM leasing." . $entity .
            " ORDER BY id DESC LIMIT 1;";

        $mysql = new MySQLConnection();
        $record = $mysql->fetchAll($sql);

        return $record;
    }

    public function fetchRoomsByCustomerID($customerID, $params = []){
        $paramClauses = [];
        if($params['datacenterName'])
            $paramClauses[] = "datacenters.commonName = '" . $params['datacenterName'] . "'";
        elseif ($params['datacenterID']) {
            $paramClauses[] = "rooms.datacenter_id = " . $params['datacenterID'];
        }
        if($params['roomName'])
            $paramClauses[] = "rooms.name = '" . $params['roomName'] . "'";
        elseif ($params['roomID']) {
            $paramClauses[] = "rooms.id = " . $params['roomID'];
        }

        $sql = "SELECT rooms.id, commonName as datacenter, name, room_type, capacity
                FROM leasing.rooms as rooms
                INNER JOIN leasing.datacenters as datacenters
                ON rooms.datacenter_id = datacenters.id
                INNER JOIN leasing.leases as leases
                ON rooms.id = leases.room_id
                WHERE leases.customer_id = " . $customerID .
                " AND " . implode(" AND ", $paramClauses) .
                " ORDER BY name LIMIT 100;";

        $mysql = new MySQLConnection();
        $rooms = $mysql->fetchAll($sql);

        return $rooms;
    }

    public function fetchRoomsByCustomerAndDatacenter($customerID, $datacenterID){
        $sql = "SELECT DISTINCT rooms.id, commonName as datacenter, rooms.name
                FROM leasing.rooms as rooms
                INNER JOIN leasing.datacenters as datacenters
                ON rooms.datacenter_id = datacenters.id
                INNER JOIN leasing.leases as leases
                ON rooms.id = leases.room_id
                WHERE leases.customer_id = " . $customerID .
                " AND rooms.datacenter_id = " . $datacenterID .
                " ORDER BY cast(substr(rooms.name, 4, 2) as int), substr(rooms.name, 5, 2) LIMIT 100;";

        $mysql = new MySQLConnection();
        $rooms = $mysql->fetchAll($sql);

        return $rooms;
    }

    public function fetchRooms($type = null){
        $sql = "SELECT rooms.id, commonName as datacenter, name, room_type, capacity
                FROM leasing.rooms as rooms
                INNER JOIN leasing.datacenters as datacenters
                ON rooms.datacenter_id = datacenters.id";

        if($type)
            $sql .= " WHERE rooms.room_type = '" . $type . "'";

        $sql .= " LIMIT 100;";

        $mysql = new MySQLConnection();
        $rooms = $mysql->fetchAll($sql);

        return $rooms;
    }

    public function fetchRoom($id){
        $sql = "SELECT rooms.id, commonName as datacenter, name, room_type, capacity
                FROM leasing.rooms as rooms
                INNER JOIN leasing.datacenters as datacenters
                ON rooms.datacenter_id = datacenters.id
                WHERE rooms.id = " . $id .
                " LIMIT 1;";

        $mysql = new MySQLConnection();
        $rooms = $mysql->fetchAll($sql);

        return $rooms;
    }

    public function fetchRoomsByDatacenter($dc){
        $sql = "SELECT commonName as datacenter, name, room_type, capacity
                FROM leasing.rooms
                INNER JOIN leasing.datacenters as datacenters
                ON rooms.datacenter_id = datacenters.id
                WHERE datacenters.commonName = '" . $dc .
                "' ORDER BY name LIMIT 100;";

        $mysql = new MySQLConnection();
        $rooms = $mysql->fetchAll($sql);

        return $rooms;
    }

    public function fetchRoomsByDatacenterID($dc){
        $sql = "SELECT *
                FROM leasing.rooms as rooms
                WHERE rooms.datacenter_id = " . $dc .
                " ORDER BY name LIMIT 100;";

        $mysql = new MySQLConnection();
        $rooms = $mysql->fetchAll($sql);

        return $rooms;
    }

    public function fetchLeasesByRoom($id){
        $sql = "SELECT *
                FROM leasing.leases
                WHERE room_id = " . $id .
                " LIMIT 100;";

        $mysql = new MySQLConnection();
        $leases = $mysql->fetchAll($sql);

        return $leases;
    }

    public function fetchReadersByRoom($id){
        $sql = "SELECT panel_id, reader_id
                FROM leasing.rooms_readers
                WHERE room_id = " . $id .
                " LIMIT 100;";

        $mysql = new MySQLConnection();
        $readers = $mysql->fetchAll($sql);

        return $readers;
    }

    public function fetchReadersByCustomerDatacenterRoom($customerID, $datacenterID, $roomID){
        $sql = "SELECT DISTINCT panel_id, reader_id
                FROM leasing.rooms_readers as rooms_readers
                INNER JOIN leasing.rooms as rooms
                ON rooms_readers.room_id = rooms.id
                INNER JOIN leasing.datacenters as datacenters
                ON rooms.datacenter_id = datacenters.id
                INNER JOIN leasing.leases as leases
                ON rooms.id = leases.room_id
                WHERE leases.customer_id = " . $customerID
            . " AND datacenters.id = " . $datacenterID
            . " AND rooms.id = " . $roomID
            . " LIMIT 100;";

        $mysql = new MySQLConnection();
        $readers = $mysql->fetchAll($sql);

        $lenel = new Lenel();
        foreach($readers as &$reader)
        {
            $reader['name'] = $lenel->getReaderName($reader)[0]['name'];
        }

        return $readers;
    }

    public function fetchReadersByCustomerID($customerID, $params){
        $paramClauses = [];
        if($params['datacenterName'])
            $paramClauses[] = "datacenters.commonName = '" . $params['datacenterName'] . "'";
        elseif ($params['datacenterID']) {
            $paramClauses[] = "rooms.datacenter_id = " . $params['datacenterID'];
        }
        if($params['roomName'])
            $paramClauses[] = "rooms.name = '" . $params['roomName'] . "'";
        elseif ($params['roomID']) {
            $paramClauses[] = "rooms.id = " . $params['roomID'];
        }

        if(count($paramClauses))
            $paramClause = " AND " . implode(" AND ", $paramClauses);
        else
            $paramClause = "";

        $sql = "SELECT panel_id, reader_id
                FROM leasing.rooms_readers as rooms_readers
                INNER JOIN leasing.rooms as rooms
                ON rooms_readers.room_id = rooms.id
                INNER JOIN leasing.datacenters as datacenters
                ON rooms.datacenter_id = datacenters.id
                INNER JOIN leasing.leases as leases
                ON rooms.id = leases.room_id
                WHERE leases.customer_id = " . $customerID .
            $paramClause .
            " LIMIT 100;";

        $mysql = new MySQLConnection();
        $readers = $mysql->fetchAll($sql);

        return $readers;
    }

    public function constructParamClause($params){
        $paramClause = "";
        $paramClauses = [];
        if(isset($params['customerName']) && $params['customerName'])
            $paramClauses[] = "customers.name = '" . $params['customerName'] . "'";
        elseif (isset($params['customerID']) && $params['customerID']) {
            $paramClauses[] = "leases.customer_id = " . $params['customerID'];
        }
        if(isset($params['datacenterName']) && $params['datacenterName'])
            $paramClauses[] = "datacenters.commonName = '" . $params['datacenterName'] . "'";
        elseif (isset($params['datacenterID']) && $params['datacenterID']) {
            $paramClauses[] = "rooms.datacenter_id = " . $params['datacenterID'];
        }
        if(isset($params['roomName']) && $params['roomName'])
            $paramClauses[] = "rooms.name = '" . $params['roomName'] . "'";
        elseif (isset($params['roomID']) && $params['roomID']) {
            $paramClauses[] = "rooms.id = " . $params['roomID'];
        }
        if(isset($params['panelID']) && $params['panelID'])
            $paramClauses[] = "rooms_readers.panel_id = '" . $params['panelID'] . "'";
        if(isset($params['readerID']) && $params['readerID'])
            $paramClauses[] = "rooms_readers.reader_id = '" . $params['readerID'] . "'";

        if(count($paramClauses))
            $paramClause = " WHERE " . implode(" AND ", $paramClauses);
        
        return $paramClause;
    }

    public function fetchLeases($params){
        $paramClause = $this->constructParamClause($params);

        $sql = "SELECT leases.* , datacenters.commonName as datacenter, rooms.name as room, customers.name as customer
                FROM leasing.leases
                INNER JOIN leasing.rooms
                ON leases.room_id = rooms.id
                INNER JOIN leasing.datacenters
                ON rooms.datacenter_id = datacenters.id
                INNER JOIN leasing.customers
                ON leases.customer_id = customers.id" .
                $paramClause .
                " LIMIT 100;";

        $mysql = new MySQLConnection();
        $readers = $mysql->fetchAll($sql);

        return $readers;
    }

    public function fetchReaders($params){
        $paramClause = $this->constructParamClause($params);

        $sql = "SELECT DISTINCT panel_id, reader_id
                FROM leasing.rooms_readers as rooms_readers
                INNER JOIN leasing.rooms as rooms
                ON rooms_readers.room_id = rooms.id
                INNER JOIN leasing.datacenters as datacenters
                ON rooms.datacenter_id = datacenters.id
                INNER JOIN leasing.leases as leases
                ON rooms.id = leases.room_id
                INNER JOIN leasing.customers as customers
                ON leases.customer_id = customers.id" .
                $paramClause .
                " LIMIT 100;";

        $mysql = new MySQLConnection();
        $readers = $mysql->fetchAll($sql);

        return $readers;
    }

    public function fetchReadersWithRooms($params){
        $paramClause = $this->constructParamClause($params);

        $sql = "SELECT panel_id, reader_id, datacenters.commonName as datacenter, rooms.name as room
                FROM leasing.rooms_readers as rooms_readers
                INNER JOIN leasing.rooms as rooms
                ON rooms_readers.room_id = rooms.id
                INNER JOIN leasing.datacenters as datacenters
                ON rooms.datacenter_id = datacenters.id
                INNER JOIN leasing.leases as leases
                ON rooms.id = leases.room_id
                INNER JOIN leasing.customers as customers
                ON leases.customer_id = customers.id" .
                $paramClause .
                "order by datacenter asc, cast(substr(room, 4, 2) as int), substr(room, 5, 2);";

        $mysql = new MySQLConnection();
        $readers = $mysql->fetchAll($sql);

        return $readers;
    }

    public function fetchDatacentersByCustomer($customerID){
        $sql = "SELECT DISTINCT datacenters.id as id, datacenters.commonName as datacenter
                FROM leasing.datacenters as datacenters
                INNER JOIN leasing.rooms as rooms
                ON rooms.datacenter_id = datacenters.id
                INNER JOIN leasing.leases as leases
                ON rooms.id = leases.room_id
                WHERE leases.customer_id = " . $customerID .
            " ORDER BY commonName LIMIT 100;";

        $mysql = new MySQLConnection();
        $rooms = $mysql->fetchAll($sql);

        return $rooms;
    }

    public function getActiveDatacenters() {
        $sql = "SELECT distinct lower(leasing.datacenters.commonName) as datacenter, 
            leasing.computer_rooms.name as room
        FROM leasing.computer_rooms
            INNER JOIN leasing.datacenters
                ON leasing.datacenters.id = leasing.computer_rooms.datacenter_id
            join leasing.leases on leasing.computer_rooms.id = leasing.leases.room_id and (leasing.leases.begin_date < NOW() or leasing.leases.begin_date is NULL)
        where leasing.datacenters.date_in_service is not null and leasing.computer_rooms.capacity is not null 
        ORDER BY datacenter, cast(substr(room, 4, 2) as int), substr(room, 5, 2)";

        $mysql = new MySQLConnection();
        $rooms = $mysql->fetchAll($sql);

        return $rooms;
    }
}
