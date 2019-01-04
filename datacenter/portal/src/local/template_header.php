<!DOCTYPE html>
<html lang="en"><!-- <?= @file_get_contents($_SERVER["DOCUMENT_ROOT"] . "/build_number.txt"); ?>  -->
<head>
    <title>XXXXXX PORTAL</title>
    <!-- Latest compiled and minified CSS -->
    <link rel="icon" type="image/x-icon" href="/css/favicon.ico" />
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <!-- Optional theme -->
    <link rel="stylesheet" href="/css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="/css/portal.css">
    <script type="text/javascript" src="/js/jquery-3.3.1.min.js"></script>
    <script type="text/javascript" src="/js/bootstrap.min.js"></script>
</head>
<body>
<?php
if (!empty($_SESSION["email"])) {
    echo "
<!-- Modal -->
<script type='text/javascript' src='/js/authTimeout.js'></script>
<div class='modal fade' id='myModal' tabindex='-1' role='dialog' aria-hidden=true aria-labelledby='myModalLabel' data-backdrop='static'>
    <div class='modal-dialog' role='document'>
        <div class='modal-content'>
            <div class='modal-header'>
                <h4 class='modal-title' id='myModalLabel'>Idle Timeout</h4>
            </div>
            <div class='modal-body'>
                Your session has been idle and will expire in <span id='countdownSeconds'>10</span> seconds.
            </div>
            <div class='modal-footer'>
                <button type='button' id='btnKeepAlive' class='btn btn-default' data-dismiss='modal'>Keep My Session Alive</button>
            </div>
        </div>
    </div>
</div>
<!-- End Modal -->\n";
}
?>
<nav class="navbar navbar-default">
  <div class="container-fluid" style='margin: 0px 0px 0px 0px; padding: 0px 0px 0px 0px'>
      <!-- Brand and toggle get grouped for better mobile display -->
      <div class="navbar-header">
        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        </button>

        
        <a href="/"><img src='/img/portal_header_dlr_bg.png' border='0' style='margin: 0px 0px 0px 0px; padding: 0px 0px 0px 0px; height: 80px;'></a>
        </div>

        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1" style='color: #FFFFFF; <?= ($_SERVER["ENVIRONMENT"] != "prod" ? "background: url(/img/" . $_SERVER["ENVIRONMENT"] . "_notice.png) center no-repeat #0a2a3b;" : "background-color: #0a2a3b;") ?>'>
        <ul class="nav navbar-nav nav-links">
            <li><a href="/dashboard/">Dashboard<span class="sr-only">(current)</span></a></li>
            <li><a href="/fiberaccess/">Fiber Access</a></li>
        </ul>
        <?php
            if (!empty($msg)) {
                echo "<p class='text-center' style='margin: 0px;'>$msg</p>";
            }
            if (!empty($_SESSION["email"])) {
                ?>
                <ul class="nav navbar-nav navbar-right nav-links" style='margin: 20px'>
                    <li class="dropdown">
                        <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <img src='/img/person_icon_blue_small.png' style='margin: 2px'>
                        <?= $_SESSION["firstName"] . " " . $_SESSION["lastName"]; ?>
                        <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
                            <?php
                            if (!empty($_SESSION["company"]) && ($_SESSION["company"] == "XXXXXX" || $_SESSION["company"] == "XXXXXY")) {
                                echo "<li><a href='https://" . (!empty($_SERVER["NONPROD_PREFIX"]) ? $_SERVER["NONPROD_PREFIX"] : "") . "inside.XXXXXX.com/'>XXXXXX Inside</a></li>";
                                if (in_array($_SESSION["email"], ADMINARRAY)) {
                                    echo "<li><a href='/cache/'>Memcache</a></li>";
                                }
                            }
                            ?>
                            <li><a href='https://<?= (!empty($_SERVER["NONPROD_PREFIX"]) ? $_SERVER["NONPROD_PREFIX"] : "") . "auth.XXXXXX.com/logout.php"; ?>'>logout</a></li>
                        </ul>
                    </li>
                </ul>
                <?php

            }

        ?>
        </div><!-- /.navbar-collapse -->
    </div><!-- /.container-fluid -->
</nav>

<div class="row" style='margin-left: 0px; margin-right: 0px;'>
    <div class="col-md-6">
        <?php
            echo authMaintenanceNotice();
        ?>
    </div>
    <div class="col-md-6">
        <button id='navbar-toggle-secondary' type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-ex1-collapse-1" aria-expanded="false">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        </button>
    </div>
</div>
<div class="row" style='margin-left: 0px; margin-right: 0px;'>
    <div class="col-md-12 site-body">
