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


/*
 * This is a CLI script that launches the local processing of the recordings 
 * and sends a request to podman to download the recordings
 * Usage: cli_process_upload.php
 * 
 */

require_once 'global_config.inc';

require_once $cam_lib;
require_once $slide_lib;
require_once $session_lib;
require_once 'lib_error.php';
require_once 'lib_various.php';

$fct = "session_" . $session_module . "_metadata_get";
$meta_assoc = $fct();


$record_date = $meta_assoc['record_date'];
$course_name = $meta_assoc['course_name'];
$record_type = $meta_assoc['record_type'];

$asset = $record_date . '_' . $course_name;
$tmp_dir = "$basedir/var/$asset/";
if (!file_exists($tmp_dir . "/metadata.xml")) {
    echo "Error: metadata file $basedir/var/$asset/metadata.xml does not exist" . PHP_EOL;
    //  die;
}

$fct = "session_" . $session_module . "_metadata_delete";
$fct();

// Stopping and releasing the recorder
// if cam module is enabled
if ($cam_enabled) {
    $fct = 'capture_' . $cam_module . '_process';
    $fct($meta_assoc, $cam_pid);
}
// if slide module is enabled
if ($slide_enabled) {
    $fct = 'capture_' . $slide_module . '_process';
    $fct($meta_assoc, $slide_pid);
}

// wait for local processing to finish
while (is_process_running($cam_pid) || is_process_running($slide_pid)) {
    sleep(0.5);
}

system("echo \"`date` : local processing finished for both cam and slide modules\" >> $basedir/var/finish");



////call EZcast server and tell it a recording is ready to download

$nb_retry = 500;


if ($cam_enabled) {
    // get downloading information required by EZcast server
    $fct = 'capture_' . $cam_module . '_info_get';
    $cam_download_info = $fct('download', $asset);
}

if ($slide_enabled) {
    // get downloading information required by EZcast server
    $fct = 'capture_' . $slide_module . '_info_get';
    $slide_download_info = $fct('download', $asset);
}
//try repeatedly to call EZcast server and send the right post parameters
$err = true;
while ($err && $nb_retry > 0) {
    $err = server_start_download($record_type, $record_date, $course_name, $cam_download_info, $slide_download_info);
    if ($err) {
        log_append('EZcast_curl_call', "Will retry later: Error connecting to EZcast server ($ezcast_submit_url). curl error: $err \n");
        sleep(120);
    }//endif error
    $nb_retry--;
}//end while

if ($err) {
    log_append('EZcast_curl_call', "Giving up: Error connecting to EZcast server ($ezcast_submit_url). curl error: $err \n");
    sleep(120);
}

/**
 *
 * @param <slide|cam|camslide> $recording_type
 * @param <YYYY_MM_DD_HHhmm> $recording_date
 * @param <mnemonique> $course_name
 * @param <associative_array> $cam_download_info information relative to the downloading of cam movie
 * @param <associative_array> $slide_download_info information relative to the downloading of slide movie
 * @return false|error_code
 */
function server_start_download($record_type, $record_date, $course_name, $cam_download_info, $slide_download_info) {
//tells the server that a recording is ready to be downloaded
    global $ezcast_submit_url;
    global $tmp_dir;
    global $recorder_version;
    global $php_cli_cmd;

    $post_array['action'] = 'download';
    $post_array['record_type'] = $record_type;
    $post_array['record_date'] = $record_date;
    $post_array['course_name'] = $course_name;
    $post_array['php_cli'] = $php_cli_cmd;

    $post_array['metadata_file'] = $tmp_dir . "/metadata.xml";

    if (isset($cam_download_info) && count($cam_download_info) > 0) {
        $post_array['cam_info'] = serialize($cam_download_info);
    }

    if (isset($slide_download_info) && count($slide_download_info) > 0) {
        $post_array['slide_info'] = serialize($slide_download_info);
    }

    if (isset($recorder_version) && !empty($recorder_version)) {
        $post_array['recorder_version'] = $recorder_version;
    }
    return strpos(server_request_send($ezcast_submit_url, $post_array), 'Curl error') !== false;
}

// determines if a process is running or not
function is_process_running($pid) {
    if (!isset($pid) || $pid == '' || $pid == 0)
        return false;
    exec("ps $pid", $output, $result);
    return count($output) >= 2;
}

?>
