#!/usr/bin/php
<?php
//open qt new video record in full screen
system("osascript ./qtcamfullscreen.scpt");
$t1=time();
$picdelay=500;// wait between 2 pictures in milisecs
$dt=5000;
while (time()-$dt<$t1){
    //print "/usr/sbin/screencapture -x -t jpg /Library/WebServer/Documents/libvisca-web/cam_snaps/pic_new.jpg\n";
//take a snapshot of the screen
    system("/usr/sbin/screencapture -x -t jpg /Library/WebServer/Documents/libvisca-web/cam_snaps/pic_new.jpg");
//copy to actual snap
    rename("/Library/WebServer/Documents/libvisca-web/cam_snaps/pic_new.jpg","/Library/WebServer/Documents/libvisca-web/cam_snaps/pic.jpg");
    usleep($picdelay*1000);
}
if(!is_file("/tmp/qtwindowname"));
system("osascript ./qtcamclose.scpt");
?>
