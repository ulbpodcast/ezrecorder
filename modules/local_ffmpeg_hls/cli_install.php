<?php

require_once 'etc/config_sample.inc';
require_once(__DIR__."/../../lib_various.php");

echo PHP_EOL . 
     "*******************************************" . PHP_EOL;
echo "* Installation of local_ffmpeg_hls module *" . PHP_EOL;
echo "*******************************************" . PHP_EOL;

echo "Creating etc/config.inc" . PHP_EOL;

$config = file_get_contents("$ffmpeg_basedir/etc/config_sample.inc");

echo "No configurable option yet, you must edit config.inc by hand" . PHP_EOL;
// todo: allow to customise options

file_put_contents("$ffmpeg_basedir/etc/config.inc", $config);

$perms_file = file_get_contents("$ffmpeg_basedir/setperms_sample.sh");
$perms_file = str_replace("!USER", $ezrecorder_username, $perms_file);
$perms_file = str_replace("!WEB_USER", $ezrecorder_web_user, $perms_file);
file_put_contents("$ffmpeg_basedir/setperms.sh", $perms_file);

system("chmod -R 755 $ffmpeg_basedir/bash");
chmod("$ffmpeg_basedir/setperms.sh", 0755);
echo "Enter sudo password for executing setperms.sh .";
system("sudo $ffmpeg_basedir/setperms.sh");

