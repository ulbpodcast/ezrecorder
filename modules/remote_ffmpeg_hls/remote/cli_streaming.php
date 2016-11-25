<?php

/**
 *  This CLI script handles streaming related actions.
 * It opens and closes the connection to EZmanager
 */
require_once __DIR__.'/lib_tools.php';
require_once __DIR__.'/config.inc';
require_once __DIR__.'/../../../global_config.inc';
require_once "$basedir/lib_various.php";
require_once __DIR__."/../info.php";

Logger::$print_logs = true;

if ($argc !== 2) {
    print "Expected 1 parameter (found $argc) " . PHP_EOL;
    print "<action> the action to be done " . PHP_EOL;
    return;
}

$action = $argv[1];

switch ($action) {
    case 'init' :
        streaming_init();
        break;
    case 'close' :
        streaming_close();
        break;
}

function streaming_init() {
    global $remoteffmpeg_streaming_info;
    global $remoteffmpeg_cli_streaming;
    global $php_cli_cmd;
    global $logger;
    
    // init the streamed asset
    $post_array = xml_file2assoc_array($remoteffmpeg_streaming_info);
    if ($post_array['module_quality'] == 'none') return false;
    
    $post_array['action'] = 'streaming_init';
    $result = server_request_send($post_array["submit_url"], $post_array);

    if (strpos($result, 'Curl error') !== false) { 
        // an error occured with CURL
        unlink($remoteffmpeg_streaming_info);
    }
    $result = unserialize($result);

    $course = $post_array['course'];
    $asset_time = $post_array['asset'];
    
    $asset = get_asset_name($course, $asset_time);
    
    // executes the command for sending TS segments to EZmanager in background
    // for low and high qualities
    $return_val_high = 0;
    $return_val_low = 0;
    $start_high = (strpos($post_array['module_quality'], 'high') !== false);
    $start_low = (strpos($post_array['module_quality'], 'low') !== false);
    
    if(!$start_high && !$start_low) {
         $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::ERROR, "No valid module quality found, overriding with low quality", array(__FUNCTION__), $asset);
         $start_low = true;
    }
    
    if ($start_high)
        system("$php_cli_cmd $remoteffmpeg_cli_streaming $course $asset_time high > /dev/null 2>&1 &", $return_val_high);    
    
    if ($start_low)
        system("$php_cli_cmd $remoteffmpeg_cli_streaming $course $asset_time low > /dev/null 2>&1 &", $return_val_low);
    
    if($return_val_high != 0 || $return_val_low != 0) {
        $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::ERROR, "Failed to start at least one background process. High return code: $return_val_high. Low return code: $return_val_low.", array(__FUNCTION__), $asset);
        return false;
    }
    
    $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::ERROR, "Started background streaming processes for qualities: high $start_high | low $start_low ", array(__FUNCTION__), $asset);
    
    return true;
}

function streaming_close() {
    global $remoteffmpeg_streaming_info;
    global $logger;
    
    // init the streamed asset
    $post_array = xml_file2assoc_array($remoteffmpeg_streaming_info);
    $post_array['action'] = 'streaming_close';
    $result = server_request_send($post_array["submit_url"], $post_array);

    unlink($remoteffmpeg_streaming_info);
    if (strpos($result, 'Curl error') !== false) {
        // an error occured with CURL
         $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::ERROR, "Failed to close streaming. Curl returned: $result", array(__FUNCTION__));
    
    }
    $result = unserialize($result);
}