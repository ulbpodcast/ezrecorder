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
 *  This CLI script sends the TS segments for HLS video to EZmanager.
 * This script is called by capture_ffmpeg_init() in lib_capture.php
 */
require_once 'config.inc';
require_once $basedir . '/lib_various.php';
require_once 'lib_capture.php';

// verifies that all required parameters are correctly set
if ($argc !== 4) {
    print "Expected 3 parameters (found $argc) " . PHP_EOL;
    print "<course> the mnemonic of the course to be streamed " . PHP_EOL;
    print "<record_date> the record date of the current recording (YYYY_MM_DD_HHhMM)" . PHP_EOL;
    print "<quality> the quality of the stream (high | low) " . PHP_EOL;
    return;
}

$album = $argv[1];
$asset = $argv[2];
$quality = $argv[3];

// gets needed information for streaming, from the module
$meta_assoc = capture_ffmpeg_info_get('streaming', $asset . '_' . $album);
$post_array['album'] = $album;
$post_array['asset'] = $asset;
$post_array['quality'] = $quality;
$post_array['record_type'] = $meta_assoc['record_type'];
$post_array['module_type'] = $meta_assoc['module_type'];
$post_array['protocol'] = $ffmpeg_streaming_protocol;
$post_array['action'] = 'streaming_content_add';

// This is the main loop. Runs until the status is unset
while (true) {

    $status = capture_ffmpeg_status_get();
    // We stop if the file does not exist anymore ("kill -9" simulation)
    // or the status is not set (should be open / recording / paused / stopped)
    if ($status == '') {
        die;
    }

    // retrieves the next .ts segment (handles server delays)
    // $m3u8_segment is an array containing both the .ts file and usefull information about the segment
    $m3u8_segment = (get_next());
    if ($m3u8_segment !== NULL) { // there is a new segment to send to the server
        $post_array = array_merge($post_array, $m3u8_segment);
        // sends a request to the server with the next .ts segment
        $result = server_request_send($ezcast_submit_url, $post_array);
        if (strpos($result, 'Curl error') !== false) {
            // an error occured with CURL
            file_put_contents($basedir . "/var/curl.log", "--------------------------" . PHP_EOL . date("h:i:s") . ": [${asset}_$album] curl error occured ($result)" . PHP_EOL, FILE_APPEND);
        }
    }

    sleep(2); // 2 s
}

/**
 * saves the .ts segments in a static array
 * The function reads the m3u8 file. When there is a new content in the file,
 * it is parsed and saved in the array. Usualy, there is only one .ts segment
 * that is added to the file between 2 calls. But it happens that several 
 * .ts segments are added between 2 calls. In that case, the function pushes
 * several entries in the static array. 
 * The static array is used as FIFO (first in, first out). The function appends
 * the new .ts segments at the end of the array and return the first entry.
 * @global type $ffmpeg_basedir
 * @global type $ffmpeg_moviesdir
 * @global type $ffmpeg_movie_name
 * @global type $status
 * @global type $quality
 * @staticvar int $file_index
 * @staticvar int $len
 * @staticvar int $lastpos
 * @staticvar array $segments_array
 * @return type
 */
function get_next() {
    global $ffmpeg_basedir;
    global $ffmpeg_moviesdir;
    global $ffmpeg_movie_name;
    global $status;
    global $quality;
    static $file_index = 0;
    static $len = 0;
    static $lastpos = 0;
    static $segments_array = array();
    static $previous_status;

    // checks if an error occured during the recording 
    if (file_exists("$ffmpeg_moviesdir/${ffmpeg_movie_name}_" . ($file_index + 1) . "/$quality/$ffmpeg_movie_name.m3u8")) {
        $file_index++;
    }

    $m3u8_file = "$ffmpeg_moviesdir/${ffmpeg_movie_name}_$file_index/$quality/$ffmpeg_movie_name.m3u8";

    if (file_exists($m3u8_file)) {
        clearstatcache(false, $m3u8_file);
        // verifies that the m3u8 file has been modified
        $len = filesize($m3u8_file);
        if ($len < $lastpos) {
            //file deleted or reset
            $lastpos = $len;
        } elseif ($len > $lastpos) {
            // reads the file from the last position
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

            // parses the new content of the m3u8 file
            $m3u8_array = explode(PHP_EOL, $buffer);
            $array_len = count($m3u8_array);
            $m3u8_segment = array();
            $saved_index = -1;
            for ($i = 0; $i < $array_len; $i++) {
                // loops on the lines of the new content to save each .ts segment
                // separately in the segments array
                if (strpos($m3u8_array[$i], '#EXTINF') !== false) {
                    // at the next line, we must push the current segment
                    // in the segments array
                    $saved_index = $i + 1;
                }
                // adds the line in the current segment
                array_push($m3u8_segment, $m3u8_array[$i]);
       
                if ($saved_index == $i) {
                    // we must push the current segment in the segments array
                    $m3u8_filename = rtrim($m3u8_array[$i]);
                    // the content of m3u8 file
                    $m3u8_string = implode(PHP_EOL, $m3u8_segment) . PHP_EOL;
                    
                    if ($previous_status != '' && $previous_status != $status){
                        $m3u8_string = "#EXT-X-DISCONTINUITY" . PHP_EOL . $m3u8_string;
                    }
                    $previous_status = $status;

                    // adapts the .ts segment to deliver, according to the current 
                    // status of the recorder
                    switch ($status) {
                        case 'open' :
                            $m3u8_segment = "$ffmpeg_basedir/resources/videos/${quality}_init.ts";
                            break;
                        case 'paused' :
                            $m3u8_segment = "$ffmpeg_basedir/resources/videos/${quality}_pause.ts";
                            break;
                        case 'stopped':
                            $m3u8_segment = "$ffmpeg_basedir/resources/videos/${quality}_stop.ts";
                            break;
                        default :
                            $m3u8_segment = "$ffmpeg_moviesdir/${ffmpeg_movie_name}_$file_index/$quality/" . $m3u8_filename;
                            break;
                    }
                    $php_version = explode('.', phpversion());
                    $php_version = ($php_version[0] * 10000 + $php_version[1] * 100 + $php_version[2]);
                    if ($php_version >= 50500){
                        // uses new class CURLFile instead of deprecated @ notation
                        $m3u8_segment = new CURLFile($m3u8_segment);
                    } else {
                        $m3u8_segment = '@' . $m3u8_segment;
                    }
                    // pushes the current segment in the segments array
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

    // returns the first segment from the array
    return array_shift($segments_array);
}


?>
