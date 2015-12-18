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
include_once $basedir . "/lib_error.php";

/**
 * @implements 
 * Initialize the camera settings.
 * This function should be called before the use of the camera
 * @param associate_array $meta_assoc metadata relative to the current recording
 */
function capture_remoteqtb_init(&$pid, $meta_assoc) {
    global $remoteqtb_script_qtbnew;
    global $remoteqtb_recorder_logs;
    global $remoteqtb_ip;
    global $remote_script_call;
    global $remoteqtb_username;

    $asset = $meta_assoc['record_date'] . '_' . $meta_assoc['course_name'];
    $tmp_dir = capture_remoteqtb_tmpdir_get($asset);

    $xml = escapeshellarg(capture_assoc_array2metadata($meta_assoc));
    // put the xml string in a metadata file on the local mac mini
    file_put_contents($tmp_dir . "/_metadata.xml", $xml);

    if (capture_remoteqtb_status_get() == '') {
        /* remote script call requires:
         * - the remote ip
         * - the absolute path to the logs file
         * - the remote script to execute
         */
        // '> /dev/null' discards output, '&' executes the process as background task and 'echo $!' returns the pid
        system("sudo -u $remoteqtb_username $remote_script_call $remoteqtb_ip $remoteqtb_recorder_logs $remoteqtb_script_qtbnew > /dev/null & echo $! > $tmp_dir/pid");
        //      system("sudo -u $remoteqtb_username ssh -o ConnectTimeout=10 $remoteqtb_ip \"$remoteqtb_script_qtbnew >> $remoteqtb_recorder_logs 2>&1\"");
        $pid = file_get_contents($tmp_dir . '/pid');
        if (capture_remoteqtb_status_get() == 'launch_failure') {
            error_last_message("can't open because QTB failed to launch");
            return false;
        }

        capture_remoteqtb_status_set('open');
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
function capture_remoteqtb_start($asset) {
    global $remoteqtb_script_qtbrec;
    global $remoteqtb_ip;
    global $remote_script_call;
    global $remote_script_datafile_set;
    global $remoteqtb_recorder_logs;
    global $remoteqtb_username;


    /* remote script call requires:
     * - the remote ip
     * - the absolute path to the logs file
     * - the remote script to execute
     * - optional args for the script to execute
     */
    system("sudo -u $remoteqtb_username $remote_script_call $remoteqtb_ip $remoteqtb_recorder_logs $remoteqtb_script_qtbrec > /dev/null &");

    //update recording status
    if (capture_remoteqtb_status_get() == "open") {
        capture_remoteqtb_status_set('recording');
    } else {
        error_last_message("capture_start: can't start recording because current status: $status");
        capture_remoteqtb_status_set("error");
        return false;
    }

    return true;
}

/**
 * @implements
 * Pauses the current recording
 */
function capture_remoteqtb_pause($asset) {
    global $remoteqtb_script_qtbpause;
    global $remoteqtb_ip;
    global $remote_script_call;
    global $remoteqtb_recorder_logs;
    global $remoteqtb_username;

    if (capture_remoteqtb_status_get() == 'recording') {
        system("sudo -u $remoteqtb_username $remote_script_call $remoteqtb_ip $remoteqtb_recorder_logs $remoteqtb_script_qtbpause > /dev/null &");
        capture_remoteqtb_status_set('paused');
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
function capture_remoteqtb_resume($asset) {
    global $remoteqtb_script_qtbresume;
    global $remoteqtb_ip;
    global $remote_script_call;
    global $remoteqtb_recorder_logs;
    global $remoteqtb_username;

    $status = capture_remoteqtb_status_get();
    if ($status == 'paused' || $status == 'stopped') {
        system("sudo -u $remoteqtb_username $remote_script_call $remoteqtb_ip $remoteqtb_recorder_logs $remoteqtb_script_qtbresume > /dev/null &");
        capture_remoteqtb_status_set('recording');
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
function capture_remoteqtb_stop(&$pid, $asset) {
    global $remoteqtb_script_qtbpause;
    global $remoteqtb_ip;
    global $remote_script_call;
    global $remoteqtb_recorder_logs;
    global $remoteqtb_username;

    $tmp_dir = capture_remoteqtb_tmpdir_get($asset);

    $status = capture_remoteqtb_status_get();
    if ($status == 'recording') {
        system("sudo -u $remoteqtb_username $remote_script_call $remoteqtb_ip $remoteqtb_recorder_logs $remoteqtb_script_qtbpause > /dev/null & echo $! > $tmp_dir/pid");
        $pid = file_get_contents("$tmp_dir/pid");
        capture_remoteqtb_status_set('stopped');
    } else if ($status == 'paused') {
        capture_remoteqtb_status_set('stopped');
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
function capture_remoteqtb_cancel($asset) {
    global $remoteqtb_script_qtbcancel;
    global $remoteqtb_ip;
    global $remote_script_call;
    global $remoteqtb_recorder_logs;
    global $remoteqtb_username;

    $tmp_dir = capture_remoteqtb_tmpdir_get($asset);

    $status = capture_remoteqtb_status_get();
    if ($status == 'recording' || $status == 'stopped' || $status == 'paused' || $status == 'open' || $status == '') {

        $cmd = "sudo -u $remoteqtb_username $remote_script_call $remoteqtb_ip $remoteqtb_recorder_logs $remoteqtb_script_qtbcancel";
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
function capture_remoteqtb_process($meta_assoc, &$pid) {
    global $remoteqtb_script_qtbstop;
    global $remoteqtb_ip;
    global $remote_script_call;
    global $remoteqtb_recorder_logs;
    global $remoteqtb_processing_tool;
    global $remoteqtb_processing_tools;
    global $remote_script_datafile_set;
    global $remoteqtb_username;
    global $remoteqtb_basedir;

    $asset = $meta_assoc['record_date'] . '_' . $meta_assoc['course_name'];
    $tmp_dir = capture_remoteqtb_tmpdir_get($asset);

    if (capture_remoteqtb_status_get() != 'recording' && capture_remoteqtb_status_get() != 'open') {

        if (!in_array($remoteqtb_processing_tool, $remoteqtb_processing_tools))
            $remoteqtb_processing_tool = $remoteqtb_processing_tools[0];

        $xml = capture_assoc_array2metadata($meta_assoc);
        // put the xml string in a metadata file on the remote mac mini
        system("sudo -u $remoteqtb_username $remote_script_datafile_set $remoteqtb_ip " . escapeshellarg($xml) . " $remoteqtb_basedir/var/_metadata.xml &");
        // put the xml string in a metadata file on the local mac mini
        file_put_contents($tmp_dir . "/_metadata.xml", $xml);

        $course_name = $meta_assoc['course_name'];
        $record_date = $meta_assoc['record_date'];
        $cmd = "sudo -u $remoteqtb_username $remote_script_call $remoteqtb_ip $remoteqtb_recorder_logs $remoteqtb_script_qtbstop $course_name $record_date $remoteqtb_processing_tool > /dev/null 2>&1 & echo $! > $tmp_dir/pid";
        log_append('recording', "launching command: $cmd");
        system($cmd);
        $pid = file_get_contents("$tmp_dir/pid");

        //update (clear) status
        capture_remoteqtb_status_set('');
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
 * @global type $remoteqtb_ip
 * @global type $remoteqtb_script_qtbfinalize
 * @global type $remoteqtb_recorder_logs
 * @global type $remote_script_datafile_get
 * @global type $remote_script_call
 */
function capture_remoteqtb_finalize($asset) {
    global $remoteqtb_ip;
    global $remoteqtb_script_qtbfinalize;
    global $remoteqtb_recorder_logs;
    global $remote_script_call;
    global $remoteqtb_username;

    // retrieves the metadata relative to the recording
    $tmp_dir = capture_remoteqtb_tmpdir_get($asset);
    $meta_assoc = capture_metadata2assoc_array($tmp_dir . '/_metadata.xml');

    $record_date = $meta_assoc['record_date'];
    $course_name = $meta_assoc['course_name'];

    // calls the remote script
    $cmd = "sudo -u $remoteqtb_username $remote_script_call $remoteqtb_ip $remoteqtb_recorder_logs $remoteqtb_script_qtbfinalize $course_name $record_date > /dev/null";
    log_append("finalizing: execute cmd '$cmd'");

    $pid = system($cmd);

    system("rm -rf $tmp_dir");
}

/**
 * #implements
 * Creates a thumbnail picture
 */
function capture_remoteqtb_thumbnail() {
    global $remoteqtb_basedir;
    global $remoteqtb_script_qtbthumbnail;
    global $remoteqtb_capture_file;
    global $remoteqtb_capture_tmp_file;
    global $remoteqtb_ip;
    global $remote_script_thumbnail_create;
    global $remoteqtb_username;

    // Slide screenshot
    if (!file_exists($remoteqtb_capture_file) || (time() - filemtime($remoteqtb_capture_file) > 3)) {
        //if no image or image is old get a new screencapture
        $cmd = "sudo -u $remoteqtb_username $remote_script_thumbnail_create $remoteqtb_ip $remoteqtb_script_qtbthumbnail $remoteqtb_basedir/var/pic_new.jpg $remoteqtb_capture_tmp_file";
        $res = exec($cmd, $output_array, $return_code);
        if ((time() - filemtime($remoteqtb_capture_tmp_file) > 3)) {
            //print "could not take a screencapture";
            copy("./nopic.jpg", "$remoteqtb_capture_file");
        } else {
            //copy screencapture to actual snap
            copy("$remoteqtb_capture_tmp_file", "$remoteqtb_capture_tmp_file" . "tmp");
            rename("$remoteqtb_capture_tmp_file" . "tmp", "$remoteqtb_capture_file");
        }
    }
    return file_get_contents($remoteqtb_capture_file);
}

/**
 * @implements
 * Returns an associative array containing information required for given action
 * @global type $remoteqtb_ip
 * @global type $remoteqtb_download_protocol
 * @global type $remoteqtb_username
 * @return type
 */
function capture_remoteqtb_info_get($action, $asset = '') {
    global $remoteqtb_ip;
    global $remoteqtb_download_protocol;
    global $remoteqtb_username;
    global $remoteqtb_upload_dir;
    global $remoteqtb_username;

    switch ($action) {
        case 'download':
            $tmp_dir = capture_remoteqtb_tmpdir_get($asset);
            $meta_assoc = capture_metadata2assoc_array($tmp_dir . "/_metadata.xml");

            $download_info_array = array("ip" => $remoteqtb_ip,
                "protocol" => $remoteqtb_download_protocol,
                "username" => $remoteqtb_username,
                "filename" => $remoteqtb_upload_dir . $meta_assoc['record_date'] . "_" . $meta_assoc['course_name'] . "/slide.mov");
            return $download_info_array;
            break;
    }
}

/**
 * @implements
 * Returns the current status of the video slide
 * Status may be "open", "recording", "paused", "stopped", "error"
 */
function capture_remoteqtb_status_get() {
    global $remoteqtb_ip;
    global $remoteqtb_status_file;
    global $remote_script_datafile_get;
    global $remoteqtb_username;

    $cmd = "sudo -u $remoteqtb_username $remote_script_datafile_get $remoteqtb_ip $remoteqtb_status_file";
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
function capture_remoteqtb_status_set($status) {

    global $remoteqtb_ip;
    global $remoteqtb_status_file;
    global $remote_script_datafile_set;
    global $remoteqtb_username;

    $status = "'$status'";

    $curr_time = time();
    $cmd = "sudo -u $remoteqtb_username $remote_script_datafile_set $remoteqtb_ip $status $remoteqtb_status_file";
    $res = exec($cmd, $outputarray, $errorcode);
}

/**
 * @implements
 * Returns an array containing the features offered by the module
 * @global type $remoteqtb_features
 * @return type
 */
function capture_remoteqtb_features_get() {
    global $remoteqtb_features;
    return $remoteqtb_features;
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

/**
 * transforms an xml file or xml string in an associative array
 * @param type $meta_path
 * @param type $xml_file
 * @return boolean
 */
function capture_metadata2assoc_array($meta_path, $xml_file = true) {
    if ($xml_file) {
        $xml = simplexml_load_file($meta_path);
    } else {
        $xml = simplexml_load_string($meta_path);
    }
    if ($xml === false) {
        return false;
    }
    $assoc_array = array();
    foreach ($xml as $key => $value) {
        $assoc_array[$key] = (string) $value;
    }
    return $assoc_array;
}

function capture_remoteqtb_tmpdir_get($asset) {
    global $remoteqtb_local_basedir;
    static $tmp_dir;

    $tmp_dir = $remoteqtb_local_basedir . '/var/' . $asset;
    if (!file_exists($tmp_dir) && !dir($tmp_dir))
        mkdir($tmp_dir, 0777, true);

    return $tmp_dir;
}

?>
