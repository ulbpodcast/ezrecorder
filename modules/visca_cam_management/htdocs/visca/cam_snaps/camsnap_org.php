<?php
/* 
 *returns a snapshot of camera
*/
$tmpimagefile="tmpsnapshot.jpg";
$imagefile="snapshot.jpg";
@$res=exec("sudo -u /usr/local/sbin/imagesnap $tmpimagefile",$stdoutarray,$returncode);
print $res;
if($returncode==0){
rename ($tmpimagefile ,$imagefile);
}
header('content-type: image/jpg');
$jpg=  file_get_contents($imagefile);
echo $jpg;
?>
