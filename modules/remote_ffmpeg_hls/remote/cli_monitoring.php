<?php

/**
 *  This CLI script performs various monitoring tasks. It is started when the user starts a recording, and stopped when they stop recording.
 * This script is called by ffmpeg_start and ffmpeg_stop.
 * Current checks performed:
 * - recovery check (checks whether ffmpeg has crashed, and restart it if it hs)
 */

require_once __DIR__.'/config.inc';
require_once __DIR__.'/lib_tools.php';
require_once __DIR__ . '/../../../global_config.inc';
require_once __DIR__.'/../lib_capture.php';
require_once __DIR__.'/common.inc';

Logger::$print_logs = true;

if ($argc !== 2) {
    print "Usage:  " . $argv[0] . " <asset>" . PHP_EOL;
    print "<asset> Full asset name" . PHP_EOL;
    exit(1);
}

$asset = $argv[1];

$working_dir = get_asset_module_folder($remoteffmpeg_module_name, $asset);
if($working_dir == false) {
    $logger->log(EventType::RECORDE_MODULE_MONIT, LogLevel::ERROR, "Could not find remoteffmpeg working dir for asset $asset", array(basename(__FILE__)), $asset);
    exit(2);
}

$logger->log(EventType::RECORDE_MODULE_MONIT, LogLevel::DEBUG, "Started remoteffmpeg module monit with working dir $working_dir", array(basename(__FILE__)), $asset);
       
// Delays, in seconds
$recovery_threshold = 20; // Threshold before we start worrying about FMLE
$sleep_time = 20; // Duration of the sleep between two checks
$pid = getmypid();

set_time_limit(0);
fwrite(fopen($remoteffmpeg_monitoring_file, 'w'), $pid);

// This is the main loop. Runs until the lock file disappears
while (true) {
    
    // We stop if the file does not exist anymore ("kill -9" simulation)
    // or the file containsan other pid
    if (!file_exists($remoteffmpeg_monitoring_file) 
            || $pid != file_get_contents($remoteffmpeg_monitoring_file)) {
        $logger->log(EventType::RECORDE_MODULE_MONIT, LogLevel::INFO, "Stopped monit, pid file was removed or missing", array(basename(__FILE__)), $asset);
        exit(0);
    }

    // or the status is not set (should be open / recording / paused / stopped)
    $status = status_get();
    if($status == '' || $status == 'launch_failure') {
        $logger->log(EventType::RECORDE_MODULE_MONIT, LogLevel::INFO, "Stopped monit, module is not recording anymore", array(basename(__FILE__)), $asset);
        exit(0);
    }
    
    // What this for?
    clearstatcache();
    
    $rec_status = rec_status_get();
    
    if (last_modif_file_too_old()) {
        $logger->log(EventType::RECORDE_MODULE_MONIT, LogLevel::ERROR, "Last video file is older than $recovery_threshold! Trying to relaunch.", array(basename(__FILE__), 'remote_ffmpeg_hls'), $asset);
        rec_status_set('stopped');
        
        $log_file = "$working_dir/relaunch.log";
        system( __DIR__ ."/bash/ffmpeg_relaunch $working_dir $ffmpeg_input_source >> $log_file 2>&1; wait");  

        mail($mailto_admins, 'FFMPEG crash', 'Remote FFMPEG crashed in room ' . $classroom . '. Recording will resume, but rendering will probably fail.');

        if (!last_modif_file_too_old()) {
            rec_status_set('recording');
        }
    } else if ($rec_status == 'stopped') {
        echo "Found recent videos files";
        rec_status_set('recording');
    }

    sleep($sleep_time);
}

function last_modif_file_too_old() {
    global $recovery_threshold;
    
    $last_modif = get_last_modif_time();
    $now = time();
    
    echo "Last modif: $last_modif". PHP_EOL;
    return ($now - $last_modif) > $recovery_threshold;
}

function get_last_modif_time() {
    global $remoteffmpeg_movie_name;
    global $working_dir;
    
    $last_modif = 0;
    
    //get last ffmpeg output directory
    $movie_count = trim(system("ls -la $working_dir/ | grep $remoteffmpeg_movie_name | wc -l"));
    $movie_dir = "$working_dir/$remoteffmpeg_movie_name"."_".($movie_count - 1) . "/high/";
    //echo "movie_dir = $movie_dir" . PHP_EOL;
    //get all videos files
    $files = glob("$movie_dir/$remoteffmpeg_movie_name*.ts");
    //var_dump($files);
    // Get the last modification time in all the video files
    foreach ($files as $file) {
        $last_modif = max($last_modif, filemtime($file));
    }
    return $last_modif;
}