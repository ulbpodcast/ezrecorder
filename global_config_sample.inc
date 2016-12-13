<?php

// Dev: Change this value everytime you add an option to this file, as well as the value in common.inc. When version in config and common.inc don't match, a warning is triggered.
$config_version = "20161003"; 

/* ---------- GLOBAL SETTINGS ----------- */
$classroom = "Classroom name"; // classroom name
$recorder_version = "2.0"; //used as communication with server protocol version

$ezrecorder_ip = "127.0.0.1"; // static ip used by EZcast remote server
$ezrecorder_username = "podclient"; // user name used to launch bash scripts
$ezrecorder_recorddir = "~/Movies/"; // basedir for the local video storage
$remote_recorder_ip = "";
$remote_recorder_username = "";

//list of supported timezones: http://php.net/manual/en/timezones.europe.php
$recorder_timezone = "Europe/Brussels";
date_default_timezone_set($recorder_timezone);

 // Project URI
$basedir = __DIR__ . '/';
$web_basedir = "/Library/WebServer/Documents/ezrecorder/"; // Web server URI
$ezrecorder_web_user = "_www"; // Apache's username

$ezcast_manager_url = "http://your.ezcast.server/ezmanager/";
$mailto_admins = "ezrecorder@your.domain"; // Email address aimed to receive EZrecorder alerts
$php_cli_cmd = "/usr/bin/php"; // PHP binary
$ffmpeg_cli_cmd = "/usr/local/bin/ffmpeg"; //FFMPEG binary

$dir_date_format = "Y_m_d_H\hi";// ls friendly date to create directories/files
$title_max_length = 70; // Max. number of characters

/* ---------------- MODULES ---------------- */

// cam management (PTZ)
$cam_management_enabled = false;
$cam_management_module = "onvif_ptz";
$cam_management_lib = "$basedir/modules/onvif_cam_management/lib_cam.php";
$cam_management_views_dir = "ptzposdir/";
$cam_screen_scene = 'screen';
$cam_default_scene = 'default';

// cam recording
$cam_enabled = true;
$cam_module = "ffmpeg";
$cam_lib = "$basedir/modules/local_ffmpeg_hls/lib_capture.php";

// slide recording
$slide_enabled = false;
$slide_module = "remoteffmpeg";
$slide_lib = "$basedir/modules/remote_ffmpeg_hls/lib_capture.php";

// session data
$session_module = "xml";
$session_lib = "$basedir/modules/recording_session/lib_recording_session.php";

// authentication
$auth_module = "file";
$auth_lib = "$basedir/modules/auth_file/lib_auth.php";

// debugging
$debug_mode = false; //debug mode print recorder events and php errors to console
$send_debug_logs_to_server = false; //by default, events of type debug are not sent to ezcast

$enable_vu_meter = true;


$modules_basedir = "$basedir/modules/";

global $in_install;
//load static variables
if(!isset($in_install)) {
    require_once(__DIR__ . '/common.inc');
}

