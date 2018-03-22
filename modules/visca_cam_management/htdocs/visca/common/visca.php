<?php
$imagesnap_cmd="/usr/local/bin/imagesnap";
require_once 'D70limits.php';
function openSocket(&$fp) {
  $fp = fsockopen("localhost", 3376, $errno, $errstr);
  if (!$fp) {
    echo "$errstr ($errno)<br />\n";
  } else {
    //get the welcome message
    fgets($fp, 128);
  }
}

function closeSocket($fp) {
  fclose($fp);
}

function sendCommand($fp, $command, &$answer, &$ret1, &$ret2, &$ret3) {
    global $emulate_set_pantilt;
  $answer = "";
  $ret1 = "";
  $ret2 = "";
  $ret3 = "";
//print "cmd:$command<br>";
/*
  if($emulate_set_pantilt && substr($command,0,29)=="set_pantilt_absolute_position"){
      include_once '../common/ptz_functions.php';
      print "calling emulate_set_pantilt_absolute_position(\"$command\") ";
      $answer=emulate_set_pantilt_absolute_position($fp,$command);
  }
  else
*/
  if ($fp) {
    fwrite($fp, $command."\n");
    $answer = fgets($fp, 128);
    if (strncmp($answer, "11", 2) == 0) {
      $ret1 = ereg_replace(".*:","",fgets($fp, 128));
    } else if (strncmp($answer, "12", 2) == 0) {
      $ret1 = ereg_replace(".*:","",fgets($fp, 128));
      $ret2 = ereg_replace(".*:","",fgets($fp, 128));
    } else if (strncmp($answer, "13", 2) == 0) {
      $ret1 = ereg_replace(".*:","",fgets($fp, 128));
      $ret2 = ereg_replace(".*:","",fgets($fp, 128));
      $ret3 = ereg_replace(".*:","",fgets($fp, 128));
    }
  }

  print "cmd:$command answ:$answer ret1:$ret1 ret2:$ret2 ret3:$ret3<br>";
}

?>
