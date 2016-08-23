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
$remoteffmpeg_basedir = __DIR__.'/';
$remoteffmpeg_recorddir = '~/Movies/';
$remoteffmpeg_moviesdir = $remoteffmpeg_recorddir.'ffmpeg_hls';
$remoteffmpeg_movie_name = "ffmpegmovie";
$remoteffmpeg_monitoring_file = $remoteffmpeg_basedir . 'var/_monitoring_pid';
$remoteffmpeg_rec_status_file = $remoteffmpeg_basedir . 'var/rec_status';
$remoteffmpeg_status_file = $remoteffmpeg_basedir . 'var/status';
$remoteffmpeg_streaming_info = $remoteffmpeg_basedir . 'var/streaming.xml';
$remoteffmpeg_cli_streaming = $remoteffmpeg_basedir . 'cli_streaming_content_send.php';

$remoteffmpeg_input_source = "avfoundation"; // specifies the input source (IntensityShuttle | UltraStudioMiniRecorder | ...)
// you might have to adapt the 'bash/ffmpeg_init' script