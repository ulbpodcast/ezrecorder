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

// used by cli_monitoring.php when a recording has timed out
$remotefmle_basedir = dirname(__FILE__).'/';
$remotefmle_recorddir = '~/Movies/';
$remotefmle_movie_name = "fmlemovie";
$remotefmle_monitoring_file = $remotefmle_basedir . 'var/_monitoring_pid';
$remotefmle_rec_status_file = $remotefmle_basedir . 'var/rec_status';
$remotefmle_status_file = $remotefmle_basedir . 'var/status';

$remotefmle_open = $remotefmle_basedir . '/applescript/fmle_open.scpt';
$remotefmle_action = $remotefmle_basedir . '/applescript/fmle_action.scpt';

$remotefmle_capture_file = $remotefmle_basedir . 'var/pic_new.jpg';
$ffmpegpath = "/usr/local/bin/ffmpeg";
$remotefmle_mono = true; // determines whether the input audio source is mono or stereo
?>
