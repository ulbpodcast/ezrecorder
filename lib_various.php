<?php

/**
 * Resizes an image and adds a status on it
 * @param type $input source image
 * @param type $output destination file
 * @param type $maxwidth 
 * @param type $maxheight
 * @param type $status could be a path to a file containing the status if status_file is set to true;
 * the status [recording / pending / error / ...] otherwise
 * @param type $status_file $status expects a path to a file if true; a status if false
 */
function image_resize($input, $output, $maxwidth, $maxheight, $status, $status_file = true) {

    $img_path = array();
    $img_path['broadcasting'] = dirname(__FILE__) . '/img/broadcasting.png';
    $img_path['connection'] = dirname(__FILE__) . '/img/connection.png';
    $img_path['error'] = dirname(__FILE__) . '/img/error.png';
    $img_path['pending'] = dirname(__FILE__) . '/img/pending.png';

    $img = imagecreatefromjpeg($input);
//or imagecreatefrompng,imagecreatefromgif,etc. depending on user's uploaded file extension

    $width = imagesx($img); //get width and height of original image
    $height = imagesy($img);

//determine which side is the longest to use in calculating length of the shorter side, since the longest will be the max size for whichever side is longest.    
    if ($height > $width) {
        $ratio = $maxheight / $height;
        $newheight = $maxheight;
        $newwidth = $width * $ratio;
        $writex = round(($maxwidth - $newwidth) / 2);
        $writey = 0;
    } else {
        $ratio = $maxwidth / $width;
        $newwidth = $maxwidth;
        $newheight = $height * $ratio;
        $writex = 0;
        $writey = round(($maxheight - $newheight) / 2);
    }

    $newimg = imagecreatetruecolor($maxwidth, $maxheight);

//Since you probably will want to set a color for the letter box do this
//Assign a color for the letterbox to the new image, 
//since this is the first call, for imagecolorallocate, it will set the background color
//in this case, black rgb(0,0,0)
    imagecolorallocate($newimg, 0, 0, 0);

    $palsize = ImageColorsTotal($img);  //Get palette size for original image
    for ($i = 0; $i < $palsize; $i++) { //Assign color palette to new image
        $colors = ImageColorsForIndex($img, $i);
        ImageColorAllocate($newimg, $colors['red'], $colors['green'], $colors['blue']);
    }

    imagecopyresized($newimg, $img, $writex, $writey, 0, 0, $newwidth, $newheight, $width, $height);

    if ($status_file) {
        if (file_exists($status)) {
            $cam_status = file_get_contents($status_file);
        }
    } else {
        $cam_status = $status;
    }

    switch ($cam_status) {
        case "recording":
            $img_status = imagecreatefrompng($img_path['broadcasting']);
            break;
        case "connection problem":
            $img_status = imagecreatefrompng($img_path['connection']);
            break;
        case "stopped":
            $img_status = imagecreatefrompng($img_path['error']);
            break;
        case "pending":
            $img_status = imagecreatefrompng($img_path['pending']);
            break;
    }
    imagecopymerge($newimg, $img_status, 5, 130, 0, 0, 225, 25, 75);

    imagejpeg($newimg, $output); //$output file is the path/filename where you wish to save the file.  
//Have to figure that one out yourself using whatever rules you want.  Can use imagegif() or imagepng() or whatever.
}

/**
 *
 * @param <type> $assoc_array
 * @return <xml_string>
 * @desc takes an assoc array and transform it in a xml metadata file
 */
function assoc_array2xml_file($assoc_array, $metadata_file) {
    global $logger;
    
    $xmlstr = "<?xml version='1.0' standalone='yes'?>\n<metadata>\n</metadata>\n";
    $xml = new SimpleXMLElement($xmlstr);
    foreach ($assoc_array as $key => $value) {
        $xml->addChild($key, $value);
    }
    $xml_txt = $xml->asXML();
    $result = file_put_contents($metadata_file, $xml_txt);
    if($result == false) {
        print_r(debug_backtrace());
        $logger->log(EventType::TEST, LogLevel::ERROR, "Couldn't write metadata file $metadata_file: $xml_txt", array("assoc_array2xml_file"));
        return false;
    }

    chmod($metadata_file, 0644);
    return true;
}

function xml_file2assoc_array($meta_path) {
    $xml = simplexml_load_file($meta_path);
    if ($xml === false)
    {
        global $debug_mode;
        if($debug_mode)
            print_r(debug_backtrace());
        return false;
    }
    $assoc_array = array();
    foreach ($xml as $key => $value) {
        $assoc_array[$key] = (string) $value;
    }
    return $assoc_array;
}

// sends an associative array to a server via CURL
// return true on success, error http code on failure
function server_request_send($server_url, $post_array) {
    global $logger;
    global $basedir;

    $ch = curl_init($server_url);
    curl_setopt($ch, CURLOPT_POST, 1); //activate POST parameters
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_array);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //don't send answer to stdout but in returned string
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,30); 
    curl_setopt($ch, CURLOPT_TIMEOUT, 6000); //timeout in seconds
    $res = curl_exec($ch);

    $curlinfo = curl_getinfo($ch);
    curl_close($ch);
    file_put_contents("$basedir/var/curl.log", var_export($curlinfo, true) . PHP_EOL . $res, FILE_APPEND);
    if (!$res) {//error
        $logger->log(EventType::RECORDER_REQUEST_TO_MANAGER, LogLevel::ERROR, "Curl failed to POST data to $server_url", array("cli_upload_to_server", "server_request_send"));

        if (isset($curlinfo['http_code'])) {
            return "Curl error : " . $curlinfo['http_code'];
        } else
            return "Curl error";
    }
    
    $logger->log(EventType::RECORDER_REQUEST_TO_MANAGER, LogLevel::DEBUG, "server_request_send $server_url, result= $res", array("cli_upload_to_server", "server_request_send"));

    //All went well send http response in stderr to be logged
    //fputs(STDERR, "curl result: $res", 2000);

    return $res;
}

// determines if a process is running or not
function is_process_running($pid) {
    if (!isset($pid) || $pid == '' || $pid == 0)
        return false;
    exec("ps $pid", $output, $result);
    return count($output) >= 2;
}

function get_pid_from_file($filePath) {
    $handle = fopen($filePath, "r");
    if($handle == false)
        return 0;
    
    $pid = fgets($handle);
    fclose($handle);
    return $pid;
}

function debug_to_console($data) {
    if(is_array($data) || is_object($data)) {
            echo("<script>console.log('PHP: ".json_encode($data)."');</script>");
    } else {
            echo("<script>console.log('PHP: ".$data."');</script>");
    }
}

function get_asset_name($course_name, $record_date) {
    return $record_date . '_' . $course_name;
}

/* step == "upload" or "local_processing" or "" 
    Empty step will return first found
 *  */
function get_asset_dir($asset, $step = '') {
    if ($step != 'upload' && $step != 'local_processing' && $step != '')
        return false;

    switch ($step) {
        case "upload":
            return get_upload_to_server_dir($asset);
        case "local_processing":
            return get_local_processing_dir($asset);
        default:
            $dir = get_upload_to_server_dir($asset);
            if(!file_exists($dir))
                $dir = get_local_processing_dir($asset);
            
            if(!file_exists($dir))
                return false;
            
            return $dir;
    }
}

function get_local_processing_dir($asset = '') {
    global $ezrecorder_recorddir;

    return $ezrecorder_recorddir . '/local_processing/' . $asset . '/';
}

function get_upload_to_server_dir($asset = '') {
    global $ezrecorder_recorddir;

    return $ezrecorder_recorddir . '/upload_to_server/' . $asset . '/';
}

// @returns <slide|cam|camslide>
function get_record_type($cam, $slide) {
    if ($cam && $slide)
        return "camslide";
    elseif ($cam)
        return "cam";
    elseif ($slide)
        return "slide";
    else
        return false;
}
