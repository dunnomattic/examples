<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/../global/config.php");
if (!empty($_SESSION["email"])) {
    if ($_SESSION["company"] == "XXXXXX") {
        header("Location: https://" . $_SERVER["NONPROD_PREFIX"] . "inside.XXXXXX.com/");
        exit;
    }
    else {
        header("Location: https://" . $_SERVER["NONPROD_PREFIX"] . "portal.XXXXXX.com/");
        exit;
    }
}
?>
<html><!-- <?= @file_get_contents("build_number.txt"); ?>  -->
    <head>
        <title>Auth Login</title>
        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

        <!-- Optional theme -->
        <link rel="stylesheet" href="css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

        <link rel="stylesheet" href="css/login.css">
    </head>
    <body>
        <div class="container">
        <?php
            echo authMaintenanceNotice();
        ?>
            <div class="row">
                <div class="col-md-6 col-md-offset-3">
                    <div class="panel panel-login">
                        <div class="panel-heading">
                            <div class="row">
                                <div class="col-xs-6">
                                    <a href="#" <?= ((!empty($_REQUEST["p"]) && $_REQUEST["p"]=="r") ? ""  : " class='active'") ?> id="login-form-link">Login</a>
                                </div>
                                <div class="col-xs-6">
                                    <a href="#" <?= ((!empty($_REQUEST["p"]) && $_REQUEST["p"]=="r") ? " class='active'" : "") ?> id="register-form-link">Register</a>
                                </div>
                            </div>
                            <hr>
                        </div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-lg-12">
                                        <?php
                                        if (isset($_REQUEST["i"]) && $_REQUEST["i"] > 0 &&
                                                !empty($authNoticeMessages[$_REQUEST["i"]])) {
                                            $failedText = "
                                        <div class='form-group'>
                                            <div class='row'>
                                                <div class='col-lg-12'>
                                                    <div class='text-center failed-password'>
                                                        " . $authNoticeMessages[$_REQUEST["i"]] . "
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        ";
                                            echo $failedText;
                                        }
                                        ?>

                                    <form id="login-form" action="process_login.php" method="post" role="form" <?= ((!empty($_REQUEST["p"]) && $_REQUEST["p"]=="r") ? "style='display: none;'"  : " style='display: block;'") ?>>
                                        <div class="form-group">
                                            <input type="text" name="email" id="email" tabindex="1" class="form-control" placeholder="Email" value="" required>
                                        </div>
                                        <div class="form-group">
                                            <input type="password" name="password" id="password" tabindex="2" class="form-control" placeholder="Password" required>
                                        </div>
                                        <div class="form-group">
                                            <div class="row">
                                                <div class="col-sm-6 col-sm-offset-3">
                                                    <input type="submit" name="login-submit" id="login-submit" tabindex="4" class="form-control btn btn-login" value="Log In">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="text-center">
                                                        <a href="#" tabindex="5" class="forgot-password" id="forgot-form-link">Forgot Password?</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                    <form id="register-form" action="process_registration.php" method="post" role="form" <?= ((!empty($_REQUEST["p"]) && $_REQUEST["p"]=="r") ? "style='display: block;'"  : " style='display: none;'") ?>>
                                        <div class="form-group">
                                            <input type="text" name="first_name" tabindex="1" class="form-control" placeholder="First Name" value="" required>
                                        </div>
                                        <div class="form-group">
                                            <input type="text" name="last_name" tabindex="1" class="form-control" placeholder="Last Name" value="" required>
                                        </div>
                                        <div class="form-group">
                                            <input type="email" name="email" id="email" tabindex="1" class="form-control" placeholder="Email Address" value="" required>
                                        </div>
                                        <div class="form-group">
                                            <input type="password" name="password" id="password1" tabindex="2" class="form-control" placeholder="Password" required>
                                        </div>
                                        <div class="form-group">
                                            <input type="password" name="confirm-password" id="confirm-password1" tabindex="2" class="form-control" placeholder="Confirm Password" required>
                                        </div>
                                        <div class="form-group">
                                            <div class="row">
                                                <div class="col-sm-6 col-sm-offset-3">
                                                    <input type="submit" name="register-submit" id="register-submit" tabindex="4" class="form-control btn btn-register" value="Register Now">
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                    <form id="forgot-form" action="process_reset.php" method="post" role="form" style="display: none;">
                                        <div class='text-center'>
                                            <p>Please enter the email address you used to sign up.  A link with instructions to reset your password will be emailed to you.</p>
                                        </div>
                                        <div class="form-group">
                                            <input type="email" name="email" id="email" tabindex="1" class="form-control" placeholder="Email" value="" required>
                                        </div>
                                        <div class="form-group">
                                            <div class="row">
                                                <div class="col-sm-6 col-sm-offset-3">
                                                    <input type="submit" name="forgot-submit" id="forgot-submit" tabindex="4" class="form-control btn btn-forgot" value="Submit">
                                                </div>
                                            </div>
                                        </div>
                                    </form>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 col-md-offset-3 text-center">
                Need assistance?  <a href='mailto:SoftwareDevelopment@XXXXXX.com?subject=auth.XXXXXX.com%20assistance'>SoftwareDevelopment@XXXXXX.com</a>
                </div>
                </div>
        </div>
        <script   src="/js/jquery-3.1.1.min.js"   integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8="   crossorigin="anonymous"></script>
        <script src="/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
        <script type="text/javascript" src="js/login.js"></script>
        <?php
            if (!empty($_REQUEST["p"]) && $_REQUEST["p"]=="r") {
            }
        ?>
    </body>
</html>
