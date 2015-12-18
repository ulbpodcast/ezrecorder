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

require_once dirname(__FILE__)."/../../global_config.inc";

$localqt_features = array('recording');
$localqt_ip = $ezrecorder_ip;
$localqt_username = $ezrecorder_username;

$localqt_basedir = $modules_basedir . '/local_quicktime/';
$localqt_recorddir = $ezrecorder_recorddir;
$localqt_movie_name = 'qtmovie';

$localqt_metadata_file = $localqt_basedir.'var/_metadata.xml'; // The file containing metadata of the current recording
$localqt_monitoring_file = $localqt_basedir.'var/_monitoring_pid';
$localqt_status_file = $localqt_basedir.'var/status'; // Current recorder status (paused, recording, stopped,...)
$localqt_recorder_logs = $localqt_basedir.'var/_log'; // Log file
$localqt_capture_file = $localqt_basedir.'var/screencapture.jpg'; // Camera screenshot

$localqt_upload_dir = $localqt_recorddir . 'upload_to_server/';

$localqt_script_qtcancel = $localqt_basedir.'bash/qtcancel';
$localqt_script_qtnew = $localqt_basedir.'bash/qtnew';
$localqt_script_qtrec = $localqt_basedir.'bash/qtrec';
$localqt_script_qtpause = $localqt_basedir.'bash/qtpause';
$localqt_script_qtresume = $localqt_basedir.'bash/qtresume';
$localqt_script_qtstop = $localqt_basedir.'bash/qtstop';
$localqt_script_qtfinalize = $localqt_basedir.'bash/qtfinalize';
$localqt_script_qtthumbnail = $localqt_basedir.'bash/screencapturecrop';

$localqt_record_type = 'screen'; // [cam | screen] the source for the recording
$localqt_qtnew = ($localqt_record_type == "cam") ? $localqt_basedir.'applescript/qtnewmovie.scpt' : $localqt_basedir.'applescript/qtnewscreen.scpt'; //used in cli_monitoring.php
$localqt_qtstartmovierec = $localqt_basedir.'applescript/qtstartmovierec.scpt'; //used in cli_monitoring.php
$localqt_qtposition = $localqt_basedir.'applescript/positionquicktime.scpt'; //used in cli_monitoring.php
$localqt_download_protocol = "rsync"; // if you change the protocol, you might
// have to adapt the function 'capture_localqtb_download_info_get()' in 'lib_capture.php'

?>
