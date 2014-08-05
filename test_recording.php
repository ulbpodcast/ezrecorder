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

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

define("COOKIE_FILE", "cookie.txt");
$default_classroom = 1;
$default_records = 1;
$default_pause = 1;
$default_part_duration = 30;
$default_pause_delay = 15;
$default_delay_between_records = 10;

date_default_timezone_set("Europe/Brussels");
$date = date("Y_m_d_H\hi\ms\s");

$logs_file = dirname(__FILE__) . "/var/_recorder_test_" . $date;

echo "This program is aimed to test the recorders." . PHP_EOL;
echo "First, you are going to enter some specific settings for the tests." . PHP_EOL;
echo "-------------------------------------------------------------------" . PHP_EOL . PHP_EOL;
echo "Select the classroom you want to perform the tests: " . PHP_EOL;
echo " - S-V (Recorder devl) [1]" . PHP_EOL;
echo " - S-R42-5-503         [2]" . PHP_EOL;
echo " - S-R42-5-110         [3]" . PHP_EOL;
echo " - S-UB2-147           [4]" . PHP_EOL;
echo " - S-UD2-118           [5]" . PHP_EOL;
echo " - S-K                 [6]" . PHP_EOL;
echo " - P-FORUM-B           [7]" . PHP_EOL;
echo " - E-J                 [8]" . PHP_EOL;
echo " - E-BREMER            [9]" . PHP_EOL;
echo " - E-F2-303            [10]" . PHP_EOL;
$choice = readline("Choice [default: $default_classroom]: ");
switch ($choice) {
    case 2: 
        $classroom = "podcv-s-r42-5-503";
        $recorder = 'ezcast';
        break;
    case 3: 
        $classroom = "podc-s-r42-5-110";
        $recorder = 'ezcast';
        break;
    case 4: 
        $classroom = "podcv-s-ub2";
        $recorder = 'ezcast';
        break;
    case 5: 
        $classroom = "podcv-s-ud2";
        $recorder = 'ezcast';
        break;
    case 6: 
        $classroom = "podcv-s-k";
        $recorder = 'ezcast';
        break;
    case 7: 
        $classroom = "podcv-p-forumb";
        $recorder = 'ezcast';
        break;
    case 8: 
        $classroom = "podcv-e-j";
        $recorder = 'ezcast';
        break;
    case 9: 
        $classroom = "podcv-e-bremer";
        $recorder = 'ezcast';
        break;
    case 10: 
        $classroom = "podcv-e-f2";
        $recorder = 'ezcast';
        break;
    default: 
        $classroom = "podcs-s-v";
        $recorder = 'ezrecorder';
        break;
}
$username = readline("Enter username for recorder: ");
$password = readline("Enter password for recorder: ");

$settings = false;

do {
// Login the user
    display_logs("Logins the user [$username]");
    $response = curl_read_url("http://$classroom.ulb.ac.be/$recorder/index.php?action=login&login=$username&passwd=$password");
    $response = explode("\n", $response);
    display_logs($response[1]);
    if (strpos($response[1], 'login screen') !== false) {
        display_logs('Authentication failure');
        die;
    }

    if ($settings == false) {
        $settings = true;

        $album = readline("Enter album mnemonic: ");
        echo "Select the recording format: " . PHP_EOL;
        echo " - camslide   [1]" . PHP_EOL;
        echo " - cam only   [2]" . PHP_EOL;
        echo " - slide only [3]" . PHP_EOL;
        $choice = readline("Choice [default: camslide]: ");
        switch ($choice) {
            case 2: $camslide = "cam";
                break;
            case 3: $camslide = "slide";
                break;
            default: $camslide = "camslide";
                break;
        }
        $choice = readline("Enter number of recordings [default: $default_records]: ");
        $records = is_numeric($choice) ? $choice : $default_records;
        $choice = readline("Enter number of pauses during the recording [default: $default_pause]: ");
        $pause = is_numeric($choice) ? $choice : $default_pause;
        $choice = readline("Enter duration of each part of the recording (in seconds) [default: $default_part_duration]: ");
        $part_duration = is_numeric($choice) ? $choice : $default_part_duration;
        if ($pause > 0) {
            $choice = readline("Enter delay of the pause (in seconds) [default: $default_pause_delay]: ");
            $pause_delay = is_numeric($choice) ? $choice : $default_pause_delay;
        }
        $choice = readline("With moderation (y/N) [default: y]: ");
        $moderation = (strtoupper($choice) == 'N' || strtoupper($choice) == 'NO') ? 'false' : 'true';
        if ($records > 1){
            $choice = readline("Enter delay between two recordings (in seconds) [default: $default_delay_between_records]: ");
            $delay_between_records = (is_numeric($choice) && $choice > 5) ? $choice : $default_delay_between_records;
        }

        file_put_contents($logs_file, "***********************************************************" . PHP_EOL, FILE_APPEND);
        file_put_contents($logs_file, "*              R E C O R D E R    T E S T                 *" . PHP_EOL, FILE_APPEND);
        file_put_contents($logs_file, "***********************************************************" . PHP_EOL, FILE_APPEND);
        file_put_contents($logs_file, "  Username : $username                                     " . PHP_EOL, FILE_APPEND);
        file_put_contents($logs_file, "  Album : $album                                           " . PHP_EOL, FILE_APPEND);
        file_put_contents($logs_file, "  Classroom : $classroom                                   " . PHP_EOL, FILE_APPEND);
        file_put_contents($logs_file, "  Number of recordings : $records                          " . PHP_EOL, FILE_APPEND);
        file_put_contents($logs_file, "  Number of pauses : $pause                                " . PHP_EOL, FILE_APPEND);
        file_put_contents($logs_file, "  Duration of each part : $part_duration seconds           " . PHP_EOL, FILE_APPEND);
        if ($pause > 0)
            file_put_contents($logs_file, "  Duration of pause : $pause_delay seconds                 " . PHP_EOL, FILE_APPEND);
        file_put_contents($logs_file, "  Record type : $camslide                                  " . PHP_EOL, FILE_APPEND);
        file_put_contents($logs_file, "  Moderation : $moderation                                  " . PHP_EOL, FILE_APPEND);
        file_put_contents($logs_file, "  Delay between records : $delay_between_records seconds                 " . PHP_EOL, FILE_APPEND);
        file_put_contents($logs_file, "***********************************************************" . PHP_EOL, FILE_APPEND);
        file_put_contents($logs_file, PHP_EOL, FILE_APPEND);

        $default_records = $records;
        $default_pause = $pause;
    }
// Submit values for recording
    display_logs("Submits form values for the recording");

    $response = curl_read_url("http://$classroom.ulb.ac.be/$recorder/index.php?" .
            "action=submit_record_infos" .
            "&course=$album" .
            "&title=${classroom}_${date}_" . (($default_records+1) - $records) .
            "&description=Record%3A+$default_records%0D%0APause%3A+$default_pause%0D%0APause+duration%3A+$pause_delay%0D%0APart+duration%3A+$part_duration%0D%0AType%3A+$camslide" .
            "&record_type=$camslide");

    $action = "recording_start";
    $pause = $default_pause;

    do {
// Recording start
        display_logs((($action == "recording_start") ? "Starts" : "Resumes") . " the recording [" . (($default_records+1) - $records) ."]");
        $response = curl_read_url("http://$classroom.ulb.ac.be/$recorder/index.php?" .
                "action=$action");

// Records for N seconds
        sleep($part_duration);

// Pauses the recording if required
        if ($pause > 0) {
            display_logs("Pauses the recording");
            $response = curl_read_url("http://$classroom.ulb.ac.be/$recorder/index.php?" .
                    "action=recording_pause");

// Wait for the pause
            sleep($pause_delay);
            $action = "recording_resume";
        }
        $pause--;
    } while ($pause >= 0);

// Recording stop
    display_logs("Stops the recording");
    $response = curl_read_url("http://$classroom.ulb.ac.be/$recorder/index.php?" .
            "action=view_record_submit");

// Publish in private album
    display_logs("Publishes recording in album [moderation : $moderation]");
    $response = curl_read_url("http://$classroom.ulb.ac.be/$recorder/index.php?" .
            "action=recording_stop" .
            "&moderation=$moderation");

    $records--;
    unlink(COOKIE_FILE);
    sleep($delay_between_records);
} while ($records > 0);


function curl_read_url($url) {
    global $logs_file;
    // create curl resource 
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    //return the transfer as a string 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIE_FILE);
    curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $retValue = curl_exec($ch);
    $errno = curl_errno($ch);
    // close curl resource to free up system resources 
    curl_close($ch);

  //  file_put_contents($logs_file, PHP_EOL . "**********************************************" . PHP_EOL, FILE_APPEND);
  //  file_put_contents($logs_file, $retValue, FILE_APPEND);
  //  file_put_contents($logs_file, PHP_EOL . "**********************************************" . PHP_EOL, FILE_APPEND);
    return ($errno != 0) ? false : $retValue;
}

function display_logs($txt) {
    global $logs_file;
    $msg = "[" . date("Y-m-d H:i:s") . "] " . $txt . PHP_EOL;
    echo $msg;
    file_put_contents($logs_file, $msg, FILE_APPEND);
}

?>
