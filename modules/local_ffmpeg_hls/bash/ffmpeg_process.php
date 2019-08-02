<?php

/**
Stops the recording and start post processing
*/

require_once __DIR__."/../etc/config.inc";
require_once __DIR__."/../lib_capture.php";
require_once "$basedir/lib_various.php";
require_once __DIR__."/../info.php";
require_once "$basedir/lib_ffmpeg.php";

Logger::$print_logs = true;
$log_context = basename(__FILE__, '.php');

if ($argc != 3) {
    echo 'Usage: php ffmpeg_process.php <asset> <videofile_name>' . PHP_EOL;
    $logger->log(EventType::RECORDER_FFMPEG_PROCESS, LogLevel::WARNING, basename(__FILE__) . " called with wrong arg count", array($log_context));
    exit(1);
}

$asset = $argv[1];
$video_file_name = $argv[2];

$process_dir = get_asset_module_folder($module_name, $asset);
$asset_dir = get_asset_dir($asset);
$pid_file = "$process_dir/process_pid";
$cutlist_file = ffmpeg_get_cutlist_file($module_name, $asset);
$process_result_file = "$process_dir/$process_result_filename";

if(!file_exists($asset_dir)) {
    $logger->log(EventType::RECORDER_FFMPEG_PROCESS, LogLevel::CRITICAL,
            "Could not find asset dir $asset_dir", array($log_context));
    exit(2);
}

file_put_contents($process_result_file, "2"); //error by default in result file

$cmd = "/usr/bin/nice -n 10 $php_cli_cmd $ffmpeg_script_merge_movies $process_dir $ffmpeg_movie_name $video_file_name $cutlist_file $asset >> $process_dir/merge_movies.log 2>&1";
echo $cmd;

$return_val = 0;
system($cmd, $return_val);
if($return_val != 0) {
    $logger->log(EventType::RECORDER_FFMPEG_PROCESS, LogLevel::WARNING,
            "Call to merge movies failed, error code: $return_val. Check merge_movies.log", array($log_context));
    file_put_contents($process_result_file, $return_val);
}

//if merge movies return 2, merge has been successfully but cutting failed. Let's continue with what we got.
if($return_val == 5) {
    $logger->log(EventType::RECORDER_FFMPEG_PROCESS, LogLevel::ERROR,
            "Parts merge succeeded but cutting failed. This is BAD, but let's continue with what we got.", array($log_context));
    //at this point, merge movie script should still have produced a cam.mov file
} else if($return_val != 0) {
    exit(1);
}

$logger->log(EventType::RECORDER_FFMPEG_PROCESS, LogLevel::DEBUG,
        "ffmpeg_process finished movie merging", array($log_context));

//move resulting cam file to asset folder (from ffmpeg folder)
rename("$process_dir/$video_file_name", "$asset_dir/$video_file_name");

#all okay, write success
file_put_contents($process_result_file, "0");

$logger->log(EventType::RECORDER_FFMPEG_PROCESS, LogLevel::INFO,
        "ffmpeg_process finished successfully", array($log_context));

exit(0);
