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
 * - recovery check (checks whether QTB has crashed, and restart it if it hs)
 */
require_once 'config.inc';
require_once 'lib_capture.php';

// Delays, in seconds
$recovery_threshold = 20; // Threshold before we start worrying about QTB
$sleep_time = 20; // Duration of the sleep between two checks

set_time_limit(0);
fwrite(fopen($localqtb_monitoring_file, 'w'), getmypid());

// This is the main loop. Runs until the lock file disappears
while (true) {
    // QTB check
    clearstatcache();
    $files = glob("$localqtb_recorddir/$localqtb_movie_name*.mov");
    $status = capture_localqtb_status_get();

    if ($status == 'recording') {
        // Checking when was the last modif
        // (remember: quicktime broadcaster uses several qtbmovie files)
        $last_modif = 0;
        foreach ($files as $file) {
            $last_modif = max($last_modif, filemtime($file));
        }

        // Compares with current microtime
        $now = (int) microtime(true);

        if (($now - $last_modif) > $recovery_threshold) {
            system("osascript $localqtb_qtbstartmovierec");
            system("osascript $localqtb_qtbposition");
            //log_append('warning', 'Quicktime Broadcaster crashed. Recording will resume, but rendering will probably fail.');
            mail($mailto_admins, 'Quicktime Broadcaster crash', 'Quicktime Broadcaster crashed in room ' . $classroom . '. Recording will resume, but rendering will probably fail.');
            //echo "QTB stopped.\n";
        }
    }

    sleep($sleep_time);

    // We stop if the file does not exist anymore ("kill -9" simulation)
    if (!file_exists($localqtb_monitoring_file)) {
        die;
    }
}

