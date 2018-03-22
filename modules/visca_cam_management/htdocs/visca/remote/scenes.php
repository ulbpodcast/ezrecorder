<html>
<body>

<?php
include_once "./ptzpos.php";
$nbimagesinline=6;
//get all scenes (ptz positions) names
$ptz_posname_array=get_ptz_posnames();
?>
<table>
<tr>
<?php
$lineidx=0;
foreach ($ptz_posname_array as $ptzposname) {
?>
<td align="center">
<?php
//print $ptzposdir."/".$ptzposname.'jpg';
   if(file_exists($ptzposdir."/".$ptzposname.'.jpg'))
        $imgurl=$ptzposdir."/".$ptzposname.'.jpg';
       else
        $imgurl="blackscreen.jpg";
   
 ?>
    <a  href="goto_scene.php?scene=<?php echo $ptzposname; ?>" target="scenetop"><img src="<?php echo $imgurl; ?>" width="200"><p><?php echo $ptzposname; ?></a>
</td>
<?php
$lineidx+=1;
if($lineidx==$nbimagesinline){
  $lineidx=0;
?>
</tr><tr>
<?php
  } //end if
}//end foreach
?>
</tr>
</table>
</body>
</html>


