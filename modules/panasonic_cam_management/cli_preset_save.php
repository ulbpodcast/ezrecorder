<?php

//WARNING!!!! YOU HAVE TO CREATE THE PRESET IN THE CAMERA BEFORE AND REUSE THE EXAM SAME PRESET NAME!!!!
if($argc != 2) {
	echo "Usage: savepreset.php <preset_name>" . PHP_EOL;
	die();
}

$presetName = $argv[1];

require(__DIR__ . '/lib_cam.php');

cam_panasonic_pos_save($presetName);
