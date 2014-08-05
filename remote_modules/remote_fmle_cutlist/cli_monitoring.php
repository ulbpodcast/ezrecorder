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

// Delays, in seconds
$threshold_timeout = 7200; // Threshold before we start worrying about the user
$recovery_threshold = 20; // Threshold before we start worrying about QTB
$timeout = 900; // Timeout after which we consider a user has forgotten to stop their recording
//$timeout = 30;
$sleep_time = 20; // Duration of the sleep between two checks
$pid = getmygid();

rec_status_set('');
set_time_limit(0);
fwrite(fopen($remotefmle_monitoring_file, 'w'), $pid);

// This is the main loop. Runs until the lock file disappears
while (true) {
    
    // We stop if the file does not exist anymore ("kill -9" simulation)
    // or the file containsan other pid
    // or the status is not set (should be open / recording / paused / stopped)
    if (!file_exists($remotefmle_monitoring_file) 
            || $pid != file_get_contents($remotefmle_monitoring_file)
            || status_get() == '') {
        die;
    }

    // FMLE check
    clearstatcache();
    $files = glob("$remotefmle_recorddir/$remotefmle_movie_name*.f4v");
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
        system("osascript $remotefmle_open; wait;");
        system("osascript $remotefmle_action; wait");

        mail($mailto_admins, 'FMLE crash', 'Flash Media Live Encoder crashed in room ' . $classroom . '. Recording will resume, but rendering will probably fail.');


        $files = glob("$remotefmle_recorddir/$remotefmle_movie_name*.f4v");
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

    // Timeout check
    //*

    $startrec_time = starttime_get();
    $lastmod_time = lastmodtime_get();
    $now = time();

    if ($now - $startrec_time > $threshold_timeout && $now - $lastmod_time > $timeout) {
        mail($mailto_admins, 'Recording timed out', 'The recording in classroom ' . $classroom . ' was stopped and published in private album because there has been no user activity since ' . ($now - $lastmod_time) . ' seconds ago.');
        send_timeout();
    }

    //*/

    sleep($sleep_time);
}

function status_get() {
    global $remotefmle_status_file;

    if (!file_exists($remotefmle_status_file))
        return '';

    return trim(file_get_contents($remotefmle_status_file));
}

function rec_status_get() {
    global $remotefmle_rec_status_file;

    if (!file_exists($remotefmle_rec_status_file))
        return '';

    return trim(file_get_contents($remotefmle_rec_status_file));
}

function rec_status_set($status) {
    global $remotefmle_rec_status_file;
    global $remotefmle_last_request_file;

    file_put_contents($remotefmle_rec_status_file, $status);
    file_put_contents($remotefmle_last_request_file, time());
}

/**
 * Returns time of creation of the recording file
 * Only used for local purposes
 */
function starttime_get() {
    global $remotefmle_time_started_file;

    if (!file_exists($remotefmle_time_started_file))
        return false;

    return trim(file_get_contents($remotefmle_time_started_file));
}

/**
 * Returns time of last action
 * Only used for local purposes
 */
function lastmodtime_get() {
    global $remotefmle_capture_file;

    return filemtime($remotefmle_capture_file);
}

function send_timeout() {
//sends a request to the 'main core' to let it know that a recording has timed out
    global $remotefmle_force_quit_url;

    $ch = curl_init($remotefmle_force_quit_url);
    $res = curl_exec($ch);
    $curlinfo = curl_getinfo($ch);
    curl_close($ch);

    if (!$res) {//error
        if (isset($curlinfo['http_code']))
            return $curlinfo['http_code'];
        else
            return "Curl error";
    }

    //All went well send http response in stderr to be logged
    return false;
}

?>
