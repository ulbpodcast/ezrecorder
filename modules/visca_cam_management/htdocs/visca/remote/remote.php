<?php 
  include_once "../common/visca.php";
  include_once "ptzpos.php";

?>
<html>
<head>
<title>Sony <?php echo $CAM_MODEL; ?> web interface</title>
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="expires" content="0">
</head>
<body>
<H1>Sony <?php echo $CAM_MODEL; ?> Camera - Web Interface - Remote Control mode</H1>
<small><a href="../index.html" target="_top">
Back to list of web interfaces</a></small><br>

<?php 

if (isset($_GET['goto'])){
 goto_ptz_pos($_GET['goto']);
}

if (isset($_GET['speed'])) { //write speed to file
  $fp=fopen("speed.inc","w"); 
  flock($fp,2);
  fputs($fp,"<?php \$speed=".$_GET['speed']."; ?>");
  flock($fp,3);
  fclose($fp);
}
$speed=10;
include("speed.inc"); 

if (isset($_GET['button'])) {
  $button = $_GET['button'];
  openSocket($fp);
  if (strcmp($button, "power") == 0) {
    sendCommand($fp, "get_power", $answer, $ret1, $ret2, $ret3);  
    if ($ret1 == 0) {
      $command = "set_power 0";
    } else {
      $command = "set_power 1";
    }
  } else if (strcmp($button, "backlight") == 0) {
    sendCommand($fp, "get_backlight_comp", $answer, $ret1, $ret2, $ret3);  
    if ($ret1 == 0) {
      $command = "set_backlight_comp 1";
    } else {
      $command = "set_backlight_comp 0";
    }
  } else if (strcmp($button, "far") == 0) {
    clearstatcache();
    if (file_exists("focusfar")) {
      $command = "set_focus_stop";
      unlink("focusfar");
    } else if (file_exists("focusnear")) {    
      $command = "set_focus_far";
      unlink("focusnear");
      touch("focusfar");    
    } else {
      $command = "set_focus_far";
      touch("focusfar");    
    }
  } else if (strcmp($button, "near") == 0) {
    clearstatcache();
    if (file_exists("focusnear")) {
      $command = "set_focus_stop";
      unlink("focusnear");
    } else if (file_exists("focusfar")) {    
      $command = "set_focus_near";
      unlink("focusfar");
      touch("focusnear");    
    } else {
      $command = "set_focus_near";
      touch("focusnear");    
    }
  } else if (strcmp($button, "manualfocus") == 0) {
    $command = "set_focus_auto 0";
  } else if (strcmp($button, "autofocus") == 0) {
    $command = "set_focus_auto 1";
  } else if (strcmp($button, "atonoff") == 0) {
    $command = "set_at_mode_onoff";
  } else if (strcmp($button, "offset") == 0) {
    $command = "set_at_frameoffset_onoff";
  } else if (strcmp($button, "atentry") == 0) {
    sendCommand($fp, "get_at_entry", $answer, $ret1, $ret2, $ret3);  
    if ($ret1 == 0) {
      $command = "set_at_entry 1";
    } else if ($ret1 == 1) {
      $command = "set_at_entry 2";
    } else if ($ret1 == 2) {
      $command = "set_at_entry 3";
    } else if ($ret1 == 3) {
      $command = "set_at_entry 0";
    }
  } else if (strcmp($button, "atchase") == 0) {
    $command = "set_at_chase_next";
  } else if (strcmp($button, "atae") == 0) {
    $command = "set_at_ae_onoff";
  } else if (strcmp($button, "ataz") == 0) {
    $command = "set_at_autozoom_onoff";
  } else if (strcmp($button, "mdonoff") == 0) {
    $command = "set_md_mode_onoff";
  } else if (strcmp($button, "mdframe") == 0) {
    $command = "set_md_frame";
  } else if (strcmp($button, "mddetect") == 0) {
    $command = "set_md_detect";
  } else if (strcmp($button, "datascreen") == 0) {
    $command = "set_datascreen_onoff";
  } else if (strcmp($button, "startstop") == 0) {
    $command = "set_atmd_startstop";
  } else if (strcmp($button, "framedisplay") == 0) {
    $command = "set_atmd_framedisplay_onoff";
  } else if (strcmp($button, "mem1") == 0) {
    if (file_exists("reset")) {
      $command = "memory_reset 0";
      unlink("reset");
    } else if (file_exists("preset")) {
      $command = "memory_set 0";
      unlink("preset");
    } else {
      $command = "memory_recall 0";
    }
  } else if (strcmp($button, "mem2") == 0) {
    if (file_exists("reset")) {
      $command = "memory_reset 1";
      unlink("reset");
    } else if (file_exists("preset")) {
      $command = "memory_set 1";
      unlink("preset");
    } else {
      $command = "memory_recall 1";
    }
  } else if (strcmp($button, "mem3") == 0) {
    if (file_exists("reset")) {
      $command = "memory_reset 2";
      unlink("reset");
    } else if (file_exists("preset")) {
      $command = "memory_set 2";
      unlink("preset");
    } else {
      $command = "memory_recall 2";
    }
  } else if (strcmp($button, "mem4") == 0) {
    if (file_exists("reset")) {
      $command = "memory_reset 3";
      unlink("reset");
    } else if (file_exists("preset")) {
      $command = "memory_set 3";
      unlink("preset");
    } else {
      $command = "memory_recall 3";
    }
  } else if (strcmp($button, "mem5") == 0) {
    if (file_exists("reset")) {
      $command = "memory_reset 4";
      unlink("reset");
    } else if (file_exists("preset")) {
      $command = "memory_set 4";
      unlink("preset");
    } else {
      $command = "memory_recall 4";
    }
  } else if (strcmp($button, "mem6") == 0) {
    if (file_exists("reset")) {
      $command = "memory_reset 5";
      unlink("reset");
    } else if (file_exists("preset")) {
      $command = "memory_set 5";
      unlink("preset");
    } else {
      $command = "memory_recall 5";
    }
  } else if (strcmp($button, "preset") == 0) {
    clearstatcache();
    if (file_exists("preset")) {
      unlink("preset");
    } else if (file_exists("reset")) {
      unlink("reset");
      touch("preset");
    } else {
      touch("preset");
    }    
  } else if (strcmp($button, "reset") == 0) {
    clearstatcache();
    if (file_exists("reset")) {
      unlink("reset");
    } else if (file_exists("preset")) {
      unlink("preset");
      touch("reset");
    } else {
      touch("reset");
    }
  }
  else if (strcmp($button, "direction") == 0) {
    clearstatcache();
    if (file_exists("direction")) {
      unlink("direction");
    } else {
      touch("direction");
    }
  } else if (strcmp($button, "home") == 0) {
    $command = "set_pantilt_home";
  } else if (strcmp($button, "ptreset") == 0) {
    $command = "set_pantilt_reset";
  } else if (strcmp($button, "up") == 0) {
    sendCommand($fp, "get_zoom_value", $answer, $ret1, $ret2, $ret3);
    $speedcalc = $speed - floor($ret1 / ($zoom_max/$speed));
    if($speedcalc <1)$speedcalc=1;
    $speedstring = " ".$speedcalc." ".$speedcalc;
    clearstatcache();
    if (file_exists("movingup")) {
      $command = "set_pantilt_stop ".$speedstring;
      unlink("movingup");
    } else if (file_exists("movingdown")) {    
      $command = "set_pantilt_up ".$speedstring;
      unlink("movingdown");
      touch("movingup");    
    } else {
      $command = "set_pantilt_up ".$speedstring;
      touch("movingup");    
    }
  } else if (strcmp($button, "left") == 0) {
    sendCommand($fp, "get_zoom_value", $answer, $ret1, $ret2, $ret3);
    $speedcalc = $speed - floor($ret1 / ($zoom_max/$speed));
    if($speedcalc <1)$speedcalc=1;
    $speedstring = " ".$speedcalc." ".$speedcalc;
    clearstatcache();
    if (file_exists("movingleft")) {
      $command = "set_pantilt_stop ".$speedstring;
      unlink("movingleft");
    } else if (file_exists("movingright")) {    
      if (file_exists("direction")) {
        $command = "set_pantilt_left ".$speedstring;
      } else {
        $command = "set_pantilt_right ".$speedstring;
      }
      unlink("movingright");
      touch("movingleft");    
    } else {
      if (file_exists("direction")) {
        $command = "set_pantilt_left ".$speedstring;
      } else {
        $command = "set_pantilt_right ".$speedstring;
      }
      touch("movingleft");
    }
  } else if (strcmp($button, "right") == 0) {
    sendCommand($fp, "get_zoom_value", $answer, $ret1, $ret2, $ret3);
    $speedcalc = $speed - floor($ret1 / ($zoom_max/$speed));
    if($speedcalc <1)$speedcalc=1;
    $speedstring = " ".$speedcalc." ".$speedcalc;
    clearstatcache();
    if (file_exists("movingright")) {
      $command = "set_pantilt_stop ".$speedstring;
      unlink("movingright");
    } else if (file_exists("movingleft")) {    
      if (file_exists("direction")) {
        $command = "set_pantilt_right ".$speedstring;
      } else {
        $command = "set_pantilt_left ".$speedstring;
      }
      unlink("movingleft");
      touch("movingright");    
    } else {
      if (file_exists("direction")) {
        $command = "set_pantilt_right ".$speedstring;
      } else {
        $command = "set_pantilt_left ".$speedstring;
      }
      touch("movingright");
    }
  } else if (strcmp($button, "down") == 0) {
    sendCommand($fp, "get_zoom_value", $answer, $ret1, $ret2, $ret3);
    $speedcalc = $speed - floor($ret1 / ($zoom_max/$speed));
    if($speedcalc <1)$speedcalc=1;
    $speedstring = " ".$speedcalc." ".$speedcalc;
    clearstatcache();
    if (file_exists("movingdown")) {
      $command = "set_pantilt_stop ".$speedstring;
      unlink("movingdown");
    } else if (file_exists("movingup")) {    
      $command = "set_pantilt_down ".$speedstring;
      unlink("movingup");
      touch("movingdown");    
    } else {
      $command = "set_pantilt_down ".$speedstring;
      touch("movingdown");    
    }
  } else if (strcmp($button, "slowtele") == 0) {
    clearstatcache();
    if (file_exists("zoomin")) {
      $command = "set_zoom_stop";
      unlink("zoomin");
    } else if (file_exists("zoomout")) {    
      $command = "set_zoom_tele_speed 2";
      unlink("zoomout");
      touch("zoomin");    
    } else {
      $command = "set_zoom_tele_speed 2";
      touch("zoomin");
    }  
  } else if (strcmp($button, "slowwide") == 0) {
    clearstatcache();
    if (file_exists("zoomout")) {
      $command = "set_zoom_stop";
      unlink("zoomout");
    } else if (file_exists("zoomin")) {    
      $command = "set_zoom_wide_speed 2";
      unlink("zoomin");
      touch("zoomout");    
    } else {
      $command = "set_zoom_wide_speed 2";
      touch("zoomout");
    }  
  } else if (strcmp($button, "fasttele") == 0) {
    clearstatcache();
    if (file_exists("zoomin")) {
      $command = "set_zoom_stop";
      unlink("zoomin");
    } else if (file_exists("zoomout")) {    
      $command = "set_zoom_tele_speed 7";
      unlink("zoomout");
      touch("zoomin");    
    } else {
      $command = "set_zoom_tele_speed 7";
      touch("zoomin");
    }  
  } else if (strcmp($button, "fastwide") == 0) {
    clearstatcache();
    if (file_exists("zoomout")) {
      $command = "set_zoom_stop";
      unlink("zoomout");
    } else if (file_exists("zoomin")) {    
      $command = "set_zoom_wide_speed 7";
      unlink("zoomin");
      touch("zoomout");    
    } else {
      $command = "set_zoom_wide_speed 7";
      touch("zoomout");
    }  
  }

  sendCommand($fp, $command, $answer, $ret1, $ret2, $ret3);  
  closeSocket($fp);
  echo "The last button pressed was: ".$button."<br>";
  echo "The reply was: ".$answer."<br>";
  if ($ret1 != "" ||$ret2 != "" || $ret2 != "") {
    echo "Return values: ";
  }
  if ($ret1 != "") {
    echo "ret1=".$ret1."; ";
  }
  if ($ret2 != "") {
    echo "ret2=".$ret2."; ";
  }
  if ($ret3 != "") {
    echo "ret3=".$ret3."; ";
  }
}
?>
</body>
</html>
