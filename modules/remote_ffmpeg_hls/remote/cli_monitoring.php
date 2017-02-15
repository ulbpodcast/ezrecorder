<?php

/**
 *  This CLI script performs various monitoring tasks. It is started when the user starts a recording, and stopped when they stop recording.
 * This script is called by ffmpeg_start and ffmpeg_stop.
 * Current checks performed:
 * - recovery check (checks whether ffmpeg has crashed, and restart it if it hs)
 */

require_once __DIR__.'/config.inc';
require_once __DIR__.'/lib_tools.php';

if ($argc !== 2) {
    print "Usage:  " . $argv[0] . " <remoteffmpeg_working_dir>" . PHP_EOL;
    print "<remoteffmpeg_working_dir> Working directory for this module. This directory should contain the 'ffmpegmovie_*' folders." . PHP_EOL;
    exit(1);
}

$working_dir = $argv[1];

// Delays, in seconds
$recovery_threshold = 20; // Threshold before we start worrying about FMLE
$sleep_time = 20; // Duration of the sleep between two checks
$pid = getmypid();

rec_status_set('');
set_time_limit(0);
fwrite(fopen($remoteffmpeg_monitoring_file, 'w'), $pid);

// This is the main loop. Runs until the lock file disappears
while (true) {
    
    // We stop if the file does not exist anymore ("kill -9" simulation)
    // or the file containsan other pid
    if (!file_exists($remoteffmpeg_monitoring_file) 
            || $pid != file_get_contents($remoteffmpeg_monitoring_file)) {
        die;
    }

    // or the status is not set (should be open / recording / paused / stopped)
    $status = status_get();
    if($status == '' || $status == 'launch_failure')
        die;
    
    // FMLE check
    clearstatcache();
    
    $movie_count = trim(system("ls -la $working_dir/ | grep $remoteffmpeg_movie_name | wc -l"));
    $files = glob("$working_dir/${remoteffmpeg_movie_name}_" . ($movie_count - 1) . "/high/$remoteffmpeg_movie_name*.ts");
    $status = rec_status_get();
    if ($status == '')
        rec_status_set('recording');

    // Checking when was the last modif
    // (remember: FMLE uses several movie files)
    $last_modif = 0;
    foreach ($files as $file) {
        $last_modif = max($last_modif, filemtime($file));
    }
    // Compares with current microtime
    $now = (int) microtime(true);

    if (($now - $last_modif) > $recovery_threshold) {
        rec_status_set('stopped');
        
        $log_file = "$working_dir/relaunch.log";
        system("$ffmpeg_basedir/bash/ffmpeg_relaunch $working_dir $ffmpeg_input_source >> $log_file 2>&1; wait");  

        mail($mailto_admins, 'FFMPEG crash', 'Remote FFMPEG crashed in room ' . $classroom . '. Recording will resume, but rendering will probably fail.');

        $movie_count = trim(system("ls -la $working_dir/ | grep $remoteffmpeg_movie_name | wc -l"));
        $files = glob("$working_dir/${remoteffmpeg_movie_name}_" . ($movie_count - 1) . "/high/$remoteffmpeg_movie_name*.ts");
        foreach ($files as $file) {
            $last_modif = max($last_modif, filemtime($file));
        }
        $now = (int) microtime(true);
        if (($now - $last_modif) <= $recovery_threshold) {
            rec_status_set('recording');
        }
    } else if ($status == 'stopped') {
        rec_status_set('recording');
    }

    sleep($sleep_time);
}


?>
