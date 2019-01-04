<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/../global/config.php");

if (empty($_SESSION['email']) && $_SERVER['REMOTE_ADDR'] != "127.0.0.1") {
    // check basic auth
    if (empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW'])) {
        // force prompt
        //error_log('empty session and empty basic auth');
        header('WWW-Authenticate: Basic realm="XXXXXX API - Login with email address and password"');
        //error_log('sending first 401');
        header('HTTP/1.0 401 Unauthorized');
        echo 'Incorrect email address or password.  Please try again.';
        error_log('Incorrect email address or password.  Please try again. 1');
        usleep(500);
        //var_dump($_SERVER);
        exit;
    }
    else {
        //error_log('basic creds provided');
        // perform auth-style login and create session
        $_SESSION["authRedirect"] = "";
        $_POST["email"] = $_SERVER['PHP_AUTH_USER'];
        $_POST["password"] = $_SERVER['PHP_AUTH_PW'];
        if (strpos($_POST["password"], ":") !== false) {
            $mimicArray = explode(":", $_POST["password"]);
            $_POST["email"] .= ":" . $mimicArray[0];
            $_POST["password"] = $mimicArray[1];
        }
        //error_log('attempting login with u:' . $_POST["email"] . ', p:' . $_POST['password']);
        error_log('attempting login with u:' . $_POST["email"] . ', p:***');
        require_once($_SERVER["DOCUMENT_ROOT"] . "/../../../auth/src/web/process_login.php");
    }
}
//error_log(print_r($_SESSION, true));
//error_log('Session set, do the API');
?>
