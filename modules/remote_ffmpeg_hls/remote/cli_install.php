<?php

#This file should be called automatically at each remote_ffmpeg_hls init, no need to execute it yourself

if($argc != 2)
    exit(1);

require_once '../../../global_config.inc';
require_once 'config_sample.inc';

$remoteffmpeg_recorddir = $argv[1];

//create config.inc
$config = file_get_contents(dirname(__FILE__) . "/config_sample.inc");
$config = preg_replace('/\$remoteffmpeg_recorddir = (.+);/', '\$remoteffmpeg_recorddir = "' . $remoteffmpeg_recorddir . '";', $config);
$res = file_put_contents("$remoteffmpeg_basedir/config.inc", $config);
if(!$res)
    exit(2);

//create work folder
if (!is_dir($remoteffmpeg_recorddir . '/ffmpeg_hls')) {
    $res = mkdir($remoteffmpeg_recorddir . '/ffmpeg_hls', 0755, true);
    if(!$res)
        exit(3);
}

//create bash config file
$bash_file = file_get_contents("$remoteffmpeg_basedir/bash/localdefs_sample");
$bash_file = str_replace("!PATH", $remoteffmpeg_basedir, $bash_file);
$bash_file = str_replace("!RECORD_PATH", $remoteffmpeg_recorddir, $bash_file);
$bash_file = str_replace("!MOVIES_PATH", $remoteffmpeg_moviesdir, $bash_file);
$bash_file = str_replace("!CLASSROOM", $classroom, $bash_file);
$bash_file = str_replace("!MAIL_TO", $mailto_admins, $bash_file);
$bash_file = str_replace("!INPUT_SOURCE", $remoteffmpeg_input_source, $bash_file);
$bash_file = str_replace("!PHP_PATH", $php_cli_cmd, $bash_file);
$bash_file = str_replace("!FFMPEG_PATH", $ffmpeg_cli_cmd, $bash_file);
$res = file_put_contents("$remoteffmpeg_basedir/bash/localdefs", $bash_file);
if(!$res)
    exit(4);

system("chmod -R 755 $remoteffmpeg_local_basedir/bash");

