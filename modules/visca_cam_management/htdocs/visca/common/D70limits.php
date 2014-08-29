<?php
/*
 *D70 limit values and specific settings
 */

$emulate_set_pantilt=true; //set_pantilt_absoluteposition doesn't seem to work (with libvisca)
$memory_min=1;
$memory_max=6;
$CAM_MODEL="D70";
$zoom_min=0;
$zoom_max=31424;
$zoom_speed_min=0;
$zoom_speed_max=7;
$pan_min=-36353;
$pan_max=36383;
$tilt_min=-6449;
$tilt_max=19215;
$pan_speed_min=1;
$pan_speed_max=24;
$tilt_speed_min=1;
$tilt_speed_max=23;
$focus_min=1000;
$focus_max=40959;
$shutter_speed_min=0;// 1/60 sec
$shutter_speed_max=27; // 1/10000 sec
$iris_min=0; //closed
$iris_max=17; // wide open

$gain_min=0; // +0db
$gain_max= 7;// +18dB
?>