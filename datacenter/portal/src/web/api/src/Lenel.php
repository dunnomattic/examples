<?php

class Lenel
{
    public function constructParamClause($params){
        $paramClause = "";
        $paramClauses = [];

        foreach ($params as $key => $param) {
            $paramClauses[] = $key . " = " . $param;
        }

        if(count($paramClauses))
            $paramClause = " WHERE " . implode(" AND ", $paramClauses);
        
        return $paramClause;
    }

    public function fetchAll($entity, $params=[])
    {
        $sql = "SELECT TOP (100) * FROM [ACCESSCONTROL].[dbo].[" . $entity . "]" . $this->constructParamClause($params);

        $mssql = new MSSQLConnection();
        $records = $mssql->fetchAll($sql);

        return $records;
    }

    public function fetchAccessLevel($id)
    {
        $sql = "SELECT TOP (1) ACCESSLVID, DESCRIPT
                FROM [ACCESSCONTROL].[dbo].[ACCESSLVL]
                WHERE [ACCESSLVID] = " . $id;

        $mssql = new MSSQLConnection();
        $record = $mssql->fetchRecord($sql);

        return $record;
    }

    public function fetchAccessLevelReaders($id)
    {
        $sql = "SELECT TOP (100) reader.READERDESC, reader.PANELID, reader.READERID
                FROM [ACCESSCONTROL].[dbo].[READER] as reader
                INNER JOIN [ACCESSCONTROL].[dbo].[ACCLVLINK] as acclvlink
                ON reader.[PANELID] = acclvlink.[PANELID] and reader.[READERID] = acclvlink.[READERID]
                WHERE acclvlink.[ACCESSLVID] = " . $id;

        $mssql = new MSSQLConnection();
        $record = $mssql->fetchAll($sql);

        return $record;
    }

    public function fetchRecord($entity, $id)
    {
        $sql = "SELECT TOP (1) * FROM [ACCESSCONTROL].[dbo].[" . $entity .
            "] WHERE [ID] = " . $id;

        $mssql = new MSSQLConnection();
        $record = $mssql->fetchRecord($sql);

        return $record;
    }

    public function constructReaderActivityClause($readers)
    {
        $whereClause = " AND (";
        foreach ($readers as $reader) {
            $whereClause .= "([MACHINE] = ". $reader['panel_id'] . " AND [DEVID] = " . $reader['reader_id'] . ") OR ";
        }
        $whereClause = substr($whereClause, 0, -4);

        return $whereClause;
    }

    public function getActivity($customer, $datacenter, $room, $start_date, $end_date)
    {
        $leasing = new Leasing();
        $params = [
            'customerName' => $customer,
            'datacenterName' => $datacenter,
            'roomName' => $room
        ];
        $readers = $leasing->fetchReadersWithRooms($params);
        $readerActivities = $this->getReaderActivity($readers, $start_date, $end_date);
        $activity = $this->mapReaderActivityToRooms($readerActivities, $readers);
        return $activity;
    }

    public function getActivityTest($customer, $datacenter, $room, $start_date, $end_date)
    {
        $leasing = new Leasing();
        $params = [
            'customerName' => $customer,
            'datacenterName' => $datacenter,
            'roomName' => $room
        ];
        $readers = $leasing->fetchReadersWithRooms($params);
        return $readers;
        $readerActivities = $this->getReaderActivity($readers, $start_date, $end_date);
        $activity = $this->mapReaderActivityToRooms($readerActivities, $readers);
        return $activity;
    }

    public function mapReaderActivityToRooms(&$readerActivities, $readers)
    {
        //error_log(print_r($readers, true));
        $startstamp = microtime(true);

        //error_log(print_r($readerActivities, true));
        for ($x=0; $x < count($readerActivities); $x++) {
            foreach ($readers as $reader) {
                if(($readerActivities[$x]['panel_id'] == $reader['panel_id']) && ($readerActivities[$x]['reader_id'] == $reader['reader_id'])){
                    //$readerActivities[$x]['datacenter'] = $reader['datacenter'];
                    $readerActivities[$x]['room'] = $reader['room'];
                }
            }
        }
        //$endstamp = microtime(true);
        //$optime = $endstamp - $startstamp;
        //error_log("mapReaderActivityToRooms optime $optime");
        //error_log(print_r($readerActivities, true));
        return $readerActivities;
    }

    public function getReaderActivity($readers, $start_date, $end_date)
    {
        $whereClause = $this->constructReaderActivityClause($readers);

        $sql = "SELECT TOP 20000
                      [MACHINE] as panel_id
                      ,[DEVID] as reader_id
                      ,NULL as room
                      ,emp.[LASTNAME] as 'Last Name'
                      ,emp.[FIRSTNAME] as 'First Name'
                      ,[CARDNUM]
                      ,dept.[NAME] as Department
                      ,convert(varchar(20), DATEADD(mi, DATEDIFF(mi, GETUTCDATE(), GETDATE()), events.[EVENT_TIME_UTC]), 120) AS LocalTime
                      ,eventype.[EVTDESCR] as event
                      ,accesspane.[NAME] as panel_name
                      ,reader.[READERDESC]
                      FROM [dbo].[EVENTS] as events
                      LEFT JOIN [dbo].[READER] as reader
                      ON reader.[PANELID] = events.[MACHINE] AND reader.[READERID] = events.[DEVID]
                      LEFT JOIN [dbo].[EMP] as emp
                      ON emp.[ID] = events.[EMPID]
                      LEFT JOIN [dbo].[UDFEMP] as udfemp
                      ON emp.[ID] = udfemp.[ID]
                      LEFT JOIN [dbo].[TITLE] as title
                      ON title.[ID] = udfemp.[TITLE]
                      LEFT JOIN [dbo].[DEPT] as dept
                      ON dept.[ID] = udfemp.[DEPT]
                      LEFT JOIN [dbo].[ACCESSPANE] as accesspane
                      on accesspane.[PANELID] = reader.[PANELID]
                      LEFT JOIN [dbo].[EVENTYPE] as eventype
                      on  eventype.[EVTYPEID] = events.[EVENTTYPE]
                      WHERE CARDNUM > 0
                        and DATEADD(mi, DATEDIFF(mi, GETUTCDATE(), GETDATE()), events.[EVENT_TIME_UTC]) >= '". $start_date . " 00:00:00' 
                        AND DATEADD(mi, DATEDIFF(mi, GETUTCDATE(), GETDATE()), events.[EVENT_TIME_UTC]) <= '". $end_date . " 23:59:59'" .
                      $whereClause . ") ORDER BY [EVENT_TIME_UTC] ASC";


        
        //error_log(str_replace("\n", "", $sql));
        $mssql = new MSSQLConnection();
        //$startstamp = microtime(true);
        $records = $mssql->fetchAll($sql);
        //$endstamp = microtime(true);
        //$optime = $endstamp - $startstamp;
        //error_log("getReaderActivity optime $optime");

        return $records;
    }

    public function constructReaderAccessClause($readers, $tableName = "reader")
    {
        $whereClause = " (";
        foreach ($readers as $reader) {
            $whereClause .= "(".$tableName.".[PANELID] = ". $reader['panel_id'] . " AND ". $tableName .".[READERID] = " . $reader['reader_id'] . ") OR ";
        }
        $whereClause = substr($whereClause, 0, -4);

        return $whereClause;
    }

    public function getCardholderByAccessLevel($accessLevelID)
    {
        $sql = "SELECT DISTINCT badge.[ID] as 'Badge ID'
              ,badgetyp.[NAME] as 'Badge Name'
              ,badgstat.[NAME] as 'Badge Status'
              ,emp.[ID] as 'Employee ID'
              ,emp.[FIRSTNAME] as 'First Name'
              ,emp.[LASTNAME] as 'Last Name'
              ,accesslvl.[ACCESSLVID]
              FROM [dbo].[BADGE] as badge
              INNER JOIN [dbo].[BADGETYP] as badgetyp
              ON badgetyp.[ID] = badge.[TYPE]
              INNER JOIN [dbo].[EMP] as emp
              ON emp.[ID] = badge.[EMPID]
              INNER JOIN [dbo].[BADGELINK] as badgelink
              ON badgelink.[BADGEKEY] = badge.[BADGEKEY]
              INNER JOIN [dbo].[ACCESSLVL] as accesslvl
              on accesslvl.[ACCESSLVID] = badgelink.[ACCLVLID]
              INNER JOIN [dbo].[BADGSTAT] as badgstat
              ON badgstat.[ID] = badge.[STATUS]
              WHERE badge.[STATUS] != 38 AND accesslvl.[ACCESSLVID] = '" . $accessLevelID .
              "' ORDER BY emp.[LASTNAME]";

        $mssql = new MSSQLConnection();
        $cardholders = $mssql->fetchAll($sql);

        return $cardholders;
    }

    public function getEmptyAccessLevel()
    {
        $sql = "SELECT TOP (1000)
              FROM [dbo].[ACCESSLVL] as accesslvl
              INNER JOIN [dbo].[BADGELINK] as badgelink
              ON accesslvl.[ACCESSLVID] = badgelink.[ACCLVLID]
              INNER JOIN [dbo].[BADGE] as badge
              ON badgelink.[BADGEKEY] = badge.[BADGEKEY]
              INNER JOIN [dbo].[BADGETYP] as badgetyp
              ON badgetyp.[ID] = badge.[TYPE]
              INNER JOIN [dbo].[EMP] as emp
              ON emp.[ID] = badge.[EMPID]
              INNER JOIN [dbo].[BADGSTAT] as badgstat
              ON badgstat.[ID] = badge.[STATUS]";

        $mssql = new MSSQLConnection();
        $cardholders = $mssql->fetchAll($sql);

        return $cardholders;
    }

    private function fetchAccessByCardholders($readers)
    {
        $whereClause = $this->constructReaderAccessClause($readers);

        $sql = "SELECT DISTINCT badge.[ID] as 'Badge ID'
              ,badgetyp.[NAME] as 'Badge Name'
              ,badgstat.[NAME] as 'Badge Status'
              ,[EMPID] as 'Employee ID'
              ,emp.[FIRSTNAME] as 'First Name'
              ,emp.[LASTNAME] as 'Last Name'
              ,dept.[NAME] as Department
              FROM [dbo].[BADGE] as badge
              INNER JOIN [dbo].[BADGETYP] as badgetyp
              ON badgetyp.[ID] = badge.[TYPE]
              INNER JOIN [dbo].[EMP] as emp
              ON emp.[ID] = badge.[EMPID]
              INNER JOIN [dbo].[UDFEMP] as udfemp
              ON emp.[ID] = udfemp.[ID]
              INNER JOIN [dbo].[TITLE] as title
              ON title.[ID] = udfemp.[TITLE]
              INNER JOIN [dbo].[DEPT] as dept
              ON dept.[ID] = udfemp.[DEPT]
              INNER JOIN [dbo].[DIVISION] as division
              ON division.[ID] = udfemp.[DIVISION]
              INNER JOIN [dbo].[BUILDING] as building
              ON building.[ID] = udfemp.[BUILDING]
              INNER JOIN [dbo].[LOCATION] as location
              ON location.[ID] = udfemp.[LOCATION]
              INNER JOIN [dbo].[BADGELINK] as badgelink
              ON badgelink.[BADGEKEY] = badge.[BADGEKEY]
              INNER JOIN [dbo].[ACCESSLVL] as accesslvl
              on accesslvl.[ACCESSLVID] = badgelink.[ACCLVLID]
              INNER JOIN [dbo].[BADGSTAT] as badgstat
              ON badgstat.[ID] = badge.[STATUS]
              INNER JOIN [dbo].[ACCLVLINK] as acclvlink
              ON accesslvl.[ACCESSLVID] = acclvlink.[ACCESSLVID]
              INNER JOIN [dbo].[READER] as reader
              ON reader.[PANELID] = acclvlink.[PANELID] and reader.[READERID] = acclvlink.[READERID]
              WHERE" . $whereClause . ") AND badgstat.[NAME] = 'Active' " . ($badgeID != null ? "and badge.[ID] = $badgeID " : "") . "
              ORDER BY dept.[NAME] DESC, emp.[LASTNAME]";

        $mssql = new MSSQLConnection();
        $accessList = $mssql->fetchAll($sql);

        return $accessList;
    }

    public function getAccessList($customer, $datacenter, $room)
    {
        $leasing = new Leasing();
        $params = [
            'customerName' => $customer,
            'datacenterName' => $datacenter,
            'roomName' => $room
        ];
        $readers = $leasing->fetchReaders($params);
        $accessList = $this->fetchAccessByCardholders($readers);

        return $accessList;
    }

    public function getReaderName($reader)
    {
        $sql = "SELECT TOP (1) reader.[READERDESC] as name
                FROM [ACCESSCONTROL].[dbo].[READER] as reader
                WHERE (reader.[PANELID] = ". $reader['panel_id'].  " AND reader.[READERID] = " . $reader['reader_id'] . ")";

        $mssql = new MSSQLConnection();
        $record = $mssql->fetchAll($sql);

        return $record;
    }

    public function getAccessListByReader($customer, $datacenter, $room, $panelID, $readerID, $raw = "")
    {
        $leasing = new Leasing();
        $params = [
            'customerName' => $customer,
            'datacenterName' => $datacenter,
            'roomName' => $room,
            'panelID' => $panelID,
            'readerID' => $readerID
        ];
        $accessList = [];
        $readers = $leasing->fetchReaders($params);
        foreach ($readers as $reader)
        {
            $readerName = $this->getReaderName($reader);
            $accessLevels = $this->fetchAccessLevelsByReader($reader);
            $grouping = [];
            foreach ($accessLevels as $accessLevel)
            {
                $cardholders = $this->getCardholderByAccessLevel($accessLevel['access_level_id']);
                foreach ($cardholders as &$cardholder) {
                    $cardholder['Reader Name'] = $readerName[0]["name"];
                }
                if ($raw) {
                    $accessList = array_merge($accessList, $cardholders);
                }
                else {
                    $grouping = array_merge($grouping,$cardholders);
                }
            }
            if (empty($raw)) {
                $accessList[$readerName[0]["name"]] = $grouping;
            }
        }
        return $accessList;
    }

    public function getAccessListByEmployee($customer, $datacenter, $room)
    {
        $accessList = $this->getAccessListByReader($customer, $datacenter, $room);
        $employeeList = [];
        foreach ($accessList as $reader => $employees)
        {
            foreach ($employees as $employee)
            {
                if (!isset($employeeList[$employee['Badge ID']]))
                    $employeeList[$employee['Badge ID']] = $employee;
                if (!in_array($reader, $employeeList[$employee['Badge ID']]['readers']))
                    $employeeList[$employee['Badge ID']]['readers'][] = $reader;
            }
        }
        return $employeeList;
    }

    public function getAccessListByRoom($datacenter, $room)
    {
        $leasing = new Leasing();
        $params = [
            'datacenterName' => $datacenter,
            'roomName' => $room
        ];
        $accessList = [];
        $readers = $leasing->fetchReaders($params);
        foreach ($readers as $reader)
        {
            $readerName = $this->getReaderName($reader);
            $accessLevels = $this->fetchAccessLevelsByReader($reader);
            $grouping = [];
            foreach ($accessLevels as $accessLevel)
            {
                $grouping = array_merge($grouping,$this->getCardholderByAccessLevel($accessLevel['access_level_id']));
            }
            $accessList[$readerName[0]["name"]] = $grouping;
        }
        return $accessList;
    }

    public function getAccessListByDatacenter($customer, $datacenter)
    {
        $leasing = new Leasing();
        $params = [
            'customerName' => $customer,
            'datacenterName' => $datacenter,
        ];
        $accessList = [];
        $readers = $leasing->fetchReadersWithRooms($params);
//        return $readers;
        foreach ($readers as $reader)
        {
            $readerName = $this->getReaderName($reader);
            $accessLevels = $this->fetchAccessLevelsByReader($reader);
            $grouping = [];
            foreach ($accessLevels as $accessLevel)
            {
                $grouping = array_merge($grouping,$this->getCardholderByAccessLevel($accessLevel['access_level_id']));
            }
            $accessList[$reader["room"]][$readerName[0]["name"]] = $grouping;
        }
        return $accessList;
    }

    private function fetchAccessLevelsByReaders($readers)
    {
        $whereClause = $this->constructReaderAccessClause($readers, "ACCLVLINK");

        $sql = "SELECT DISTINCT accesslvl.[ACCESSLVID] as access_level_id
                ,accesslvl.[DESCRIPT] as description
                FROM [ACCESSCONTROL].[dbo].[ACCESSLVL] as accesslvl
                INNER JOIN [ACCESSCONTROL].[dbo].[ACCLVLINK] as acclvlink
                ON accesslvl.[ACCESSLVID] = acclvlink.[ACCESSLVID]
                WHERE" . $whereClause . ")";

        $mssql = new MSSQLConnection();
        $accessList = $mssql->fetchAll($sql);

        return $accessList;
    }

    private function fetchAccessLevelsByReader($reader)
    {
        $sql = "SELECT DISTINCT accesslvl.[ACCESSLVID] as access_level_id
                ,accesslvl.[DESCRIPT] as description
                FROM [ACCESSCONTROL].[dbo].[ACCESSLVL] as accesslvl
                INNER JOIN [ACCESSCONTROL].[dbo].[ACCLVLINK] as acclvlink
                ON accesslvl.[ACCESSLVID] = acclvlink.[ACCESSLVID]
                WHERE (acclvlink.[PANELID] = ". $reader['panel_id'].  " AND acclvlink.[READERID] = " . $reader['reader_id'] . ")";

        $mssql = new MSSQLConnection();
        $accessList = $mssql->fetchAll($sql);

        return $accessList;
    }
}
