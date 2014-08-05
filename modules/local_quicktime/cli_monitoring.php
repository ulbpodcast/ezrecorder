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
 * This script is called by qtstart and qtstop.
 * Current checks performed:
 * - timeout check (checks whether the user has forgotten to stop recording, and publish the recording if they did)
 * - recovery check (checks whether QuickTime has crashed, and restart it if it hs)
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
$threshold_timeout = 7200; // Threshold before we start worrying about the user
//$threshold_timeout = 120; // Threshold before we start worrying about the user
$recovery_threshold = 20; // Threshold before we start worrying about QTB
$timeout = 900; // Timeout after which we consider a user has forgotten to stop their recording
//$timeout = 30;
$sleep_time = 20; // Duration of the sleep between two checks

set_time_limit(0);
fwrite(fopen($localqt_monitoring_file, 'w'), getmypid());

// This is the main loop. Runs until the lock file disappears
while (true) {
    $status = capture_localqt_status_get();

    // QuickTime check
    clearstatcache();
    $files = glob("$localqt_recorddir/*\ Recording*.mov");
    $status = capture_localqt_status_get();

    if ($status == 'recording') {
        // Checking when was the last modif
        $last_modif = 0;
        foreach ($files as $file) {
            $last_modif = max($last_modif, filemtime($file));
        }

        // Compares with current microtime
        $now = (int) microtime(true);

        if (($now - $last_modif) > $recovery_threshold) {
            system("osascript $localqt_qtnew");
            system("osascript $localqt_qtstartmovierec");
            system("osascript $localqt_qtposition");
            //log_append('warning', 'Quicktime Broadcaster crashed. Recording will resume, but rendering will probably fail.');
            mail($mailto_admins, 'Quicktime crash', 'Quicktime crashed in room ' . $classroom . '. Recording will resume, but rendering will probably fail.');
        }
    }

    $status = capture_localqt_status_get();

    // Timeout check
    //*
    if ($status == 'recording') {
        $startrec_time = private_capture_localqt_starttime_get();
        $lastmod_time = private_capture_localqt_lastmodtime_get();
        $now = time();

        if ($now - $startrec_time > $threshold_timeout && $now - $lastmod_time > $timeout) {
            mail($mailto_admins, 'Recording timed out', 'The recording in classroom ' . $classroom . ' was stopped and published in private album because there has been no user activity since ' . ($now - $lastmod_time) . ' seconds ago.');
            send_timeout();
        }
    }
    //*/

    sleep($sleep_time);

    // We stop if the file does not exist anymore ("kill -9" simulation)
    if (!file_exists($localqt_monitoring_file)) {
        die;
    }
}

function send_timeout() {
//sends a request to the 'main core' to let it know that a recording has timed out
    global $localqt_force_quit_url;

    $ch = curl_init($localqt_force_quit_url);
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
