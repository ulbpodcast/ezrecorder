<?php

require_once 'config_sample.inc';
require_once(__DIR__."/../../lib_install.php");
require_once(__DIR__."/../../global_config.inc");

echo PHP_EOL;
echo "***********************************************" . PHP_EOL;
echo "* Installation of ptzoptics_cam_management module *" . PHP_EOL;
echo "***********************************************" . PHP_EOL;

echo PHP_EOL . "Creating config.inc" . PHP_EOL;

echo "Please, enter now the requested values :" . PHP_EOL;
$value = read_line("Static IP address for PTZOptics Camera [default: '$cam_ip']: ");
if ($value != "")
    $onvifcam_ip = $value; 
unset($value);

$config = file_get_contents("$modules_basedir/ptzoptics_cam_management/config_sample.inc");

$config = preg_replace('/\$cam_ip = (.+);/', '\$cam_ip = "' . $cam_ip . '";', $config);
file_put_contents("$modules_basedir/ptzoptics_cam_management/config.inc", $config);

//echo "Note that you can create camera views with cli_* scripts in folder " . __DIR__ . PHP_EOL;