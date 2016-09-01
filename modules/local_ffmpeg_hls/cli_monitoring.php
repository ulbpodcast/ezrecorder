<?php

/**
 *  This CLI script performs various monitoring tasks. It is started when the user starts a recording, and stopped when they stop recording.
 * This script is called by qtbstart and qtbstop.
 * Current checks performed:
 * - timeout check (checks whether the user has forgotten to stop recording, and publish the recording if they did)
 * - recovery check (checks whether QTB has crashed, and restart it if it hs)
 */
/**
 * Timeout check:
 * For the first "threshold" seconds (typically 2 or 3 hours), we decide to trust the user.
 * After that, we check that there has been activity at least once every "timeout" seconds (typically 15 min).
 * This program is meant to be run as a crontask at least once every "timeout" seconds
 */
require_once 'etc/config.inc';
require_once '../../global_config.inc';
require_once 'lib_capture.php';

if ($argc !== 2) {
    print "Usage:  " . $argv[0] . " <ffmpeg_working_dir>" . PHP_EOL;
    print "<ffmpeg_working_dir> Working directory for this module. This directory should contain the 'ffmpegmovie_*' folders." . PHP_EOL;
    exit(1);
}

$working_dir = $argv[1];

// Delays, in seconds
$recovery_threshold = 20; // Threshold before we start worrying about QTB
$sleep_time = 20; // Duration of the sleep between two checks
$pid = getmypid();

set_time_limit(0);
fwrite(fopen($ffmpeg_monitoring_file, 'w'), $pid);

// This is the main loop. Runs until the lock file disappears
while (true) {

    // We stop if the file does not exist anymore ("kill -9" simulation)
    // or the file contains an other pid
    // or the status is not set (should be open / recording / paused / stopped)
    if (!file_exists($ffmpeg_monitoring_file) || $pid != file_get_contents($ffmpeg_monitoring_file) || capture_ffmpeg_status_get() == '') {
        die;
    }

    clearstatcache();

    $movie_count = trim(system("ls -la $working_dir/ | grep $ffmpeg_movie_name | wc -l"));
    $files = glob("$working_dir/${ffmpeg_movie_name}_" . ($movie_count - 1) . "/high/$ffmpeg_movie_name*.ts");
    $status = capture_ffmpeg_recstatus_get();
    if ($status == '')
        capture_ffmpeg_recstatus_set('recording');

    // Checking when was the last modif
    // (remember: QTB-FFMPEG uses several fmlemovie files)
    $last_modif = 0;
    foreach ($files as $file) {
        $last_modif = max($last_modif, filemtime($file));
    }

    // Compares with current microtime
    $now = time();

    if (($now - $last_modif) > $recovery_threshold) {
        capture_ffmpeg_recstatus_set('stopped');
        
        $log_file = "$working_dir/relaunch.log";
        system("$ffmpeg_basedir/bash/ffmpeg_relaunch $working_dir $ffmpeg_input_source >> $log_file 2>&1; wait");    

        mail($mailto_admins, 'FFMPEG crash', 'FFMPEG crashed in room ' . $classroom . '. Recording will resume, but rendering will probably fail.');

        $movie_count = trim(system("ls -la $working_dir/ | grep $ffmpeg_movie_name | wc -l"));
        $files = glob("$working_dir/${ffmpeg_movie_name}_" . ($movie_count - 1) . "/high/$ffmpeg_movie_name*.ts");
        foreach ($files as $file) {
            $last_modif = max($last_modif, filemtime($file));
        }
        $now = (int) microtime(true);
        if (($now - $last_modif) <= $recovery_threshold) {
            capture_ffmpeg_recstatus_set('recording');
        }
    } else if ($status == 'stopped') {
        capture_ffmpeg_recstatus_set('recording');
    }

    sleep($sleep_time);
}