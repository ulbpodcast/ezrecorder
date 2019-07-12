<?php

/** All static variables of ezrecorder should go here
 *  This files is included AT HE BEGINNING of global_config.inc, values here can't depend on config
 *
 *  */


$recorder_version = "2.0"; //used as communication with server protocol version
$basedir = __DIR__ . '/';

$modules_basedir = "$basedir/modules/";

$ffmpeg_script_merge_movies = "$basedir/cli_merge_movies.php";
$move_asset_script = "$basedir/cli_move_asset.php";
$recorder_logs = "$basedir/var/_log"; // Log file
$recorder_session = "$basedir/var/session";
$recorder_monitoring_pid = "$basedir/var/_timeout_monitoring_pid";
$nopic_file = "$basedir/nopic.jpg";

//portable script to emulate "timeout" behavior
// for linux: timeout2.sh
$timeout_script = "$basedir/timeout2.sh";

// cli script for  uploading to server
$cli_upload = "$basedir/cli_upload_to_server.php";
// cli script for recording processing. Auto start $cli_upload afterwards
$cli_post_process = "$basedir/cli_post_process.php";
// cli script for monitoring the timeout on a recording
$cli_timeout_monitoring = "$basedir/cli_timeout_monitoring.php";

// logging
$database_file = "$basedir/var/db.sqlite";
$cam_file_name = "cam.mov";
$slide_file_name = "slide.mov";

$dir_date_format = "Y_m_d_H\hi_s\s";// ls friendly date to create directories/files

$template_folder = 'tmpl/';

$session_class = "SessionSQLite";
