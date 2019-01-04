<?php

function encryptPassword(string $rawPass) : string {
    // all logic redacted
    return $retval;
}

function checkPassword(string $rawPass, string $dbPassEncrypted) : bool {
    if (!empty($rawPass) && !empty($dbPassEncrypted)) {
        return (/* redacted */);
    }
    return false;
}

?>
