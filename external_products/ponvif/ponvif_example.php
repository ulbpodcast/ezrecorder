<?php

if($argc != 4) {
    echo "Usage: ponvif_example.php <ip:port> <user> <password>\n";
    echo "Example: ponvif_example.php 10.0.2.253:1018 admin password\n";
    return;
}

date_default_timezone_set("Europe/Brussels");

require 'lib/class.ponvif.php';
$test=new ponvif();
$test->setIPAddress($argv[1]);
$test->setUsername($argv[2]);
$test->setPassword($argv[3]);
try {
        $test->initialize();
        if ($test->isFault($sources=$test->getSources())) die("Error getting sources");
        $profileToken=$sources[0][0]['profiletoken'];
        $ptzNodeToken=$sources[0][0]['ptz']['nodetoken'];
        $mediaUri=$test->media_GetStreamUri($profileToken);
        echo $mediaUri."\n";
} catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
}

?>