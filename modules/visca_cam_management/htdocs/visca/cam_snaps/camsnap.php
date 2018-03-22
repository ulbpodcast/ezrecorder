<?php
$ezcast_recorder_path="/Library/ezrecorder/modules/local_qtb/";
$visca_path="/Library/WebServer/Documents/visca/";
$cam_capture_file=$ezcast_recorder_path."/var/screencapture.jpg";
$screen_capture_file=$visca_path."/cam_snaps/screencapture.jpg";
$screen_capture_file_url="/visca/cam_snaps/screencapture.jpg";
chdir($ezcast_recorder_path);
include_once "lib_capture.php";
//ask ezcast_recorder the last picture or take a picture
capture_localqtb_thumbnail();
copy($cam_capture_file , $screen_capture_file);
?><html>
<head>
<META http-equiv="Cache-Control" content="no-cache">
<META http-equiv="Pragma" content="no-cache">
<META http-equiv="Cache" content="no store">
<meta http-equiv="refresh" content="3" />
<meta http-equiv="expires" content="0" />

</head>
<body>
<img src="<?php echo $screen_capture_file_url; ?> " width="640" height="480" border="0" alt="camera pic" >
</body>
</html>
