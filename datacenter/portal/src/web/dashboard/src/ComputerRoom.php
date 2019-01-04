<?php
/**
 * ComputerRoom class file
 */
namespace Dashboard;

use Dashboard\Sensors\CDP;
use Dashboard\Sensors\STS;
use Dashboard\Sensors\Totals;
use Dashboard\Utils;
use Dashboard\Mango;
use Dashboard\MangoUtils;
use Dashboard\Sensors\Electrical;
use Dashboard\Sensors\PDU;
use Dashboard\Sensors\SWB;
use Dashboard\Sensors\UPS;
use Dashboard\Sensors\DistributionBoard;
use Dashboard\Sensors\BCM;
use Dashboard\Sensors\CRAH;
use Dashboard\Sensors\ZoneSensor;
use Dashboard\Sensors\StaticPressure;
use Dashboard\Sensors\FireAlarm;
use Dashboard\Sensors\Ambient;
use Dashboard\Sensors\Alert;

/**
 * Class ComputerRoom
 * @package Dashboard
 */
class ComputerRoom
{
    protected $dataCenter;
    protected $computerRoom;
    protected $authorized;

    /**
     * @param $dataCenter
     * @param $computerRoom
     */
    public function __construct($dataCenter, $computerRoom)
    {
        $this->dataCenter = $dataCenter;
        $this->computerRoom = $computerRoom;
        $this->authorized = null;
    }

    public function isAuthorized() {
        global $dbh;
        $dbh = new \Database();
        if ($this->authorized !== null) {
            //error_log("111ComputerRoom.php::this->authorized::" . $this->authorized);
            return $this->authorized;
        }
        $this->authorized = false;
        if ($_SESSION["company"] == "XXXXXX" || $_SESSION["company"] == "XXXXXY") {
            $this->authorized = true;
        }
        else {
            if ($_SESSION["company"] == "XXXXXX Demo" && strtoupper($this->dataCenter) == "ABC1") {
                $this->authorized = true;
            }
            else {
                $this->authorized = false;
                $dbh->query("select aa.id, aa.datacenter_id, bb.customer_id from leasing.computer_rooms aa join leasing.leases bb on aa.id = bb.room_id join leasing.datacenters cc on aa.datacenter_id = cc.id where cc.commonName = ? and aa.name = ? and bb.customer_id = ?");
                if ($customerRec = $dbh->resultset(array(strtoupper($this->dataCenter), "CR-" . strtoupper($this->computerRoom), $_SESSION["customer_id"]))) {
                    $this->authorized = true;
                }
            }
        }
        return $this->authorized;
    }

    public function mapDatacenterToServer() {
        switch(strtoupper($this->dataCenter)) {
            case 'DC1':
                return '1.2.3.4';
                break;
            case 'DC1':
                return '1.2.3.4';
                break;
            case 'DC1':
                return '1.2.3.4:8080';
                break;
            case 'DC1':
                return '1.2.3.4';
                break;
            case 'DC1':
                return '1.2.3.4';
                break;
            case 'DC1':
                return '1.2.3.4';
                break;
            case 'DC1':
                return '1.2.3.4';
                break;
            case 'DC1':
                return '1.2.3.4';
                break;
            case 'DC1':
                return '1.2.3.4';
                break;
            case 'DC1':
                return '1.2.3.4';
                break;
            case 'DC1':
                return '1.2.3.4';
                break;
            case 'DC1':
                return '1.2.3.4';
                break;
            case 'DC1':
                return '1.2.3.4:8080';
                break;
            case 'DC1':
                return '1.2.3.4:8080';
                break;
        }
    }

    public function getZoneHumidityXids()
    {
        $pointXids = [];

        return $pointXids;
    }

    public function getZoneTemperatureXids()
    {
        $pointXids = [];

        return $pointXids;
    }

    public function getPDUkWXids()
    {
        $pointXids = [];

        return $pointXids;
    }

    public function getHistoricalTrendsFromPoints(&$points, $trends, $start, $end, $type)
    {
        foreach ($points as &$point) {
            foreach ($point[$type] as $i => &$deviceName) {
                foreach ($trends as $index => $trend) {
                    if(count($trend) > 0) {
                        if ($index == $deviceName['xid']) {
                            if (empty($start) || empty($end)) {
                                $trend = array_reverse($trend);
                            }
                            $trend = $this->meltIcicles($trend);
                            $deviceName['historical'] = [];
                            foreach ($trend as $key => $value) {
                                    if (!empty($deviceName['pointName']) && ($deviceName['pointName'] == "PRESS" || $deviceName['pointName'] == "SP"))
                                        $v = is_numeric($value->value) ? floatval(number_format($value->value, 3, '.', '')) : $value->value;
                                    else
                                        $v = is_numeric($value->value) ? floatval(number_format($value->value, 1, '.', '')) : $value->value;
                                    $deviceName['historical'][] = array(
                                        'timestamp' => intval(round($value->timestamp, -3)),
                                        'value' => $v
                                    );
                            }
                        }
                    }
                }
            }
        }

        return $points;
    }

    public function getHistoricalTotalTrends($trend, $start, $end)
    {
        $deviceData = [];
        $deviceData['pointName'] = 'TkW';
        $trend = $this->meltIcicles($trend);
        foreach ($trend as $key => $value) {
            $v = is_numeric($value->value) ? floatval(number_format($value->value, 1, '.', '')) : $value->value;
            $deviceData['historical'][] = array(
                'timestamp' => intval(round($value->timestamp, -3)),
                'value' => $v
            );
        }

        return $deviceData;
    }

    protected function meltIcicles($trend)
    {
        foreach($trend as $key => $value) {
            if ($key > 0 && $key < count($trend) && $value->value == 0 && $trend[$key-1]->value != 0 && $trend[$key+1]->value != 0)
                $value->value = null;
        }
        return $trend;
    }

    protected function detectFlatTrend($trend)
    {
        $flatline = [];
        foreach($trend as $key => $value) {
            if ($key > 0 && $value->value == $trend[$key-1]->value && $value->value == $trend[$key+1]->value)
                $flatline[] = $value;
        }
        return $flatline;
    }

    protected function printPointUrl($xid)
    {
        if(strtoupper($this->dataCenter) == 'ACC9' || strtoupper($this->dataCenter) == 'CH3')
            return 'http://' . $this->mapDatacenterToServer() . '/ui/data-point-details/' . $xid;
        else
            return 'http://' . $this->mapDatacenterToServer() . '/point_details.shtm?point=' . $xid;
    }

    protected function mapXidValues($valuesByXid, $hierarchy)
    {
        $valueMap = [];
        foreach ($valuesByXid as $xid => $value)
        {
            foreach ($hierarchy as $point)
            {
                if($xid == $point->xid) {
                    $point->value = $value;
                    $point->mango_url = $this->printPointUrl($xid);
                    $valueMap[] = $point;
                }
            }
        }

        return $valueMap;
    }

    protected function detectFlatlines($valuesByTimestamps)
    {
        $XidsVals = [];
        foreach($valuesByTimestamps as $valuesByTimestamp)
        {
            array_shift($valuesByTimestamp);

            foreach ($valuesByTimestamp as $xid => $value)
            {
                $XidsVals[$xid][] = $value;
            }
        }

        $flatXids = [];
        foreach ($XidsVals as $xid => $values) {
            if(count(array_unique($values)) === 1 && $values[0] != 0)
                $flatXids[$xid] = $values[0];
        }

        return $flatXids;
    }

    public function getElectricalFlatlines()
    {
        $mango = new Mango($this->mapDatacenterToServer());
        $mango->login();
        $m = Utils::getMemCached();
        $cachePrefix = $this->dataCenter . '-' . $this->computerRoom . '-';

        $start = microtime(true);
        $electrical_room_id = $m->get($cachePrefix . 'electricalRoomID') ?: MangoUtils::mapRoomId($mango,$this->dataCenter, $this->computerRoom, 'electrical');
        $electricalXids = $m->get($cachePrefix . 'electricalRoomXids') ?: $mango->grabAllXIDs($electrical_room_id);
        $electricalHierarchy = $m->get($cachePrefix . 'electricalRoomHierarchy') ?: $mango->getHierarchy($electrical_room_id);
        $m->set($cachePrefix . 'electricalRoomID', $electrical_room_id, 24*60*60);
        $m->set($cachePrefix . 'electricalRoomXids', $electricalXids, 24*60*60);
        $m->set($cachePrefix . 'electricalRoomHierarchy', $electricalHierarchy, 24*60*60);
        $cachedTime = microtime(true) - $start;

        $start = microtime(true);
        $latestElectrical = $mango->getLatestValuesFromXids($electricalXids, 8);
        $getValuesTime = microtime(true) - $start;

        $start = microtime(true);
        $electricalFlatline = $this->detectFlatlines($latestElectrical);
        $detectFlatlinesTime = microtime(true) - $start;

        $start = microtime(true);
        $electricalMap = $this->mapXidValues($electricalFlatline, $electricalHierarchy->points);
        $mapPointsTime = microtime(true) - $start;

//        return [
//            "cache" => $cachedTime,
//            "get values" => $getValuesTime,
//            "flatline" => $detectFlatlinesTime,
//            "map" => $mapPointsTime
//        ];

        return $electricalMap;
    }

    public function getMechanicalFlatlines() : array
    {
        $mango = new Mango($this->mapDatacenterToServer());
        $mango->login();
        $m = Utils::getMemCached();
        $cachePrefix = $this->dataCenter . '-' . $this->computerRoom . '-';

        $start = microtime(true);
        $mechanical_room_id = $m->get($cachePrefix . 'mechanicalRoomID') ?: MangoUtils::mapRoomId($mango,$this->dataCenter, $this->computerRoom, 'mechanical');
        $mechanicalXids = $m->get($cachePrefix . 'mechanicalRoomXids') ?: $mango->grabAllXIDs($mechanical_room_id);
        $mechanicalHierarchy = $m->get($cachePrefix . 'mechanicalRoomHierarchy') ?: $mango->getHierarchy($mechanical_room_id);
        $m->set($cachePrefix . 'mechanicalRoomID', $mechanical_room_id, 24*60*60);
        $m->set($cachePrefix . 'mechanicalRoomXids', $mechanicalXids, 24*60*60);
        $m->set($cachePrefix . 'mechanicalRoomHierarchy', $mechanicalHierarchy, 24*60*60);
        $cachedTime = microtime(true) - $start;

        $start = microtime(true);
        $latestmechanical = $mango->getLatestValuesFromXids($mechanicalXids, 96);
        $getValuesTime = microtime(true) - $start;

        $start = microtime(true);
        $mechanicalFlatline = $this->detectFlatlines($latestmechanical);
        $detectFlatlinesTime = microtime(true) - $start;

        $start = microtime(true);
        $mechanicalMap = $this->mapXidValues($mechanicalFlatline, $mechanicalHierarchy->points);
        $mapPointsTime = microtime(true) - $start;

//        return [
//            "cache" => $cachedTime,
//            "get values" => $getValuesTime,
//            "flatline" => $detectFlatlinesTime,
//            "map" => $mapPointsTime
//        ];

        return $mechanicalMap;
    }

    public function mapPointNames($pointName)
    {
        switch($pointName) {
            case 'PDU kW':
                return 'kW';
                break;
            case 'Zone Humidity':
                return 'ZH';
                break;
            case 'Zone Temperature':
                return 'ZT';
                break;
            case 'Ambient Humidity':
                return 'AH';
                break;
            case 'Ambient Temperature':
                return 'AT';
                break;
            case 'Rooms TkW':
                return 'TkW';
            default:
                return $pointName;
        }
    }

    /**
     * @param $deviceNames
     * @param $start_date
     * @param $end_date
     * @return array
     */
    public function getMangoTrendDataFromDevicesNames($deviceNames, $start_date = null, $end_date = null)
    {
        $mango = new Mango($this->mapDatacenterToServer());
        $mango->login();

        $roomIds = array();
        $roomIds[] = MangoUtils::mapRoomId($mango, $this->dataCenter, $this->computerRoom, 'electrical');
        $roomIds[] = MangoUtils::mapRoomId($mango, $this->dataCenter, $this->computerRoom, 'mechanical');

        $possiblePointNames = ['PDU kW', 'Zone Humidity', 'Zone Temperature', 'Ambient Humidity', 'Ambient Temperature', 'Rooms TkW'];
        $pointNames = array_intersect($deviceNames, $possiblePointNames);
        $deviceNames = array_values(array_diff($deviceNames, $pointNames));
        $pointNames = array_map(array($this, 'mapPointNames'), $pointNames);

        $devices = $mango->mapDeviceNamesToXIDs($roomIds, $deviceNames);
        $trends = $mango->getTrendDataFromDevices($devices, $start_date, $end_date);
        $devices = $this->getHistoricalTrendsFromPoints($devices, $trends, $start_date, $end_date, 'devicePoints');

        $points = $mango->mapPointNamesToXIDs($roomIds, $pointNames);
        $pointTrends = $mango->getTrendDataFromPoints($points, $start_date, $end_date);
        $points = $this->getHistoricalTrendsFromPoints($points, $pointTrends, $start_date, $end_date, 'deviceNames');

        $devices = array_merge($devices, $points);

        return $devices;
    }

    /**
     * @param $electricalType
     * @param Electrical $electricalDevice
     * @return array
     */
    protected function setElectricalHeaders($electricalType, $electricalDevice)
    {
        if ($electricalType == 'BCMs') {
            $headers = array_fill(0, 12, null);
            if($electricalDevice->isPropertySet('ampA')) {
                $headers[0] = 'Current A Phase (Amps)';
            }
            if($electricalDevice->isPropertySet('ampB')) {
                $headers[1] = 'Current B Phase (Amps)';
            }
            if($electricalDevice->isPropertySet('ampC')) {
                $headers[2] = 'Current C Phase (Amps)';
            }
            if($electricalDevice->isPropertySet('AKW')) {
                $headers[3] = 'Real Power A Phase (kW)';
            }
            if($electricalDevice->isPropertySet('APF')) {
                $headers[4] = 'Power Factor A Phase';
            }
            if($electricalDevice->isPropertySet('BKW')) {
                $headers[5] = 'Real Power B Phase (kW)';
            }
            if($electricalDevice->isPropertySet('BPF')) {
                $headers[6] = 'Power Factor B Phase';
            }
            if($electricalDevice->isPropertySet('CKW')) {
                $headers[7] = 'Real Power C Phase (kW)';
            }
            if($electricalDevice->isPropertySet('CPF')) {
                $headers[8] = 'Power Factor C Phase';
            }
            if($electricalDevice->isPropertySet('kVA')) {
                $headers[9] = 'Apparent Power (kVA)';
            }
            if($electricalDevice->isPropertySet('kW')) {
                $headers[10] = 'Real Power (kW)';
            }
            if($electricalDevice->isPropertySet('PF')) {
                $headers[11] = 'Power Factor';
            }
        } elseif ($electricalType == 'PDUs' || $electricalType == 'SWBs' || $electricalType == 'DBs' || $electricalType == 'UPSs') {
            $headers = array_fill(0, 6, null);
            if($electricalDevice->isPropertySet('ampA')) {
                $headers[0] = 'Current A Phase (Amps)';
            }
            if($electricalDevice->isPropertySet('ampB')) {
                $headers[1] = 'Current B Phase (Amps)';
            }
            if($electricalDevice->isPropertySet('ampC')) {
                $headers[2] = 'Current C Phase (Amps)';
            }
            if($electricalDevice->isPropertySet('kVA')) {
                $headers[3] = 'Apparent Power (kVA)';
            }
            if($electricalDevice->isPropertySet('kW')) {
                $headers[4] = 'Real Power (kW)';
            }
            if($electricalDevice->isPropertySet('kWh')) {
                $headers[5] = 'Real Energy (kWh)';
            }
        }
        elseif ($electricalType == 'Totals') {
            $headers = array_fill(0, 5, null);
            if($electricalDevice->isPropertySet('kW')) {
                $headers[0] = 'Real Power (kW)';
            }
            if($electricalDevice->isPropertySet('kWH')) {
                $headers[1] = 'Real Energy (kWh)';
            }
            if($electricalDevice->isPropertySet('kWM')) {
                $headers[2] = 'kWM';
            }
            if($electricalDevice->isPropertySet('TkW')) {
                $headers[3] = 'TkW';
            }
            if($electricalDevice->isPropertySet('PUE')) {
                $headers[4] = 'PUE';
            }
        }

        return array_filter($headers, 'strlen');
    }

    /**
     * @param $mechanicalType
     * @param $mechanicalDevice
     * @return array
     */
    protected function setMechanicalHeaders($mechanicalType, $mechanicalDevice)
    {
        $headers = array_fill(0, 9, null);
        if ($mechanicalType == 'CRAHs') {
            if($mechanicalDevice->isPropertySet('FAN')) {
                $headers[0] = 'Fan Status';
            }
            if($mechanicalDevice->isPropertySet('RAH')) {
                $headers[1] = 'Return Air Humidity (%)';
            }
            if($mechanicalDevice->isPropertySet('RAT')) {
                $headers[2] = 'Return Air Temperature (°F)';
            }
            if($mechanicalDevice->isPropertySet('SAT')) {
                $headers[3] = 'Supply Air Temperature (°F)';
            }
            if($mechanicalDevice->isPropertySet('CHWV')) {
                $headers[4] = 'Chilled Water Valve (% Open)';
            }
            if($mechanicalDevice->isPropertySet('DAT')) {
                $headers[5] = 'DAT';
            }
            if($mechanicalDevice->isPropertySet('EWT')) {
                $headers[6] = 'Entering Water Temperature (°F)';
            }
            if($mechanicalDevice->isPropertySet('LWT')) {
                $headers[7] = 'Leaving Water Temperature (°F)';
            }
            if($mechanicalDevice->isPropertySet('SAH')) {
                $headers[8] = 'Supply Air Humidity (%)';
            }
        } elseif ($mechanicalType == 'Zones') {
            if($mechanicalDevice->isPropertySet('ZH')) {
                $headers[0] = 'Humidity (%)';
            }
            if($mechanicalDevice->isPropertySet('ZT')) {
                $headers[1] = 'Temperature (°F)';
            }
        } elseif ($mechanicalType == 'Pressures') {
            if($mechanicalDevice->isPropertySet('PRESS')) {
                $headers[0] = 'Pressure (inch WC)';
            }
        } elseif ($mechanicalType == 'Ambients') {
            if($mechanicalDevice->isPropertySet('AH')) {
                $headers[0] = 'Ambient Humidity (%)';
            }
            if($mechanicalDevice->isPropertySet('AT')) {
                $headers[1] = 'Ambient Temperature (°F)';
            }
        } elseif ($mechanicalType == 'Fire_alarms') {
//            if ($mechanicalDevice->getStatus() !== null) {
//            if($mechanicalDevice->isPropertySet('STATUS')) {
            $headers[0] = 'Status';
//            }
        }

        return array_filter($headers, 'strlen');
    }

    /**
     * @param $electricalType
     * @param Electrical $electricalDevice
     * @param $points
     */
    protected function setElectricalData($electricalType, $electricalDevice, &$points, $headers)
    {
        if ($electricalType == 'BCMs') {
            if(in_array('Current A Phase (Amps)', $headers)) {
                $points[] = Utils::formatMangoPoint('Amp A', $electricalDevice->getAmpA());
            }
            if(in_array('Current B Phase (Amps)', $headers)) {
                $points[] = Utils::formatMangoPoint('Amp B', $electricalDevice->getAmpB());
            }
            if(in_array('Current C Phase (Amps)', $headers)) {
                $points[] = Utils::formatMangoPoint('Amp C', $electricalDevice->getAmpC());
            }
            if(in_array('Real Power A Phase (kW)', $headers)) {
                $points[] = Utils::formatMangoPoint('AKW', $electricalDevice->getAKW());
            }
            if(in_array('Power Factor A Phase', $headers)) {
                $points[] = Utils::formatMangoPoint('APF', $electricalDevice->getAPF(), null, 3);
            }
            if(in_array('Real Power B Phase (kW)', $headers)) {
                $points[] = Utils::formatMangoPoint('BKW', $electricalDevice->getBKW());
            }
            if(in_array('Power Factor B Phase', $headers)) {
                $points[] = Utils::formatMangoPoint('BPF', $electricalDevice->getBPF(), null, 3);
            }
            if(in_array('Real Power C Phase (kW)', $headers)) {
                $points[] = Utils::formatMangoPoint('CKW', $electricalDevice->getCKW());
            }
            if(in_array('Power Factor C Phase', $headers)) {
                $points[] = Utils::formatMangoPoint('CPF', $electricalDevice->getCPF(), null, 3);
            }
            if(in_array('Apparent Power (kVA)', $headers)) {
                $points[] = Utils::formatMangoPoint('kVA', $electricalDevice->getKVA());
            }
            if(in_array('Real Power (kW)', $headers)) {
                $points[] = Utils::formatMangoPoint('kW', $electricalDevice->getKW());
            }
            if(in_array('Power Factor', $headers)) {
                $points[] = Utils::formatMangoPoint('PF', $electricalDevice->getPF(), null, 3);
            }
            if(in_array('Real Power Total (kW)', $headers)) {
                $points[] = Utils::formatMangoPoint('TKW', $electricalDevice->getTKW());
            }
        } elseif($electricalType == 'Totals') {
            if(in_array('Real Power (kW)', $headers)) {
                $points[] = Utils::formatMangoPoint('kW', $electricalDevice->getKW());
            }
            if(in_array('Real Energy (kWh)', $headers)) {
                $points[] = Utils::formatMangoPoint('kWH', $electricalDevice->getKWH());
            }
            if(in_array('kWM', $headers)) {
                $points[] = Utils::formatMangoPoint('kWM', $electricalDevice->getKWM());
            }
            if(in_array('Real Power Total (kW)', $headers)) {
                $points[] = Utils::formatMangoPoint('TkW', $electricalDevice->getTkW());
            }
            if(in_array('PUE', $headers)) {
                $points[] = Utils::formatMangoPoint('PUE', $electricalDevice->getPUE(), null, 3);
            }
        } else {
            if(in_array('Current A Phase (Amps)', $headers)) {
                $points[] = Utils::formatMangoPoint('Amp A', $electricalDevice->getAmpA());
            }
            if(in_array('Current B Phase (Amps)', $headers)) {
                $points[] = Utils::formatMangoPoint('Amp B', $electricalDevice->getAmpB());
            }
            if(in_array('Current C Phase (Amps)', $headers)) {
                $points[] = Utils::formatMangoPoint('Amp C', $electricalDevice->getAmpC());
            }
            if(in_array('Apparent Power (kVA)', $headers)) {
                $points[] = Utils::formatMangoPoint('kVA', $electricalDevice->getKVA());
            }
            if(in_array('Real Power (kW)', $headers)) {
                $points[] = Utils::formatMangoPoint('kW', $electricalDevice->getKW());
            }
            if(in_array('Real Energy (kWh)', $headers)) {
                $points[] = Utils::formatMangoPoint('kWh', $electricalDevice->getKWh());
            }
        }
    }

    public static function mapElectricalHeaders($v)
    {
        switch($v) {
            case 'ampA':
                return 'Current A Phase (Amps)';
                break;
            case 'ampB':
                return 'Current B Phase (Amps)';
                break;
            case 'ampC':
                return 'Current C Phase (Amps)';
                break;
            case 'AKW':
                return 'Real Power A Phase (kW)';
                break;
            case 'APF':
                return 'Power Factor A Phase';
                break;
            case 'BKW':
                return 'Real Power B Phase (kW)';
                break;
            case 'BPF':
                return 'Power Factor B Phase';
                break;
            case 'CKW':
                return 'Real Power C Phase (kW)';
                break;
            case 'CPF':
                return 'Power Factor C Phase';
                break;
            case 'kVA':
                return 'Apparent Power (kVA)';
                break;
            case 'kW':
                return 'Real Power (kW)';
                break;
            case 'kWh':
                return 'Real Energy (kWh)';
                break;
            case 'PF':
                return 'Power Factor';
                break;
            case 'TKW':
                return 'Real Power Total (kW)';
                break;
            case 'TkW':
                return 'Real Power Total (kW)';
                break;
            case 'PUE':
                return 'PUE';
                break;
            default:
                return $v;
        }
    }

    public static function electricalHeaderSort($a, $b)
    {
        $order = array(
            'Current A Phase (Amps)',
            'Current B Phase (Amps)',
            'Current C Phase (Amps)',
            'Real Power A Phase (kW)',
            'Power Factor A Phase',
            'Real Power B Phase (kW)',
            'Power Factor B Phase',
            'Real Power C Phase (kW)',
            'Power Factor C Phase',
            'Apparent Power (kVA)',
            'Real Power (kW)',
            'Power Factor',
            'Real Power Total (kW)',
            'Real Energy (kWh)',
            'kWM',
            'PUE'
        );

        $a = array_search($a, $order);
        $b = array_search($b, $order);

        return $a - $b;

    }

    public function putElectricalHeaders($electricalDevices)
    {
        $keys = [];
        foreach($electricalDevices as $electricalDevice)
        {
            foreach($electricalDevice as $key=>$value)
            {
                if($value !== null)
                {
                    $keys[] = $key;
                }
            }
        }

        $keys = array_unique($keys);
        $keys = array_diff($keys, array('deviceName'));
        $keys = array_values($keys);
        $keys = array_map(array($this, 'mapElectricalHeaders'), $keys);
        usort($keys, array($this, 'electricalHeaderSort'));

        return $keys;
    }

    /**
     * @return array|mixed
     */
    public function prepareElectricalData()
    {
        $electricalOutput = $headers = array();
        if ($this->isAuthorized()) {
            $m = Utils::getMemCached();
            $memName = $this->dataCenter . '-' . $this->computerRoom . '-electricalData';
//            if ($cacheData = $m->get($memName) && !empty($cacheData)) {
//                return $cacheData;
//            }
            $electricalTypes = $this->getElectricalData();

            foreach ($electricalTypes as $electricalType => $electricalDevices) {
                $electricalOutput[$electricalType] = array();
                $headers = $this->putElectricalHeaders($electricalDevices);

                foreach ($electricalDevices as $electricalDevice) {

                    $devicePoint = array();
                    $devicePoint['deviceName'] = $electricalDevice->getDeviceName();
                    $devicePoint['points'] = array();

                    $this->setElectricalData($electricalType, $electricalDevice, $devicePoint['points'], $headers);

                    $electricalOutput[$electricalType]['body'][] = $devicePoint;
                }
                $electricalOutput[$electricalType]['headers'] = $headers;
            }

            $m->set($memName, $electricalOutput, 15*60);
        }
        return $electricalOutput;
    }

    /**
     * @param Electrical $electricalDevice
     * @param $data
     */
    protected function saveElectricalData(Electrical &$electricalDevice, $data)
    {
        //$alert = new Alert(array(0,25, 75, 100));
        foreach ($data as $key => $value) {
            switch (strtoupper($key)) {
                case 'IA':
                    $electricalDevice->setAmpA($value);
                    break;
                case 'IB':
                    $electricalDevice->setAmpB($value);
                    break;
                case 'IC':
                    $electricalDevice->setAmpC($value);
                    break;
                case 'KVA':
                    $electricalDevice->setKVA($value);
                    break;
                case 'KW':
                    $electricalDevice->setKW($value);
                    break;
                case 'KWH':
                    $electricalDevice->setKWh($value);
                    break;
            }
        }
    }

    protected function saveCDPData(Electrical &$electricalDevice, $data)
    {
        //$alert = new Alert(array(0,25, 75, 100));
        foreach ($data as $key => $value) {
            switch ($key) {
                case 'AA':
                    $electricalDevice->setAmpA($value);
                    break;
                case 'AB':
                    $electricalDevice->setAmpB($value);
                    break;
                case 'AC':
                    $electricalDevice->setAmpC($value);
                    break;
                case 'KVA':
                    $electricalDevice->setKVA($value);
                    break;
                case 'KW':
                    $electricalDevice->setKW($value);
                    break;

            }
        }
    }

    /**
     * @param BCM $bcm
     * @param $data
     */
    protected function saveBCMData(BCM &$bcm, $data)
    {
        foreach ($data as $key => $value) {
            switch (strtoupper($key)) {
                case 'AKW':
                    $bcm->setAKW($value);
                    break;
                case 'APF':
                    $bcm->setAPF($value);
                    break;
                case 'BKW':
                    $bcm->setBKW($value);
                    break;
                case 'BPF':
                    $bcm->setBPF($value);
                    break;
                case 'CKW':
                    $bcm->setCKW($value);
                    break;
                case 'CPF':
                    $bcm->setCPF($value);
                    break;
                case 'IA':
                    $bcm->setAmpA($value);
                    break;
                case 'IB':
                    $bcm->setAmpB($value);
                    break;
                case 'IC':
                    $bcm->setAmpC($value);
                    break;
                case 'KVA':
                    $bcm->setKVA($value);
                    break;
                case 'KW':
                    $bcm->setKW($value);
                    break;
                case 'PF':
                    $bcm->setPF($value);
                    break;
                case 'TKW':
                    $bcm->setTKW($value);
                    break;
            }
        }
    }

    public function getRoomPowerTotal()
    {
        $mango = new Mango($this->mapDatacenterToServer());
        $mango->login();

        $points = $mango->getBulkElectrical($this->dataCenter, $this->computerRoom);

        if(empty($points)) {
            $room_id = MangoUtils::mapRoomId($mango, $this->dataCenter, $this->computerRoom, 'electrical');
            $points = $mango->getAllRealtimeData($room_id);
        }

        $devices = MangoUtils::groupHierarchy($points);

        foreach ($devices as $key => $data) {
            if(strtoupper($this->dataCenter) == 'CH1') {
                if (preg_match('/-C-/', $key) && isset($data["kW"])) {
                    return number_format($data["kW"], 1);
                }
            }
            else {
                if (preg_match('/-TOTALS/', $key) && isset($data["TkW"])) {
                    return number_format($data["TkW"], 1);
                }
            }
        }
    }

    protected function getTotalXids($points)
    {
        $xids = [];
        foreach($points as $point)
        {
            if(strtoupper($this->dataCenter) == 'CH1') {
                if (preg_match('/-C-/', $point->deviceName) && (strtoupper($point->unit) == 'KW')) {
                    $xids[] = $point->xid;
                }
            }
            else {
                if (preg_match('/-TOTALS/', $point->deviceName) && (strtoupper($point->unit) == 'KW')) {
                    $xids[] = $point->xid;
                }
            }
        }
        return $xids;
    }

    public function getHistoricalRoomTotal($start_date = null, $end_date = null)
    {
        $mango = new Mango($this->mapDatacenterToServer());
        $mango->login();

        $xid = getXidByComputerRoom($this->dataCenter, $this->computerRoom);
        $trends = $mango->getTrendDataFromXid($xid, $start_date, $end_date);
        $deviceData = $this->getHistoricalTotalTrends($trends, $start_date, $end_date);

        return $deviceData;
    }

    public function getRoomPowerStats($start_date = null, $end_date = null)
    {
        $memName = "dashboard_roompowerstats_DC" . $this->dataCenter . "_CR" . $this->computerRoom;
        if ($start_date !== null) {
            $memName .= "_$start_date";
        }
        if ($end_date !== null) {
            $memName .= "_$end_date";
        }
        $m = Utils::getMemCached();
        if ($m->get($memName))
            return $m->get($memName);

        $mango = new Mango($this->mapDatacenterToServer());
        $mango->login();

        $xid = getXidByComputerRoom($this->dataCenter, $this->computerRoom);
        if(!empty($xid)) {
            $stats = $mango->getStatsFromXid($xid, $start_date, $end_date);
        }
        else{
            $points = $mango->getBulkElectrical($this->dataCenter, $this->computerRoom);

            if(empty($points)) {
                $room_id = MangoUtils::mapRoomId($mango, $this->dataCenter, $this->computerRoom, 'electrical');
                $points = $mango->getAllRealtimeData($room_id);
            }

            if(!empty($points))
                $xids = $this->getTotalXids($points);

            if(!empty($xids))
                $stats = $mango->getStatsFromXid($xids[0], $start_date, $end_date);
        }

        if(!empty($stats)) {
            $retval = [
                'maximum' => isset($stats->maximum->value) ? $stats->maximum->value : null,
                'average' => isset($stats->average->value) ? $stats->average->value : null,
                'last' => isset($stats->last->value) ? $stats->last->value : null,
                'max_time' => isset($stats->maximum->timestamp) ? $stats->maximum->timestamp : null,
                'start_time' => isset($stats->first->timestamp) ? $stats->first->timestamp : null,
                'end_time' => isset($stats->last->timestamp) ? $stats->last->timestamp : null
            ];
        }
        else {
            $retval = [
                'maximum' => null,
                'average' => null,
                'last' => null,
                'max_time' => null,
                'start_time' => null,
                'end_time' => null
            ];
        }
        $m->set($memName, $retval, 15*60);
        return $retval;
    }

    protected function saveTotalsData(Totals &$totalsDevice, $data)
    {
        foreach ($data as $key => $value) {
            switch (strtoupper($key)) {
//                case 'KW':
//                    $totalsDevice->setKW($value);
//                    break;
//                case 'KWH':
//                    $totalsDevice->setKWH($value);
//                    break;
//                case 'KWM':
//                    $totalsDevice->setKWM($value);
//                    break;
                case 'TKW':
                    $totalsDevice->setTkW($value);
                    break;
                case 'PUE':
                    $totalsDevice->setPUE($value);
                    break;
            }
        }
    }

    /**
     * @return array
     */
    protected function getElectricalData()
    {
        $mango = new Mango($this->mapDatacenterToServer());
        $mango->login();

        $points = $mango->getBulkElectrical($this->dataCenter, $this->computerRoom);
        $room_id = MangoUtils::mapRoomId($mango, $this->dataCenter, $this->computerRoom, 'electrical');

        if(empty($points))
            $points = $mango->getAllRealtimeData($room_id);

        $devices = MangoUtils::groupHierarchy($points);
        if (strtoupper($this->dataCenter) == 'ACC9' || strtoupper($this->dataCenter) == 'CH3'){
            $devices = $mango->getDevicesFromRoomID($room_id);
        }

        $PDUs = $UPSs = $SWBs = $DBs = $BCMs = $CDPs = $STSs = $Totals = array();

        foreach ($devices as $key => $data) {
            if (preg_match('/-PDU-/', $key)) {
                $pdu = new PDU($key);
                $this->saveElectricalData($pdu, $data);
                $PDUs[] = $pdu;
            } elseif (preg_match('/-SWB-/', $key)) {
                $swb = new SWB($key);
                $this->saveElectricalData($swb, $data);
                $SWBs[] = $swb;
            } elseif (preg_match('/-D-/', $key)) {
                $db = new DistributionBoard($key);
                $this->saveElectricalData($db, $data);
                $DBs[] = $db;
            } elseif (preg_match('/-C-/', $key)) {
                $db = new DistributionBoard($key);
                $this->saveElectricalData($db, $data);
                $DBs[] = $db;
            } elseif (preg_match('/-BCM-/', $key)) {
                $bcm = new BCM($key);
                $this->saveBCMData($bcm, $data);
                $BCMs[] = $bcm;
            } elseif (preg_match('/-TOTALS/', $key)) {
                $totals = new Totals($key);
                $this->saveTotalsData($totals, $data);
                $Totals[] = $totals;
            } elseif (preg_match('/-CDP-/', $key)) {
                $cdp = new CDP($key);
                $this->saveCDPData($cdp, $data);
                $CDPs[] = $cdp;
            } elseif (preg_match('/-STS-/', $key)) {
                $sts = new STS($key);
                $this->saveElectricalData($sts, $data);
                $STSs[] = $sts;
            } elseif (preg_match('/-UPS-/', $key)) {
                $ups = new UPS($key);
                $this->saveElectricalData($ups, $data);
                $UPSs[] = $ups;
            }

        }

        usort($PDUs, array($this, 'natDeviceSort'));
        usort($SWBs, array($this, 'natDeviceSort'));
        usort($UPSs, array($this, 'natDeviceSort'));
        usort($DBs, array($this, 'natDeviceSort'));
        usort($BCMs, array($this, 'natDeviceSort'));
        usort($CDPs, array($this, 'natDeviceSort'));
        usort($STSs, array($this, 'natDeviceSort'));

        return [
            'PDUs' => $PDUs,
            'SWBs' => $SWBs,
            'UPSs' => $UPSs,
            'DBs' => $DBs,
            'BCMs' => $BCMs,
            'Totals' => $Totals,
            'CDPs' => $CDPs,
            'STSs' => $STSs
        ];
    }

    /**
     * @param CRAH $crah
     * @param $data
     */
    protected function saveCRAHData(CRAH &$crah, $data)
    {
        //$alert = new Alert(array(0,25, 75, 100));

        foreach ($data as $key => $value) {
            if (preg_match('/RAH/', $key)) {
                $crah->setReturnAirHumidity($value);
            } elseif (preg_match('/RAT/', $key)) {
                $crah->setReturnAirTemperature($value);
            } elseif (preg_match('/FAN/', $key)) {
                $crah->setFanStatus($value);
            } elseif (preg_match('/SAT/', $key)) {
                $crah->setSupplyAirTemperature($value);
            } elseif (preg_match('/SAH/', $key)) {
                $crah->setSupplyAirHumidity($value);
            }elseif (preg_match('/CHWV/', $key)) {
                $crah->setChillerValve($value);
            } elseif (preg_match('/DAT/', $key)) {
                $crah->setDAT($value);
            } elseif (preg_match('/EWT/', $key)) {
                $crah->setEnteringWaterTemperature($value);
            } elseif (preg_match('/LWT/', $key)) {
                $crah->setLeavingWaterTemperature($value);
            }
        }
    }

    /**
     * @return array|mixed
     */
    public function getDeviceNames()
    {
        $m = Utils::getMemCached();
        $memName = $this->dataCenter . '-' . $this->computerRoom . '-deviceNames';
        if ($m->get($memName))
            return $m->get($memName);

        $mango = new Mango($this->mapDatacenterToServer());
        $mango->login();

        $electricalRoomId = MangoUtils::mapRoomId($mango, $this->dataCenter, $this->computerRoom, 'electrical');
        $mechanicalRoomId = MangoUtils::mapRoomId($mango, $this->dataCenter, $this->computerRoom, 'mechanical');

        $electricalDeviceNames = $mango->getDeviceNamesFromRoomId($electricalRoomId);
        $mechanicalDeviceNames = $mango->getDeviceNamesFromRoomId($mechanicalRoomId);

        $PDUs = $SWBs = $DBs = $UPSs = $BCMs = $CDPs = $STSs = $Totals = $CRAHs = $Zones = $Ambients = $Pressures = $Fire_alarms = array();

        foreach ($electricalDeviceNames as $deviceName) {
            if (preg_match('/-PDU-/', $deviceName)) {
                $PDUs[] = $deviceName;
            } elseif (preg_match('/-SWB-/', $deviceName)) {
                $SWBs[] = $deviceName;
            } elseif (preg_match('/-D-/', $deviceName)) {
                $DBs[] = $deviceName;
            } elseif (preg_match('/-C-/', $deviceName)) {
                $DBs[] = $deviceName;
            } elseif (preg_match('/-BCM-/', $deviceName)) {
                $BCMs[] = $deviceName;
            } elseif (preg_match('/-CDP-/', $deviceName)) {
                $CDPs[] = $deviceName;
            } elseif (preg_match('/-STS-/', $deviceName)) {
                $STSs[] = $deviceName;
            } elseif (preg_match('/-UPS-/', $deviceName)) {
                $UPSs[] = $deviceName;
            } elseif (preg_match('/-TOTALS/', $deviceName)) {
                $Totals[] = $deviceName;
            }
        }

        foreach ($mechanicalDeviceNames as $deviceName) {
            if (preg_match('/-CRAH-/', $deviceName) || preg_match('/-RTU-/', $deviceName)) {
                $CRAHs[] = $deviceName;
            } elseif (preg_match('/-ZONE-/', $deviceName)) {
                $Zones[] = $deviceName;
            } elseif (preg_match('/-AMBIENT-/', $deviceName)) {
                $Ambients[] = $deviceName;
            } elseif (preg_match('/-PRESS-/', $deviceName)) {
                $Pressures[] = $deviceName;
            } elseif (preg_match('/FIRE/', $deviceName)) {
                $Fire_alarms[] = $deviceName;
            }
        }

        $deviceNames = [
            'PDUs' => naturalSortResetKeys($PDUs),
            'Distribution' => array_merge(naturalSortResetKeys($SWBs), naturalSortResetKeys($DBs)),
            'BCMs' => naturalSortResetKeys($BCMs),
            'CDPs' => naturalSortResetKeys($CDPs),
            'STSs' => naturalSortResetKeys($STSs),
            'UPSs' => naturalSortResetKeys($UPSs),
            'CRAHs' => naturalSortResetKeys($CRAHs),
            'Totals' => naturalSortResetKeys($Totals),
            'Environmentals' => array_merge(naturalSortResetKeys($Ambients), naturalSortResetKeys($Zones)),
            'Pressures' => naturalSortResetKeys($Pressures),
            'Fire Alarms' => naturalSortResetKeys($Fire_alarms)
        ];

        if(count($deviceNames['PDUs']) > 0)
            $deviceNames['Totals'][] = 'PDU kW';

        if(count($Zones) > 0) {
            $deviceNames['Totals'][] = 'Zone Humidity';
            $deviceNames['Totals'][] = 'Zone Temperature';
        }

        if(count($Ambients) > 0) {
            $deviceNames['Totals'][] = 'Ambient Humidity';
            $deviceNames['Totals'][] = 'Ambient Temperature';
        }

        $m->set($memName, $deviceNames, 15*60);
        return $deviceNames;
    }

    public static function natDeviceSort($a, $b)
    {
        return strnatcmp($a->deviceName, $b->deviceName);
    }

    public static function mapMechanicalHeaders($v)
    {
        switch($v) {
            case 'fanStatus':
                return 'Fan Status';
                break;
            case 'enteringWaterTemperature':
                return 'Entering Water Temperature (°F)';
                break;
            case 'leavingWaterTemperature':
                return 'Leaving Water Temperature (°F)';
                break;
            case 'returnAirTemperature';
                return 'Return Air Temperature (°F)';
                break;
            case 'returnAirHumidity';
                return 'Return Air Humidity (%)';
                break;
            case 'supplyAirTemperature';
                return 'Supply Air Temperature (°F)';
                break;
            case 'supplyAirHumidity';
                return 'Supply Air Humidity (%)';
                break;
            case 'chillerValve';
                return 'Chilled Water Valve (% Open)';
                break;
            case 'DAT';
                return 'Discharge Air Temperature (°F)';
                break;
            case 'humidity';
                return 'Humidity (%)';
                break;
            case 'temperature';
                return 'Temperature (°F)';
                break;
            case 'ambientHumidity';
                return 'Ambient Humidity (%)';
                break;
            case 'ambientTemperature';
                return 'Ambient Temperature (°F)';
                break;
            case 'pressure';
                return 'Pressure (inch WC)';
                break;
        }
    }

    public static function headerSort($a, $b)
    {
        $order = array(
            'Fan Status',
            'Return Air Humidity (%)',
            'Return Air Temperature (°F)',
            'Supply Air Temperature (°F)',
            'Supply Air Humidity (%)',
            'Chilled Water Valve (% Open)',
            'Discharge Air Temperature (°F)',
            'Entering Water Temperature (°F)',
            'Leaving Water Temperature (°F)',
            'Humidity (%)',
            'Temperature (°F)',
            'Ambient Humidity (%)',
            'Ambient Temperature (°F)',
            'Pressure (inch WC)'
        );

        $a = array_search($a, $order);
        $b = array_search($b, $order);

        return $a - $b;

    }

    public function putMechanicalHeaders($mechanicalDevices)
    {
        $keys = [];
        foreach($mechanicalDevices as $mechanicalDevice)
        {
            foreach($mechanicalDevice as $key=>$value)
            {
                if($value !== null)
                {
                    $keys[] = $key;
                }
            }
        }

        $keys = array_unique($keys);
        $keys = array_diff($keys, array('deviceName'));
        $keys = array_values($keys);
        $keys = array_map(array($this, 'mapMechanicalHeaders'), $keys);
        usort($keys, array($this, 'headerSort'));

        return $keys;
    }


    /**
     * @return array|mixed
     */
    public function prepareMechanicalData()
    {

        $mechanicalOutput = $headers = array();

        if ($this->isAuthorized()) {
            $m = Utils::getMemCached();
            $memName = $this->dataCenter . '-' . $this->computerRoom . '-mechanicalData';
            if ($m->get($memName))
                return $m->get($memName);

            $mechanicalTypes = $this->getMechanicalData();

            foreach ($mechanicalTypes as $mechanicalType=>$mechanicalDevices) {
                $mechanicalOutput[$mechanicalType] = array();
                $headers = $this->putMechanicalHeaders($mechanicalDevices);

                foreach ($mechanicalDevices as $mechanicalDevice) {

                    $devicePoint = array();
                    $devicePoint['deviceName'] = $mechanicalDevice->getDeviceName();

                    if ($mechanicalType == 'CRAHs') {
                        $devicePoint['points'] = array();
                        if(in_array('Fan Status', $headers) && $mechanicalDevice->getFanStatus() !== null) {
                            if($mechanicalDevice->getFanStatus() === 0 || $mechanicalDevice->getFanStatus() === 0.0) {
                                $devicePoint['points'][] = Utils::formatMangoPressurePoint(0);
                            }
                            else {
                                $devicePoint['points'][] = Utils::formatMangoPressurePoint($mechanicalDevice->getFanStatus());
                            }
                        }
                        else {
                            $devicePoint['points'][] = Utils::formatMangoPressurePoint();
                        }
                        if(in_array('Return Air Humidity (%)', $headers)) {
                            $devicePoint['points'][] = Utils::formatMangoPoint('Return Air Humidity', $mechanicalDevice->getReturnAirHumidity());
                        }
                        if(in_array('Return Air Temperature (°F)', $headers)) {
                            $devicePoint['points'][] = Utils::formatMangoPoint('Return Air Temperature', $mechanicalDevice->getReturnAirTemperature());
                        }
                        if(in_array('Supply Air Temperature (°F)', $headers)) {
                            $devicePoint['points'][] = Utils::formatMangoPoint('Supply Air Temperature', $mechanicalDevice->getSupplyAirTemperature());
                        }
                        if(in_array('Supply Air Humidity (%)', $headers)) {
                            $devicePoint['points'][] = Utils::formatMangoPoint('Supply Air Humidity', $mechanicalDevice->getSupplyAirHumidity());
                        }
                        if(in_array('Chilled Water Valve (% Open)', $headers)) {
                            $devicePoint['points'][] = Utils::formatMangoPoint('Chilled Water Valve', $mechanicalDevice->getChillerValve());
                        }
                        if(in_array('Discharge Air Temperature (°F)', $headers)) {
                            $devicePoint['points'][] = Utils::formatMangoPoint('Discharge Air Temperature', $mechanicalDevice->getDAT());
                        }
                        if(in_array('Entering Water Temperature (°F)', $headers)) {
                            $devicePoint['points'][] = Utils::formatMangoPoint('Entering Water Temperature', $mechanicalDevice->getEnteringWaterTemperature());
                        }
                        if(in_array('Leaving Water Temperature (°F)', $headers)) {
                            $devicePoint['points'][] = Utils::formatMangoPoint('Leaving Water Temperature', $mechanicalDevice->getLeavingWaterTemperature());
                        }
                    } elseif ($mechanicalType == 'Zones') {
                        $devicePoint['points'] = array();
                        if(in_array('Humidity (%)', $headers)) {
                            $devicePoint['points'][] = Utils::formatMangoPoint('Humidity', $mechanicalDevice->getHumidity());
                        }
                        if(in_array('Temperature (°F)', $headers)) {
                            $devicePoint['points'][] = Utils::formatMangoPoint('Temperature', $mechanicalDevice->getTemperature());
                        }
                    } elseif ($mechanicalType == 'Pressures') {
                        if(in_array('Pressure (inch WC)', $headers)) {
                            $devicePoint['points'] = array();
                            $devicePoint['points'][] = Utils::formatMangoPoint('Pressure', $mechanicalDevice->getPressure(), null, 3);
                        }
                    } elseif ($mechanicalType == 'Ambients') {
                        $devicePoint['points'] = array();
                        if(in_array('Ambient Humidity (%)', $headers)) {
                            $devicePoint['points'][] = Utils::formatMangoPoint('Ambient Humidity', $mechanicalDevice->getAmbientHumidity());
                        }
                        if(in_array('Ambient Temperature (°F)', $headers)) {
                            $devicePoint['points'][] = Utils::formatMangoPoint('Ambient Temperature', $mechanicalDevice->getAmbientTemperature());
                        }
                    } elseif ($mechanicalType == 'Fire_alarms') {
    //                    if($mechanicalDevice->isPropertySet('STATUS')) {
                        if ($mechanicalDevice->getStatus() !== null) {
                            $devicePoint['status'] = 'on';
                        } else {
                            $devicePoint['status'] = 'off';
                        }
    //                    }
    //                    else
    //                        $devicePoint['status'] = 'No Comm';
                    }

                    $mechanicalOutput[$mechanicalType]['body'][] = $devicePoint;
                }
                $mechanicalOutput[$mechanicalType]['headers'] = $headers;
            }

            if(!empty($mechanicalOutput['CRAHs']) && !empty($mechanicalOutput['CRAHs']['body']) && $mechanicalOutput['CRAHs']['body'][0]['points'][0]['name'] == 'Fan Speed')
                $mechanicalOutput['CRAHs']['headers'][0] = 'Fan Speed (%)';

            $m->set($memName, $mechanicalOutput, 15*60);
        }
        return $mechanicalOutput;
    }

    /**
     * @return array|mixed
     */
    public function getDevicesTrendData()
    {
        $m = Utils::getMemCached();
        $memName = $this->dataCenter . '-' . $this->computerRoom . '-deviceTrends';
        if ($m->get($memName)) {
            return $m->get($memName);
        }

        $mango = new Mango($this->mapDatacenterToServer());
        $mango->login();

        $electricalRoomId = MangoUtils::mapRoomId($mango, $this->dataCenter, $this->computerRoom, 'electrical');
        $mechanicalRoomId = MangoUtils::mapRoomId($mango, $this->dataCenter, $this->computerRoom, 'mechanical');

        $trends = array_merge($mango->getAllTrendData($electricalRoomId), $mango->getAllTrendData($mechanicalRoomId));
        $devices = array_merge($mango->mapDeviceToXID($electricalRoomId), $mango->mapDeviceToXID($mechanicalRoomId));

        foreach ($devices as &$device) {
            foreach ($device['devicePoints'] as $i => &$devicePoint) {
                foreach ($trends as $index => $trend) {
                    if ($index == $devicePoint['xid']) {
                        $trend = array_reverse($trend);
                        foreach ($trend as $key => $value) {
                            $devicePoint['historical'][] = array(
                                'timestamp' => $value->timestamp,
                                'value' => $value->value
                            );
                        }
                    }
                }
            }
        }

        $m->set($memName, $devices, 15*60);
        return $devices;
    }

    protected function getXYZ1Data($cr)
    {
        $url = "http://mango.XXXXXX.com/getSoapFromAlc.php?dc=xyz1";
        $pathList = file_get_contents($url);
        $matches = [];
        $crahs = [];
        $zones = [];
        $statics = [];

        $j = $cr;

        if($j < 5) {
            for($i=1;$i<17;$i++) {
                if(preg_match("%\/#mr".$j."-ac".$i."\/returntemp: (.*?) value%", $pathList, $matches))
                    $crahs["XYZ1-".$j."-CRAH-".$i]["RAT"] = $matches[1];
                if(preg_match("%\/#mr".$j."-ac".$i."\/returnhum: (.*?) value%", $pathList, $matches))
                    $crahs["XYZ1-".$j."-CRAH-".$i]["RAH"] = $matches[1];
                if(preg_match("%\/#mr".$j."-ac".$i."\/remotetempsensorvalue: (.*?) value%", $pathList, $matches))
                    $crahs["XYZ1-".$j."-CRAH-".$i]["SAT"] = $matches[1];
                if(preg_match("%\/#mr".$j."-ac".$i."\/remotehumiditysensorvalue: (.*?) value%", $pathList, $matches))
                    $crahs["XYZ1-".$j."-CRAH-".$i]["SAH"] = $matches[1];
                if(preg_match("%\/#mr".$j."-ac".$i."\/coolingoutput: (.*?) value%", $pathList, $matches))
                    $crahs["XYZ1-".$j."-CRAH-".$i]["CHWV"] = $matches[1];
                if(preg_match("%\/#mr".$j."-ac".$i."\/current_fan_speed_in: (.*?) value%", $pathList, $matches))
                    $crahs["XYZ1-".$j."-CRAH-".$i]["FAN"] = $matches[1];
            }
        }
        else {
            for($i=1;$i<17;$i++) {
                if(preg_match("%\/#mr-".$j."_ac-".$i."\/returntemp: (.*?) value%", $pathList, $matches))
                    $crahs["XYZ1-".$j."-CRAH-".$i]["RAT"] = $matches[1];
                if(preg_match("%\/#mr-".$j."_ac-".$i."\/returnhum: (.*?) value%", $pathList, $matches))
                    $crahs["XYZ1-".$j."-CRAH-".$i]["RAH"] = $matches[1];
                if(preg_match("%\/#mr-".$j."_ac-".$i."\/remotetempsensorvalue: (.*?) value%", $pathList, $matches))
                    $crahs["XYZ1-".$j."-CRAH-".$i]["SAT"] = $matches[1];
                if(preg_match("%\/#mr-".$j."_ac-".$i."\/remotehumiditysensorvalue: (.*?) value%", $pathList, $matches))
                    $crahs["XYZ1-".$j."-CRAH-".$i]["SAH"] = $matches[1];
                if(preg_match("%\/#mr-".$j."_ac-".$i."\/coolingoutput: (.*?) value%", $pathList, $matches))
                    $crahs["XYZ1-".$j."-CRAH-".$i]["CHWV"] = $matches[1];
                if(preg_match("%\/#mr-".$j."_ac-".$i."\/current_fan_speed_in: (.*?) value%", $pathList, $matches))
                    $crahs["XYZ1-".$j."-CRAH-".$i]["FAN"] = $matches[1];
            }
        }

        for($i=1;$i<11;$i++) {
            if(preg_match("%\/#gr".$j."_zn" . $i . "_temp_humidity\/m088: (.*?) value%", $pathList, $matches))
                $zones["XYZ1-".$j."-ZONE-".$i]["ZH"] = $matches[1];
            if(preg_match("%\/#gr".$j."_zn" . $i . "_temp_humidity\/m087: (.*?) value%", $pathList, $matches))
                $zones["XYZ1-".$j."-ZONE-".$i]["ZT"] = $matches[1];
        }
        if(preg_match("%\/#gr".$j."_temp_rh_static\/m056: (.*?) value%", $pathList, $matches))
            $statics["XYZ1-".$j."-PRESS-1"]["SP"] = $matches[1];

        $devices = array_merge($crahs, $zones, $statics);

        return $devices;
    }

    protected function getXYZ1EData($cr) {

        $xml_url = "http://dupontfabros:dupontfabros@1.2.3.4/webviews/Floorplan/Meters/verbose.command";

        $ch = curl_init();

        // set url
        curl_setopt($ch, CURLOPT_URL, $xml_url);

        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // $output contains the output string
        $xml_response = curl_exec($ch);

        // close curl resource to free up system resources
        curl_close($ch);

        $xml = simplexml_load_string($xml_response);

        $PDUs = [];
        $DBs = [];
        $Totals = [];

        foreach ($xml[0]->channel as $channel) {
            if(preg_match("%PDU([0-9])([0-9]*)([A-B])%", (string)$channel->attributes()->device, $matches)) {
                if($matches[1] == $cr) {
                    if ($channel->attributes()->name == 'M_Current Phase A')
                        $PDUs["XYZ1-".$matches[1]."-PDU-".$matches[1].$matches[2].$matches[3]]['IA'] = (float)$channel->attributes()->value;
                    elseif ($channel->attributes()->name == 'M_Current Phase B')
                        $PDUs["XYZ1-".$matches[1]."-PDU-".$matches[1].$matches[2].$matches[3]]['IB'] = (float)$channel->attributes()->value;
                    elseif ($channel->attributes()->name == 'M_Current Phase C')
                        $PDUs["XYZ1-".$matches[1]."-PDU-".$matches[1].$matches[2].$matches[3]]['IC'] = (float)$channel->attributes()->value;
                    elseif ($channel->attributes()->name == 'M_kVA Total')
                        $PDUs["XYZ1-".$matches[1]."-PDU-".$matches[1].$matches[2].$matches[3]]['kVA'] = (float)$channel->attributes()->value;
                    elseif ($channel->attributes()->name == 'M_kW Total')
                        $PDUs["XYZ1-".$matches[1]."-PDU-".$matches[1].$matches[2].$matches[3]]['kW'] = (float)$channel->attributes()->value;
                }
            }

            elseif(preg_match("%DP-([A-B])([1-6])%", (string)$channel->attributes()->device, $matches)) {
                if($matches[2] == $cr) {
                    if ($channel->attributes()->name == 'Current Phase A')
                        $DBs["XYZ1-".$matches[2]."-D-".$matches[1].$matches[2]]['IA'] = (float)$channel->attributes()->value;
                    elseif ($channel->attributes()->name == 'Current Phase B')
                        $DBs["XYZ1-".$matches[2]."-D-".$matches[1].$matches[2]]['IB'] = (float)$channel->attributes()->value;
                    elseif ($channel->attributes()->name == 'Current Phase C')
                        $DBs["XYZ1-".$matches[2]."-D-".$matches[1].$matches[2]]['IC'] = (float)$channel->attributes()->value;
                    elseif ($channel->attributes()->name == 'kVA 3-Phase Total')
                        $DBs["XYZ1-".$matches[2]."-D-".$matches[1].$matches[2]]['kVA'] = (float)$channel->attributes()->value;
                    elseif ($channel->attributes()->name == 'kW 3-Phase Total')
                        $DBs["XYZ1-".$matches[2]."-D-".$matches[1].$matches[2]]['kW'] = (float)$channel->attributes()->value;
                }
            }
        }

        $TkW = 0;

        foreach($DBs as $DB) {
            $TkW += $DB['kW'];
        }

        $Totals["XYZ1-".$cr."-TOTALS"]['TkW'] = $TkW;

        $devices = array_merge($PDUs, $DBs, $Totals);

        return $devices;
    }

    protected function getXYZ1MechanicalData($cr)
    {
        $devices = $this->getXYZ1Data($cr);

        foreach ($devices as $key => $data) {
            if (preg_match('/-CRAH-/', $key)) {
                $crah = new CRAH($key);
                $this->saveCRAHData($crah, $data);
                $CRAHs[] = $crah;
            } elseif (preg_match('/-ZONE-/', $key)) {
                $zone = new ZoneSensor($key);
                $this->saveWirelessSensorData($zone, $data);
                $Zones[] = $zone;
            } elseif (preg_match('/-PRESS-/', $key)) {
                $pressure = new StaticPressure($key);
                $pressure->setPressure($data["SP"]);
                $Pressures[] = $pressure;
            }
        }

        usort($CRAHs, array($this, 'natDeviceSort'));
        usort($Zones, array($this, 'natDeviceSort'));
        usort($Pressures, array($this, 'natDeviceSort'));

        return [
            'CRAHs' => $CRAHs,
            'Zones' => $Zones,
            'Pressures' => $Pressures
        ];
    }

    protected function getXYZ1ElectricalData($cr)
    {
        $devices = $this->getXYZ1EData($cr);

        foreach ($devices as $key => $data) {
            if (preg_match('/-PDU-/', $key)) {
                $pdu = new PDU($key);
                $this->saveElectricalData($pdu, $data);
                $PDUs[] = $pdu;
            } elseif (preg_match('/-D-/', $key)) {
                $db = new DistributionBoard($key);
                $this->saveElectricalData($db, $data);
                $DBs[] = $db;
            } elseif (preg_match('/-TOTALS/', $key)) {
                $totals = new Totals($key);
                $this->saveTotalsData($totals, $data);
                $Totals[] = $totals;
            }
        }

        usort($PDUs, array($this, 'natDeviceSort'));
        usort($DBs, array($this, 'natDeviceSort'));

        return [
            'PDUs' => $PDUs,
            'DBs' => $DBs,
            'Totals' => $Totals
        ];
    }

    /**
     * @return array
     */
    protected function getMechanicalData()
    {
        $mango = new Mango($this->mapDatacenterToServer());
        $mango->login();

        $room_id = MangoUtils::mapRoomId($mango, $this->dataCenter, $this->computerRoom, 'mechanical');

        if (strtoupper($this->dataCenter) == 'ACC9' || strtoupper($this->dataCenter) == 'CH3'){
            $devices = $mango->getDevicesFromRoomID($room_id);
        }
        else {
            $points = $mango->getBulkMechanical($this->dataCenter, $this->computerRoom);
            if(empty($points))
                $points = $mango->getAllRealtimeData($room_id);
            $devices = MangoUtils::groupHierarchy($points);
        }

        $CRAHs = $Zones = $Pressures = $Ambients = $Fire_alarms = array();

        foreach ($devices as $key => $data) {
            if (preg_match('/-CRAH-/', $key) || preg_match('/-RTU-/', $key)) {
                $crah = new CRAH($key);
                $this->saveCRAHData($crah, $data);
                $CRAHs[] = $crah;
            } elseif (preg_match('/-ZONE-/', $key)) {
                $zone = new ZoneSensor($key);
                $this->saveWirelessSensorData($zone, $data);
                $Zones[] = $zone;
            } elseif (preg_match('/-AMBIENT-/', $key)) {
                $ambient = new Ambient($key);
                $this->saveAmbientData($ambient, $data);
                $Ambients[] = $ambient;

                foreach ($data as $i=>$j) {
                    if ($i == 'SP') {
                        $pressure = new StaticPressure($key);
                        $pressure->setPressure($j);
                        $Pressures[] = $pressure;
                    }
                }
            } elseif (preg_match('/-PRESS-/', $key)) {
                foreach ($data as $i=>$j) {
                    if ($i == 'PRESS') {
                        $pressure = new StaticPressure($key);
                        $pressure->setPressure($j);
                        $Pressures[] = $pressure;
                    }
                }
            } elseif (preg_match('/First_Stage_Detector/', $key)) {
                foreach ($data as $i=>$j) {
                    if ($i == 'STATUS') {
                        $fire_alarm = new FireAlarm($key);
                        $fire_alarm->setStatus($j);
                        $Fire_alarms[] = $fire_alarm;
                    }
                }
            } elseif (preg_match('/VESDA/', $key)) {
                foreach ($data as $i=>$j) {
                    if ($i == 'STATUS') {
                        $fire_alarm = new FireAlarm($key);
                        $fire_alarm->setStatus($j);
                        $Fire_alarms[] = $fire_alarm;
                    }
                }
            }
        }

        usort($CRAHs, array($this, 'natDeviceSort'));
        usort($Zones, array($this, 'natDeviceSort'));
        usort($Pressures, array($this, 'natDeviceSort'));
        usort($Ambients, array($this, 'natDeviceSort'));
        usort($Fire_alarms, array($this, 'natDeviceSort'));

        return [
            'CRAHs' => $CRAHs,
            'Zones' => $Zones,
            'Pressures' => $Pressures,
            'Ambients' => $Ambients,
            'Fire_alarms' => $Fire_alarms
        ];

    }

    public function getBuildingFireAlarmData() {
        $mango = new Mango($this->mapDatacenterToServer());
        $mango->login();

        $room_id = MangoUtils::getFireRoomId($mango, $this->dataCenter);

        $Fire_alarms = array();
        $totals = [];

        if ($room_id) {
            $points = $mango->getAllRealtimeData($room_id);
            $devices = MangoUtils::groupHierarchy($points);

            foreach ($devices as $key => $data) {
                if (preg_match('/FIRE/', $key)) {
                    foreach ($data as $alert => $status) {
                        $Fire_alarms[] = [
                            $alert => $status
                        ];
                    }
                }

                $rollup = [];
                $rollup['deviceName'] = $key;
                if (preg_match('/FIRE/', $key)) {
                    $rollup['deviceType'] = 'FIRE';
                }
                elseif (preg_match('/TANK/', $key)) {
                    $rollup['deviceType'] = 'TANK';
                }
                elseif (preg_match('/UTIL/', $key)) {
                    $rollup['deviceType'] = 'UTIL';
                }
                elseif (preg_match('/UPS/', $key)) {
                    $rollup['deviceType'] = 'UPS';
                }

                $rollup['points'] = array();
                foreach ($data as $k => $v){
                    $rollup['points'][] = Utils::formatMangoPoint($k, $v);
                }
                $totals[] = $rollup;
            }
        }

        return $totals;
    }

    /**
     * @param ZoneSensor $zone
     * @param $data
     */
    protected function saveWirelessSensorData(ZoneSensor &$zone, $data)
    {
        //$alert = new Alert(array(0,25, 75, 100));

        foreach ($data as $key => $value) {
            if (preg_match('/ZH/', $key)) {
                $zone->setHumidity($value);
                //$zone->setAlertH($alert);
            } elseif (preg_match('/ZT/', $key)) {
                $zone->setTemperature($value);
                //$zone->setAlertT($alert);
            }
        }
    }

    /**
     * Save ambient sensor data to instantiated object
     * @param Ambient $ambient <p>is expected</p>
     * @param $data <p>
     * The data to search through.
     * </p>
     */
    protected function saveAmbientData(Ambient &$ambient, $data)
    {
        //$alert = new Alert(array(0,25, 75, 100));

        foreach ($data as $key => $value) {
            if (preg_match('/AH/', $key)) {
                $ambient->setAmbientHumidity($value);
                //$ambient->setAlertAH($alert);
            } elseif (preg_match('/AT/', $key)) {
                $ambient->setAmbientTemperature($value);
                //$ambient->setAlertAT($alert);
            }
        }
    }
}
