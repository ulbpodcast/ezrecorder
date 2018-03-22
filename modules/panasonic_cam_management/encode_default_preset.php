<?php
    require_once __DIR__ .'/lib_cam.php';
    include_once "config.inc";
    //initialise class
    $api = new Panasonic_CGI_API($cam_ip);

    // Go to preset number 2
    //$api->preset_go_to(2); //go to preset
    
    
    //move (param = pan & tilt)
    //$res=$api->move(2,2);     
  
//    Save ALL the presets in an array
       $res=cam_panasonic_set_presets( array("1" => "PLAN RAPPROCHE","2" => "PLAN LARGE"));
      
    
//    MOVE TO PRESET BY NAME
//    $res=cam_ptzoptics_move('PLAN LARGE');

   
    if ($res) 
        echo 'ok';
    
    else 
        echo 'PAS OK';

?>  