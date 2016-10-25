<?php

/**
 * This script can be used to automate test to a recorder (can be used from a distant machine)
 */

define("COOKIE_FILE", "cookie.txt");
$default_user = "admin";
$default_records = 1;
$default_pause = 1;
$default_part_duration = 30;
$default_pause_delay = 15;
$default_delay_between_records = 10;
$web_path = "ezrecorder/";

date_default_timezone_set("Europe/Brussels");
$date = date("Y_m_d_H\hi\ms\s");

$logs_file = __DIR__ . "/var/_test_" . $date;
$last_page_file = __DIR__ . "/last_page_returned";
$usage = "You can either run this script in interactive mode or call it with arguments." . PHP_EOL .
         "Usage: php ".__FILE__." <classroom_address> <username> <password> <album> [<camslide> <recording_count> <pause_count> <part_duration> <pause_duration> <moderation(y/n)>]";
       
/* Args */
$classroom = false;
$username = $default_user;
$password = false;
$album = false;
$camslide = 'camslide';
$recording_count = $default_records;
$pause_count = $default_pause;
$pause_delay = $default_pause_delay;
$part_duration = $default_part_duration;
$delay_between_records = $default_delay_between_records;
$moderation = 'true';

//user didn't input any args, use interactive prompt
if($argc == 1) {
    echo "This program is aimed to test the recorders." . PHP_EOL;
    echo "$usage" . PHP_EOL;
    echo "-------------------------------------------------------------------" . PHP_EOL . PHP_EOL;
    echo "Enter the classroom address/ip on which you want to perform the test. " . PHP_EOL;
    $classroom = readline("Address: ");
    if(!$classroom) {
        echo "No classroom provided" .PHP_EOL;
        die();
    }
    
    $username = readline("Enter username for recorder [default: $default_user]: ");
    if(!$username)
        $username = $default_user;

    $password = readline("Enter password for user: ");
    if(!$password) {
        echo "No password provided" .PHP_EOL;
        die();
    }
    
    $album = readline("Enter album mnemonic: ");
    if(!$album) {
        echo "No album provided" .PHP_EOL;
        die();
    }
    
    echo "Select the recording format: " . PHP_EOL;
    echo " - camslide   [1]" . PHP_EOL;
    echo " - cam only   [2]" . PHP_EOL;
    echo " - slide only [3]" . PHP_EOL;
    $choice = readline("Choice [default: camslide]: ");
    switch ($choice) {
        case "2": 
            $camslide = "cam";
            break;
        case "3": 
            $camslide = "slide";
            break;
        case "1":
        case "" : 
            $camslide = "camslide";
            break;
        default: 
            echo "Invalid type provided" .PHP_EOL;
            die();
    }
    //fixme: invalid input will silently set the default value, this is not really user friendly
    $choice = readline("Enter number of recordings [default: $default_records]: ");
    $recording_count = is_numeric($choice) ? $choice : $default_records;
    $choice = readline("Enter number of pauses during the recording [default: $default_pause]: ");
    $pause_count = is_numeric($choice) ? $choice : $default_pause;
    $choice = readline("Enter duration of each part of the recording (in seconds) [default: $default_part_duration]: ");
    $part_duration = is_numeric($choice) ? $choice : $default_part_duration;
    if ($pause_count > 0) {
        $choice = readline("Enter delay of the pause (in seconds) [default: $default_pause_delay]: ");
        $pause_delay = is_numeric($choice) ? $choice : $default_pause_delay;
    }
    $choice = readline("With moderation (y/n) [default: y]: ");
    $moderation = (strtoupper($choice) == 'N' || strtoupper($choice) == 'NO') ? 'false' : 'true';
    if ($recording_count > 1){
        $choice = readline("Enter delay between two recordings (in seconds) [default: $default_delay_between_records]: ");
        $delay_between_records = (is_numeric($choice) && $choice > 5) ? $choice : $default_delay_between_records;
    } else {
            $delay_between_records = $default_delay_between_records;
    }    
} else { 
    if($argc < 5) {
        echo $usage . PHP_EOL;
        return 1;
    }
    
    $classroom = $argv[1];
    $username = $argv[2];
    $password = $argv[3];
    $album = $argv[4];
    
    $count = 5;
    if($argc > $count)
        $camslide = $argv[$count++];

    if($argc > $count)
        $recording_count = $argv[$count++];
    if($argc > $count)
        $pause_count = $argv[$count++];
    if($argc > $count)
        $part_duration = $argv[$count++];
    if($argc > $count)
        $pause_delay = $argv[$count++];
    if($argc > $count)
        $delay_between_records = $argv[$count++];
}

$curl_url = "http://$classroom/$web_path/index.php";


file_put_contents($logs_file, "***********************************************************" . PHP_EOL, FILE_APPEND);
file_put_contents($logs_file, "*              R E C O R D E R    T E S T                 *" . PHP_EOL, FILE_APPEND);
file_put_contents($logs_file, "***********************************************************" . PHP_EOL, FILE_APPEND);
file_put_contents($logs_file, "  Username : $username                                     " . PHP_EOL, FILE_APPEND);
file_put_contents($logs_file, "  Album : $album                                           " . PHP_EOL, FILE_APPEND);
file_put_contents($logs_file, "  Classroom : $classroom                                   " . PHP_EOL, FILE_APPEND);
file_put_contents($logs_file, "  Number of recordings : $recording_count                  " . PHP_EOL, FILE_APPEND);
file_put_contents($logs_file, "  Number of pauses : $pause_count                          " . PHP_EOL, FILE_APPEND);
file_put_contents($logs_file, "  Duration of each part : $part_duration seconds           " . PHP_EOL, FILE_APPEND);
if ($pause_count > 0)
    file_put_contents($logs_file, "  Duration of pause : $pause_delay seconds                 " . PHP_EOL, FILE_APPEND);
file_put_contents($logs_file, "  Record type : $camslide                                  " . PHP_EOL, FILE_APPEND);
file_put_contents($logs_file, "  Moderation : $moderation                                  " . PHP_EOL, FILE_APPEND);
file_put_contents($logs_file, "  Delay between records : $delay_between_records seconds                 " . PHP_EOL, FILE_APPEND);
file_put_contents($logs_file, "***********************************************************" . PHP_EOL, FILE_APPEND);
file_put_contents($logs_file, PHP_EOL, FILE_APPEND);

function get_autotest_page($curl_response) {
    
    //print_r($curl_response);
    $known_pages = array("autotest_record_form", "autotest_login_screen", "autotest_record_screen", "autotest_record_submit", "submitted");
    foreach($known_pages as $page) {
        //echo "Checking for $page" . PHP_EOL;
        if (strpos($curl_response, $page) != false) {
            //echo "OK, found page $page" . PHP_EOL;
            return $page;
        }
    }
    
    return "unknown";
}

function logout() {
   global $curl_url;
    
   curl_read_url("$curl_url?action=recording_force_quit"); 
   curl_read_url("$curl_url?action=logout"); 
}

function login() {
    global $curl_url;
    global $username;
    global $password;
    
    $response = curl_read_url("$curl_url?action=login&login=$username&passwd=$password");
    $page = get_autotest_page($response);
    if($page != "autotest_record_form") {
        test_log("Authentication failure or wrong page returned. Desired page was autotest_record_form, recorder returned $page");
        exit(1);
    }
}

function submit_init_form() {
    global $curl_url;
    global $album;
    global $default_records;
    global $recording_count;
    global $default_pause;
    global $pause_delay;
    global $part_duration;
    global $camslide;
    global $classroom;
    global $date;
    
    $response = curl_read_url("$curl_url?" .
            "action=submit_record_infos" .
            "&course=$album" .
            "&title=${classroom}%0A-%0A${date}%0A-%0A" . (($default_records+1) - $recording_count) .
            "&description=Record%3A+$default_records%0D%0APause%3A+$default_pause%0D%0APause+duration%3A+$pause_delay%0D%0APart+duration%3A+$part_duration%0D%0AType%3A+$camslide" .
            "&record_type=$camslide");
            
    $page = get_autotest_page($response);
    if($page != "autotest_record_screen") {
        test_log("Init failure or wrong page returned. Desired page was autotest_record_screen, recorder returned $page");
        exit(2);
    }

}

function stop() {
    global $curl_url;
    
    $response = curl_read_url("$curl_url?action=view_press_stop");
    $page = get_autotest_page($response);
    if($page != "autotest_record_submit") {
        test_log("Stop failure or wrong page returned. Desired page was autotest_record_submit, recorder returned $page");
        exit(5);
    }
}

function publish() {
    global $curl_url;
    global $moderation;
    
    $response = curl_read_url("$curl_url?action=stop_and_publish" .
            "&moderation=$moderation");
    $page = get_autotest_page($response);
    if($page != "autotest_record_submitted") {
        test_log("Stop failure or wrong page returned. Desired page was autotest_record_submitted, recorder returned $page");
        exit(6);
    }
}

// TEST START
do {
    // logout the user just to be sure, else we may endup on the wrong page on connexion
    logout();
    
    // Login the user
    test_log("Login");
    login();

    // Submit values for recording
    test_log("Submits form values for the recording");
    submit_init_form();
    
    $action = "recording_start";

    do {
        // Recording start
        test_log((($action == "recording_start") ? "Starts" : "Resumes") . " the recording [" . (($default_records+1) - $recording_count) ."]");
        $response = curl_read_url("$curl_url?" .
                "action=$action");
            
        // Records for N seconds
        sleep($part_duration);

        // Pauses the recording if required
        if ($pause_count > 0) {
            test_log("Pauses the recording");
            $response = curl_read_url("$curl_url?action=recording_pause");
            $page = get_autotest_page($response);
            
            // Wait for the pause
            sleep($pause_delay);
            $action = "recording_resume";
        }
        $pause_count--;
    } while ($pause_count >= 0);

    // Recording stop
    test_log("Stops the recording");
    stop();
    
    // Publish in public/private album
    test_log("Publishes recording in album [moderation : $moderation]");
    publish();
    
    $recording_count--;
    unlink(COOKIE_FILE);
    sleep($delay_between_records);
} while ($recording_count > 0);

test_log("Finished");

function curl_read_url($url) {
    global $last_page_file;
    
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

    unlink($last_page_file);
    file_put_contents($last_page_file, "URL: $url" . PHP_EOL . PHP_EOL, FILE_APPEND);
    file_put_contents($last_page_file, $retValue, FILE_APPEND);
    return ($errno != 0) ? false : $retValue;
}

function test_log($txt) {
    global $logs_file;
    $msg = "[" . date("Y-m-d H:i:s") . "] " . $txt . PHP_EOL;
    echo $msg;
    file_put_contents($logs_file, $msg, FILE_APPEND);
}
