<?php

function authMaintenanceNotice() {
    $retval = "";
    if(!empty($_SERVER["maintenanceTrigger"])) {
        $startHour = $_SERVER["maintenanceTrigger"];
        $startStamp = strtotime("$startHour:00");
        $endStamp = $startStamp + (15*60);
        $startLabel = date("g:ia", $startStamp);
        $endLabel = date("g:ia", $endStamp);
        $dayNightLabel = "tonight";
        if ($startHour > 5 && $startHour < 18) {
            $dayNightLabel = "today";
        }
        $retval = "<span style='font-size: 11px'>
            <strong><u>Notice</u>:</strong> This application will be unavailable during maintenance $dayNightLabel between $startLabel and $endLabel.
        </span>";
    }

    return $retval;
}

?>
