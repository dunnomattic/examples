<?php

namespace Dashboard;

class Mango {

    protected $server;
    protected $username;
    protected $password;
    protected $cookie;
    protected $loggedIn;
    protected $token;
    public $version;

    public function __construct($server = 'localhost') {
        $this->server = $server;
        $this->username = getenv('MANGO_USERNAME');
        $this->password = getenv('MANGO_PASSWORD');
        if ($server == '1.2.3.4:8080' || $server == '1.2.3.4:8080' || $server == '1.2.3.4:8080') {
            $this->version = 3;
        }
        else
            $this->version = 2;
    }

    /**
     * @return string
     */
    public function getCookie()
    {
        return $this->cookie;
    }

    /**
     * @param string $cookie
     */
    public function setCookie($cookie)
    {
        $this->cookie = $cookie;
    }

    public function login()
    {
        if($this->version == 3)
            return $this->loginv3();

        if ($this->loggedIn)
            return true;

        $endpoint = $this->server . "/rest/v1/login/" . $this->username;

        $login_response = $this->makeGETCall($endpoint, array('password' => $this->password));
        $this->loggedIn = true;
        $this->username = $login_response->username;

        return $login_response;
    }

    public function loginv3()
    {
        if ($this->loggedIn)
            return true;

        $endpoint = $this->server . "/rest/v2/login";

        $authentication = array("username" => $this->username, "password" => $this->password);
        $login_response = $this->loginPost($endpoint, $authentication);
        //error_log(print_r($login_response, true));
        $this->loggedIn = true;

        $this->username = $login_response->username;

        return $login_response;
    }

    public function getCall($url)
    {
        $ch = curl_init();
        $headers = $this->setHeaders($this->token, $this->cookie);

        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_VERBOSE => TRUE,
            CURLOPT_HEADER => TRUE,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_URL => $url
        ));

        $response=curl_exec($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $response_header = substr($response, 0, $header_size);
        $response_body = substr($response, $header_size);

        curl_close($ch);

        return json_decode($response_body);
    }

    public function loginPost($url, $body=null)
    {
        // $token = bin2hex(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM)); // Depricated in 7.1
        $token = bin2hex(random_bytes(32));

        $ch = curl_init();
        $headers = $this->setHeaders($token);

        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_VERBOSE => TRUE,
            CURLOPT_HEADER => TRUE,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POST => TRUE,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_URL => $url
        ));


        $response=curl_exec($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $response_header = substr($response, 0, $header_size);
        $response_body = substr($response, $header_size);

        curl_close($ch);

        $this->grabCookie($response_header);
        $this->grabToken($response_header);

        return json_decode($response_body);
    }

    private function grabCookie($response_header) // void return type introduced in 7.1
    {
        if($this->version == 3)
            $regex = '/^Set-Cookie: MANGO8080=\s*([^;]*)/mi';
        else
            $regex = '/^Set-Cookie: MANGO80=\s*([^;]*)/mi';

        if (preg_match($regex, $response_header, $m)) {
            parse_str($m[1], $cookies);
            if (isset($cookies))
                $this->setCookie($m[1]);
        }
    }

    private function grabToken($response_header) // void return type introduced in 7.1
    {
        if(preg_match('/^Set-Cookie: XSRF-TOKEN=\s*([^;]*)/mi', $response_header, $m))
        {
            parse_str($m[1], $cookies);
            if (isset($cookies))
                $this->setToken($m[1]);
        }
    }

    private function setHeaders($token=null, $cookie=null)
    {
        if($this->version == 2)
            $mango_header = 'Cookie:MANGO80=';
        else
            $mango_header = 'Cookie:MANGO8080=';

        if ($cookie) {
            $cookie = $mango_header . $cookie . '; XSRF-TOKEN=' . $token;
        } else {
            $cookie = 'Cookie:XSRF-TOKEN=' . $token;
        }

        return [
            'Accept: application/json',
            'Content-Type: application/json;charset=UTF-8',
            'Connection: keep-alive',
            'Cache-Control: no-cache',
            'logout: true',
            $cookie,
            'X-XSRF-TOKEN:'.$token
        ];
    }

    public function logout($username = null)
    {
//        if(!$this->loggedIn)
//            return true;

        $endpoint = $this->server . "/rest/v1/logout/";

        if ($username != null)
            $endpoint .= $username;

        $logout_response = $this->makeGETCall($endpoint, array('cookie' => $this->cookie, 'token' => $this->token)); //response is NULL if user is already logged out
        $this->loggedIn = false;

        return $logout_response;
    }

    public function getBulkElectrical($datacenter, $computer_room)
    {
        $endpoint = $this->server . "/rest/v1/realtime?like(path,%2F" . strtoupper($datacenter) . "%2FCOLO%20" . strtoupper($computer_room) . "%2FAll%20Electrical%2F)";

        $response = $this->makeGETCall($endpoint, array('cookie' => $this->cookie, 'token' => $this->token));

        return $response;
    }

    public function getBulkMechanical($datacenter, $computer_room)
    {
        $endpoint = $this->server . "/rest/v1/realtime?format=json&like(path,%2F" . strtoupper($datacenter) . "%2FCOLO%20" . strtoupper($computer_room) . "%2FAll%20Mechanical%2F)";

        $response = $this->makeGETCall($endpoint, array('cookie' => $this->cookie, 'token' => $this->token));
        return $response;
    }

    public function getPointValues($id = null)
    {
        if (!$this->loggedIn)
            $this->login();

        $endpoint = $this->server . "/rest/v1/pointValues/";

        if($id != null)
            $endpoint .= $id . '/latest?limit=10000';

        $hierarchy_response = $this->getCall($endpoint);

        return $hierarchy_response;
    }

    public function getHierarchyByName($folderName)
    {
        if (!$this->loggedIn)
            $this->login();

        $endpoint = $this->server . "/rest/v1/hierarchy/by-name/" . $folderName; // may need to add full to account for changes in api

        $hierarchy_response = $this->getCall($endpoint);

        return $hierarchy_response;
    }

    public function getDataSources()
    {
        if (!$this->loggedIn)
            $this->login();

        $endpoint = $this->server . "/rest/v1/hierarchy/data-sources"; // may need to add full to account for changes in api

        $data_sources = $this->getCall($endpoint);

        return $data_sources;
    }

    public function getHierarchy($room_id = null)
    {
        if (!$this->loggedIn)
            $this->login();

        $endpoint = $this->server . "/rest/v1/hierarchy/"; // may need to add full to account for changes in api

        if($room_id != null)
            $endpoint .= "by-id/" . $room_id; // changed from 'byId'
        else
            $endpoint .= "full/";

        $hierarchy_response = $this->makeGETCall($endpoint, array('cookie' => $this->cookie, 'token' => $this->token));
        $hierarchy_response = $this->getCall($endpoint);

        return $hierarchy_response;
    }

    public function getDataPoints($id = null)
    {
        if (!$this->loggedIn)
            $this->login();

        $endpoint = $this->server . "/rest/v1/dataPoints/";

        if($id != null)
            $endpoint = $endpoint . $id;

        $datapoint_response = $this->makeGETCall($endpoint, array('cookie' => $this->cookie, 'token' => $this->token));

        return $datapoint_response;
    }

    public function getRealtimeData($id = null)
    {
        if (!$this->loggedIn)
            $this->login();

        $endpoint = $this->server . "/rest/v1/realtime/";

        if ($id != null)
            $endpoint .= "/by-xid/" . $id;

        $realtime_data_response = $this->makeGETCall($endpoint, array('cookie' => $this->cookie, 'token' => $this->token));

        return $realtime_data_response;
    }

    public function getRealtimeDataValue($id) //specific for the data format and so would be outside of PHP wrapper
    {
        $realtime_data_response = $this->getRealtimeData($id);

        return $realtime_data_response->value;
    }

    public function getTrendDataFromDevices($devices, $from = null, $to = null)
    {
        $xids = array();

        foreach($devices as $device)
        {
            foreach($device['devicePoints'] as $devicePoint)
            {
                $xids[] = $devicePoint['xid'];
            }
        }

        $endpoints = array();
        $endpoint = $this->server . "/rest/v1/point-values/"; // changed from /pointValues

        if(is_null($from) || is_null($to))
        {
            foreach($xids as $xid)
            {
                $endpoints[$xid] = $endpoint . $xid . '/latest?limit=3000';
            }
        }
        else
        {
            foreach($xids as $xid)
            {
                $endpoints[$xid] = $endpoint . $xid . '?from=' . $from . 'T00%3A00%3A00.000-04%3A00&to=' . $to . 'T23%3A59%3A59.999-04%3A00';
            }
        }

        return $this->makeTrendCalls($endpoints, array('cookie' => $this->cookie, 'token' => $this->token));
    }

    public function getTrendDataFromPoints($points, $from = null, $to = null)
    {
        $xids = array();

        foreach($points as $point)
        {
            foreach($point['deviceNames'] as $deviceName)
            {
                $xids[] = $deviceName['xid'];
            }
        }

        $endpoints = array();
        $endpoint = $this->server . "/rest/v1/point-values/"; // changed from /pointValues

        if(is_null($from) || is_null($to))
        {
            foreach($xids as $xid)
            {
                $endpoints[$xid] = $endpoint . $xid . '/latest?limit=10000';
            }
        }
        else
        {
            foreach($xids as $xid)
            {
                $endpoints[$xid] = $endpoint . $xid . '?from=' . $from . 'T00%3A00%3A00.000-04%3A00&to=' . $to . 'T23%3A59%3A59.999-04%3A00';
            }
        }


        return $this->makeTrendCalls($endpoints, array('cookie' => $this->cookie, 'token' => $this->token));
    }

    public function getAllTrendData($room_id = null)
    {
        $xids = $this->grabAllXIDs($room_id);

        $endpoints = array();
        $endpoint = $this->server . "/rest/v1/pointValues/";

        if ($room_id == null)
            return $this->makeGETCall($endpoint, array('cookie' => $this->cookie, 'token' => $this->token));

        foreach($xids as $xid)
        {
            $endpoints[$xid] = $endpoint . $xid . '/latest?limit=10000';
        }

        if (count($endpoints) > 500)
        {
            $responses = array();
            $chunks = array_chunk($endpoints, 500);
            foreach($chunks as $chunk)
            {
                array_merge($responses, $this->makeGETCalls($chunk, array('cookie' => $this->cookie, 'token' => $this->token)));
            }
            return $responses;
        }
        else
            return $this->makeTrendCalls($endpoints, array('cookie' => $this->cookie, 'token' => $this->token));
    }

    public function getDevicesFromRoomID($roomID)
    {
        if (!$this->loggedIn)
            $this->login();

        $endpoint = $this->server . "/rest/v1/hierarchy/by-id/" . $roomID;
        $hierarchy_response = $this->getCall($endpoint);
        $points = $this->gatherPoints($hierarchy_response->points);
        $start = microtime(true);
        $values = $this->getLatestValuesFromPoints($points);
        $time_elapsed_secs = microtime(true) - $start;
//        Utils::printToConsole(array('Single call with multiple XIDs',$time_elapsed_secs));

        return $values;
    }

    public function getLatestValuesFromXids($xids, $limit = 1)
    {
        $xids_leftover = [];
        if(count($xids) > 792)
            $xids_leftover = array_slice($xids, 792);

        $xids = array_slice($xids, 0, 792);
        $joined_xids = implode(",", $xids);

        $data = [
            "limit" => $limit
        ];

        $params = "";
        foreach($data as $key=>$value)
            $params .= $key."=".$value."&";
        $params = trim($params, "&");

        $endpoint = $this->server . "/rest/v1/point-values/";
        $endpoint .= $joined_xids;
        $endpoint .= "/latest-multiple-points-single-array?";
        $endpoint .= $params;

        $values = $this->getCall($endpoint);

        if(!empty($xids_leftover))
            return array_merge($values, $this->getLatestValuesFromXids($xids_leftover));
        else
            return $values;
    }

    public function getLatestValuesFromPoints($points, $limit = 1)
    {
        $xids = $points["xids"];
        $joined_xids = implode(",", $xids);

        $points = $points["points"];

        $data = [
            "limit" => $limit
        ];

        $params = "";
        foreach($data as $key=>$value)
            $params .= $key."=".$value."&";
        $params = trim($params, "&");

        $endpoint = $this->server . "/rest/v1/point-values/";
        $endpoint .= $joined_xids;
        $endpoint .= "/latest-multiple-points-single-array?";
        $endpoint .= $params;

        $values = $this->getCall($endpoint);

        $finalValues = [];
        foreach ($values as $valued) {
            $finalValues = array_merge($finalValues, (array) $valued);
        }

        foreach ($points as &$point) {
            $point["value"] = $finalValues[$point["xid"]];
        }

        return $this->groupPointsByDevice($points);
    }

    private function groupPointsByDevice($points)
    {
        $values = [];

        foreach ($points as $point) {
            $values[$point["deviceName"]][$point["name"]] = $point["value"];
        }

        return $values;
    }

    private function gatherPoints($p)
    {
        $points = [];
        $xids = [];
        foreach ($p as $point) {
            $xids[] = $point->xid;
            $points[] = [
                "xid" => $point->xid,
                "deviceName" => $point->deviceName,
                "name" => $point->name
            ];
        }

        return [
            "points" => $points,
            "xids" => $xids
        ];

    }


    public function getAllRealtimeData($room_id = null)
    {
        $xids = $this->grabAllXIDs($room_id);
        $endpoints = array();
        $endpoint = $this->server . "/rest/v1/realtime/";

        if ($room_id == null)
            return $this->makeGETCall($endpoint, array('cookie' => $this->cookie, 'token' => $this->token));

        foreach($xids as $xid)
        {
            $endpoints[] = $endpoint . "by-xid/" . $xid; //changed from "byXid"
        }

        if (count($endpoints) > 500)
        {
            $start = microtime(true);
            $responses = array();
            $chunks = array_chunk($endpoints, 500);

            foreach($chunks as $chunk)
            {
                $response = $this->makeGETCalls($chunk, array('cookie' => $this->cookie, 'token' => $this->token));
                $responses = array_merge($responses, $response);
            }
            $time_elapsed_secs = microtime(true) - $start;
            return $responses;
        }
        else {
            $start = microtime(true);
            return $this->makeGETCalls($endpoints, array('cookie' => $this->cookie, 'token' => $this->token));
            $time_elapsed_secs = microtime(true) - $start;
        }
    }

    public function grabAllPoints($id = null)
    {
        $points = [];

        $hierarchy = $this->getHierarchy($id);

        foreach($hierarchy->points as $point)
        {
            $points[] = $point;
        }

        return $points;

    }

    public function grabAllXIDs($room_id = null)
    {
        $xids = [];

        $hierarchy = $this->getHierarchy($room_id);

        foreach($hierarchy->points as $point)
        {
            $xids[] = $point->xid;
        }

        return $xids;

    }

    public function getXIDsForDevice($room_id = null, $deviceName = null)
    {
        $xids = [];

        $hierarchy = $this->getHierarchy($room_id);

        foreach($hierarchy->points as $point)
        {
            if($point->deviceName == $deviceName)
                $xids[] = $point->xid;
        }
    }

    public function mapXID($id = null)
    {
        $mapXID = array();

        $hierarchy = $this->getHierarchy($id);

        foreach($hierarchy->points as $point)
        {
            $mapXID[$point->xid] = [
                'deviceName' => $point->deviceName,
                'name' => $point->name
            ];
        }

        return $mapXID;
    }

    public function mapDeviceToXID($room_id = null, $deviceNames = null)
    {
        $mapDevices = array();

        $hierarchy = $this->getHierarchy($room_id);

        if($deviceNames == null)
            $deviceNames = $this->getDeviceNamesFromHierarchy($hierarchy);

        foreach($deviceNames as $index => $deviceName)
        {
            $mapDevices[] = [
                'deviceName' => $deviceName,
                'devicePoints' => array()
            ];

            foreach($hierarchy->points as $point)
            {
                if($point->deviceName == $deviceName)
                {
                    $mapDevices[$index]['devicePoints'][] = [
                        'xid' => $point->xid,
                        'pointName' => $point->name
                    ];
                }
            }
        }

        return $mapDevices;
    }

    public function mapDeviceNamesToXIDs($roomIds = null, $deviceNames = null)
    {
        $mapDevices = array();
        $hierarchyPoints = array();

        foreach($roomIds as $roomId)
        {
            $hierarchy = $this->getHierarchy($roomId);
            $hierarchyPoints = array_merge($hierarchyPoints, $hierarchy->points);
        }

        foreach($deviceNames as $index => $deviceName)
        {
            $mapDevices[] = [
                'deviceName' => $deviceName,
                'devicePoints' => array()
            ];

            foreach($hierarchyPoints as $point)
            {
                if($point->deviceName == $deviceName)
                {
                    $mapDevices[$index]['devicePoints'][] = [
                        'xid' => $point->xid,
                        'pointName' => $point->name
                    ];
                }
            }
        }

        return $mapDevices;
    }

    public function mapPointNamesToXIDs($roomIds = null, $pointNames = null)
    {
        $mapPoints = array();
        $hierarchyPoints = array();

        foreach($roomIds as $roomId)
        {
            $hierarchy = $this->getHierarchy($roomId);
            $hierarchyPoints = array_merge($hierarchyPoints, $hierarchy->points);
        }

        foreach($pointNames as $index => $pointName)
        {
            $mapPoints[$index] = [
                'pointName' => $pointName,
                'deviceNames' => []
            ];

            foreach($hierarchyPoints as $point)
            {
                if(strtoupper($pointName) == strtoupper($point->name) && strtoupper($point->name) == 'KW' && strpos($point->deviceName, 'PDU'))
                {
                    $mapPoints[$index]['deviceNames'][] = [
                        'xid' => $point->xid,
                        'deviceName' => $point->deviceName
                    ];
                }
                elseif(strtoupper($pointName) == strtoupper($point->name) && strtoupper($point->name) != 'KW')
                {
                    $mapPoints[$index]['deviceNames'][] = [
                        'xid' => $point->xid,
                        'deviceName' => $point->deviceName
                    ];
                }
                elseif(strtoupper($pointName) == strtoupper($point->name) && strtoupper($point->name) == 'TKW' && strpos($point->deviceName, 'TOTALS'))
                {
                    $mapPoints[$index]['deviceNames'][] = [
                        'xid' => $point->xid,
                        'deviceName' => $point->deviceName
                    ];
                }
            }
        }

        return array_values($mapPoints);
    }

    public function getStatsFromXid($xid, $from = null, $to = null)
    {
        $endpoint = $this->server . "/rest/v1/point-values/" . $xid . "/statistics";
        $endpoint .= '?from=' . $from . 'T00%3A00%3A00.000-04%3A00&to=' . $to . 'T23%3A59%3A59.999-04%3A00';

        return $this->makeGETCall($endpoint, array('cookie' => $this->cookie, 'token' => $this->token));
    }

    public function getRealtimeFromXid($xid, $from = null, $to = null)
    {
        $endpoint = $this->server . "/rest/v1/realtime/by-xid/" . $xid;

        return $this->makeGETCall($endpoint, array('cookie' => $this->cookie, 'token' => $this->token));
    }

    public function getTrendDataFromXid($xid, $from = null, $to = null)
    {
        $endpoint = $this->server . "/rest/v1/point-values/" . $xid;

        if(is_null($from) || is_null($to))
            $endpoint .= '/latest?limit=3000';
        else
            $endpoint .= '?from=' . $from . 'T00%3A00%3A00.000-04%3A00&to=' . $to . 'T23%3A59%3A59.999-04%3A00';

        return $this->makeGETCall($endpoint, array('cookie' => $this->cookie, 'token' => $this->token));
    }

    public function getDeviceNamesFromRoomId($room_id)
    {
        $hierarchy = $this->getHierarchy($room_id);

        $deviceNames = $this->getDeviceNamesFromHierarchy($hierarchy);

        return $deviceNames;
    }

    public function getDeviceNamesFromHierarchy($hierarchy)
    {
        $deviceNames = array();

        foreach($hierarchy->points as $point)
        {
            $deviceNames[] = $point->deviceName;
        }

        return array_values(array_unique($deviceNames));
    }

    public function getUsers($username = null)
    {
        if (!$this->loggedIn)
            $this->login();

        $endpoint = $this->server . "/rest/v1/users/";

        if ($username != null)
            $endpoint = $endpoint . $username;

        $users_response = $this->makeGETCall($endpoint, array('cookie' => $this->cookie, 'token' => $this->token));


        return $users_response;
    }

    /**
     * @return string
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @param string $server
     */
    public function setServer($server)
    {
        $this->server = $server;
    }

    public function makeTrendCalls($endpoints, $headers = null)
    {
        $start = microtime(true);
        $responses = array();
        $multi = curl_multi_init();
        $channels = array();

        // Loop through the URLs, create curl-handles
        // and attach the handles to our multi-request
        foreach ($endpoints as $xid => $endpoint) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_HEADER, 0); //so that we only get body data
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch,CURLOPT_TIMEOUT,1000);

            if($this->version == 3) {
                $headers = $this->setHeaders($this->token, $this->cookie);

                curl_setopt_array($ch, array(
                    CURLOPT_HTTPHEADER => $headers,
                ));
            }
            else {
                if (isset($headers['token']))
                    curl_setopt($ch, CURLOPT_HTTPHEADER,array('Content-Type: application/json', 'Accept: application/json', 'X-XSRF-TOKEN: ' . $headers['token']));
                else
                    curl_setopt($ch, CURLOPT_HTTPHEADER,array('Content-Type: application/json', 'Accept: application/json'));

                if (isset($headers['cookie']))
                    curl_setopt($ch, CURLOPT_COOKIE, 'MANGO80='.$headers['cookie']);
            }

            curl_multi_add_handle($multi, $ch);

            $channels[$xid] = $ch;
        }

        // While we're still active, execute curl
        $active = null;
        do {
            $mrc = curl_multi_exec($multi, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            // Wait for activity on any curl-connection
            if (curl_multi_select($multi) == -1) {
                continue;
            }

            // Continue to exec until curl is ready to
            // give us more data
            do {
                $mrc = curl_multi_exec($multi, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }

        // Loop through the channels and retrieve the received
        // content, then remove the handle from the multi-handle
        foreach ($channels as $xid => $channel) {
            $responses[$xid] = json_decode(curl_multi_getcontent($channel));
            curl_multi_remove_handle($multi, $channel);
        }

        // Close the multi-handle and return our results
        curl_multi_close($multi);

        $time_elapsed_secs = microtime(true) - $start;
//        printToConsole(array($time_elapsed_secs, $endpoints));

        return $responses;
    }

    public function makeGETCalls($endpoints, $headers = null) //will be using curl library for default wrapper. hopefully can configure w/ guzzle later
    {
        $start = microtime(true);
        $responses = array();
        $multi = curl_multi_init();
        $channels = array();

        // Loop through the URLs, create curl-handles
        // and attach the handles to our multi-request
        foreach ($endpoints as $endpoint) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_HEADER, 0); //so that we only get body data
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            if (isset($headers['token']))
                curl_setopt($ch, CURLOPT_HTTPHEADER,array('Content-Type: application/json', 'Accept: application/json', 'X-XSRF-TOKEN: ' . $headers['token']));
            else
                curl_setopt($ch, CURLOPT_HTTPHEADER,array('Content-Type: application/json', 'Accept: application/json'));

            $cookie = '';
            if (isset($headers['cookie']))
                $cookie .= 'MANGO80=' . $headers['cookie'] . '; ';
            if (isset($headers['token']))
                $cookie .= 'XSRF-TOKEN=' . $headers['token'];

            if(substr($cookie, -2) == '; ')
                $cookie = substr($cookie, 0, -2);

            curl_setopt($ch, CURLOPT_COOKIE, $cookie);

            curl_multi_add_handle($multi, $ch);

            $channels[$endpoint] = $ch;
        }

        // While we're still active, execute curl
        $active = null;
        do {
            $mrc = curl_multi_exec($multi, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            // Wait for activity on any curl-connection
            if (curl_multi_select($multi) == -1) {
                continue;
            }

            // Continue to exec until curl is ready to
            // give us more data
            do {
                $mrc = curl_multi_exec($multi, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }

        // Loop through the channels and retrieve the received
        // content, then remove the handle from the multi-handle
        foreach ($channels as $channel) {
            $responses[] = json_decode(curl_multi_getcontent($channel));
            curl_multi_remove_handle($multi, $channel);
        }

        // Close the multi-handle and return our results
        curl_multi_close($multi);

        $time_elapsed_secs = microtime(true) - $start;
//        printToConsole(array($time_elapsed_secs, $endpoints));

        return $responses;
    }

    public function makeGETCall($endpoint, $headers = null)
    {
//        $start = microtime(true);
        //  Initiate curl
        $ch = curl_init();
        // Disable SSL verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // Will return the response, if false it print the response
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch,CURLOPT_ENCODING , "gzip");

        if($this->version == 3){
            $headers = $this->setHeaders($headers['token'], $headers['cookie']);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        else {
            if (isset($headers['password']))
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Accept: application/json', 'password: ' . $headers['password']));
            elseif (isset($headers['token']))
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Accept: application/json', 'X-Xsrf-Token: ' . $headers['token']));
            else
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Accept: application/json'));

            $cookie = '';
            if (isset($headers['cookie']))
                $cookie .= 'MANGO80=' . $headers['cookie'] . '; ';
            if (isset($headers['token']))
                $cookie .= 'XSRF-TOKEN=' . $headers['token'];

            if(substr($cookie, -2) == '; ')
                $cookie = substr($cookie, 0, -2);

            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }

//      Set the url
        curl_setopt($ch, CURLOPT_URL,$endpoint);

        // Execute
        $response=curl_exec($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $response_header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        // Closing
        curl_close($ch);

        $this->grabCookie($response_header);
        $this->grabToken($response_header);

//        printToConsole(array(microtime(true) - $start, $endpoint));

        return json_decode($body);
    }

    public function makePOSTCall($endpoint, $headers = null, $data = null)
    {
        $start = microtime(true);
        //  Initiate curl
        $ch = curl_init();
        // Disable SSL verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // Will return the response, if false it print the response
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch,CURLOPT_ENCODING , "gzip");

        if (isset($headers['token']))
            curl_setopt($ch, CURLOPT_HTTPHEADER,array('Content-Type: application/json', 'Accept: application/json', 'X-Xsrf-Token: ' . $headers['token']));
        else
            curl_setopt($ch, CURLOPT_HTTPHEADER,array('Content-Type: application/json', 'Accept: application/json'));

        // Set the url
        curl_setopt($ch, CURLOPT_URL,$endpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        if ($data != null)
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $cookie = '';
        if (isset($headers['cookie']))
            $cookie .= 'MANGO80=' . $headers['cookie'] . '; ';
        if (isset($headers['token']))
            $cookie .= 'XSRF-TOKEN=' . $headers['token'];

        if(substr($cookie, -2) == '; ')
            $cookie = substr($cookie, 0, -2);

        if (count($cookie))
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);

        // Execute
        $response=curl_exec($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $response_header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        // Closing
        curl_close($ch);

        $this->grabCookie($response_header);
        $this->grabToken($response_header);

//        printToConsole(array(microtime(true) - $start, $endpoint));

        return json_decode($body);
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param mixed $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

}
