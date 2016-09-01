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
 *  This CLI script sends the TS segments for HLS video to EZmanager.
 * This script is called by capture_ffmpeg_init() in lib_capture.php
 */
require_once 'config.inc';
require_once 'lib_tools.php';
require_once '../../../global_config.inc';
require_once "$basedir/lib_various.php";
require_once 'info.php';

if ($argc !== 4) {
    print "Expected 3 parameters (found ".($argc - 1).") " . PHP_EOL;
    print "<course> the mnemonic of the course to be streamed " . PHP_EOL;
    print "<asset> Asset name" . PHP_EOL;
    print "<quality> the quality of the stream (high | low) " . PHP_EOL;
    exit(1);
}


$course = $argv[1];
$asset = $argv[2];
$quality = $argv[3];

$meta_assoc = xml_file2assoc_array($remoteffmpeg_streaming_info);
$post_array['course'] = $course;
$post_array['asset'] = $asset;
$post_array['quality'] = $quality;
$post_array['record_type'] = $meta_assoc['record_type'];
$post_array['module_type'] = $meta_assoc['module_type'];
$post_array['protocol'] = $meta_assoc['protocol'];
$post_array['action'] = 'streaming_content_add';

// This is the main loop. Runs until the lock file disappears
while (true) {

    $status = status_get();
    // We stop if the file does not exist anymore ("kill -9" simulation)
    // or the status is not set (should be open / recording / paused / stopped)
    if ($status == '') {
        die;
    }

    $m3u8_segment = (get_next($asset));
    if ($m3u8_segment !== NULL) {
        $post_array = array_merge($post_array, $m3u8_segment);

        $result = server_request_send($meta_assoc['submit_url'], $post_array);
        if (strpos($result, 'Curl error') !== false) {
            // an error occured with CURL
            file_put_contents($remoteffmpeg_basedir . "/var/curl.log", "--------------------------" . PHP_EOL . date("h:i:s") . ": [${asset}_$album] curl error occured ($result)" . PHP_EOL, FILE_APPEND);
        }
    }

    sleep(2); // 2 s
}

function get_next($asset) {
    global $remoteffmpeg_basedir;
    global $remoteffmpeg_movie_name;
    global $status;
    global $quality;
    global $module_name;
    
    static $file_index = 0;
    static $len = 0;
    static $lastpos = 0;
    static $segments_array = array();
    static $previous_status;

    $remoteffmpeg_moviesdir = get_asset_module_folder($module_name, $asset);
    
    // checks if an error occured during the recording 
    if (file_exists("$remoteffmpeg_moviesdir/${remoteffmpeg_movie_name}_" . ($file_index + 1) . "/$quality/$remoteffmpeg_movie_name.m3u8")) {
        $file_index++;
    }

    $m3u8_file = "$remoteffmpeg_moviesdir/${remoteffmpeg_movie_name}_$file_index/$quality/$remoteffmpeg_movie_name.m3u8";

    if (file_exists($m3u8_file)) {
        clearstatcache(false, $m3u8_file);
        $len = filesize($m3u8_file);
        if ($len < $lastpos) {
            //file deleted or reset
            $lastpos = $len;
        } elseif ($len > $lastpos) {
            $f = fopen($m3u8_file, "rb");
            if ($f === false)
                die();
            fseek($f, $lastpos);
            while (!feof($f)) {
                $buffer = fread($f, 4096);
                //      flush();
            }
            $lastpos = ftell($f);
            fclose($f);

            $m3u8_array = explode(PHP_EOL, $buffer);
            $array_len = count($m3u8_array);
            $m3u8_segment = array();
            $saved_index = -1;
            for ($i = 0; $i < $array_len; $i++) {
                if (strpos($m3u8_array[$i], '#EXTINF') !== false) {
                    $saved_index = $i + 1;
                }
                array_push($m3u8_segment, $m3u8_array[$i]);
                if ($saved_index == $i) {
                    $m3u8_filename = rtrim($m3u8_array[$i]);
                    $m3u8_string = implode(PHP_EOL, $m3u8_segment) . PHP_EOL;
                    
                    if ($previous_status != '' && $previous_status != $status) {
                        $m3u8_string = "#EXT-X-DISCONTINUITY" . PHP_EOL . $m3u8_string;
                    }
                    $previous_status = $status;

                    switch ($status) {
                        case 'open' :
                            $m3u8_segment = "@$remoteffmpeg_basedir/resources/videos/${quality}_init.ts";
                            break;
                        case 'paused' :
                            $m3u8_segment = "@$remoteffmpeg_basedir/resources/videos/${quality}_pause.ts";
                            break;
                        case 'stopped':
                            $m3u8_segment = "@$remoteffmpeg_basedir/resources/videos/${quality}_stop.ts";
                            break;
                        default :
                            $m3u8_segment = "@$remoteffmpeg_moviesdir/${remoteffmpeg_movie_name}_$file_index/$quality/" . $m3u8_filename;
                            break;
                    }

                    array_push($segments_array, array(
                        'm3u8_string' => $m3u8_string,
                        'm3u8_segment' => $m3u8_segment,
                        'filename' => $m3u8_filename,
                        'status' => $status
                    ));
                    $m3u8_segment = array();
                }
            }
        }
    }

    return array_shift($segments_array);
}
