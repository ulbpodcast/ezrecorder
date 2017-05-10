<?php

/**
Stops the recording
Improvmenet idea: Maybe delay the stop? Will that cause possible problems with consecutives records?
*/

require_once __DIR__."/../etc/config.inc";
require_once __DIR__ . '/../../../global_config.inc';
require_once "$basedir/lib_ffmpeg.php";

Logger::$print_logs = true;
$log_context = basename(__FILE__, '.php');

if ($argc != 3) {
    echo 'Usage: php init.php <asset> <working_dir>' . PHP_EOL;
    $logger->log(EventType::RECORDER_SOUND_BACKUP, LogLevel::WARNING, "Called with wrong arg count", array($log_context));
    exit(1);
}

$asset = $argv[1];
$working_dir = $argv[2];

#stop record if it's already running
if(file_exists($sound_backup_pid_file))
    stop_ffmpeg($sound_backup_pid_file); 
      

$return_val = 0;
$cmd = "$ffmpeg_cli_cmd -f avfoundation -i \":$avfoundation_audio_interface\" -acodec libfdk_aac -ac 1 $working_dir/$backup_filename";
system($cmd, $return_val);
if($return_val != 0) {
    $logger->log(EventType::RECORDER_SOUND_BACKUP, LogLevel::ERROR, "Failed to start ffmpeg command $cmd", array($log_context));
    exit(2);
}

$logger->log(EventType::RECORDER_SOUND_BACKUP, LogLevel::NOTICE, "Started ffmpeg backup sound", array($log_context));

exit(0);