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

/* ---------- GLOBAL SETTINGS ----------- */
$classroom = "Classroom name"; // classroom name
$recorder_version = "2.0";

$ezrecorder_ip = "127.0.0.1"; // static ip used by EZcast remote server 
$ezrecorder_username = "podclient"; // user name used to launch bash scripts
$ezrecorder_recorddir = "/Users/".$ezrecorder_username."/Movies/"; // basedir for the local video storage

 // Project URI
$basedir = "/Library/ezrecorder/"; // Path to EZrecorder basedir 
$web_basedir = "/Library/WebServer/Documents/ezrecorder/"; // Web server URI
$ezrecorder_web_user = "_www"; // Apache's username
$modules_basedir = "$basedir/modules/";

// cli script for recording processing and uploading to server
$process_upload = "$basedir/cli_process_upload.php";
// cli script for monitoring the timeout on a recording
$cli_timeout_monitoring = "$basedir/cli_timeout_monitoring.php"; 

$ezcast_submit_url = "http://your.ezcast.server/ezmanager/recorder/index.php"; // URL to EZmanager server
$mailto_admins = "ezrecorder@your.domain"; // Email address aimed to receive EZrecorder alerts
$php_cli_cmd = "/usr/bin/php"; // PHP binary
$ffmpeg_cli_cmd = "/usr/local/bin/ffmpeg"; //FFMPEG binary

$nopic_file = "$basedir/nopic.jpg";

$recorder_logs = "$basedir/var/_log"; // Log file
$recorder_session = "$basedir/var/session";
$recorder_monitoring_pid = "$basedir/var/_timeout_monitoring_pid";

$dir_date_format = "Y_m_d_H\hi";// ls friendly date to create directories/files
$title_max_length = 70; // Max. number of characters

/* ---------------- MODULES ---------------- */

// cam management (PTZ)
$cam_management_enabled = false;
$cam_management_module = "axis_ptz";
$cam_management_lib = "$basedir/modules/axis_cam_management/lib_cam.php";
$cam_management_views_dir = "ptzposdir/";
$cam_screen_scene = 'ecran';
$cam_default_scene = 'chaire';

// cam recording
$cam_enabled = true;
$cam_module = "localqtb";
$cam_lib = "$basedir/modules/local_qtb/lib_capture.php";

// slide recording
$slide_enabled = true;
$slide_module = "remoteqtb";
$slide_lib = "$basedir/modules/remote_qtb/lib_capture.php";

// session data
$session_module = "xml";
$session_lib = "$basedir/modules/recording_session/lib_recording_session.php";

// authentication
$auth_module = "file";
$auth_lib = "$basedir/modules/auth_file/lib_auth.php";



?>
