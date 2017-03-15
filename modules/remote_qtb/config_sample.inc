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
 * Every var name should be preceded by the 'remoteqtb' prefix to avoid conflict 
 * in web_index.php (two different vars with same name)
 */
require_once __DIR__."/../../global_config.inc";

$remoteqtb_features = array('recording');
$remoteqtb_ip = "127.0.0.1"; // ip address of the remote recorder
$remoteqtb_download_protocol = "rsync";
$remoteqtb_username = "podclient"; // username on the remote recorder

$remote_basedir = "/path/to/ezrecorder"; // basedir on the remote recorder
$remoteqtb_basedir = "$remote_basedir/remote_modules/remote_qtb/"; // remote_qtb module on the remote recorder
$remoteqtb_local_basedir = $modules_basedir . '/remote_qtb/'; // basedir on this machine
$remoteqtb_recorddir = '~/Movies/';
$remoteqtb_upload_dir = $remoteqtb_recorddir . '/upload_to_server/';

$remoteqtb_status_file = $remoteqtb_basedir . 'var/status'; // Current recorder status (paused, recording, stopped,...)
$remoteqtb_recorder_logs = $remoteqtb_basedir . 'var/_log'; // Log file

$remoteqtb_capture_file = $remoteqtb_local_basedir.'var/screencapture.jpg'; // Camera screenshot
$remoteqtb_capture_tmp_file = $remoteqtb_local_basedir.'var/tmp_screencapture.jpg'; // Camera screenshot

$remoteqtb_processing_tool = "ffmpeg"; // [ffmpeg | pcastaction]
$remoteqtb_processing_tools = array("ffmpeg", "pcastaction");

$remoteqtb_script_qtbcancel = $remoteqtb_basedir . 'bash/qtbcancel';
$remoteqtb_script_qtbnew = $remoteqtb_basedir . 'bash/qtbnew';
$remoteqtb_script_qtbrec = $remoteqtb_basedir . 'bash/qtbrec';
$remoteqtb_script_qtbpause = $remoteqtb_basedir . 'bash/qtbpause';
$remoteqtb_script_qtbresume = $remoteqtb_basedir . 'bash/qtbresume';
$remoteqtb_script_qtbstop = $remoteqtb_basedir . 'bash/qtbstop';
$remoteqtb_script_qtbfinalize = $remoteqtb_basedir . 'bash/qtbfinalize';
$remoteqtb_script_qtbthumbnail = $remoteqtb_basedir . 'bash/screencapturecrop';

// put some data in a specified file
$remote_script_datafile_set = $remoteqtb_local_basedir . 'bash/dataset';
// get data from a specified file
$remote_script_datafile_get = $remoteqtb_local_basedir . 'bash/dataget';
$remote_script_call = $remoteqtb_local_basedir . 'bash/remotecall';
$remote_script_thumbnail_create = $remoteqtb_local_basedir . 'bash/thumbnailcreate';
