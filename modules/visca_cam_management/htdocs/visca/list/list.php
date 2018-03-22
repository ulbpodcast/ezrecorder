<?php include("../common/visca.php"); ?>
<html>
<head>
<title>Sony D30 web interface</title>
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="expires" content="0">
</head>
<body>
<H1>Sony D30 Camera - Web Interface - List mode</H1>
<small><a href="../index.html" target="_top">
Back to list of web interfaces</a></small><br>

<?php 
if (isset($_GET['command'])) {
  $command = $_GET['command'];
  
  if($command=="goto"){
      print "command: $command";
      ptz_goto($_GET['var1'],$_GET['var2']);
      die;
  }
  
  if (isset($_GET['var1'])) {
    $command.=" ".$_GET['var1'];
  }
  if (isset($_GET['var2'])) {
    $command.=" ".$_GET['var2'];
  }
  if (isset($_GET['var3'])) {
    $command.=" ".$_GET['var3'];
  }
  if (isset($_GET['var4'])) {
    $command.=" ".$_GET['var4'];
  }
  openSocket($fp);
  sendCommand($fp, $command, $answer, $ret1, $ret2, $ret3);
  closeSocket($fp);
  echo "The last command sent was: ".$command."<br>";
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

<?php
function ptz_goto($goto_pan,$goto_tilt){
 $speed_dec=5;
 $pan_speed=15;
 $tilt_speed=15;
 $pan_resol=30;
 $tilt_resol=30;
 $direction="";
 $pan_move="";
 $tilt_move="";
 $nbdirchange=0;
 while( $nbdirchange<90){
 //check position
  openSocket($fp);
  sendCommand($fp, "get_pantilt_position", $answer,$pan,$tilt,$ret3);
  closeSocket($fp);
  //print "get_pantilt_position pan: $pan tilt: $tilt<br>\n";
  $pan_dist=abs($goto_pan-$pan);
  if($pan_dist<$pan_resol)
     $pan_move="";
   else{
    $old_pan_move=$pan_move;
    if($goto_pan-$pan>0)$pan_move="right";
    if($goto_pan-$pan<0)$pan_move="left";
    if($old_pan_move!=$pan_move && $old_pan_move!=""){
        //we went too far to go back with at a slower speed
      $pan_speed-=$speed_dec;
      if($pan_speed<1)$pan_speed=1;
      print "Pan Speed:$pan_speed <br>\n";
    }
   }
   $tilt_dist=abs($goto_tilt-$tilt);
   if($tilt_dist<$tilt_resol)
     $tilt_move="";
   else{
    $old_tilt_move=$tilt_move;
    if($goto_tilt-$tilt>0)$tilt_move="up";
    if($goto_tilt-$tilt<0)$tilt_move="down";
     if($old_tilt_move!=$tilt_move && $old_tilt_move!=""){
        //we went too far to go back with at a slower speed
      $tilt_speed-=$speed_dec;
      if($tilt_speed<1)$tilt_speed=1;
      print "Tilt Speed:$tilt_speed <br>\n";
    }
   }
   $old_direction=$direction;
   $direction=$tilt_move.$pan_move;
   //print "direction:$direction<br>\n";
   if($direction==""){
       $ptz_cmd="set_pantilt_stop ".$pan_speed." ".$tilt_speed;
       openSocket($fp);
        sendCommand($fp, $ptz_cmd, $answer, $ret1, $ret2, $ret3);
       closeSocket($fp);
       print "destination reached<br>\n";
       return true;
   }
   if($direction!=$old_direction){
    
     $nbdirchange+=1;

     $ptz_cmd="set_pantilt_".$tilt_move.$pan_move." ".$pan_speed." ".$tilt_speed;
     print "direction change:$direction command:$ptz_cmd<br>\n";
     openSocket($fp);
     sendCommand($fp, $ptz_cmd, $answer, $ret1, $ret2, $ret3);
     closeSocket($fp);
   }
  }//end while
}

?>