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
 *  This CLI script performs various monitoring tasks. It is started when the user starts a recording, and stopped when they stop recording.
 * This script is called by qtbstart and qtbstop.
 * Current checks performed:
 * - timeout check (checks whether the user has forgotten to stop recording, and publish the recording if they did)
 * - recovery check (checks whether QTB has crashed, and restart it if it hs)
 */

/**
 * Timeout check:
 * For the first "threshold" seconds (typically 2 or 3 hours), we decide to trust the user.
 * After that, we check that there has been activity at least once every "timeout" seconds (typically 15 min).
 * This program is meant to be run as a crontask at least once every "timeout" seconds
 */

require_once 'config.inc';
require 'lib_tools.php';

// Delays, in seconds
$recovery_threshold = 20; // Threshold before we start worrying about QTB
$sleep_time = 20; // Duration of the sleep between two checks

set_time_limit(0);
fwrite(fopen($monitoring_file, 'w'), getmypid());

// This is the main loop. Runs until the lock file disappears
while(true) {
    $status = status_get();
    
    // QTB check
    clearstatcache();
    $files = glob("$remoteqtb_recorddir/$remoteqtb_movie_name*.mov");
    
    if($status == 'recording') {
        // Checking when was the last modif
        // (remember: quicktime broadcaster uses several qtbmovie files)
        $last_modif = 0;
        foreach($files as $file) {
            $last_modif = max($last_modif, filemtime($file));
        }
        // Compares with current microtime
        $now = (int) microtime(true);

        if(($now - $last_modif) > $recovery_threshold) {
            system("osascript $qtbstartmovierec");
            system("osascript $qtbposition");
            //log_append('warning', 'Quicktime Broadcaster crashed. Recording will resume, but rendering will probably fail.');
            mail($mailto_admins, 'Quicktime Broadcaster crash', 'Quicktime Broadcaster crashed in room '.$classroom.'. Recording will resume, but rendering will probably fail.');
            //echo "QTB stopped.\n";
        }
    }
    
    $status = status_get();
    
    sleep($sleep_time);
    
    // We stop if the file does not exist anymore ("kill -9" simulation)
    if(!file_exists($monitoring_file)) {
        die;
    }
}

function status_get(){
        global $status_file;

    if (!file_exists($status_file))
        return '';

    return trim(file_get_contents($status_file));
}

function status_set($status) {
    global $status_file;

    file_put_contents($status_file, $status);
}

?>
