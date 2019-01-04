<?php

require_once("/usr/share/php/libphp-phpmailer/PHPMailerAutoload.php");

function dmail ($to, $subject, $message, $headersOrCcArray = null, $bccArray = null, $attachmentPathArray = null, $ical = null) :bool {
    //error_log("dmail(ical: $ical)");
    if (empty($_SERVER["ENVIRONMENT"])) {
        $_SERVER["ENVIRONMENT"] = "dev";
    }
    if (empty($_SERVER["SERVER_ADMIN"])) {
        $_SERVER["SERVER_ADMIN"] = "SoftwareDevelopment@xxxxxx.com";
    }

    //error_log("dmail with to = $to");
    //error_log("dmail with subject = $subject");
    //error_log("dmail with message = $message");
    $subdomain = substr($_SERVER["HTTP_HOST"], 0, strpos($_SERVER["HTTP_HOST"], "."));
    $fromAddress = "$subdomain@xxxxxx.com";
    $fromName = "XXXXXX " . ucfirst($subdomain);
    $replyAddress = $_SERVER['SERVER_ADMIN'];
    $replyName = 'XXXXXX Software Development';

    if (!empty($to) && is_array($to)) {
        $to = implode(", ", $to); // make sure $to is a string
    }

    $mail = new PHPMailer;
    $mail->isSMTP();
    $mail->Host = 'XXXXXX-com.mail.protection.outlook.com';
    $mail->SMTPAuth = false;
    $mail->Port = 25;
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $message;
    $mail->AltBody = strip_tags($message);

    $ccArray = array();
    if (!is_null($headersOrCcArray) && !empty($headersOrCcArray) && !is_array($headersOrCcArray)) {
        // assume conventional php mail() headers parameter, attempt to parse out from, replyTo, cc, bcc, etc.
        parseConventionalMailHeaders($mail, $headersOrCcArray, $fromAddress, $fromName, $replyAddress, $replyName, $ccArray, $bccArray);
    }
    else {
        $bccArray = array();
    }

    $mail->setFrom($fromAddress, $fromName);
    $mail->addReplyTo($replyAddress, $replyName);

    if ($_SERVER["ENVIRONMENT"] == "prod") {
        if (!empty($to)) {
            foreach ($mail->parseAddresses($to) as $address) {
                $mail->addAddress($address['address'], $address['name']);
            }
        }
        if (!is_null($ccArray) && is_array($ccArray)) {
            for ($x = 0; $x < count($ccArray); $x++) {
                $mail->addCC($ccArray[$x]);
            }
        }
        if (!is_null($bccArray) && is_array($bccArray)) {
            for ($x = 0; $x < count($bccArray); $x++) {
                $mail->addBCC($bccArray[$x]);
            }
        }
    }
    else {
        if ($_SERVER["ENVIRONMENT"] == "test") {
            $mail->addAddress("SoftwareDevelopment@XXXXXY.com");
            //error_log("test?" . $_SERVER['SERVER_ADMIN']);
        }
        else {
            $mail->addAddress($_SERVER['SERVER_ADMIN']);
            //error_log("dev?" . $_SERVER['SERVER_ADMIN']);
        }
        $message = $_SERVER["ENVIRONMENT"] . " email; on prod, would have sent with the following:<br>\n"
            . "To: $to<br>\n"
            . "From: $fromName &lt;$fromAddress&gt;<br>\n"
            . "Reply-To: $replyName &lt;$replyAddress&gt;<br>\n"
            . "CC: " . implode(",", $ccArray) . "<br>\n"
            . "BCC: " . implode(",", $bccArray) . "<br><br>\n(body)<br>\n"
            . $message; 
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = strip_tags($message);
    }
    if (!is_null($attachmentPathArray) && is_array($attachmentPathArray)) {
        for ($x = 0; $x < count($attachmentPathArray); $x++) {
            $mail->addAttachment($attachmentPathArray[$x]);
        }
    }
    if (!is_null($ical)) {
        $mail->addStringAttachment($ical,'ical.ics','base64','text/calendar');
        //error_log("addStringAttachment");
        //error_log($ical);
    }

    //error_log('attempting to send');
    if(!$mail->send()) {
        error_log('Mailer Error. Message could not be sent: ' . $mail->ErrorInfo);
        return false;
    }
    return true;
}

function parseConventionalMailHeaders($mailObj, $headers, & $fromAddress, & $fromName, & $replyAddress, & $replyName, 
        & $ccArray, & $bccArray) {
    $ccArray = $bccArray = array();
    $headerArray = preg_split('~([\w-]+): ~',$headers,-1,PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
    for ($x = 0; $x < count($headerArray); $x+=2) {
        switch(strtolower($headerArray[$x])) {
            case "from" :
                $fromArray = $mailObj->parseAddresses($headerArray[$x+1]);
                $fromAddress = $fromArray[0]["address"];
                $fromName = $fromArray[0]["name"];
            break;
            case "cc" :
                $ccArray = getAddressOnlyArray($mailObj, $headerArray[$x+1]);
            break;
            case "bcc" :
                $bccArray = getAddressOnlyArray($mailObj, $headerArray[$x+1]);
            break;
            case "reply-to" :
                $replyArray = $mailObj->parseAddresses($headerArray[$x+1]);
                $replyAddress = $replyArray[0]["address"];
                $replyName = $replyArray[0]["name"];
            break;
            case "x-priority" :
                $mailObj->Priority = trim($headerArray[$x+1]);
            break;
            default :
                $mailObj->AddCustomHeader($headerArray[$x] . ": " . trim($headerArray[$x+1]));
            break;
        }
    }
}

function getAddressOnlyArray($mailObj, $addressString) {
    $retval = array();
    foreach ($mailObj->parseAddresses($addressString) as $addressArray) {
        $retval[] = $addressArray['address'];
    }
    return $retval;
}

?>
