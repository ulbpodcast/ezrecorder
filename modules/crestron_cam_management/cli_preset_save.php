<?php

require(__DIR__ . '/lib_cam.php');

if($argc != 3) {
	echo "Usage: cli_preset_save.php <preset_info> <preset_name>" . PHP_EOL;
	die();
}

$presetInfo = $argv[1];
$presetName = $argv[2];
cam_crestron_ptz_set_presets($presetInfo,$presetName);