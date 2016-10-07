<?php

/**
Stops the recording and start post processing
*/

require_once __DIR__."/../config.inc";
//require_once __DIR__."/../../lib_capture.php";
require_once __DIR__."/../../../../lib_various.php"; 
require_once __DIR__."/../../../../global_config.inc"; 
require_once __DIR__."/../../info.php";
require_once __DIR__."/../../../../lib_ffmpeg.php";

Logger::$print_logs = true;
$log_context = basename(__FILE__, '.php');

if ($argc != 3) {
    echo 'Usage: php ffmpeg_stop.php <asset> <videofile_name>' . PHP_EOL;
    $logger->log(EventType::RECORDER_FFMPEG_STOP, LogLevel::WARNING, __FILE__ . " called with wrong arg count", array($log_context));
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
    $logger->log(EventType::RECORDER_FFMPEG_STOP, LogLevel::CRITICAL, 
            "Could not find asset dir $asset_dir", array($log_context));
    exit(2);
}
    
file_put_contents($process_result_file, "2"); //error by default in result file

#stop monitoring
if(file_exists($remoteffmpeg_monitoring_file))
    unlink($remoteffmpeg_monitoring_file);

#stop recording 
stop_ffmpeg($remoteffmpeg_pid_file);
        
$cmd = "/usr/bin/nice -n 10 $php_cli_cmd $ffmpeg_script_merge_movies $process_dir $ffmpeg_movie_name $video_file_name $cutlist_file $asset >> $process_dir/merge_movies.log 2>&1";
$return_val = 0;
system($cmd, $return_val);
if($return_val != 0) {
    $logger->log(EventType::RECORDER_FFMPEG_STOP, LogLevel::WARNING, 
            "Call to merge movies failed, error code: $return_val. Check merge_movies.log. Cmd: $cmd", array($log_context));
    file_put_contents($process_result_file, $return_val); 
}

if($return_val != 0) {
    $logger->log(EventType::RECORDER_FFMPEG_STOP, LogLevel::ERROR, 
            "Parts merge succeeded but cutting failed. This is BAD, but let's try to continue with what we got.", array($log_context));
    //at this point, merge movie script can still have produced a slide.mov file (if cut failed but merge succeeded)
}

if(!file_exists("$process_dir/$video_file_name")) {
    $logger->log(EventType::RECORDER_FFMPEG_STOP, LogLevel::CRITICAL, 
            "No output $process_dir/$video_file_name file found, aborting", array($log_context));
    exit(1);
}

$logger->log(EventType::RECORDER_FFMPEG_STOP, LogLevel::DEBUG, 
        "ffmpeg_stop finished movie merging", array($log_context));

//move resulting cam file to asset folder (from ffmpeg folder)
rename("$process_dir/$video_file_name", "$asset_dir/$video_file_name");

#all okay, write success
file_put_contents($process_result_file, "0"); 

$logger->log(EventType::RECORDER_FFMPEG_STOP, LogLevel::INFO, 
        "ffmpeg_stop finished successfully", array($log_context));

exit(0);

// ######################################################
// ######################################################
// ######################################################

function stop_ffmpeg($pid_file) {
    global $logger;
    global $log_context;
    
    if(file_exists($pid_file)) {
        $pid = file_get_contents($pid_file);
        unlink($pid_file);
        $return_val = 0;
        system("kill -2 $pid", $return_val);
        if($return_val != 0) {
            $logger->log(EventType::RECORDER_FFMPEG_STOP, LogLevel::ERROR, 
                    "Could not kill FFMPEG process (pid $pid)", array($log_context));
            return false;
        }
        //wait until process closed, or $kill_timeout passed
        $kill_timeout = 10;
        $start = time();
        while(true) {
            $now = time();
            if(($now - $kill_timeout) > $start) {
                system("kill -9 $pid", $return_val);
                break;
            }
            if(!is_process_running($pid))
                break;
        }
    }
    return true;
}
