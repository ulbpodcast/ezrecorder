<?php include("../common/visca.php"); 

//include "../common/D70limits.php";
?>
<html>
<head><title>Sony <?php echo $CAM_MODEL; ?>web interface - Informational mode</title></head>
<body>
<H1>Sony <?php echo $CAM_MODEL; ?> Camera - Web Interface</H1>
<small><a href="../index.html" target="_top">Back to list of web
interfaces</a></small><br>

<?php
  openSocket($fp); 
  sendCommand($fp, "get_power", $answer,$ret1,$ret2,$ret3);
    echo "<H3>Power: ";
    echo ($ret1 == 0) ? "On" : "Off";
    echo "</H3>";
  if ($ret1 != 1) {//Power On
  sendCommand($fp, "get_keylock", $answer,$ret1,$ret2,$ret3);  
    echo "<H3>Keylock: ";
    echo ($ret1 == 1) ?"On":"Off";
    echo "</H3>";
  sendCommand($fp, "get_id", $answer,$ret1,$ret2,$ret3);
    echo "<H3>Camera ID: ";
    echo $ret1;
    echo "</H3>";  
  sendCommand($fp, "get_videosystem", $answer,$ret1,$ret2,$ret3);
    echo "<H3>Video System: ";
    if ($ret1 == 0) echo "NTSC";
    if ($ret1 == 1) echo "PAL";  
    echo "</H3>";  
  sendCommand($fp, "get_memory", $answer,$ret1,$ret2,$ret3);
    echo "<H3>Current Memory Position: ";
    echo $ret1+1;  
    echo " out of [1..6]</H3>";    
  echo "<hr>";
  sendCommand($fp, "get_zoom_value", $answer,$ret1,$ret2,$ret3);
    echo "<H3>Zoom: ";
    echo $ret1;  
    echo " out of [$zoom_min..$zoom_max]</H3>";
  sendCommand($fp, "get_pantilt_position", $answer,$ret1,$ret2,$ret3);
    echo "<H3>Pan/Tilt Position: ";
    echo $ret1." / ".$ret2;  
    echo " out of [$pan_min..$pan_max] / [$tilt_min..$tilt_max]</H3>";
  sendCommand($fp, "get_pantilt_maxspeed", $answer,$ret1,$ret2,$ret3);
    echo "<H3>Pan/Tilt Maximum Speed: ";
    echo $ret1." / ".$ret2;
     echo " out of [$pan_speed_min..$pan_speed_max] / [$tilt_speed_min..$tilt_speed_max]</H3>";
    echo "</H3>";  
  sendCommand($fp, "get_focus_auto", $answer,$ret1,$ret2,$ret3);
    echo "<H3>Autofocus: ";
    echo ($ret1 == 1) ?"On":"Off";
    echo "</H3>";
  sendCommand($fp, "get_focus_value", $answer,$ret1,$ret2,$ret3);
    echo "<H3>Focus: ";
    echo $ret1;
    echo " out of [$focus_min..$focus_max]</H3>";
  echo "<hr>";
  sendCommand($fp, "get_backlight_comp", $answer,$ret1,$ret2,$ret3);
    echo "<H3>Backlight Compensation: ";
    echo ($ret1 == 1) ?"On":"Off";
    echo "</H3>";
  sendCommand($fp, "get_whitebal_mode", $answer,$ret1,$ret2,$ret3);
    echo "<H3>Whitebalance: ";
    if ($ret1 == 0) echo "Auto";
    if ($ret1 == 1) echo "Indoor";
    if ($ret1 == 2) echo "Outdoor";
    if ($ret1 == 3) echo "OnePush";
    echo "</H3>";  
  sendCommand($fp, "get_auto_exp_mode", $answer,$ret1,$ret2,$ret3);  
    echo "<H3>Exposure Mode: ";
    if ($ret1 == 0) echo "Full Auto";
    if ($ret1 == 3) echo "Manual";
    if ($ret1 == 10) echo "Shutter Priority";
    if ($ret1 == 11) echo "Iris Priority";
    if ($ret1 == 13) echo "Bright Mode";
    echo "</H3>";  
  sendCommand($fp, "get_shutter_value", $answer,$ret1,$ret2,$ret3);
    echo "<H3>Shutter Speed: ";
    echo $ret1;    
    echo " out of [$shutter_speed_min (1/60) ... $shutter_speed_max (1/10000)]</H3>";
  sendCommand($fp, "get_iris_value", $answer,$ret1,$ret2,$ret3);
    echo "<H3>Iris: ";
    echo $ret1;    
    echo " out of [$iris_min: closed .. $iris_max]</H3>";
  sendCommand($fp, "get_gain_value", $answer,$ret1,$ret2,$ret3);
    echo "<H3>Gain: ";
    echo $ret1;    
    echo " out of [$gain_min: +0dB .. $gain_max: +18dB]</H3>";
  echo "<hr>";
  sendCommand($fp, "get_atmd_mode", $answer,$ret1,$ret2,$ret3);
    echo "<H3>ATMD Mode: ";
    if ($ret1 == 0) echo "Normal Mode";
    if ($ret1 == 1) echo "AT Mode";
    if ($ret1 == 2) echo "MD Mode";
    echo "</H3>";
  $atmd=$ret1;
  if($atmd == 1) { //AT Mode
  sendCommand($fp, "get_at_mode", $answer,$ret1,$ret2,$ret3);
    echo "<H3>AT mode: ";
    echo $ret1;    
    echo " (use D30 command reference to decode)</H3>";
  sendCommand($fp, "get_at_entry", $answer,$ret1,$ret2,$ret3);
    echo "<H3>AT Entry: ";
    echo $ret1;    
    echo " out of [0..3]</H3>";
  sendCommand($fp, "get_at_obj_pos", $answer,$ret1,$ret2,$ret3);
    echo "<H3>AT Object Status: ";
    if ($ret3 == 0) echo "Setting";
    if ($ret3 == 1) echo "Tracking";
    if ($ret3 == 2) echo "Lost";
    echo "</H3>";
    echo "<H3>AT Object Position: ";
    echo $ret1." / ".$ret2;
    echo " (center of detection frame, divided by 48x30 pixels)</H3>";
  sendCommand($fp, "get_datascreen", $answer,$ret1,$ret2,$ret3);
    echo "<H3>Datascreen: ";
    echo ($ret1 == 1) ?"On":"Off";
    echo "</H3>";
  } else if($atmd == 2) { //MD Mode    
  sendCommand($fp, "get_md_mode", $answer,$ret1,$ret2,$ret3);
    echo "<H3>MD Mode: ";
    echo $ret1;
    echo " (use D30 command reference to decode)</H3>";
  sendCommand($fp, "get_md_ylevel", $answer,$ret1,$ret2,$ret3);
    echo "<H3>MD Y-Level: ";
    echo $ret1;    
    echo "out of [0 .. 15]</H3>";
  sendCommand($fp, "get_md_huelevel", $answer,$ret1,$ret2,$ret3);
    echo "<H3>MD Hue-Level: ";
    echo $ret1;    
    echo "out of [0 .. 15]</H3>";
  sendCommand($fp, "get_md_size", $answer,$ret1,$ret2,$ret3);
    echo "<H3>MD Size-Level: ";
    echo $ret1;    
    echo "out of [0 .. 15]</H3>";
  sendCommand($fp, "get_md_disptime", $answer,$ret1,$ret2,$ret3);
    echo "<H3>MD Disptime-Level: ";
    echo $ret1;
    echo "out of [0 .. 15]</H3>";
  sendCommand($fp, "get_md_refmode", $answer,$ret1,$ret2,$ret3);
    echo "<H3>MD Refresh-Mode: ";
    echo $ret1;
    echo "out of [0 .. 2]</H3>";
  sendCommand($fp, "get_md_reftime", $answer,$ret1,$ret2,$ret3);
    echo "<H3>MD Refresh-Time: ";
    echo $ret1;    
    echo "out of [0 .. 15]</H3>";
  sendCommand($fp, "get_md_obj_pos", $answer,$ret1,$ret2,$ret3);
    echo "<H3>MD Object Status: ";
    if ($ret3 == 1) echo "UnDetected";
    if ($ret3 == 2) echo "Detected";
    echo "</H3>";
    echo "<H3>MD Object Position: ";
    echo $ret1." / ".$ret2;
    echo " (center of detection frame, divided by 48x30 pixels)</H3>";
  sendCommand($fp, "get_datascreen", $answer,$ret1,$ret2,$ret3);
    echo "<H3>Datascreen: ";
    echo ($ret1 == 1) ?"On":"Off";
    echo "</H3>";
  }//end MD Mode
  }//end Power On
  closeSocket($fp);
?>
</body>
</html>
