<?php


require_once 'config_sample.inc';
require_once(__DIR__."/../../lib_various.php");

echo PHP_EOL . "**********************************************" . PHP_EOL;
echo "* Installation of Axis_cam_management module *" . PHP_EOL;
echo "**********************************************" . PHP_EOL;

echo PHP_EOL . "Creating config.inc" . PHP_EOL;

echo "Please, enter now the requested values :" . PHP_EOL;
$value = read_line("Static IP address for Axis Camera [default: '$axiscam_ip']: ");
if ($value != "")
    $axiscam_ip = $value; unset($value);
$value = read_line("Username for Axis camera [default: '$axiscam_username']: ");
if ($value != "")
    $axiscam_username = $value; unset($value);
$value = read_line("Password for Axis Camera [default: '$axiscam_password']: ");
if ($value != "")
    $axiscam_password = $value; unset($value);


$config = file_get_contents("$modules_basedir/axis_cam_management/config_sample.inc");

$config = preg_replace('/\$axiscam_ip = (.+);/', '\$axiscam_ip = "' . $axiscam_ip . '";', $config);
$config = preg_replace('/\$axiscam_username = (.+);/', '\$axiscam_username = "' . $axiscam_username . '";', $config);
$config = preg_replace('/\$axiscam_password = (.+);/', '\$axiscam_password = "' . $axiscam_password . '";', $config);
file_put_contents("$modules_basedir/axis_cam_management/config.inc", $config);

system("mv $web_basedir/ptzposdir $web_basedir/ptzposdir_old");
system("cp -rp $modules_basedir/axis_cam_management/ptzposdir $web_basedir/ptzposdir");
