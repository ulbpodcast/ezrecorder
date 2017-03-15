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

require_once __DIR__.'/../../global_config.inc';

$session_basedir = $modules_basedir . '/recording_session/';
$metadata_file = $session_basedir . 'var/metadata.xml'; // Where to temporarily store the metadata
$lock_file = $session_basedir . 'var/_current_user'; // Exclusive lock file
$recstarttime_file = $session_basedir . 'var/recstarttime'; // The file containing the start time and album
$initstarttime_file = $session_basedir . 'var/initstarttime'; // The file containing the moment when the recording has been init
$last_request_file = $session_basedir . 'var/last_request'; // The time of the last request from user
