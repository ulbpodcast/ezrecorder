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
 * Every var name should be preceded by the 'remotefmle' prefix to avoid conflict 
 * in web_index.php (two different vars with same name)
 */
require_once dirname(__FILE__)."/../../global_config.inc";

$remotefmle_features = array('recording');
$remotefmle_ip = "127.0.0.1"; // ip address of the remote recorder
$remotefmle_download_protocol = "rsync";
$remotefmle_username = "podclient"; // username on the remote recorder

$remote_basedir = "/path/to/ezrecorder"; // basedir on the remote recorder
$remotefmle_basedir = "$remote_basedir/remote_modules/remote_fmle_regular/"; // remote_fmle module on the remote recorder
$remotefmle_local_basedir = dirname(__FILE__)."/"; // basedir on this machine
$remotefmle_recorddir = '~/Movies/';
$remotefmle_upload_dir = $remotefmle_recorddir . '/upload_to_server/';

$remotefmle_status_file = $remotefmle_basedir . 'var/status'; // Current recorder status (paused, recording, stopped,...)
$remotefmle_recorder_logs = $remotefmle_basedir . 'var/_log'; // Log file

$remotefmle_capture_file = $remotefmle_local_basedir.'var/screencapture.jpg'; // Camera screenshot
$remotefmle_capture_tmp_file = $remotefmle_local_basedir.'var/tmp_screencapture.jpg'; // Camera screenshot

$remotefmle_processing_tool = "ffmpeg"; // [ffmpeg | pcastaction]
$remotefmle_processing_tools = array("ffmpeg", "pcastaction");

$remotefmle_script_cancel = $remotefmle_basedir . 'bash/fmle_cancel';
$remotefmle_script_init = $remotefmle_basedir . 'bash/fmle_init';
$remotefmle_script_start = $remotefmle_basedir . 'bash/fmle_start';
$remotefmle_script_action = $remotefmle_basedir . 'bash/fmle_action';
$remotefmle_script_stop = $remotefmle_basedir . 'bash/fmle_stop';
$remotefmle_script_finalize = $remotefmle_basedir . 'bash/fmle_finalize';
$remotefmle_script_thumbnail = $remotefmle_basedir . 'bash/fmle_thumbnail';

// put some data in a specified file
$remote_script_datafile_set = $remotefmle_local_basedir . 'bash/dataset';
// get data from a specified file
$remote_script_datafile_get = $remotefmle_local_basedir . 'bash/dataget';
$remote_script_call = $remotefmle_local_basedir . 'bash/remotecall';
$remote_script_thumbnail_create = $remotefmle_local_basedir . 'bash/thumbnailcreate';
?>
