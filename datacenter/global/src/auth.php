<?php

function authenticate() {
    if (empty($_SESSION["email"])) {
        $_SESSION["authRedirect"] = "https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
        header("Location: https://" . $_SERVER["NONPROD_PREFIX"] . "auth.XXXXXX.com/");
        exit;
    }
    if (function_exists('local_authenticate')) {
        local_authenticate();
    }
}

function authorized($requestPath = "") {
    if (!hasPermission($requestPath)) {
        error_log("Permission denied, missing " . getRequestAuthorizationCategory($requestPath));
        header("Location: /\r\n");
        exit;
    }
}

function hasPermission($requestPath = "") {
    $authCat = getRequestAuthorizationCategory($requestPath);
    return ( empty($authCat) || ( isset($_SESSION['permissions'][$authCat]) && $_SESSION['permissions'][$authCat] ) );
}

function getRequestAuthorizationCategory($requestPath = "") {
    // returns the named permission to search the user's permissionList for 
    error_log(print_r(PROTECTEDPATHARRAY, true));
    $retval = "";
    if (empty($requestPath)) {
        $requestPath = $_SERVER["PHP_SELF"];
    }
    $dirNames = explode("/", $requestPath);
    $catIndex = array_search($dirNames[1], PROTECTEDPATHARRAY);
    if ($catIndex !== false) {
        //error_log($catIndex);
        $retval = PROTECTEDPATHARRAY[$catIndex];
    }
    else if (isAPIRequest()) {
        $catIndex = array_search($dirNames[2], PROTECTEDPATHARRAY);
         if ($catIndex = array_search($dirNames[2], PROTECTEDPATHARRAY) !== false) {
            //error_log($catIndex);
            $retval = PROTECTEDPATHARRAY[$catIndex];
        }   
    }
    //error_log("authCat for this requests is $retval");
    return $retval;
}

?>
