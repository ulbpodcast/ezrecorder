<?php
    include "config.inc";
    include "crestron.php";

    $PRESET_FILE = __DIR__ . '/var/presets';

    function cam_crestron_ptz_posnames_get() {
        $presets = cam_crestron_ptz_get_presets();
        if($presets)
            return json_decode($presets,true);
        else
            return array();
    }

    function cam_crestron_ptz_move($name) {
        global $cam_ip;
        global $cam_port;
    
        $presets = cam_crestron_ptz_posnames_get();
        if(!$presets)
            return false;

        $position = array_search($name, $presets);
        if($position === false)
            return false;
        
        $crestron = new Crestron_management($cam_ip,$cam_port);
        $crestron->move($position);
    }

    function cam_crestron_ptz_get_presets() {
        global $PRESET_FILE;

        if(file_exists($PRESET_FILE)) {
            $string_data = file_get_contents($PRESET_FILE);
            return $string_data;
        }
        return false;
    }

    function cam_crestron_ptz_set_presets($presetInfo,$presetName){
        global $PRESET_FILE;
        
        $json = cam_crestron_ptz_get_presets();
        $json = json_decode($json, true);
        $json[$presetInfo] = $presetName;
        $json = json_encode($json, true);
        file_put_contents($PRESET_FILE, $json);
        echo "PRESET SAVED ! => " . $json . PHP_EOL;
        
    }

    function cam_crestron_ptz_pos_delete($name){
        global $PRESET_FILE;
        
        $json = cam_crestron_ptz_get_presets();
        $json = json_decode($json, true);
        unset($json[$name]);
        $json = json_encode($json, true);
        file_put_contents($PRESET_FILE, $json);
        echo "PRESET REMOVED ! => " . $json . PHP_EOL;
    }
?>