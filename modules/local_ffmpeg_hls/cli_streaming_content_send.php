<?php

/**
 *  This CLI script sends the TS segments for HLS video to EZmanager.
 * This script is called by capture_ffmpeg_init() in lib_capture.php
 */
require_once 'etc/config.inc';
require_once $basedir . '/lib_various.php';
require_once __DIR__.'/lib_capture.php';
require_once __DIR__.'/info.php';

Logger::$print_logs = true;

// verifies that all required parameters are correctly set
if ($argc !== 4) {
    print "Expected 3 parameters (found ".($argc - 1).") " . PHP_EOL;
    print "<course> the mnemonic of the course to be streamed " . PHP_EOL;
    print "<asset_time> Asset time" . PHP_EOL;
    print "<quality> the quality of the stream (high | low) " . PHP_EOL;
    $logger->log(EventType::RECORDER_STREAMING, LogLevel::WARNING, "Wrong arguments given: " . print_r($argv, true), array(basename(__FILE__)));
    exit(1);
}

$course = $argv[1];
$asset_time = $argv[2];
$quality = $argv[3];

$asset = get_asset_name($course, $asset_time);

// gets needed information for streaming, from the module
$meta_assoc = capture_ffmpeg_info_get('streaming', $asset);
if($meta_assoc == false) {
    $logger->log(EventType::RECORDER_STREAMING, LogLevel::ERROR, "Could not start sending streaming content, ffmpeg module returned no info", array(basename(__FILE__)), $asset);
    exit(2);
}

$post_array['course'] = $course;
$post_array['asset'] = $asset_time;
$post_array['quality'] = $quality;
$post_array['record_type'] = $meta_assoc['record_type'];
$post_array['module_type'] = $meta_assoc['module_type'];
$post_array['protocol'] = $ffmpeg_streaming_protocol;
$post_array['action'] = 'streaming_content_add';

$logger->log(EventType::RECORDER_STREAMING, LogLevel::DEBUG, "Started streaming with infos: " . print_r($post_array, true), array(basename(__FILE__)), $asset);

$start_time = time();

// This is the main loop. Runs until the status is unset
while (true) {

    $status = capture_ffmpeg_status_get();
    // We stop if the file does not exist anymore ("kill -9" simulation)
    // or the status is not set (should be open / recording / paused / stopped)
    if ($status == '' && time() > ($start_time + 5 * 60)) { //hackz, give it 5 minutes before stopping, status is not set at this point
        $logger->log(EventType::RECORDER_STREAMING, LogLevel::DEBUG, "Streaming stopped because ffmpeg module status is empty", array(basename(__FILE__)), $asset);
        exit(0);
    }

    // retrieves the next .ts segment (handles server delays)
    // $m3u8_segment is an array containing both the .ts file and usefull information about the segment
    $m3u8_segment = (get_next($asset));
    if ($m3u8_segment !== NULL) { // there is a new segment to send to the server
        $post_array = array_merge($post_array, $m3u8_segment);
        // sends a request to the server with the next .ts segment
        $result = server_request_send($ezcast_submit_url, $post_array);
        if (strpos($result, 'Curl error') !== false) {
            // an error occured with CURL
            file_put_contents($basedir . "/var/curl.log", "--------------------------" . PHP_EOL . date("h:i:s") . ": [${asset}_$album] curl error occured ($result)" . PHP_EOL, FILE_APPEND);
            static $count = 0;
            if($count % 10 == 0)
                $logger->log(EventType::RECORDER_STREAMING, LogLevel::ERROR, date("h:i:s") . ": [${asset}_$album] curl error occured ($result)", array(basename(__FILE__)), $asset);
            $count++;
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
function get_next($asset) {
    global $ffmpeg_basedir;
    global $ffmpeg_movie_name;
    global $status;
    global $quality;
    global $module_name;
    
    static $file_index = 0;
    static $len = 0;
    static $lastpos = 0;
    static $segments_array = array();
    static $previous_status;

    $ffmpeg_moviesdir = get_asset_module_folder($module_name, $asset);
    
    // checks if an error occured during the recording 
    if (file_exists("$ffmpeg_moviesdir/${ffmpeg_movie_name}_" . ($file_index + 1) . "/$quality/$ffmpeg_movie_name.m3u8")) {
        $file_index++;
    }

    $m3u8_file = "$ffmpeg_moviesdir/${ffmpeg_movie_name}_$file_index/$quality/$ffmpeg_movie_name.m3u8";

    echo $m3u8_file .PHP_EOL;
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
