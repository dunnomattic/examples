<?php
//namespace Dashboard;
$start = microtime(true);
require 'twigSetup.php';
require_once("models/config.php");
//$time_elapsed_secs = microtime(true) - $start;

/*if (!securePage($_SERVER['PHP_SELF'])){ //try to use securePageCR instead, which uses room_assignments table.
    // Forward to 404 page
    addAlert("danger", "Whoops, looks like you don't have permission to view that page.");
    header("Location: 404.php");
    exit();
}*/

$clientName = getGroupName($loggedInUser->user_id);
$dc = basename($_SERVER['PHP_SELF'],".php");
//$CRs = getComputerRoomsByDCAndClient(strtolower($dc), $clientName);
$CRs = getComputerRoomsByDC(strtoupper($dc));
if (empty($CRs)) {
    error_log("No Access");
    header("Location: 404.php");
    exit;
}

use Dashboard\ComputerRoom;
use Dashboard\Utils;

renderTemplateHeader();
setReferralPage($_SERVER['PHP_SELF']);


$totals_headers = ["Real Power Total (kW)", "Capacity", "Available", "% Remaining", "Average", "Max", "Max Time"];
$totals_body = [];
$dc_total = 0;
$dc_capacity = 0;
//$device_names = [];

$lm_start_stamp = strtotime('1 month ago');
$lm_start_date = date('Y-m-d', $lm_start_stamp);

$lm_end_stamp = strtotime('today');
$lm_end_date = date('Y-m-d', $lm_end_stamp);

$start = microtime(true);
if(!empty($CRs)) {
    foreach ($CRs as $cr) {
        $cr = strtolower(substr($cr, 3));
        $computerRoom = new ComputerRoom($dc, $cr);
        $stats = $computerRoom->getRoomPowerStats($lm_start_date, $lm_end_date);
        $capacity = getCapacityByComputerRoom($dc, $cr);

        $TkW_f = isset($stats['last']) ? floatval(str_replace(',', '', $stats['last'])) : 0;
        $dc_total += $TkW_f;
        $dc_capacity += $capacity;

        $max_time = intval(intval($stats['max_time'])/1000);
        //$dt = new DateTime("@$max_time");  // convert UNIX timestamp to PHP DateTime
        //$max_time = strval($dt->format('m-d-Y H:i'));
        $max_time = date("m-d-Y H:i", $max_time);

//        $device_names['Electrical'][] = strtoupper($dc) . "-" . strtoupper($cr) . "-TOTALS";
        $totals_body[strtoupper($dc)][] = [
            "roomName" => strtoupper($dc) . "-" . strtoupper($cr),
            "points" => [
                ["name" => "TkW", "value" => number_format($TkW_f, 1)],
                ["name" => "Capacity", "value" => isset($capacity) ? $capacity : ''],
                ["name" => "Available", "value" => isset($capacity) ? number_format($capacity - $TkW_f, 1) : ''],
                ["name" => "% Remaining", "value" => isset($capacity) ? number_format(100*($capacity - $TkW_f)/$capacity,0)."%" : ''],
                ["name" => "Average", "value" => isset($stats['average']) ? number_format($stats['average'], 1) : ''],
                ["name" => "Max", "value" => isset($stats['maximum']) ? number_format($stats['maximum'], 1) : ''],
                ["name" => "Max Time", "value" => isset($stats['max_time']) ? $max_time : ''],
            ]
        ];
    }

    $totals_body[strtoupper($dc)][] = [
        "roomName" => strtoupper($dc) . " Total",
        "points" => [
            ["name" => "TkW", "value" => number_format($dc_total, 1)],
            ["name" => "Capacty", "value" => number_format($dc_capacity, 1)],
            ["name" => "Available", "value" => number_format($dc_capacity - $dc_total, 1)],
            ["name" => "% Remaining", "value" => number_format(100*($dc_capacity - $dc_total)/$dc_capacity,0)."%"]
        ]
    ];
}

//$device_names['Totals'][] = 'Rooms TkW';

//$time_elapsed_secs = microtime(true) - $start;
//Utils::printToConsole(array('electricalTotals',$time_elapsed_secs));
//Utils::printToConsole($totals_body);
//Utils::printToConsole($device_names);
//Utils::printToConsole($computerRoom->getRoomPowerStats());

$sidebarContent = fetchSidebarContent($loggedInUser);

echo $twig->render('dc_report.html', array(
    'dc' => $dc,
    'totals_headers' => $totals_headers,
    'lm_totals_body' => $totals_body,
    'lm_start_date' => date('m/d/Y', $lm_start_stamp),
    'lm_end_date' => date('m/d/Y', $lm_end_stamp),
    'sidebar_content' => $sidebarContent
//    'device_names' => $device_names
));
