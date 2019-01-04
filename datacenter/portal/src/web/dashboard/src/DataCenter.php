<?php

namespace Dashboard;

class DataCenter
{
    protected $name;

    /**
     * @param $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    public function mapDatacenterToServer()
    {
        switch (strtoupper($this->name)) {
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
                return '1.2.3.4';
                break;
            case 'DC1':
                return '1.2.3.4';
                break;
            case 'DC1':
                return '1.2.3.4';
                break;
        }
    }

    public function getWaterData()
    {
        $mango = new Mango($this->mapDatacenterToServer());
        $mango->login();

        $room_id = MangoUtils::getWaterRoomId($mango);
        $points = $mango->getAllRealtimeData($room_id);
        $devices = MangoUtils::groupHierarchy($points);

        return $devices;
    }

    public function getWaterTrends($deviceNames, $start_date = null, $end_date = null)
    {
        $mango = new Mango($this->mapDatacenterToServer());
        $mango->login();

        $room_id = MangoUtils::getWaterRoomId($mango);
        $devices = $mango->mapDeviceNamesToXIDs([$room_id], $deviceNames);
        $trends = $mango->getTrendDataFromDevices($devices, $start_date, $end_date);
        $devices = MangoUtils::mapTrendsToDevices($trends, $devices);

        return $devices;
    }
}
