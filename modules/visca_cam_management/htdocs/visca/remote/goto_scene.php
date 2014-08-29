<?php
include_once "../common/visca.php";
include_once "./ptzpos.php";
?>
<html>
<head>
<title>Sony <?php echo $CAM_MODEL; ?> web interface</title>
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="expires" content="0">
</head>
<body>
<H1>Sony <?php echo $CAM_MODEL; ?> Camera - Web Interface - Scene mode</H1>
<small><a href="../index.html" target="_top">
Back to list of web interfaces</a></small><br>
<?php
if (isset($_GET['scene'])){
    $name=$_GET['scene'];
    $scene=str_toalnum($name);
 print "moving camera to position '$scene'<br>";
 goto_ptz_pos($scene);
}

?>
