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
    echo 'Usage: php ffmpeg_stop.php <asset> <videofile_name>' . PHP_EOL;
    $logger->log(EventType::RECORDER_FFMPEG_STOP, LogLevel::WARNING, basename(__FILE__) . " called with wrong arg count", array($log_context));
    exit(1);
}

$asset = $argv[1];
$video_file_name = $argv[2];

#stop monitoring
if(file_exists($ffmpeg_monitoring_file))
    unlink($ffmpeg_monitoring_file);

#stop recording 
stop_ffmpeg($ffmpeg_pid_file);
stop_ffmpeg($ffmpeg_pid2_file); //low stream if any
      
$cmd = "$php_cli_cmd $ffmpeg_script_process $asset $video_file_name";
echo $cmd;

$return_val = 0;
system($cmd, $return_val);
if($return_val != 0) {
    $logger->log(EventType::RECORDER_FFMPEG_STOP, LogLevel::WARNING, 
            "Call to process script failed, error code: $return_val.", array($log_context));
    exit(3);
}

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
