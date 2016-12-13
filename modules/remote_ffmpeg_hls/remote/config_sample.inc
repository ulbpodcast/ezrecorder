<?php

$remoteffmpeg_recorddir = '~/Movies/';

$remoteffmpeg_input_source = "AV.io"; //see $remoteffmpeg_input_source_list for valid sources

// -- If source is "avfoundation" or "AV.io"
//avfoundations input indexes. You can list them with:   ffmpeg -f avfoundation -list_devices true -i ""
//TODO: the next two must be changed in localdefs for now, or just rerun the cli_install.php to do it
//$avfoundation_video_interface = 0;
//$avfoundation_audio_interface = 1;
// --

require_once __DIR__."/common.inc";