<?php

$config_version = "2017031601";

require_once __DIR__.'/../../../global_config.inc';

$ffmpeg_mono = true; // determines whether the input audio channel is mono or stereo

$ffmpeg_input_source = "rtsp"; // specifies the input source. Valid values are: DeckLink | avfoundation | rtsp | AV.io
// you might have to adapt the 'bash/ffmpeg_init' script
$ffmpeg_streaming_quality = "highlow"; // quality enabled for live stream (highlow | high | low | none)

// -- If source is "rtsp"
$ffmpeg_rtsp_media_high_uri = "rtsp://xxx.xxx.xxx.xxx:xxx/xx";
$ffmpeg_rtsp_media_low_uri = "rtsp://xxx.xxx.xxx.xxx:xxx/yy";
$ffmpeg_rtsp_transport = "tcp"; //valid values are 'tcp', 'udp', 'udp_multicast', 'http'
// --

// -- If source is "avfoundation" or "AV.io"
//avfoundation input indexes or device names. You can list them with:   ffmpeg -f avfoundation -list_devices true -i ""
$avfoundation_video_interface = 0;
$avfoundation_audio_interface = 1;
// --

// -- If source is "DeckLink"
$decklink_format_index = 14;
$decklink_device = "UltraStudio Mini Recorder";
// --

// -- h264 defines
$h264_preset = "medium";
$h264_profile = "main";
// --

require_once __DIR__."/common.inc";