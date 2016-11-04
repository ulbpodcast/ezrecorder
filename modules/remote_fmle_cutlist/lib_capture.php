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

/*
 * This file contains all functions related to the video slide capture from a remote mac
 * It implements the "recorder interface" which is used in web_index. 
 * the function annotated with the comment "@implements" are required to make 
 * sure the web_index.php can work properly.
 * 
 * ATTENTION: In order to make this library works, the module has to be 'installed' 
 * on the remote server. 
 */

require "config.inc";
require_once $basedir . "/lib_various.php";
include_once $basedir . "/lib_error.php";

/**
 * @implements 
 * Initialize the camera settings.
 * This function should be called before the use of the camera
 * @param associate_array $meta_assoc metadata relative to the current recording
 */
function capture_remotefmle_init(&$pid, $meta_assoc) {
    global $remotefmle_script_init;
    global $remotefmle_recorder_logs;
    global $remotefmle_ip;
    global $remote_script_call;
    global $remotefmle_username;

    $asset = $meta_assoc['record_date'] . '_' . $meta_assoc['course_name'];
    $tmp_dir = capture_remotefmle_tmpdir_get($asset);

    $xml = escapeshellarg(xml_assoc_array2metadata($meta_assoc));
    // put the xml string in a metadata file on the local mac mini
    file_put_contents($tmp_dir . "/_metadata.xml", $xml);

    if (capture_remotefmle_status_get() == '') {
        /* remote script call requires:
         * - the remote ip
         * - the absolute path to the logs file
         * - the remote script to execute
         */
        // '> /dev/null' discards output, '&' executes the process as background task and 'echo $!' returns the pid
        system("sudo -u $remotefmle_username $remote_script_call $remotefmle_ip $remotefmle_recorder_logs $remotefmle_script_init $asset > /dev/null & echo $! > $tmp_dir/pid");
        //      system("sudo -u $remotefmle_username ssh -o ConnectTimeout=10 $remotefmle_ip \"$remotefmle_script_qtbnew >> $remotefmle_recorder_logs 2>&1\"");
        $pid = file_get_contents($tmp_dir . '/pid');
        if (capture_remotefmle_status_get() == 'launch_failure') {
            error_last_message("can't open because remote FMLE failed to launch");
            return false;
        }

        capture_remotefmle_status_set('open');
    } else {
        error_last_message("capture_init: can't open because current status: $status");
        return false;
    }

    return true;
}

/**
 * @implements
 * Launch the recording process 
 */
function capture_remotefmle_start($asset) {
    global $remotefmle_script_start;
    global $remotefmle_ip;
    global $remote_script_call;
    global $remotefmle_recorder_logs;
    global $remotefmle_username;


    /* remote script call requires:
     * - the remote ip
     * - the absolute path to the logs file
     * - the remote script to execute
     * - optional args for the script to execute
     */
    system("sudo -u $remotefmle_username $remote_script_call $remotefmle_ip $remotefmle_recorder_logs $remotefmle_script_start $asset > /dev/null &");

    //update recording status
    if (capture_remotefmle_status_get() == "open") {
        capture_remotefmle_status_set('recording');
    } else {
        error_last_message("capture_start: can't start recording because current status: $status");
        capture_remotefmle_status_set("error");
        return false;
    }

    return true;
}

/**
 * @implements
 * Pauses the current recording
 */
function capture_remotefmle_pause($asset) {
    global $remotefmle_script_cutlist;
    global $remotefmle_ip;
    global $remote_script_call;
    global $remotefmle_recorder_logs;
    global $remotefmle_username;

    if (capture_remotefmle_status_get() == 'recording') {
        system("sudo -u $remotefmle_username $remote_script_call $remotefmle_ip $remotefmle_recorder_logs $remotefmle_script_cutlist $asset pause > /dev/null &");
        capture_remotefmle_status_set('paused');
    } else {
        error_last_message("capture_pause: can't pause recording because current status: $status");
        return false;
    }

    return true;
}

/**
 * @implements
 * Resumes the current paused recording
 */
function capture_remotefmle_resume($asset) {
    global $remotefmle_script_cutlist;
    global $remotefmle_ip;
    global $remote_script_call;
    global $remotefmle_recorder_logs;
    global $remotefmle_username;

    $status = capture_remotefmle_status_get();
    if ($status == 'paused' || $status == 'stopped') {
        system("sudo -u $remotefmle_username $remote_script_call $remotefmle_ip $remotefmle_recorder_logs $remotefmle_script_cutlist $asset resume > /dev/null &");
        capture_remotefmle_status_set('recording');
    } else {
        error_last_message("capture_resume: can't resume recording because current status: $status");
        return false;
    }

    return true;
}

/**
 * @implements
 * Stops the current recording
 */
function capture_remotefmle_stop(&$pid, $asset) {
    global $remotefmle_script_cutlist;
    global $remotefmle_ip;
    global $remote_script_call;
    global $remotefmle_recorder_logs;
    global $remotefmle_username;

    $tmp_dir = capture_remotefmle_tmpdir_get($asset);

    $status = capture_remotefmle_status_get();
    if ($status == 'recording' || $status == 'paused') {
        system("sudo -u $remotefmle_username $remote_script_call $remotefmle_ip $remotefmle_recorder_logs $remotefmle_script_cutlist $asset stop > /dev/null & echo $! > $tmp_dir/pid");
        $pid = file_get_contents("$tmp_dir/pid");
        capture_remotefmle_status_set('stopped');
    } else {
        error_last_message("capture_pause: can't pause recording because current status: $status");
        return false;
    }

    return true;
}

/**
 * @implements
 * Ends the current recording and saves it as an archive
 */
function capture_remotefmle_cancel($asset) {
    global $remotefmle_script_cancel;
    global $remotefmle_ip;
    global $remote_script_call;
    global $remotefmle_recorder_logs;
    global $remotefmle_username;

    $tmp_dir = capture_remotefmle_tmpdir_get($asset);

    $status = capture_remotefmle_status_get();
    if ($status == 'recording' || $status == 'stopped' || $status == 'paused' || $status == 'open' || $status == '') {

        $cmd = "sudo -u $remotefmle_username $remote_script_call $remotefmle_ip $remotefmle_recorder_logs $remotefmle_script_cancel $asset";
        log_append('recording', "launching command: $cmd");
        $fpart = exec($cmd, $outputarray, $errorcode);
        system("rm -rf $tmp_dir");
    } else {
        error_last_message("capture_cancel: can't cancel recording because current status: " . $status);
        return false;
    }

    return true;
}

/**
 * @implements
 * Processes the record before sending it to the server
 */
function capture_remotefmle_process($meta_assoc, &$pid) {
    global $remotefmle_script_stop;
    global $remotefmle_ip;
    global $remote_script_call;
    global $remotefmle_recorder_logs;
    global $remotefmle_processing_tool;
    global $remotefmle_processing_tools;
    global $remote_script_datafile_set;
    global $remotefmle_username;
    global $remotefmle_basedir;

    $asset = $meta_assoc['record_date'] . '_' . $meta_assoc['course_name'];
    $tmp_dir = capture_remotefmle_tmpdir_get($asset);
    $status = capture_remotefmle_status_get();

    if ($status != 'recording' && $status != 'open') {

        if (!in_array($remotefmle_processing_tool, $remotefmle_processing_tools))
            $remotefmle_processing_tool = $remotefmle_processing_tools[0];

        $xml = xml_assoc_array2metadata($meta_assoc);
        // put the xml string in a metadata file on the remote mac mini
        system("sudo -u $remotefmle_username $remote_script_datafile_set $remotefmle_ip " . escapeshellarg($xml) . " $remotefmle_basedir/var/_metadata.xml &");
        // put the xml string in a metadata file on the local mac mini
        file_put_contents($tmp_dir . "/_metadata.xml", $xml);

        $course_name = $meta_assoc['course_name'];
        $record_date = $meta_assoc['record_date'];
        $cmd = "sudo -u $remotefmle_username $remote_script_call $remotefmle_ip $remotefmle_recorder_logs $remotefmle_script_stop $course_name $record_date $remotefmle_processing_tool > /dev/null 2>&1 & echo $! > $tmp_dir/pid";
        log_append('recording', "launching command: $cmd");
        system($cmd);
        $pid = file_get_contents("$tmp_dir/pid");

        //update (clear) status
        capture_remotefmle_status_set('');
        capture_remotefmle_rec_status_set('');
    } else {
        error_last_message("capture_stop: can't stop recording because current status: $status");
        return false;
    }

    //should be saved in Movies/local_processing/<date+hour>/
    //combine cam and slide:
    //one need to activate at on the mac:
    //	vi /System/Library/LaunchDaemons/com.apple.atrun.plisto
    //	change Disabled tag value from <true /> to <false/>
    //   	launchctl unload -F /System/Library/LaunchDaemons/com.apple.atrun.plist
    //  	launchctl load -F /System/Library/LaunchDaemons/com.apple.atrun.plist

    return true;
}

/**
 * @implements
 * Finalizes the recording after it has been uploaded to the server.
 * The finalization consists in archiving video files in a specific dir
 * and removing all temp files used during the session.
 * @global type $remotefmle_ip
 * @global type $remotefmle_script_qtbfinalize
 * @global type $remotefmle_recorder_logs
 * @global type $remote_script_datafile_get
 * @global type $remote_script_call
 */
function capture_remotefmle_finalize($asset) {
    global $remotefmle_ip;
    global $remotefmle_script_finalize;
    global $remotefmle_recorder_logs;
    global $remote_script_call;
    global $remotefmle_username;

    // retrieves the metadata relative to the recording
    $tmp_dir = capture_remotefmle_tmpdir_get($asset);
    $meta_assoc = xml_file2assoc_array($tmp_dir . '/_metadata.xml');

    $record_date = $meta_assoc['record_date'];
    $course_name = $meta_assoc['course_name'];

    // calls the remote script
    $cmd = "sudo -u $remotefmle_username $remote_script_call $remotefmle_ip $remotefmle_recorder_logs $remotefmle_script_finalize $course_name $record_date > /dev/null";
    log_append("finalizing: execute cmd '$cmd'");

    $pid = system($cmd);

    system("rm -rf $tmp_dir");
}

/**
 * #implements
 * Creates a thumbnail picture
 */
function capture_remotefmle_thumbnail() {
    global $remotefmle_basedir;
    global $remotefmle_script_thumbnail;
    global $remotefmle_capture_file;
    global $remotefmle_capture_tmp_file;
    global $remotefmle_capture_transit_file;
    global $remotefmle_ip;
    global $remote_script_thumbnail_create;
    global $remotefmle_username;


    $minperiod = 5;

    // Slide screenshot
    if (!file_exists($remotefmle_capture_file) || (time() - filemtime($remotefmle_capture_file) > 3)) {
        //if no image or image is old get a new screencapture
        $cmd = "sudo -u $remotefmle_username $remote_script_thumbnail_create $remotefmle_ip $remotefmle_script_thumbnail $remotefmle_basedir/var/pic_new.jpg $remotefmle_capture_tmp_file";
        $res = exec($cmd, $output_array, $return_code);
        if ((time() - filemtime($remotefmle_capture_tmp_file) > 3)) {
            //print "could not take a screencapture";
            copy("./nopic.jpg", "$remotefmle_capture_file");
        } else {
            //copy screencapture to actual snap
            $status = capture_remotefmle_status_get();
            if ($status == 'recording') {
                $status = capture_remotefmle_rec_status_get();
            }
            image_resize("$remotefmle_capture_tmp_file", "$remotefmle_capture_transit_file", 235, 157, $status, false);
            rename("$remotefmle_capture_transit_file", "$remotefmle_capture_file");
        }
    }
    return file_get_contents($remotefmle_capture_file);
}

/**
 * @implements
 * Returns an associative array containing information required for given action
 * @global type $remotefmle_ip
 * @global type $remotefmle_download_protocol
 * @global type $remotefmle_username
 * @return type
 */
function capture_remotefmle_info_get($action, $asset = '') {
    global $remotefmle_ip;
    global $remotefmle_download_protocol;
    global $remotefmle_username;
    global $remotefmle_upload_dir;
    global $remotefmle_username;

    switch ($action) {
        case 'download':
            $tmp_dir = capture_remotefmle_tmpdir_get($asset);
            $meta_assoc = xml_file2assoc_array($tmp_dir . "/_metadata.xml");

            $download_info_array = array("ip" => $remotefmle_ip,
                "protocol" => $remotefmle_download_protocol,
                "username" => $remotefmle_username,
                "filename" => $remotefmle_upload_dir . $meta_assoc['record_date'] . "_" . $meta_assoc['course_name'] . "/fmle_movie.f4v");
            return $download_info_array;
            break;
    }
}

/**
 * @implements
 * Returns the current status of the video slide
 * Status may be "open", "recording", "paused", "stopped", "error"
 */
function capture_remotefmle_status_get() {
    global $remotefmle_ip;
    global $remotefmle_status_file;
    global $remote_script_datafile_get;
    global $remotefmle_username;

    $cmd = "sudo -u $remotefmle_username $remote_script_datafile_get $remotefmle_ip $remotefmle_status_file";
    $res = exec($cmd, $output, $errorcode);
    if ($errorcode) {
        return '';
    }

    return trim($res);
}

/**
 * @implements
 * Defines the status of the current video
 */
function capture_remotefmle_status_set($status) {

    global $remotefmle_ip;
    global $remotefmle_status_file;
    global $remote_script_datafile_set;
    global $remotefmle_username;

    $status = "'$status'";

    $curr_time = time();
    $cmd = "sudo -u $remotefmle_username $remote_script_datafile_set $remotefmle_ip $status $remotefmle_status_file";
    $res = exec($cmd, $outputarray, $errorcode);
}

/**
 * @implements
 * Returns an array containing the features offered by the module
 * @global type $remotefmle_features
 * @return type
 */
function capture_remotefmle_features_get() {
    global $remotefmle_features;
    return $remotefmle_features;
}

function capture_remotefmle_rec_status_get() {
    global $remotefmle_ip;
    global $remotefmle_rec_status_file;
    global $remote_script_datafile_get;
    global $remotefmle_username;

    $cmd = "sudo -u $remotefmle_username $remote_script_datafile_get $remotefmle_ip $remotefmle_rec_status_file";
    $res = exec($cmd, $output, $errorcode);
    if ($errorcode) {
        return '';
    }

    return trim($res);
}

function capture_remotefmle_rec_status_set($status) {

    global $remotefmle_ip;
    global $remotefmle_rec_status_file;
    global $remote_script_datafile_set;
    global $remotefmle_username;

    $status = "'$status'";

    $curr_time = time();
    $cmd = "sudo -u $remotefmle_username $remote_script_datafile_set $remotefmle_ip $status $remotefmle_rec_status_file";
    $res = exec($cmd, $outputarray, $errorcode);
}

function capture_remotefmle_tmpdir_get($asset) {
    global $remotefmle_local_basedir;
    static $tmp_dir;

    $tmp_dir = $remotefmle_local_basedir . '/var/' . $asset;
    if (!file_exists($tmp_dir) && !dir($tmp_dir))
        mkdir($tmp_dir, 0777, true);

    return $tmp_dir;
}

?>
