<?php
require(__DIR__ . '/lib_cam.php');

if($argc != 2) {
	echo "Usage: cli_preset_remove.php <preset_info>" . PHP_EOL;
	die();
}

$presetInfo = $argv[1];

cam_crestron_ptz_pos_delete($presetInfo);