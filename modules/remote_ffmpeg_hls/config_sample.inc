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
 * Every var name should be preceded by the 'remoteffmpeg' prefix to avoid conflict 
 * in web_index.php (two different vars with same name)
 */
require_once __DIR__."/../../global_config.inc";

$remoteffmpeg_features = array('recording', 'streaming');
$remoteffmpeg_ip = "127.0.0.1"; // ip address of the remote recorder
$remoteffmpeg_download_protocol = "rsync";
$remoteffmpeg_streaming_protocol = "http";
$remoteffmpeg_username = "podclient"; // username on the local recorder
$remoteffmpeg_streaming_quality = "highlow"; // quality enabled for live stream (highlow | high | low | none) 

$remote_basedir = "/path/to/ezrecorder"; // basedir on the remote recorder
$remoteffmpeg_basedir = "/Library/ezrecorder/modules/remote_ffmpeg_hls/remote/"; // remote_ffmpeg_hls module on the remote recorder
$remoteffmpeg_local_basedir = dirname(__FILE__).'/'; // basedir on this machine
$remoteffmpeg_recorddir = '~/Movies/'; //path on remote recorder
$remoteffmpeg_upload_dir = $remoteffmpeg_recorddir . '/upload_to_server/';

$remoteffmpeg_capture_file = $remoteffmpeg_local_basedir.'var/screencapture.jpg'; // Camera screenshot
$remoteffmpeg_capture_tmp_file = $remoteffmpeg_local_basedir.'var/tmp_screencapture.jpg'; // Camera screenshot
$remoteffmpeg_capture_transit_file = $remoteffmpeg_local_basedir.'var/transit_screencapture.jpg'; // Camera screenshot

$remoteffmpeg_processing_tool = "ffmpeg"; // [ffmpeg | pcastaction]
$remoteffmpeg_processing_tools = array("ffmpeg");

//remote path
$remoteffmpeg_script_cancel = $remoteffmpeg_basedir . 'bash/ffmpeg_cancel';
$remoteffmpeg_script_init = $remoteffmpeg_basedir . 'bash/ffmpeg_init';
$remoteffmpeg_script_start = $remoteffmpeg_basedir . 'bash/ffmpeg_start';
$remoteffmpeg_script_cutlist = $remoteffmpeg_basedir . 'bash/ffmpeg_cutlist';
$remoteffmpeg_script_stop = $remoteffmpeg_basedir . 'bash/ffmpeg_stop';
$remoteffmpeg_script_finalize = $remoteffmpeg_basedir . 'bash/ffmpeg_finalize';
$remoteffmpeg_script_install_config = $remoteffmpeg_basedir . 'cli_install.php';

$remoteffmpeg_status_file = $remoteffmpeg_basedir . 'var/status'; // Current recorder status (paused, recording, stopped,...)
$remoteffmpeg_rec_status_file = $remoteffmpeg_basedir . 'var/rec_status'; // Current recorder status (paused, recording, stopped,...)
$remoteffmpeg_streaming_info = $remoteffmpeg_basedir.'var/streaming.xml';
$remoteffmpeg_config1 = $remoteffmpeg_basedir.'config.inc'; //used to check config existence
$remoteffmpeg_config2 = $remoteffmpeg_basedir.'bash/localdefs'; //used to check config existence

// put some data in a specified file
$remote_script_datafile_set = $remoteffmpeg_local_basedir . 'bash/dataset';
// get data from a specified file
$remote_script_datafile_get = $remoteffmpeg_local_basedir . 'bash/dataget';
$remote_script_call = $remoteffmpeg_local_basedir . 'bash/remotecall';
$remote_script_thumbnail_create = $remoteffmpeg_local_basedir . 'bash/thumbnailcreate';