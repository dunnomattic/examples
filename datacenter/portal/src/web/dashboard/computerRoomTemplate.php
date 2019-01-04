<?php
$start = microtime(true);
require 'twigSetup.php';
require_once("models/config.php");
$time_elapsed_secs = microtime(true) - $start;
//printToConsole(array('requires',$time_elapsed_secs));

use Dashboard\ComputerRoom;
use Dashboard\Utils;

if (!securePage($_SERVER['PHP_SELF'])){ //try to use securePageCR instead, which uses room_assignments table.
    // Forward to 404 page
    addAlert("danger", "Whoops, looks like you don't have permission to view that page.");
    header("Location: 404.php");
    exit();
}

setReferralPage($_SERVER['PHP_SELF']);
renderTemplateHeader();

$cr = substr(basename($_SERVER['PHP_SELF'],".php"), strpos(basename($_SERVER['PHP_SELF'],".php"), "-") + 1);
$dc = strstr(basename($_SERVER['PHP_SELF'],".php"), "-", true);

if(strtoupper($dc) == 'DEMO')
    $computerRoom = new ComputerRoom('acc5', $cr);
else
    $computerRoom = new ComputerRoom($dc, $cr);



$start = microtime(true);
$mechanicalData = $computerRoom->prepareMechanicalData();
$time_elapsed_secs = microtime(true) - $start;
//Utils::printToConsole(array('mechanicalData',$time_elapsed_secs));
//Utils::printToConsole($mechanicalData);

if (isset($mechanicalData['CRAHs']['body'])) {
    $crah_body = $mechanicalData['CRAHs']['body'];
    $crah_headers = $mechanicalData['CRAHs']['headers'];
}
else {
    $crah_body = $crah_headers = null;
}

if (isset($mechanicalData['Zones']['body'])) {
    $zone_headers = $mechanicalData['Zones']['headers'];
    $zone_body = $mechanicalData['Zones']['body'];
}
else {
    $zone_body = $zone_headers = array();
}

$humidities = [];
$temperatures = [];
foreach($zone_body as $zone_device)
{
    foreach($zone_device["points"] as $point)
    {
        if($point["name"] == "Humidity" && !empty($point["value"]))
            $humidities[] = $point["value"];
        elseif($point["name"] == "Temperature" && !empty($point["value"]))
            $temperatures[] = $point["value"];
    }
}

if (count($temperatures) > 0) {
    $average_temperature = array_sum($temperatures)/count($temperatures);
}
else {
    $average_temperature = 0;
}
if (count($humidities) > 0) {
    $average_humidity = array_sum($humidities)/count($humidities);
}
else {
    $average_humidity = 0;
}

if (isset($mechanicalData['Ambients']['body'])) {
    $ambient_headers = $mechanicalData['Ambients']['headers'];
    $ambient_body = $mechanicalData['Ambients']['body'];
}
else {
    $ambient_body = $ambient_headers = null;
}


if (isset($mechanicalData['Pressures']['body'])) {
    $pressure_headers = $mechanicalData['Pressures']['headers'];
    $pressure_body = $mechanicalData['Pressures']['body'];
}
else {
    $pressure_body = $pressure_headers = null;
}

if (isset($mechanicalData['Fire_alarms']['body'])) {
    $fire_alarm_headers = $mechanicalData['Fire_alarms']['headers'];
    $fire_alarm_body = $mechanicalData['Fire_alarms']['body'];
}
else {
    $fire_alarm_body = $fire_alarm_headers = null;
}

$start = microtime(true);
$electricalData = $computerRoom->prepareElectricalData();
$time_elapsed_secs = microtime(true) - $start;
//Utils::printToConsole(array('electricalData',$time_elapsed_secs));
//Utils::printToConsole($electricalData);


if (isset($electricalData['PDUs']['body'])) {
    $pdu_headers = $electricalData['PDUs']['headers'];
    $pdu_body = $electricalData['PDUs']['body'];
}
else {
    $pdu_body = $pdu_headers = null;
}

if (isset($electricalData['SWBs']['body'])) {
    $swb_headers = $electricalData['SWBs']['headers'];
    $swb_body = $electricalData['SWBs']['body'];
}
else {
    $swb_body = $swb_headers = null;
}

if (isset($electricalData['UPSs']['body'])) {
    $ups_headers = $electricalData['UPSs']['headers'];
    $ups_body = $electricalData['UPSs']['body'];
}
else {
    $ups_body = $ups_headers = null;
}

if (isset($electricalData['DBs']['body'])) {
    $d_board_headers = $electricalData['DBs']['headers'];
    $d_board_body = $electricalData['DBs']['body'];
}
else {
    $d_board_body = $d_board_headers = null;
}

if (isset($electricalData['BCMs']['body'])) {
    $bcm_headers = $electricalData['BCMs']['headers'];
    $bcm_body = $electricalData['BCMs']['body'];
}
else {
    $bcm_body = $bcm_headers = null;
}
if (isset($electricalData['Totals']['body'])) {
    $electrical_totals_headers = $electricalData['Totals']['headers'];
    $electrical_totals_body = $electricalData['Totals']['body'];
}
else {
    $electrical_totals_body = $electrical_totals_headers = null;
}
//Utils::printToConsole($electricalData['Totals']);

if (isset($electricalData['CDPs']['body'])) {
    $cdp_headers = $electricalData['CDPs']['headers'];
    $cdp_body = $electricalData['CDPs']['body'];
}
else {
    $cdp_body = $cdp_headers = null;
}

if (isset($electricalData['STSs']['body'])) {
    $sts_headers = $electricalData['STSs']['headers'];
    $sts_body = $electricalData['STSs']['body'];
}
else {
    $sts_body = $sts_headers = null;
}


$start = microtime(true);
$device_names = $computerRoom->getDeviceNames();
$time_elapsed_secs = microtime(true) - $start;
//Utils::printToConsole(array('deviceNames',$time_elapsed_secs));

$start = microtime(true);
$rollups = $computerRoom->getBuildingFireAlarmData();
$fire_alarms = [];
$tanks = [];
$utils = [];
foreach ($rollups as $rollup) {
    if(isset($rollup['deviceType'])) {
        if ($rollup['deviceType'] == 'FIRE') {
            $fire_alarms[] = $rollup['points'][0];
        } elseif ($rollup['deviceType'] == 'TANK') {
            $tanks[] = $rollup;
        } elseif ($rollup['deviceType'] == 'UTIL' || $rollup['deviceType'] == 'UPS') {
            $utils[] = $rollup;
        }
    }
}

$util_headers = [];
if($utils) {
    foreach ($utils[0]['points'] as $point)
    {
        if($point["name"] == "KW")
            $util_headers[] = " Real Power (kW)";
        elseif($point["name"] == "V")
            $util_headers[] = "Voltage (V)";
        elseif($point["name"] == "KVA")
            $util_headers[] = " Apparent Power (kVA)";
        else
            $util_headers[] = $point["name"];
    }
}

$tank_headers = [];
if($tanks) {
    foreach ($tanks[0]['points'] as $point)
    {
        if($point["name"] == "CAPACITY")
            $tank_headers[] = "Tank Size (Gallons)";
        elseif($point["name"] == "VOLUME")
            $tank_headers[] = "Quantity on Hand (Gallons)";
        else
            $tank_headers[] = $point["name"];
    }
}

$time_elapsed_secs = microtime(true) - $start;

foreach ($device_names as $key => &$value)
{
    $value = array_filter($value, function($device_name) use($pdu_headers, $swb_headers, $bcm_headers, $ups_headers) {
        $last_segment = substr(strrchr($device_name, "-"), 1);
        if ($last_segment == "FIRE" || $last_segment == "PREFIRE")
            return false;
//        elseif (preg_match('/-PDU-/', $device_name) && $pdu_headers == null)
//            return false;
//        elseif (preg_match('/-SWB-/', $device_name) && $swb_headers == null)
//            return false;
//        elseif (preg_match('/-BCM-/', $device_name) && $bcm_headers == null)
//            return false;
        else {
            return true;
        }
    });
}

$roomTotal = $computerRoom->getRoomPowerTotal();
//Utils::printToConsole(array('roomPowerTotal', $roomTotal));

$show_overview_tab = false;

$show_averages = false;
if(!empty($temperatures) && !empty($humidities))
    $show_averages = true;


if($fire_alarms || $fire_alarm_body)
    $show_overview_tab = true;
elseif(strtoupper($dc) == 'DC1' || strtoupper($dc) == 'DC1' || strtoupper($dc) == 'DC1') {
    $show_overview_tab = true;
}
elseif($show_averages) {
    $show_overview_tab = true;
    $util_headers = false;
    $tank_headers = false;
}

function anonymizeDemoData(&$sensors)
{
    if ($sensors === null) {
        return;
    }
    foreach($sensors as &$sensor)
    {
        $sensor['deviceName'] = str_replace('DC1', 'DEMO', $sensor['deviceName']);
    }
}

function anonymizeDemoDevices(&$device_names)
{
    foreach ($device_names as &$device_name)
    {
        $device_name = str_replace('DC1', 'DEMO', $device_name);
    }
}

if(strtoupper($dc) == 'DEMO') {
    anonymizeDemoData($pdu_body);
    anonymizeDemoData($swb_body);
    anonymizeDemoData($d_board_body);
    anonymizeDemoData($bcm_body);
    anonymizeDemoData($sts_body);
    anonymizeDemoData($crah_body);
    anonymizeDemoData($zone_body);
    anonymizeDemoData($ambient_body);
    anonymizeDemoData($pressure_body);
    anonymizeDemoData($fire_alarm_body);
    anonymizeDemoData($tanks);
    anonymizeDemoData($utils);
    anonymizeDemoData($electrical_totals_body);
    anonymizeDemoDevices($device_names);
}

$sidebarContent = fetchSidebarContent($loggedInUser);

echo $twig->render('dc-cr.html', array(
    'dc' => $dc,
    'cr' => $cr,
    'apilink' => "https://" . $_SERVER["NONPROD_PREFIX"] . "portal.XXXXXX.com/api/dashboard/v1/realtime/$dc/$cr",
    'apilinkexample' => "https://" . urlencode($_SESSION["email"]) . ":mypasswordgoeshere@" . $_SERVER["NONPROD_PREFIX"] . "portal.XXXXXX.com/api/dashboard/v1/realtime/$dc/$cr",
    'sidebar_content' => $sidebarContent,
    'pdu_headers' => $pdu_headers,
    'pdu_body' => $pdu_body,
    'swb_headers' => $swb_headers,
    'swb_body' => $swb_body,
    'ups_headers' => $ups_headers,
    'ups_body' => $ups_body,
    'd_board_headers' => $d_board_headers,
    'd_board_body' => $d_board_body,
    'bcm_headers' => $bcm_headers,
    'bcm_body' => $bcm_body,
    'crah_headers' => $crah_headers,
    'crah_body' => $crah_body,
    'zone_headers' => $zone_headers,
    'zone_body' => $zone_body,
    'pressure_body' => $pressure_body,
    'fire_alarm_body' => $fire_alarm_body,
    'fire_alarms' => $fire_alarms,
    'tank_body' => $tanks,
    'tank_headers' => $tank_headers,
    'util_body' => $utils,
    'util_headers' => $util_headers,
    'ambient_headers' => $ambient_headers,
    'ambient_body' => $ambient_body,
    'device_names' => $device_names,
    'electrical_totals_headers' => $electrical_totals_headers,
    'electrical_totals_body' => $electrical_totals_body,
    'cdp_headers' => $cdp_headers,
    'cdp_body' => $cdp_body,
    'sts_headers' => $sts_headers,
    'sts_body' => $sts_body,
    'show_overview_tab' => $show_overview_tab,
    'show_averages' => $show_averages,
    'average_temperature' => number_format($average_temperature, 1),
    'average_humidity' => number_format($average_humidity, 1),
    'layout_img' => 0
));
renderTemplateFooter();
