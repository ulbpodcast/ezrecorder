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
 *  This CLI script handles streaming related actions.
 * It opens and closes the connection to EZmanager
 */
require_once 'lib_tools.php';
require_once 'config.inc';
require_once '../../../global_config.inc';
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

    // executes the command for sending TS segments to EZmanager in background
    // for low and high qualities
    if (strpos($post_array['module_quality'], 'high') !== false)
        exec("$php_cli_cmd $remoteffmpeg_cli_streaming " . $post_array['album'] . " " . $post_array['asset'] . " high > /dev/null &", $output, $errno);    
    if (strpos($post_array['module_quality'], 'low') !== false)
        exec("$php_cli_cmd $remoteffmpeg_cli_streaming " . $post_array['album'] . " " . $post_array['asset'] . " low > /dev/null &", $output, $errno);
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
?>

