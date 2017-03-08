<?php

require_once __DIR__ . '/config_sample.inc';
require_once(__DIR__."/../../lib_install.php");

echo PHP_EOL . "***************************************" . PHP_EOL;
echo "* Installation of remote_qtb module    *" . PHP_EOL;
echo "***************************************" . PHP_EOL;

echo PHP_EOL . "Creating config.inc" . PHP_EOL;

echo "Please, enter now the requested values :" . PHP_EOL;
$value = read_line("Static IP address of the remote recorder [default: '$remoteqtb_ip']: ");
if ($value != "")
    $remoteqtb_ip = $value; unset($value);
$value = read_line("Path to EZrecorder basedir on the remote recorder [default: '$remoteqtb_basedir']: ");
if ($value != "")
    $remoteqtb_basedir = $value; unset($value);
$value = read_line("Username on the remote recorder [default: '$remoteqtb_username']: ");
if ($value != "")
    $remoteqtb_username = $value; unset($value);
$value = read_line("Path to EZrecorder repository basedir on the remote recorder [default: '$remoteqtb_recorddir']: ");
if ($value != "")
    $remoteqtb_recorddir = $value; unset($value);

$config = file_get_contents("$modules_basedir/remote_qtb/config_sample.inc");

$config = preg_replace('/\$remoteqtb_ip = (.+);/', '\$remoteqtb_ip = "' . $remoteqtb_ip . '";', $config);
$config = preg_replace('/\$remoteqtb_basedir = (.+);/', '\$remoteqtb_basedir = "' . $remoteqtb_basedir . '";', $config);
$config = preg_replace('/\$remoteqtb_recorddir = (.+);/', '\$remoteqtb_recorddir = "' . $remoteqtb_recorddir . '";', $config);
$config = preg_replace('/\$remoteqtb_username = (.+);/', '\$remoteqtb_username = "' . $remoteqtb_username . '";', $config);
file_put_contents("$modules_basedir/remote_qtb/config.inc", $config);

echo PHP_EOL . "Changing values in setperms.sh" . PHP_EOL;

$perms_file = file_get_contents("$modules_basedir/remote_qtb/setperms_sample.sh");
$perms_file = str_replace("!USER", $ezrecorder_username, $perms_file);
$perms_file = str_replace("!WEB_USER", $ezrecorder_web_user, $perms_file);
file_put_contents("$modules_basedir/remote_qtb/setperms.sh", $perms_file);

system("chmod -R 755 $modules_basedir/remote_qtb/bash");
chmod("$modules_basedir/remote_qtb/setperms.sh", 0755);
echo "Enter sudo password for executing setperms.sh .";
system("sudo $modules_basedir/remote_qtb/setperms.sh");
