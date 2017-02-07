<?php

require_once dirname(__FILE__).'/../../../global_config.inc';

$ffmpeg_mono = true; // determines whether the input audio channel is mono or stereo

$ffmpeg_input_source = "rtsp"; // specifies the input source. Valid values are: IntensityShuttle | UltraStudioMiniRecorder | avfoundation | rtsp | AV.io | IntensityShuttleThunderbolt
// you might have to adapt the 'bash/ffmpeg_init' script
$ffmpeg_streaming_quality = "highlow"; // quality enabled for live stream (highlow | high | low | none)

// -- If source is "rtsp"
$ffmpeg_rtsp_media_high_uri = "rtsp://xxx.xxx.xxx.xxx:xxx/xx";
$ffmpeg_rtsp_media_low_uri = "rtsp://xxx.xxx.xxx.xxx:xxx/yy";
// --

// -- If source is "avfoundation" or "AV.io"
//avfoundations input indexes. You can list them with:   ffmpeg -f avfoundation -list_devices true -i ""
$avfoundation_video_interface = 0;
$avfoundation_audio_interface = 1;
// --

// -- If source is "DeckLink"
$decklink_format_index = 14;
$decklink_device = "UltraStudio Mini Recorder";
// --

require_once __DIR__."/common.inc";