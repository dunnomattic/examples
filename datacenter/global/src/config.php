<?php

define("ADMINARRAY", array(
    "xxxx@xxx",
    "xxxx@xxx",
    "xxxx@xxx"
));

define("PROTECTEDPATHARRAY", array(
    "security"
));

if (!empty($_SERVER["DOCUMENT_ROOT"])) {
    if (file_exists($_SERVER["DOCUMENT_ROOT"] . "/../local/config.php")) {
        require_once($_SERVER["DOCUMENT_ROOT"] . "/../local/config.php");
    }
    require_once($_SERVER["DOCUMENT_ROOT"] . "/../global/database.php");
    require_once($_SERVER["DOCUMENT_ROOT"] . "/../global/session.php");
    require_once($_SERVER["DOCUMENT_ROOT"] . "/../global/auth.php");
    require_once($_SERVER["DOCUMENT_ROOT"] . "/../global/mail.php");
    require_once($_SERVER["DOCUMENT_ROOT"] . "/../global/password.php");
}
else {
    $runDir = dirname(__FILE__);
    $rfArray = explode("/", $runDir);
    $user = $rfArray[2];
    $site = $rfArray[4];
    $_SERVER["SERVER_ADMIN"] = $user . "@xxxxxx.com";
    $_SERVER["HTTP_HOST"] = "$user-$site.xxxxxx.com";
    $host = gethostname();
    $host_pre = substr($host, 0, 3);
    $host_suf = substr($host, 3);
    
    if ($host_pre == 'GXX') {
        $_SERVER["NONPROD_PREFIX"] = $_SERVER["USER"] . "-";
        $_SERVER["ENVIRONMENT"]= "dev";
        $_SERVER["MANGO_USERNAME"]= "xxx";
        $_SERVER["MANGO_PASSWORD"]= "xxx";
        $_SERVER["DB_HOST"]= "1.2.3.4";
        $_SERVER["DB_NAME"]= "xxx";
        $_SERVER["DB_USERNAME"]= "xxx";
        $_SERVER["DB_PASSWORD"]= "xxx";
        if ($host_suf == "DWEB1") {
            $_SERVER["DOCUMENT_ROOT"] = "/home/$user/dev/portal/src/web";
        }
        else {
            $_SERVER["DOCUMENT_ROOT"] = "/home/$user/dev/inside/src/web";
        }
    }
    else if ($host_pre == 'LXX') {
        $_SERVER["NONPROD_PREFIX"] = "test-";
        $_SERVER["ENVIRONMENT"]= "test";
        $_SERVER["MANGO_USERNAME"]= "xxx";
        $_SERVER["MANGO_PASSWORD"]= "xxx";
        $_SERVER["DB_HOST"]= "1.2.3.4";
        $_SERVER["DB_NAME"]= "xxx";
        $_SERVER["DB_USERNAME"]= "xxx";
        $_SERVER["DB_PASSWORD"]= "xxx";
        if ($host_suf == "TWEB1") {
            $_SERVER["DOCUMENT_ROOT"] = "/var/www/test/portal/src/web";
        }
        else {
            $_SERVER["DOCUMENT_ROOT"] = "/var/www/test/inside/src/web";
        }
    }
    else {
        $_SERVER["ENVIRONMENT"]= "prod";
        $_SERVER["MANGO_USERNAME"]= "admin";
        $_SERVER["MANGO_PASSWORD"]= "admin";
        $_SERVER["DB_HOST"]= "1.2.3.4";
        $_SERVER["DB_NAME"]= "xxx";
        $_SERVER["DB_USERNAME"]= "xxx";
        $_SERVER["DB_PASSWORD"]= "xxx";
        if ($host_suf == "PWEB1") {
            $_SERVER["DOCUMENT_ROOT"] = "/var/www/prod/portal/src/web";
        }
        else {
            $_SERVER["DOCUMENT_ROOT"] = "/var/www/prod/inside/src/web";
        }
    }
    if (file_exists($_SERVER["DOCUMENT_ROOT"] . "/../local/config.php")) {
        require_once($_SERVER["DOCUMENT_ROOT"] . "/../local/config.php");
    }
    require_once("database.php");
    require_once("session.php");
    require_once("mail.php");
    require_once("password.php");
}

function isXXXXXXEmployee() {
    return (!empty($_SESSION["company"]) && ($_SESSION["company"] == "DXX" || $_SESSION["company"] == "DXY"));
}

function renderTemplateHeader() {
    global $skipTemplate;
    if (file_exists($_SERVER["DOCUMENT_ROOT"] . "/../local/template_header.php") && !isset($skipTemplate)) {
        require_once($_SERVER["DOCUMENT_ROOT"] . "/../local/template_header.php");
    }
}

function renderTemplateFooter() {
    if (file_exists($_SERVER["DOCUMENT_ROOT"] . "/../local/template_footer.php") && !isset($skipTemplate)) {
        require_once($_SERVER["DOCUMENT_ROOT"] . "/../local/template_footer.php");
    }
}

function isAPIRequest() {
    return (substr($_SERVER["PHP_SELF"], 0, 4) == "/api");
}

?>
