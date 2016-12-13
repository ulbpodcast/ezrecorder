<?php

require_once dirname(__FILE__) . '/config_sample.inc';
require_once(__DIR__."/../../lib_various.php");

echo PHP_EOL;
echo "***********************************************" . PHP_EOL;
echo "* Installation of remote_ffmpeg_hls module    *" . PHP_EOL;
echo "***********************************************" . PHP_EOL;

echo PHP_EOL . "Creating config.inc" . PHP_EOL;

echo "Please, enter now the requested values:" . PHP_EOL;
//BUG?? la ligne de prompt apparait pas
$value = read_line("Path to EZrecorder basedir on the remote recorder [default: '$remoteffmpeg_basedir']:");
if ($value != "")
    $remoteffmpeg_basedir = $value; 

$value = read_line("Path to EZrecorder repository basedir on the remote recorder [default: '$remoteffmpeg_recorddir']: "); //should be removed if possible
if ($value != "")
    $remoteffmpeg_recorddir = $value; 

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
echo "Enter sudo password for executing setperms.sh ." . PHP_EOL;
system("sudo $remoteffmpeg_local_basedir/setperms.sh");


if(!ssh_ping($remote_recorder_username, $remote_recorder_ip)) {
    echo "\033[31mCannot ssh into remote recorder $remote_recorder_ip from this recorder. \033[0mCheck if our public key is authorized on it, or correct remote IP in global_config.inc if necessary." . PHP_EOL;
} else {
    echo "Remote recorder $remote_recorder_ip ping okay" . PHP_EOL;
}
        
echo "Finished." . PHP_EOL;
