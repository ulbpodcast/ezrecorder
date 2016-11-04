<?php

require_once dirname(__FILE__) . '/config_sample.inc';
require_once(__DIR__."/../../lib_various.php");

echo PHP_EOL;
echo "***************************************" . PHP_EOL;
echo "* Installation of remote_ffmpeg_hls module    *" . PHP_EOL;
echo "***************************************" . PHP_EOL;

echo PHP_EOL . "Creating config.inc" . PHP_EOL;

echo "Please, enter now the requested values :" . PHP_EOL;
$value = read_line("Path to EZrecorder basedir on the remote recorder [default: '$remoteffmpeg_basedir']: ");
if ($value != "")
    $remoteffmpeg_basedir = $value; unset($value);
$value = read_line("Path to EZrecorder repository basedir on the remote recorder [default: '$remoteffmpeg_recorddir']: "); //should be removed if possible
if ($value != "")
    $remoteffmpeg_recorddir = $value; unset($value);

$config = file_get_contents("$remoteffmpeg_local_basedir/config_sample.inc");

$config = preg_replace('/\$remoteffmpeg_basedir = (.+);/', '\$remoteffmpeg_basedir = "' . $remoteffmpeg_basedir . '";', $config);
$config = preg_replace('/\$remoteffmpeg_recorddir = (.+);/', '\$remoteffmpeg_recorddir = "' . $remoteffmpeg_recorddir . '";', $config);
file_put_contents("$remoteffmpeg_local_basedir/config.inc", $config);

echo PHP_EOL . "Changing values in setperms.sh" . PHP_EOL;

$perms_file = file_get_contents("$remoteffmpeg_local_basedir/setperms_sample.sh");
$perms_file = str_replace("!USER", $ezrecorder_username, $perms_file);
$perms_file = str_replace("!WEB_USER", $ezrecorder_web_user, $perms_file);
file_put_contents("$remoteffmpeg_local_basedir/setperms.sh", $perms_file);

system("chmod -R 755 $remoteffmpeg_local_basedir/bash");
chmod("$remoteffmpeg_local_basedir/setperms.sh", 0755);
echo "Enter sudo password for executing setperms.sh .";
system("sudo $remoteffmpeg_local_basedir/setperms.sh");


echo "Double make sure this host can ssh into the remote host, as well as to run the install of the remote module on the remote host" . PHP_EOL;
echo "Finished." . PHP_EOL;
