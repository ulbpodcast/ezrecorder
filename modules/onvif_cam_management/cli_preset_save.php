<?php


if($argc != 2) {
	echo "Usage: savepreset.php <preset_name>" . PHP_EOL;
	die();
}

$presetName = $argv[1];

require(__DIR__ . '/lib_cam.php');

Logger::$print_logs = true;
cam_onvif_ptz_pos_save($presetName);
