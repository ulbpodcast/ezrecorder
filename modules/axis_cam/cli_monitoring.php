<?php
/*
 * EZCAST EZrecorder
 *
 * Copyright (C) 2016 Université libre de Bruxelles
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
 * This script is called by capture_axiscam_start() in lib_capture.php
 * Current checks performed:
 * - timeout check (checks whether the user has forgotten to stop recording, and publish the recording if they did)
 * - recovery check (checks whether recording is ongoing on the IP camera, and restarts it if it isn't)
 */
/**
 * Timeout check:
 * For the first "threshold" seconds (typically 2 or 3 hours), we decide to trust the user.
 * After that, we check that there has been activity at least once every "timeout" seconds (typically 15 min).
 * This program is meant to be run as a crontask at least once every "timeout" seconds
 */
require_once 'config.inc';
require_once 'lib_capture.php';
require_once $basedir . 'lib_model.php';

$asset = $argv[1]; // this program is called using 'date_album' of the current record as parameter
$last_id = $argv[2];
$tmp_dir = capture_axiscam_tmpdir_get($asset);

// Delays, in seconds
$threshold_timeout = 7200; // Threshold before we start worrying about the user
//$threshold_timeout = 120; // Threshold before we start worrying about the user
$timeout = 900; // Timeout after which we consider a user has forgotten to stop their recording
//$timeout = 30;
$sleep_time = 15; // Duration of the sleep between two checks
$connected = true;
$last_record_id = (isset($last_id)) ? $last_id : "";
$current_record = "";

set_time_limit(0);
fwrite(fopen($axiscam_monitoring_file, 'w'), getmypid());

// This is the main loop. Runs until the lock file disappears
while (true) {
    $status = capture_axiscam_status_get();
    file_put_contents($tmp_dir . "/_monitoring_log", "[" . date("Y-m-d H:i:s") . "] Last record: " . $last_record_id . PHP_EOL, FILE_APPEND);
    file_put_contents($tmp_dir . "/_monitoring_log", "[" . date("Y-m-d H:i:s") . "] Recording status: " . $status . PHP_EOL, FILE_APPEND);

    if ($status != 'recording') {
        // reinit current record after pause / stop / other
        $current_record = "";    
        if ($status == ""){
            // may occur if recording has been stopped during the 15 secondes following a recording resume.
            file_put_contents($tmp_dir . "/_monitoring_log", "[" . date("Y-m-d H:i:s") . " WARNING ] Unable to get recording status : The monitoring will end now" . PHP_EOL, FILE_APPEND);
            unlink($axiscam_monitoring_file);
            die;
        }
    } else { // status == recording
        // Checking if the camera is actually recording
        // 
        // Do we have a connection to the ip camera ?
        $fp = fsockopen($axiscam_ip, 80, $errno, $errstr, 5);
        if (!$fp) {
            file_put_contents($tmp_dir . "/_monitoring_log", "[" . date("Y-m-d H:i:s") . " WARNING ] " . "Connection failed" . PHP_EOL, FILE_APPEND);
            file_put_contents($axiscam_camstatus_file, "connection problem");
            log_append("No connection to ip cam $axiscam_ip : $errstr ($errno)");
            if ($connected) {
                // first time, sends a mail to admins
                mail($mailto_admins, "[$classroom] Connection problem", "No connection to ip cam $axiscam_ip : $errstr ($errno)");
                $connected = false;
            }
        } else {
            file_put_contents($tmp_dir . "/_monitoring_log", "[" . date("Y-m-d H:i:s") . "] " . "Connection established" . PHP_EOL, FILE_APPEND);
            $connected = true;
            
            // sleep to make sure 'axis recording status' is correctly set in axis camera.
            sleep(2);
            
            // get the status of the current record
            if (!isset($current_record) || $current_record == "" || $current_record['recordingid'] == ""){
                do {
                    $record_list = capture_axiscam_recordlist_get();
                    $current_record = $record_list[0]; 
                    file_put_contents($tmp_dir . "/_monitoring_log", "[" . date("Y-m-d H:i:s") . "] " . "Current record: " . $current_record['recordingid'] . PHP_EOL, FILE_APPEND);
                } while ((!isset($current_record) || $current_record == "" || $current_record['recordingid'] == "") && (sleep(15) == 0));
            } else {
                $current = $current_record["recordingid"];
                do {
                    $current_record = capture_axiscam_recordlist_get($current);                
                    file_put_contents($tmp_dir . "/_monitoring_log", "[" . date("Y-m-d H:i:s") . "] " . "Current record: " . $current_record['recordingid'] . PHP_EOL, FILE_APPEND);
                } while ((!isset($current_record) || $current_record == "" || $current_record['recordingid'] == "") && (sleep(15) == 0));
            }
            
            file_put_contents("$tmp_dir/_records_ids", $current_record['recordingid'] . PHP_EOL, FILE_APPEND);

            if ($current_record['recordingstatus'] == 'recording') {
                file_put_contents($tmp_dir . "/_monitoring_log", "[" . date("Y-m-d H:i:s") . "] " . "Cam status (" . $current_record['recordingid']. "): ". $current_record['recordingstatus'] .  PHP_EOL, FILE_APPEND);
                file_put_contents($axiscam_camstatus_file, "recording");
            } else {
                file_put_contents($tmp_dir . "/_monitoring_log", "[" . date("Y-m-d H:i:s") . " WARNING ] " . "Cam status (" . $current_record['recordingid']. "): ". $current_record['recordingstatus'] .  PHP_EOL, FILE_APPEND);
                file_put_contents($axiscam_camstatus_file, "stopped");
                // adds the record id to the list of records to be downloaded at the end of the recording
                if ($current_record['recordingid'] != $last_record_id){
                    file_put_contents("$tmp_dir/_records_ids", $current_record['recordingid'] . PHP_EOL, FILE_APPEND);
                    $last_record_id = $current_record['recordingid'];
                }
                $current_record = "";
                // relaunches the recording
                $status = capture_axiscam_status_get();
                if ($status == "recording"){
                    file_put_contents($tmp_dir . "/_monitoring_log", "[" . date("Y-m-d H:i:s") . "] " . "Relaunched recording" .  PHP_EOL, FILE_APPEND);
                    $url = "http://$axiscam_username:$axiscam_password@$axiscam_ip/axis-cgi/io/virtualinput.cgi?action=$axiscam_input_nb:/";
                    curl_read_url($url);
                }
            }
        }
    file_put_contents($tmp_dir . "/_monitoring_log", "--------------------------------------------" . PHP_EOL, FILE_APPEND);
    }

    $status = capture_axiscam_status_get();

    // Timeout check
    //*
    if ($status == 'recording') {
        $startrec_time = private_capture_axiscam_starttime_get();
        $lastmod_time = private_capture_axiscam_lastmodtime_get();
        $now = time();

        if ($now - $startrec_time > $threshold_timeout && $now - $lastmod_time > $timeout) {
            mail($mailto_admins, 'Recording timed out', 'axis_cam: cli_monitoring.php: The recording in classroom ' . $classroom . ' was stopped and published in private album because there has been no change to the video file since ' . ($now - $lastmod_time) . ' seconds.');
            send_timeout();
        }
    }
    //*/

    sleep($sleep_time);

    // We stop if the file does not exist anymore ("kill -9" simulation)
    if (!file_exists($axiscam_monitoring_file)) {
        die;
    }
}

function send_timeout() {
    //sends a request to the 'main core' to let it know that a recording has timed out
    controller_recording_force_quit();

    //All went well send http response in stderr to be logged
    return false;
}

?>
