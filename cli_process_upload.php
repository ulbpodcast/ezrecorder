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
 * This is a CLI script that launches the local processing of the recordings 
 * and sends a request to podman to download the recordings
 * Usage: cli_process_upload.php $serialized_meta
 * 
 */

require_once 'global_config.inc';

require_once $cam_lib;
require_once $slide_lib;
require_once $session_lib;

$fct = "session_" . $session_module . "_metadata_get";
$meta_assoc = $fct();


$record_date = $meta_assoc['record_date'];
$course_name = $meta_assoc['course_name'];
$record_type = $meta_assoc['record_type'];

$asset = $record_date . '_' . $course_name;
$tmp_dir = "$basedir/var/$asset/";
if (!file_exists($tmp_dir . "/metadata.xml")) {
    echo "Error: metadata file $basedir/var/$asset/metadata.xml does not exist" . PHP_EOL;
  //  die;
}

$fct = "session_" . $session_module . "_metadata_delete";
$fct();

// Stopping and releasing the recorder
// if cam module is enabled
if ($cam_enabled) {
    $fct = 'capture_' . $cam_module . '_process';
    $fct($meta_assoc, $cam_pid);
}
// if slide module is enabled
if ($slide_enabled) {
    $fct = 'capture_' . $slide_module . '_process';
    $fct($meta_assoc, $slide_pid);
}

// wait for local processing to finish
while (is_process_running($cam_pid) || is_process_running($slide_pid)){
    sleep(0.5); 
}

system("echo \"`date` : local processing finished for both cam and slide modules\" >> /Library/ezcast_recorder/var/finish");



////call EZcast server and tell it a recording is ready to download

$nb_retry=500;


if ($cam_enabled) {
    // get downloading information required by the podcast server
    $fct = 'capture_' . $cam_module . '_download_info_get';
    $cam_download_info = $fct($asset);
}

if ($slide_enabled) {
    // get downloading information required by the podcast server
    $fct = 'capture_' . $slide_module . '_download_info_get';
    $slide_download_info = $fct($asset);
} 

//try repeatedly to call EZcast server and send the right post parameters
$err=true;
while($err && $nb_retry>0){
  $err=server_start_download($record_type, $record_date, $course_name, $cam_download_info, $slide_download_info);
  if($err){
    print "Will retry later: Error connecting to EZcast server ($ezcast_submit_url). curl error: $err \n";
    sleep(120);
 }//endif error
}//end while

if($err){
    print "Giving up: Error connecting to EZcast server ($ezcast_submit_url). curl error: $err \n";
    sleep(120);
 }


/**
 *
 * @param <slide|cam|camslide> $recording_type
 * @param <YYYY_MM_DD_HHhmm> $recording_date
 * @param <mnemonique> $course_name
 * @param <associative_array> $cam_download_info information relative to the downloading of cam movie
 * @param <associative_array> $slide_download_info information relative to the downloading of slide movie
 * @return false|error_code
 */
function server_start_download($record_type,$record_date,$course_name, $cam_download_info, $slide_download_info){
//tells the server that a recording is ready to be downloaded
global $ezcast_submit_url;
global $tmp_dir;
global $recorder_version;

$post_array['record_type']=$record_type;
$post_array['record_date']=$record_date;
$post_array['course_name']=$course_name;

$post_array['metadata_file'] = $tmp_dir."/metadata.xml";

if (isset($cam_download_info) && count($cam_download_info) > 0){
    $post_array['cam_info'] = serialize($cam_download_info);
}

if (isset($slide_download_info) && count($slide_download_info) > 0){
    $post_array['slide_info'] = serialize($slide_download_info);
}

if (isset($recorder_version) && !empty($recorder_version)) {
    $post_array['recorder_version'] = $recorder_version;
}

$ch=curl_init($ezcast_submit_url);
curl_setopt($ch,CURLOPT_POST, 1);//activate POST parameters
curl_setopt($ch,CURLOPT_POSTFIELDS, $post_array);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);//don't send answer to stdout but in returned string
$res=curl_exec($ch);
$curlinfo=curl_getinfo($ch);
curl_close($ch);

if(!$res){//error
  if(isset ($curlinfo['http_code']))
      return $curlinfo['http_code'];
    else
      return "Curl error";
 }

 //All went well send http response in stderr to be logged
 fputs(STDERR, "curl result: $res", 2000);
 
 return false;
}



// determines if a process is running or not
function is_process_running($pid) {
    if (!isset($pid) || $pid == '' || $pid == 0)
        return false;
    exec("ps $pid", $output, $result);
    return count($output) >= 2;
}

?>
