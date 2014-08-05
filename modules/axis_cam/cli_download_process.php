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
 * This file is executed as background task. It downloads
 * and processes the recording from the ip cam
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
$meta_assoc = axiscam_xml_file2assoc_array("$tmp_dir/_metadata.xml");

$records_ids_str = trim(file_get_contents("$tmp_dir/_records_ids"));
$records_ids = array_unique(explode(PHP_EOL, $records_ids_str));
file_put_contents("$tmp_dir/_records_ids", implode(PHP_EOL, $records_ids));

// downloads the movies from the IP camera and saves them in a tmp dir as zip archives
foreach ($records_ids as $record_id) {
    capture_axiscam_record_download($record_id, $asset);
}


$cmd = 'sudo -u ' . $axiscam_module_username . ' ' . $axiscam_script_process . ' ' . $meta_assoc['course_name'] . ' ' . $meta_assoc['record_date'] . ' >> ' . $axiscam_recorder_logs;
log_append('recording', "launching command: $cmd");
system($cmd);
?>
