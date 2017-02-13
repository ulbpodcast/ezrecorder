<?php

$conf_file = __DIR__."/config.inc";
if(file_exists($conf_file) && filesize($conf_file) > 0)
    require_once $conf_file;
else
    require_once __DIR__.'/config_sample.inc';

require_once(__DIR__."/../../lib_install.php");

echo PHP_EOL . "******************************************" . PHP_EOL;
echo           "* Installation of av_sound_detect module *" . PHP_EOL;
echo           "******************************************" . PHP_EOL;

$config = file_get_contents(__DIR__ ."/config_sample.inc");

$value = read_line("Remote username (ssh) [default: '$vu_meter_avfoundation_remote_username']: ");
if ($value != "")
    $vu_meter_avfoundation_remote_username = $value;
//else keep default

$value = read_line("Remote IP (ssh) [default: '$vu_meter_avfoundation_remote_ip']: ");
if ($value != "")
    $vu_meter_avfoundation_remote_ip = $value;
//else keep default
    
echo "Use given avinterface to detect sound level. You can list them with 'ffmpeg -f avfoundation -list_devices true -i \"\"'";
$value = read_line("[default: '$vu_meter_avfoundation_index']: ");
if ($value != "")
    $vu_meter_avfoundation_index = $value;
//else keep default

$config = preg_replace('/\$vu_meter_avfoundation_index = (.+);/', '\$vu_meter_avfoundation_index = "' . $vu_meter_avfoundation_index . '";', $config);
$config = preg_replace('/\$vu_meter_avfoundation_remote_username = (.+);/', '\$vu_meter_avfoundation_remote_username = "' . $vu_meter_avfoundation_remote_username . '";', $config);
$config = preg_replace('/\$vu_meter_avfoundation_remote_ip = (.+);/', '\$vu_meter_avfoundation_remote_ip = "' . $vu_meter_avfoundation_remote_ip . '";', $config);

echo PHP_EOL . "Creating 'config.inc'...";
file_put_contents(__DIR__."/config.inc", $config);

echo " OK" . PHP_EOL;