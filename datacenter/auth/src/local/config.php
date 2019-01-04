<?php

$authNoticeMessages = array(
    "",
    "Login failed.  Please try again.",
    "XXXXXX users should authenticate with their Windows credentials.",
    "User with same email already exists.",
    "An activation email has been sent to your address.  Please follow the instructions.",
    "The information you supplied was incomplete.  Please attempt the registration process again.",
    // 5
    "There was an error creating your information.",
    "The information you supplied is incorrect.  This event has been recorded.",
    "You have rejected the activation request.  If you did not originate the request, let us know immediately.",
    "A password reset email has been sent to your address.  Please follow the instructions.",
    "The new passwords you supplied did not match.  Please try again.",
    "Reset requests are not supported for this account.  Please contact the <a href='mailto:helpdesk@XXXXXX.com?subject=Password Reset assistance'>IT Helpdesk</a>."
);

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
        $retval = "<div class='alert alert-danger' role='alert'>
            <strong>Notice:</strong> This application will be unavailable during maintenance $dayNightLabel between $startLabel and $endLabel.
        </div>";
    }
    return $retval;
}

?>
