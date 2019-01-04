<?php

namespace Dashboard;

use Memcached;

class Utils
{
    public static function getMemCached()
    {
        $m = new Memcached();
        $m->addServer('localhost', 11211);
        return $m;
    }

    public static function natDeviceSort($a, $b)
    {
        return strnatcmp($a->deviceName, $b->deviceName);
    }

    public static function printToConsole($data)
    {
        if(is_array($data) || is_object($data)) {
            echo("<script>console.log('".json_encode($data)."');</script>");
        } else {
            echo("<script>console.log('".$data."');</script>");
        }
    }

    public static function formatMangoPressurePoint($value = null)
    {
        $point = array();
        $point['name'] = 'Fan Status';
        if(gettype($value) == 'NULL')
            $point['value'] = 'No Comm';
        elseif($value == 'off' || $value == 'on' || $value == 'Off' || $value == 'On')
            $point['value'] = ucwords($value);
        elseif(is_numeric($value)) {
            $point['name'] = 'Fan Speed';
            $point['value'] = number_format($value, 1);
        }
        else
            $point['value'] = $value;

        return $point;
    }

    public static function formatMangoPoint($name, $value, $alert = null, $decimals = 1)
    {
        $point = array();
        $point['name'] = $name;
        if(gettype($value) == 'NULL')
            $point['value'] = 'No Comm';
        elseif($name == 'kWh')
            $point['value'] = number_format($value, 0);
        elseif(is_numeric($value))
            $point['value'] = number_format($value, $decimals);
        else
            $point['value'] = $value;

        return $point;
//
//        if($name == 'fire_alarm') {
//            if ($value == null)
//                $point['value'] = 'off';
//            else
//                $point['value'] = 'on';
//            return $point;
//        }


//        if ($value <= $alert->critical_low || $value >= $alert->critical_high)
//            $point['alert'] = 'critical';
//        elseif ($value <= $alert->warning_low || $value >= $alert->warning_high)
//            $point['alert'] = 'warning';
//        else
//            $point['alert'] = 'normal';
    }
}
