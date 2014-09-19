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

// used by cli_monitoring.php when a recording has timed out
$classroom = 'Classroom name';
$ezrecorder_url = "http://recorder.domain";
$remoteqtb_force_quit_url = "$ezrecorder_url/index.php?action=recording_force_quit";
$mailto_admins = 'ezrecorder@your.domain';
$remoteqtb_basedir = dirname(__FILE__).'/';
$remoteqtb_recorddir = '~/Movies/';
$remoteqtb_movie_name = "qtbmovie";
$metadata_file = $remoteqtb_basedir . 'var/_metadata.xml';
$monitoring_file = $remoteqtb_basedir . 'var/_monitoring_pid';
$status_file = $remoteqtb_basedir . 'var/status'; // Current recorder status (paused, recording, stopped,...)
$time_started_file = $remoteqtb_basedir . 'var/_first_started'; // The file containing timestamp of creation
$last_request_file = $remoteqtb_basedir . 'var/_last_request'; // Time of this file says when the last refresh happened

$qtbstartmovierec = $remoteqtb_basedir . 'applescript/qtbstartmovierec.scpt';
$qtbposition = $remoteqtb_basedir . 'applescript/positionqtb.scpt';

$remoteqtb_capture_file = $remoteqtb_basedir . 'var/pic_new.jpg';
$remoteqtb_script_qtbpause = $remoteqtb_basedir . 'bash/qtbpause';
$remoteqtb_script_qtbstop = $remoteqtb_basedir . 'bash/qtbstop';
$remoteqtb_mono = true; // determines whether the input audio source is mono or stereo
$ffmpegpath = "/usr/local/bin/ffmpeg";
$php_path = "/usr/bin/php";
?>
