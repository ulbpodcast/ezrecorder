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
 * This file is executed as background task. 
 * It verifies if the record has been paused more than 15 secondes ago
 * and relaunches the recording. 
 */
include_once 'lib_capture.php';
include_once 'config.inc';


if ($argc != 2) {
    echo "usage: " . $argv[0] . " <meta_assoc> <recording_ids_file_path>\n";
    echo "        where <asset> is the current recording date_album\n";
    die;
}

$asset = $argv[1];

$tmp_dir = capture_axiscam_tmpdir_get($asset);
$last_record = capture_axiscam_last_record_get();

$pause_time = file_get_contents("$tmp_dir/paused");
unlink("$tmp_dir/paused");
$pause_duration = time() - $pause_time;
$to_wait = ($axiscam_pause_duration - $pause_duration) < 0 ? 0 : $axiscam_pause_duration - $pause_duration;
if ($to_wait > 0){
    sleep($to_wait);
}

$url = "http://$axiscam_username:$axiscam_password@$axiscam_ip/axis-cgi/io/virtualinput.cgi?action=$axiscam_input_nb:/";
curl_read_url($url);

file_put_contents($tmp_dir . "/_monitoring_log", "******************************************************" . PHP_EOL, FILE_APPEND);
file_put_contents($tmp_dir . "/_monitoring_log", "* [" . date("Y-m-d H:i:s") . "] Recording resumed  " . PHP_EOL, FILE_APPEND);
file_put_contents($tmp_dir . "/_monitoring_log", "*     - Pause duration: $pause_duration seconds              " . PHP_EOL, FILE_APPEND);
file_put_contents($tmp_dir . "/_monitoring_log", "*     - Waited before resume: $to_wait seconds" . PHP_EOL, FILE_APPEND);
file_put_contents($tmp_dir . "/_monitoring_log", "******************************************************" . PHP_EOL, FILE_APPEND);


system("$php_cli_cmd $axiscam_cli_monitoring $asset ". $last_record ." > 2>&1 /dev/null &");
