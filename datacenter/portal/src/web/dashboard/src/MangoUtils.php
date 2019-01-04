<?php

namespace Dashboard;

use Dashboard\Utils;
use Dashboard\Mango;

class MangoUtils
{

    public static function getFireRoomId(Mango $mango, $dc)
    {
        $hierarchy = $mango->getHierarchyByName('Building');

        foreach ($hierarchy->points as $point)
        {
//            if($point->deviceName == strtoupper($dc) . '-FIRE')
//            {
                return $point->pointFolderId;
//            }
        }
    }

    public static function getWaterRoomId(Mango $mango)
    {
        $hierarchy = $mango->getHierarchyByName('Water');

        foreach ($hierarchy->points as $point)
        {
            return $point->pointFolderId;
        }
    }

    public static function mapRoomId(Mango $mango, $dc, $cr = null, $sensor)
    {
        $m = Utils::getMemCached();
        $memName = 'DC_' . $dc . '_hierarchy';
        if ($m->get($memName))
            $hierarchy = $m->get($memName);
        else {
            $hierarchy = $mango->getHierarchy();
            $m->set($memName, $hierarchy, 4 * 60 * 60);
        }

        foreach ($hierarchy->subfolders as $subfolder) {
            if ($subfolder->name == strtoupper($dc)) {
                foreach ($subfolder->subfolders as $subsubfolder) {
                    if ($subsubfolder->name == ('COLO ' . strtoupper($cr))) {
                        foreach ($subsubfolder->subfolders as $subsubsubfolder) {
                            if ($subsubsubfolder->name == ('All '. ucfirst($sensor))) {
                                return $subsubsubfolder->id;
                            }
                        }
                    } elseif ($sensor == 'fire' && $subsubfolder->name == 'Building') {
                        return $subsubfolder->id;
                    }
                }
            }
        }
    }

    public static function groupHierarchy($points)
    {
        $values = array();

        foreach ($points as $key => $point) {
            if (isset($point->deviceName)) {
                $values[$point->deviceName][$point->name] = $point->value;
            }
        }

        return $values;
    }

    public static function mapTrendsToDevices(&$trends, &$devices)
    {
        foreach ($devices as &$device) {
            foreach ($device['devicePoints'] as $i => &$devicePoint) {
                foreach ($trends as $index => $trend) {
                    if ($index == $devicePoint['xid']) {
//                        $trend = array_reverse($trend);
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
        return $devices;
    }
}
