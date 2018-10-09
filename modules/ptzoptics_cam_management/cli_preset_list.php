<?php

require(__DIR__ . '/lib_cam.php');

//Logger::$print_logs = true;
$presets = cam_ptzoptics_get_presets();
var_dump($presets);
