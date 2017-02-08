<?php

require_once(__DIR__."/../../lib_install.php");

echo PHP_EOL . "******************************************" . PHP_EOL;
echo           "* Installation of av_sound_detect module *" . PHP_EOL;
echo           "******************************************" . PHP_EOL;

$config = file_get_contents(__DIR__ ."/config_sample.inc");


$value = read_line("Use given avinterface to detect sound level [default: '$vu_meter_avfoundation_index']: ");
if ($value != "")
    $vu_meter_avfoundation_index = $value;
//else keep default
    
$config = preg_replace('/\$vu_meter_avfoundation_index = (.+);/', '\$vu_meter_avfoundation_index = "' . $vu_meter_avfoundation_index . '";', $config);

echo PHP_EOL . "Creating 'config.inc'...";
file_put_contents(__DIR__."/config.inc", $config);

echo " OK" . PHP_EOL;