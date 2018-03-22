<?php

include_once("../common/visca.php");
$ptzposdir="./ptzposdir";

function get_ptz_posnames(){
    global $ptzposdir;
$ptznames=array();
if (is_dir($ptzposdir)) {
    if ($dh = opendir($ptzposdir)) {
        while (($file = readdir($dh) ) !== false) {
           if(substr($file,-4)==".ptz"){
               //its a ptz file so put it in the list
               $name=str_replace(".ptz","",$file);
               array_push($ptznames, $name);
           }
        }
        closedir($dh);
    }
}
return $ptznames;
}

function save_ptz_pos($name){
    global $ptzposdir,$imagesnap_cmd;
    //transform name into a safe filename
 $filename=str_toalnum($name);
 $ptzfilepath=$ptzposdir."/".$filename.".ptz";
//first get actual position

 openSocket($sock);
  sendCommand($sock, "get_zoom_value", $answer1,$zoom,$ret2,$ret3);
  //$ret1 zoom
  sendCommand($sock, "get_pantilt_position", $answer2,$pan,$tilt,$ret3);

  $zoom=trim($zoom);
  $pan=trim($pan);
  $tilt=trim($tilt);
 
 // list($pan,$tilt,$zoom)=array(1,2,3);
  if($anwser1!="OK" && $answer2!="OK"){
      //print "saving ptz $name to $ptzfilepath\n";
    $fp=fopen($ptzfilepath, "w");
    if($fp){
      fwrite($fp, "$pan,$tilt,$zoom");
      fclose($fp);
      //try to save a picture of the position
      $picmtime=filemtime("../cam_snaps/screencapture.jpg");
      if(time()-$picmtime<5){
       //preview picture is recent to save it along the .ptz file
       copy("../cam_snaps/screencapture.jpg", "$ptzposdir/$filename.jpg");
       print "$name saved with a picture\n";
      }
      else{
       //old pic -> imagesnap not launch in repetitive move
       //try take one snap
//       system("$imagesnap_cmd /Library/WebServer/Documents/visca/cam_snaps/pic.jpg");
        sleep(3);
        $picmtime=filemtime("../cam_snaps/screencapture.jpg");
        if(time()-$picmtime<5){
          //preview picture is recent to save it along the .ptz file
          copy("../cam_snaps/screencapture.jpg", "$ptzposdir/$filename.jpg");
          print "$name saved with a picture\n";
         }
         else {
          print "$name saved without picture\n";
          }
         return 0;
    }
    }
    else return -1;
  }
closeSocket($sock);
}

function delete_ptz_pos($name){
  global $ptzposdir;
  //remove coordinate file
  $filepath=$ptzposdir."/".$name.".ptz";
  if(is_file($filepath))unlink($filepath);
  //remove image file
   $filepath=$ptzposdir."/".$name.".jpg";
  if(is_file($filepath))unlink($filepath);
}

function goto_ptz_pos($name){
    global $ptzposdir,$zoom_max;
    include "speed.inc"; //get actual speed in $speed variable
    $string=file($ptzposdir."/".$name.".ptz");
    list($pan,$tilt,$zoom)=explode(",", $string[0]);
    print "pan:$pan tilt:$tilt zoom:$zoom\n";
    openSocket($sock);
     $speedcalc = $speed - floor($ret1 / ($zoom_max/$speed));
    $speedstring = " ".$speedcalc." ".$speedcalc;
    $pan_speed=15;
    $tilt_speed=15;
     $answer=sendCommand($sock, "set_zoom_value 0", $answer,$res1,$res2,$res3);
    $answer=sendCommand($sock, "set_pantilt_absolute_position $pan_speed $tilt_speed $pan $tilt",$res1,$res2,$res3,$res4);
     $answer=sendCommand($sock, "set_zoom_value $zoom", $answer,$res1,$res2,$res3);
   
closeSocket($sock);
}


function str_toalnum($string){
  $toalnum="";
  for($idx=0;$idx<strlen($string);$idx++)
    if(ctype_alnum($string[$idx]))
     $toalnum.=$string[$idx];
     else
     $toalnum.="_";
  return $toalnum;
}
?>
