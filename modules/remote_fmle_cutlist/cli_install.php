<?php

require_once __DIR__ . '/config_sample.inc';
require_once(__DIR__."/../../lib_install.php");

echo PHP_EOL . "***************************************" . PHP_EOL;
echo "* Installation of remote_fmle_cutlist module    *" . PHP_EOL;
echo "***************************************" . PHP_EOL;

echo PHP_EOL . "Creating config.inc" . PHP_EOL;

echo "Please, enter now the requested values :" . PHP_EOL;
$value = read_line("Static IP address of the remote recorder [default: '$remotefmle_ip']: ");
if ($value != "")
    $remotefmle_ip = $value; unset($value);
$value = read_line("Path to EZrecorder basedir on the remote recorder [default: '$remotefmle_basedir']: ");
if ($value != "")
    $remotefmle_basedir = $value; unset($value);
$value = read_line("Username on the remote recorder [default: '$remotefmle_username']: ");
if ($value != "")
    $remotefmle_username = $value; unset($value);
$value = read_line("Path to EZrecorder repository basedir on the remote recorder [default: '$remotefmle_recorddir']: ");
if ($value != "")
    $remotefmle_recorddir = $value; unset($value);

$config = file_get_contents("$remotefmle_local_basedir/config_sample.inc");

$config = preg_replace('/\$remotefmle_ip = (.+);/', '\$remotefmle_ip = "' . $remotefmle_ip . '";', $config);
$config = preg_replace('/\$remotefmle_basedir = (.+);/', '\$remotefmle_basedir = "' . $remotefmle_basedir . '";', $config);
$config = preg_replace('/\$remotefmle_recorddir = (.+);/', '\$remotefmle_recorddir = "' . $remotefmle_recorddir . '";', $config);
$config = preg_replace('/\$remotefmle_username = (.+);/', '\$remotefmle_username = "' . $remotefmle_username . '";', $config);
file_put_contents("$remotefmle_local_basedir/config.inc", $config);

echo PHP_EOL . "Changing values in setperms.sh" . PHP_EOL;

if (strtoupper(php_uname('s'))==='LINUX'){
	$perms_file = file_get_contents("$remotefmle_local_basedir/setperms_samplinux.sh");
}else{
	$perms_file = file_get_contents("$remotefmle_local_basedir/setperms_sample.sh");
}
$perms_file = str_replace("!USER", $ezrecorder_username, $perms_file);
$perms_file = str_replace("!WEB_USER", $ezrecorder_web_user, $perms_file);
file_put_contents("$remotefmle_local_basedir/setperms.sh", $perms_file);

system("chmod -R 755 $remotefmle_local_basedir/bash");
chmod("$remotefmle_local_basedir/setperms.sh", 0755);
echo "Enter sudo password for executing setperms.sh .";
system("sudo $remotefmle_local_basedir/setperms.sh");
