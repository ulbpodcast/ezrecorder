<?php

if($argc != 2) {
	echo "Usage: removepreset.php <preset_name>" . PHP_EOL;
	die();
}

$presetName = $argv[1];

require(__DIR__ . '/lib_cam.php');

Logger::$print_logs = true;
cam_ptzoptics_pos_delete($presetName);
