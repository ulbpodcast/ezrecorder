<?php
function str_toalnum($string){
  $toalnum="";
  for($idx=0;$idx<strlen($string);$idx++)
    if(ctype_alnum($string[$idx]))
     $toalnum.=$string[$idx];
     else
     $toalnum.="_";
  return $toalnum;

}


$name="A/é/è/d";
$filename="";
for($idx=0;$idx<strlen($name);$idx++)
 if(ctype_alnum($name[$idx]))
     $filename.=$name[$idx];
   else
     $filename.="_";

print "$name =>$filename\n";
print "$name (toalnum)=>".str_toalnum($name);
?>