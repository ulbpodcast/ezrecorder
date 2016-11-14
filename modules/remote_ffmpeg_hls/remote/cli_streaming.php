<?php

/**
 *  This CLI script handles streaming related actions.
 * It opens and closes the connection to EZmanager
 */
require_once __DIR__.'/lib_tools.php';
require_once __DIR__.'/config.inc';
require_once __DIR__.'/../../../global_config.inc';
require_once "$basedir/lib_various.php";

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

    $album = $post_array['album'];
    $asset = get_asset_name($post_array['asset'], $post_array['album']); //album or course name ?
    
    // executes the command for sending TS segments to EZmanager in background
    // for low and high qualities
    if (strpos($post_array['module_quality'], 'high') !== false)
        system("$php_cli_cmd $remoteffmpeg_cli_streaming $album $asset high > /dev/null &");    
    if (strpos($post_array['module_quality'], 'low') !== false)
        system("$php_cli_cmd $remoteffmpeg_cli_streaming $album $asset low > /dev/null &");
}

function streaming_close() {
    global $remoteffmpeg_streaming_info;
    global $remoteffmpeg_cli_streaming;

    // init the streamed asset
    $post_array = xml_file2assoc_array($remoteffmpeg_streaming_info);
    $post_array['action'] = 'streaming_close';
    $result = server_request_send($post_array["submit_url"], $post_array);

    unlink($remoteffmpeg_streaming_info);
    if (strpos($result, 'Curl error') !== false) {
        // an error occured with CURL
    }
    $result = unserialize($result);
}