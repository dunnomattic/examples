<?php
require_once('password.php');

if ($argc == 2) {
    echo "encrypting password: " . $argv[1] . "\n";
    $pass = $argv[1];
    $enc_pass = encryptPassword($pass);
    echo  "$enc_pass\n";
    echo "decrypted pass: " . checkPassword($pass, $enc_pass) . "\n";
}
else {
    echo "missing param 1 [password]\n";
}


?>
