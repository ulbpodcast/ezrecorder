<?php

/*
 * EZCAST EZrecorder
 *
 * Copyright (C) 2014 Université libre de Bruxelles
 *
 * Written by Michel Jansens <mjansens@ulb.ac.be>
 * 	      Arnaud Wijns <awijns@ulb.ac.be>
 *            Antoine Dewilde
 * UI Design by Julien Di Pietrantonio
 *
 * This software is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * This software is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this software; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

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
require_once 'config.inc';
require_once 'lib_capture.php';

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

    $movie_count = trim(system("ls -la $ffmpeg_recorddir/ffmpeg_hls/ | grep $ffmpeg_movie_name | wc -l"));
    $files = glob("$ffmpeg_recorddir/ffmpeg_hls/${ffmpeg_movie_name}_" . ($movie_count - 1) . "/high/$ffmpeg_movie_name*.ts");
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
    $now = (int) microtime(true);

    if (($now - $last_modif) > $recovery_threshold) {
        capture_ffmpeg_recstatus_set('stopped');

        system("$ffmpeg_basedir/bash/ffmpeg_relaunch $ffmpeg_input_source; wait");    

        mail($mailto_admins, 'FFMPEG crash', 'FFMPEG crashed in room ' . $classroom . '. Recording will resume, but rendering will probably fail.');

        $movie_count = trim(system("ls -la $ffmpeg_recorddir/ffmpeg_hls/ | grep $ffmpeg_movie_name | wc -l"));
        $files = glob("$ffmpeg_recorddir/ffmpeg_hls/${ffmpeg_movie_name}_" . ($movie_count - 1) . "/high/$ffmpeg_movie_name*.ts");
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
?>
