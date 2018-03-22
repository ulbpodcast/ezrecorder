<?php
$minperiod=5;
$screencapturefile="./screencapture.jpg";
if(!file_exists($screencapturefile) || (time() - filemtime($screencapturefile)>3)){
 //if no image or image is old get a new screecapture
    $res=exec("sudo -u podclient /usr/sbin/screencapture -x -t jpg /Library/WebServer/Documents/offlineqtb/pic_new.jpg 2>&1",$output_array,$return_code);
    //print "return code: $return_code<br>\n";
    //print "stdout&stderr:". join("<br>", $output_array)."<br>\n";
    if($return_code){
        //print "could not take a screencapture";
        copy("./nopic.jpg", $screencapturefile);
    }
    else{
    //copy screencapture to actual snap
    copy("pic_new.jpg","pic_new_www.jpg");
    rename("pic_new_www.jpg","$screencapturefile");
   }
}
?><html>
<head>
<META http-equiv="Cache-Control" content="no-cache">
<META http-equiv="Pragma" content="no-cache">
<META http-equiv="Cache" content="no store">
<meta http-equiv="refresh" content="3" />
<meta http-equiv="expires" content="0" />

</head>
<body>
<img src="<?php echo $screencapturefile; ?> " width="640" height="480" border="0" alt="screen pic" >
</body>
</html>
