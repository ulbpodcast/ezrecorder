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
function capture_remoteffmpeg_init(&$pid, $meta_assoc) {
    global $remoteffmpeg_script_init;
    global $remoteffmpeg_recorder_logs;
    global $remoteffmpeg_ip;
    global $remote_script_call;
    global $remoteffmpeg_username;
    global $remoteffmpeg_streaming_info;
    global $remote_script_datafile_set;

    $streaming = 'false';

    $asset = $meta_assoc['record_date'] . '_' . $meta_assoc['course_name'];
    $tmp_dir = capture_remoteffmpeg_tmpdir_get($asset);

    $xml = capture_assoc_array2metadata($meta_assoc);
    // put the xml string in a metadata file on the local mac mini
    file_put_contents($tmp_dir . "/_metadata.xml", $xml);

    if (capture_remoteffmpeg_status_get() == '') {
        $streaming_info = capture_remoteffmpeg_info_get('streaming', $asset);
        if ($streaming_info !== false) {
            $streaming = 'true';
            $xml = capture_assoc_array2metadata($streaming_info);
            // put the xml string in a metadata file on the remote mac mini
            system("sudo -u $remoteffmpeg_username $remote_script_datafile_set $remoteffmpeg_ip " . escapeshellarg($xml) . " $remoteffmpeg_streaming_info &");
        }
        /* remote script call requires:
         * - the remote ip
         * - the absolute path to the logs file
         * - the remote script to execute
         */
        // '> /dev/null' discards output, '&' executes the process as background task and 'echo $!' returns the pid
        system("sudo -u $remoteffmpeg_username $remote_script_call $remoteffmpeg_ip $remoteffmpeg_recorder_logs $remoteffmpeg_script_init $asset $streaming 1 > /dev/null & echo $! > $tmp_dir/pid");
        //      system("sudo -u $remoteffmpeg_username ssh -o ConnectTimeout=10 $remoteffmpeg_ip \"$remoteffmpeg_script_qtbnew >> $remoteffmpeg_recorder_logs 2>&1\"");
        $pid = file_get_contents($tmp_dir . '/pid');
        if (capture_remoteffmpeg_status_get() == 'launch_failure') {
            error_last_message("can't open because remote FMLE failed to launch");
            return false;
        }

        capture_remoteffmpeg_status_set('open');
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
function capture_remoteffmpeg_start($asset) {
    global $remoteffmpeg_script_start;
    global $remoteffmpeg_ip;
    global $remote_script_call;
    global $remoteffmpeg_recorder_logs;
    global $remoteffmpeg_username;


    /* remote script call requires:
     * - the remote ip
     * - the absolute path to the logs file
     * - the remote script to execute
     * - optional args for the script to execute
     */
    system("sudo -u $remoteffmpeg_username $remote_script_call $remoteffmpeg_ip $remoteffmpeg_recorder_logs $remoteffmpeg_script_start $asset > /dev/null &");

    //update recording status
    if (capture_remoteffmpeg_status_get() == "open") {
        capture_remoteffmpeg_status_set('recording');
    } else {
        error_last_message("capture_start: can't start recording because current status: $status");
        capture_remoteffmpeg_status_set("error");
        return false;
    }

    return true;
}

/**
 * @implements
 * Pauses the current recording
 */
function capture_remoteffmpeg_pause($asset) {
    global $remoteffmpeg_script_cutlist;
    global $remoteffmpeg_ip;
    global $remote_script_call;
    global $remoteffmpeg_recorder_logs;
    global $remoteffmpeg_username;

    if (capture_remoteffmpeg_status_get() == 'recording') {
        system("sudo -u $remoteffmpeg_username $remote_script_call $remoteffmpeg_ip $remoteffmpeg_recorder_logs $remoteffmpeg_script_cutlist $asset pause > /dev/null &");
        capture_remoteffmpeg_status_set('paused');
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
function capture_remoteffmpeg_resume($asset) {
    global $remoteffmpeg_script_cutlist;
    global $remoteffmpeg_ip;
    global $remote_script_call;
    global $remoteffmpeg_recorder_logs;
    global $remoteffmpeg_username;

    $status = capture_remoteffmpeg_status_get();
    if ($status == 'paused' || $status == 'stopped') {
        system("sudo -u $remoteffmpeg_username $remote_script_call $remoteffmpeg_ip $remoteffmpeg_recorder_logs $remoteffmpeg_script_cutlist $asset resume > /dev/null &");
        capture_remoteffmpeg_status_set('recording');
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
function capture_remoteffmpeg_stop(&$pid, $asset) {
    global $remoteffmpeg_script_cutlist;
    global $remoteffmpeg_ip;
    global $remote_script_call;
    global $remoteffmpeg_recorder_logs;
    global $remoteffmpeg_username;

    $tmp_dir = capture_remoteffmpeg_tmpdir_get($asset);

    $status = capture_remoteffmpeg_status_get();
    if ($status == 'recording' || $status == 'paused') {
        system("sudo -u $remoteffmpeg_username $remote_script_call $remoteffmpeg_ip $remoteffmpeg_recorder_logs $remoteffmpeg_script_cutlist $asset stop > /dev/null & echo $! > $tmp_dir/pid");
        $pid = file_get_contents("$tmp_dir/pid");
        capture_remoteffmpeg_status_set('stopped');
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
function capture_remoteffmpeg_cancel($asset) {
    global $remoteffmpeg_script_cancel;
    global $remoteffmpeg_ip;
    global $remote_script_call;
    global $remoteffmpeg_recorder_logs;
    global $remoteffmpeg_username;

    $tmp_dir = capture_remoteffmpeg_tmpdir_get($asset);

    $status = capture_remoteffmpeg_status_get();
    if ($status == 'recording' || $status == 'stopped' || $status == 'paused' || $status == 'open' || $status == '') {

        $cmd = "sudo -u $remoteffmpeg_username $remote_script_call $remoteffmpeg_ip $remoteffmpeg_recorder_logs $remoteffmpeg_script_cancel $asset";
        log_append('recording', "launching command: $cmd");
        $fpart = exec($cmd, $outputarray, $errorcode);
        system("rm -rf $tmp_dir");
        //update (clear) status
        capture_remoteffmpeg_rec_status_set('');
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
function capture_remoteffmpeg_process($asset, &$pid) {
    global $remoteffmpeg_script_stop;
    global $remoteffmpeg_ip;
    global $remote_script_call;
    global $remoteffmpeg_recorder_logs;
    global $remoteffmpeg_processing_tool;
    global $remoteffmpeg_processing_tools;
    global $remote_script_datafile_set;
    global $remoteffmpeg_username;
    global $remoteffmpeg_basedir;

    $tmp_dir = capture_remoteffmpeg_tmpdir_get($asset);
    $status = capture_remoteffmpeg_status_get();

    if ($status != 'recording' && $status != 'open') {

        if (!in_array($remoteffmpeg_processing_tool, $remoteffmpeg_processing_tools))
            $remoteffmpeg_processing_tool = $remoteffmpeg_processing_tools[0];

        $xml = capture_assoc_array2metadata($meta_assoc);
        // put the xml string in a metadata file on the remote mac mini
        system("sudo -u $remoteffmpeg_username $remote_script_datafile_set $remoteffmpeg_ip " . escapeshellarg($xml) . " $remoteffmpeg_basedir/var/_metadata.xml &");
        // put the xml string in a metadata file on the local mac mini
        file_put_contents($tmp_dir . "/_metadata.xml", $xml);

        $cmd = "sudo -u $remoteffmpeg_username $remote_script_call $remoteffmpeg_ip $remoteffmpeg_recorder_logs $remoteffmpeg_script_stop $asset $remoteffmpeg_processing_tool > /dev/null 2>&1 & echo $! > $tmp_dir/pid";
        log_append('recording', "launching command: $cmd");
        system($cmd);
        $pid = file_get_contents("$tmp_dir/pid");

        //update (clear) status
        capture_remoteffmpeg_status_set('');
        capture_remoteffmpeg_rec_status_set('');
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
 * @global type $remoteffmpeg_ip
 * @global type $remoteffmpeg_script_qtbfinalize
 * @global type $remoteffmpeg_recorder_logs
 * @global type $remote_script_datafile_get
 * @global type $remote_script_call
 */
function capture_remoteffmpeg_finalize($asset) {
    global $remoteffmpeg_ip;
    global $remoteffmpeg_script_finalize;
    global $remoteffmpeg_recorder_logs;
    global $remote_script_call;
    global $remoteffmpeg_username;

    // retrieves the metadata relative to the recording
    $tmp_dir = capture_remoteffmpeg_tmpdir_get($asset);
    $meta_assoc = xml_file2assoc_array($tmp_dir . '/_metadata.xml');

    $record_date = $meta_assoc['record_date'];
    $course_name = $meta_assoc['course_name'];

    // calls the remote script
    $cmd = "sudo -u $remoteffmpeg_username $remote_script_call $remoteffmpeg_ip $remoteffmpeg_recorder_logs $remoteffmpeg_script_finalize $course_name $record_date > /dev/null";
    log_append("finalizing: execute cmd '$cmd'");

    $pid = system($cmd);

    system("rm -rf $tmp_dir");
}

/**
 * #implements
 * Creates a thumbnail picture
 */
function capture_remoteffmpeg_thumbnail() {
    global $remoteffmpeg_basedir;
    global $remoteffmpeg_capture_file;
    global $remoteffmpeg_capture_tmp_file;
    global $remoteffmpeg_capture_transit_file;
    global $remoteffmpeg_ip;
    global $remote_script_thumbnail_create;
    global $remoteffmpeg_username;


    $minperiod = 5;

    // Slide screenshot
    if (!file_exists($remoteffmpeg_capture_file) || (time() - filemtime($remoteffmpeg_capture_file) > 3)) {
        //if no image or image is old get a new screencapture
        $cmd = "sudo -u $remoteffmpeg_username $remote_script_thumbnail_create $remoteffmpeg_ip $remoteffmpeg_basedir/var/pic_new.jpg $remoteffmpeg_capture_tmp_file";
        $res = exec($cmd, $output_array, $return_code);
        if ((time() - filemtime($remoteffmpeg_capture_tmp_file) > 3)) {
            //print "could not take a screencapture";
            copy("./nopic.jpg", "$remoteffmpeg_capture_file");
        } else {
            //copy screencapture to actual snap
            $status = capture_remoteffmpeg_status_get();
            if ($status == 'recording') {
                $status = capture_remoteffmpeg_rec_status_get();
            }
            image_resize("$remoteffmpeg_capture_tmp_file", "$remoteffmpeg_capture_transit_file", 235, 157, $status, false);
            rename("$remoteffmpeg_capture_transit_file", "$remoteffmpeg_capture_file");
        }
    }
    return file_get_contents($remoteffmpeg_capture_file);
}

/**
 * @implements
 * Returns an associative array containing information required for given action
 * @global type $remoteffmpeg_ip
 * @global type $remoteffmpeg_download_protocol
 * @global type $remoteffmpeg_username
 * @return type
 */
function capture_remoteffmpeg_info_get($action, $asset = '') {
    global $remoteffmpeg_ip;
    global $remoteffmpeg_download_protocol;
    global $remoteffmpeg_streaming_protocol;
    global $remoteffmpeg_username;
    global $remoteffmpeg_upload_dir;
    global $remoteffmpeg_username;
    global $remoteffmpeg_streaming_quality;
    global $ezcast_submit_url;
    global $classroom;
    global $cam_module;

    switch ($action) {
        case 'download':
            $tmp_dir = capture_remoteffmpeg_tmpdir_get($asset);
            $meta_assoc = xml_file2assoc_array($tmp_dir . "/_metadata.xml");

            $download_info_array = array("ip" => $remoteffmpeg_ip,
                "protocol" => $remoteffmpeg_download_protocol,
                "username" => $remoteffmpeg_username,
                "filename" => $remoteffmpeg_upload_dir . $meta_assoc['record_date'] . "_" . $meta_assoc['course_name'] . "/slide.mov");
            return $download_info_array;
            break;
        case 'streaming':
            include_once 'info.php';
            if ($remoteffmpeg_streaming_quality == 'none') return false; 
            $tmp_dir = capture_remoteffmpeg_tmpdir_get($asset);
            $meta_assoc = xml_file2assoc_array($tmp_dir . "/_metadata.xml");
            // streaming is disabled if it has not been enabled by user
            // or if the module type is not of record type
            $module_type = (($cam_module == $module_name) ? 'cam' : 'slide');
            if ($meta_assoc['streaming'] === 'false' || ($meta_assoc['record_type'] !== 'camslide' && $meta_assoc['record_type'] != $module_type))
                return false;
            $streaming_info_array = array(
                "ip" => $remoteffmpeg_ip, 
                "submit_url" => $ezcast_submit_url,
                "protocol" => $remoteffmpeg_streaming_protocol,
                "album" => $meta_assoc['course_name'],
                "asset" => $meta_assoc['record_date'],
                "record_type" => $meta_assoc['record_type'],
                "module_type" => $module_type,
                "module_quality" => $remoteffmpeg_streaming_quality,
                "classroom" => $classroom,
                "netid" => $meta_assoc['netid'],
                "author" => $meta_assoc['author'],
                "title" => $meta_assoc['title']);
            return $streaming_info_array;
            break;
    }
}

/**
 * @implements
 * Returns the current status of the video slide
 * Status may be "open", "recording", "paused", "stopped", "error"
 */
function capture_remoteffmpeg_status_get() {
    global $remoteffmpeg_ip;
    global $remoteffmpeg_status_file;
    global $remote_script_datafile_get;
    global $remoteffmpeg_username;

    $cmd = "sudo -u $remoteffmpeg_username $remote_script_datafile_get $remoteffmpeg_ip $remoteffmpeg_status_file";
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
function capture_remoteffmpeg_status_set($status) {

    global $remoteffmpeg_ip;
    global $remoteffmpeg_status_file;
    global $remote_script_datafile_set;
    global $remoteffmpeg_username;

    $status = "'$status'";

    $curr_time = time();
    $cmd = "sudo -u $remoteffmpeg_username $remote_script_datafile_set $remoteffmpeg_ip $status $remoteffmpeg_status_file";
    $res = exec($cmd, $outputarray, $errorcode);
}

/**
 * @implements
 * Returns an array containing the features offered by the module
 * @global type $remoteffmpeg_features
 * @return type
 */
function capture_remoteffmpeg_features_get() {
    global $remoteffmpeg_features;
    global $remoteffmpeg_streaming_quality;

    if ($remoteffmpeg_streaming_quality == 'none') {
        if ($index = array_search('streaming', $remoteffmpeg_features) !== false) {
            unset($remoteffmpeg_features[$index]);
        }
    }
    return $remoteffmpeg_features;
}

function capture_remoteffmpeg_rec_status_get() {
    global $remoteffmpeg_ip;
    global $remoteffmpeg_rec_status_file;
    global $remote_script_datafile_get;
    global $remoteffmpeg_username;

    $cmd = "sudo -u $remoteffmpeg_username $remote_script_datafile_get $remoteffmpeg_ip $remoteffmpeg_rec_status_file";
    $res = exec($cmd, $output, $errorcode);
    if ($errorcode) {
        return '';
    }

    return trim($res);
}

function capture_remoteffmpeg_rec_status_set($status) {

    global $remoteffmpeg_ip;
    global $remoteffmpeg_rec_status_file;
    global $remote_script_datafile_set;
    global $remoteffmpeg_username;

    $status = "'$status'";

    $curr_time = time();
    $cmd = "sudo -u $remoteffmpeg_username $remote_script_datafile_set $remoteffmpeg_ip $status $remoteffmpeg_rec_status_file";
    $res = exec($cmd, $outputarray, $errorcode);
}

/**
 *
 * @param <type> $assoc_array
 * @return <xml_string>
 * @desc takes an assoc array and transform it in a xml metadata string
 */
function capture_assoc_array2metadata($assoc_array) {
    $xmlstr = "<?xml version='1.0' standalone='yes'?>\n<metadata>\n</metadata>\n";
    $xml = new SimpleXMLElement($xmlstr);
    foreach ($assoc_array as $key => $value) {
        $xml->addChild($key, $value);
    }
    $xml_txt = $xml->asXML();
    return $xml_txt;
}

function capture_remoteffmpeg_tmpdir_get($asset) {
    global $remoteffmpeg_local_basedir;
    static $tmp_dir;

    $tmp_dir = $remoteffmpeg_local_basedir . '/var/' . $asset;
    if (!file_exists($tmp_dir))
        mkdir($tmp_dir, 0777, true);

    return $tmp_dir;
}

?>
