<?php

/*
 * This is a CLI script that loops on the modules in ./modules and lets the 
 * user choose what modules he wants to enable.
 * Usage: modules_selection.php 
 * 
 */

require_once(__DIR__."/lib_install.php");

if (!file_exists(__DIR__ . "/global_config.inc")) {
    echo 'Missing file : global_config.inc';
    die;
} else {
    $in_install = true; //check usage in global_config.inc, this allow including the global_config_sample without failing
    require __DIR__ . "/global_config.inc";
}

echo "*******************************************************************" . PHP_EOL;
echo "*              M O D U L E S    S E L E C T I O N                 *" . PHP_EOL;
echo "*******************************************************************" . PHP_EOL;
$modules_array = array(
    "recording" => array(),
    "authentication" => array(),
    "cam_management" => array(),
    "session" => array(),);

$modules = glob('modules/*/info.php');
foreach ($modules as $module) {
    require $module;
    $module_array = array();
    $module_array['title'] = $module_title;
    $module_array['name'] = $module_name;
    $module_array['library'] = $module_lib;
    $module_array['description'] = $module_description;
    $modules_array[$module_type][] = $module_array;
}


$config = file_get_contents(__DIR__ . "/global_config.inc");

/* ------------ CAM RECORDING MODULE ------------- */
echo PHP_EOL;
echo "------------------------------------------------------------" . PHP_EOL;
echo "               C A M    R E C O R D I N G" . PHP_EOL;
echo "------------------------------------------------------------" . PHP_EOL;

//todo: change default to false
$value = read_line("Do you want to enable cam recording ? [Y/n]: ");
if (strtoupper($value) != 'N' && strtoupper($value) != 'NO') {
    if (count($modules_array['recording']) > 0) {
        if (count($modules_array['recording']) == 1) {
            $module = $modules_array['recording'][0];
            unset($modules_array['recording'][0]);
        } else {
            echo "Select the module you want to enable for cam recording: " . PHP_EOL;
            echo "---------------------------------------------------------------- " . PHP_EOL;
            foreach ($modules_array['recording'] as $index => $module) {
                echo ($index + 1) . ". " . $module['title'] . ": " . PHP_EOL . "\t". $module['description'] . PHP_EOL;
            }
            $index++;
            do {
                $value = read_line("Enter the number of the module you want to enable [1..$index]: ");
            } while (!is_numeric($value) || $value < 1 || $value > $index);
            $module = $modules_array['recording'][--$value];
            unset($modules_array['recording'][$value]);
            $modules_array['recording'] = array_values($modules_array['recording']);
        }
        echo "The module '" . $module['title'] . "' has been selected." . PHP_EOL;
        $cam_enabled = true;
        $cam_module = $module['name'];
        $cam_lib = $module['library'];

        $config = preg_replace('/\$cam_module = (.+);/', '\$cam_module = "' . $cam_module . '";', $config);
        $config = preg_replace('/\$cam_lib = (.+);/', '\$cam_lib = "' . $cam_lib . '";', $config);
    } else {
        echo "No recording module found, cam recording is disabled." . PHP_EOL;
        $cam_enabled = false;
    }
} else {
    echo "Cam recording is disabled" . PHP_EOL;
    $cam_enabled = false;
}
$config = preg_replace('/\$cam_enabled = (.+);/', '\$cam_enabled = ' . (($cam_enabled) ? 'true' : 'false') . ';', $config);



/* ------------ SLIDE RECORDING MODULE ------------- */
echo PHP_EOL;
echo "------------------------------------------------------------" . PHP_EOL;
echo "             S L I D E    R E C O R D I N G" . PHP_EOL;
echo "------------------------------------------------------------" . PHP_EOL;
$value = read_line("Do you want to enable slide recording ? [Y/n]: ");
if (strtoupper($value) != 'N' && strtoupper($value) != 'NO') {
    if (count($modules_array['recording']) > 0) {
        if (count($modules_array['recording']) == 1) {
            $module = $modules_array['recording'][0];
            unset($modules_array['recording'][0]);
        } else {
            echo "Select the module you want to enable for slide recording: " . PHP_EOL;
            echo "---------------------------------------------------------------- " . PHP_EOL;
            foreach ($modules_array['recording'] as $index => $module) {
                echo ($index + 1) . ". " . $module['title'] . ": " . $module['description'] . PHP_EOL;
            }
            $index++;
            do {
                $value = read_line("Enter the number of the module you want to enable [1..$index]: ");
            } while (!is_numeric($value) || $value < 1 || $value > $index);
            $module = $modules_array['recording'][--$value];
            unset($modules_array['recording'][$value]);
            $modules_array['recording'] = array_values($modules_array['recording']);
        }
        echo "The module '" . $module['title'] . "' has been selected." . PHP_EOL;
        $slide_enabled = true;
        $slide_module = $module['name'];
        $slide_lib = $module['library'];

        $config = preg_replace('/\$slide_module = (.+);/', '\$slide_module = "' . $slide_module . '";', $config);
        $config = preg_replace('/\$slide_lib = (.+);/', '\$slide_lib = "' . $slide_lib . '";', $config);
    } else {
        echo "No recording module found, slide recording is disabled." . PHP_EOL;
        $slide_enabled = false;
    }
} else {
    echo "Slide recording is disabled" . PHP_EOL;
    $slide_enabled = false;
}
$config = preg_replace('/\$slide_enabled = (.+);/', '\$slide_enabled = ' . (($slide_enabled) ? 'true' : 'false') . ';', $config);


/* ------------ CAM MANAGEMENT MODULE ------------- */
if ($cam_enabled) {
    echo PHP_EOL;
    echo "------------------------------------------------------------" . PHP_EOL;
    echo "               C A M   M A N A G E M E N T" . PHP_EOL;
    echo "------------------------------------------------------------" . PHP_EOL;
    $value = read_line("Do you want to enable cam management ? [Y/n]: ");
    if (strtoupper($value) != 'N' && strtoupper($value) != 'NO') {
        if (count($modules_array['cam_management']) > 0) {
            if (count($modules_array['cam_management']) == 1) {
                $module = $modules_array['cam_management'][0];
                unset($modules_array['cam_management'][0]);
            } else {
                echo "Select the module you want to enable for cam management: " . PHP_EOL;
                echo "---------------------------------------------------------------- " . PHP_EOL;
                foreach ($modules_array['cam_management'] as $index => $module) {
                    echo ($index + 1) . ". " . $module['title'] . ": " . $module['description'] . PHP_EOL;
                }
                $index++;
                do {
                    $value = read_line("Enter the number of the module you want to enable [1..$index]: ");
                } while (!is_numeric($value) || $value < 1 || $value > $index);
                $module = $modules_array['cam_management'][--$value];
                unset($modules_array['cam_management'][$value]);
                $modules_array['cam_management'] = array_values($modules_array['cam_management']);
            }
            echo "The module '" . $module['title'] . "' has been selected." . PHP_EOL;
            $cam_management_enabled = true;
            $cam_management_module = $module['name'];
            $cam_management_lib = $module['library'];


            $value = read_line("Enter the default camera position for cam recording [default: '$cam_default_scene']: ");
            if ($value != "")
                $cam_default_scene = $value; unset($value);
            $config = preg_replace('/\$cam_default_scene = (.+);/', '\$cam_default_scene = "' . $cam_default_scene . '";', $config);
            if ($slide_enabled) {
                $value = read_line("Enter the default camera position for slide recording (this position will be used for slide only record, to record a backup video) [default: '$cam_screen_scene']: ");
                if ($value != "")
                    $cam_screen_scene = $value; unset($value);
                $config = preg_replace('/\$cam_screen_scene = (.+);/', '\$cam_screen_scene = "' . $cam_screen_scene . '";', $config);
            }
            $config = preg_replace('/\$cam_management_module = (.+);/', '\$cam_management_module = "' . $cam_management_module . '";', $config);
            $config = preg_replace('/\$cam_management_lib = (.+);/', '\$cam_management_lib = "' . $cam_management_lib . '";', $config);
        } else {
            echo "No cam management module found, cam management is disabled." . PHP_EOL;
            $cam_management_enabled = false;
        }
    } else {
        echo "Cam management is disabled" . PHP_EOL;
        $cam_management_enabled = false;
    }
    $config = preg_replace('/\$cam_management_enabled = (.+);/', '\$cam_management_enabled = ' . (($cam_management_enabled) ? 'true' : 'false') . ';', $config);
} else {
    $cam_management_enabled = false;
    $config = preg_replace('/\$cam_management_enabled = (.+);/', '\$cam_management_enabled = ' . (($cam_management_enabled) ? 'true' : 'false') . ';', $config);
}


/* ------------ AUTHENTICATION MODULE ------------- */
echo PHP_EOL;
echo "------------------------------------------------------------" . PHP_EOL;
echo "              A U T H E N T I C A T I O N " . PHP_EOL;
echo "------------------------------------------------------------" . PHP_EOL;
if (count($modules_array['authentication']) > 0) {
    if (count($modules_array['authentication']) == 1) {
        $module = $modules_array['authentication'][0];
        unset($modules_array['authentication'][0]);
    } else {
        echo "Select the module you want to enable for authentication: " . PHP_EOL;
        echo "---------------------------------------------------------------- " . PHP_EOL;
        foreach ($modules_array['authentication'] as $index => $module) {
            echo ($index + 1) . ". " . $module['title'] . ": " . $module['description'] . PHP_EOL;
        }
        $index++;
        do {
            $value = read_line("Enter the number of the module you want to enable [1..$index]: ");
        } while (!is_numeric($value) || $value < 1 || $value > $index);
        $module = $modules_array['authentication'][--$value];
    }
    echo "The module '" . $module['title'] . "' has been selected." . PHP_EOL;
    $auth_module = $module['name'];
    $auth_lib = $module['library'];

    $config = preg_replace('/\$auth_module = (.+);/', '\$auth_module = "' . $auth_module . '";', $config);
    $config = preg_replace('/\$auth_lib = (.+);/', '\$auth_lib = "' . $auth_lib . '";', $config);
} else {
    echo "No authentication module found. Make sure an authentication module has been set up before using EZrecorder !" . PHP_EOL;
}


/* ------------ SESSION MODULE ------------- */
echo PHP_EOL;
echo "------------------------------------------------------------" . PHP_EOL;
echo "                       S E S S I O N  " . PHP_EOL;
echo "------------------------------------------------------------" . PHP_EOL;
if (count($modules_array['session']) > 0) {
    if (count($modules_array['session']) == 1) {
        $module = $modules_array['session'][0];
        unset($modules_array['session'][0]);
    } else {
        echo "Select the module you want to enable for session management: " . PHP_EOL;
        echo "---------------------------------------------------------------- " . PHP_EOL;
        foreach ($modules_array['session'] as $index => $module) {
            echo ($index + 1) . ". " . $module['title'] . ": " . $module['description'] . PHP_EOL;
        }
        $index++;
        do {
            $value = read_line("Enter the number of the module you want to enable [1..$index]: ");
        } while (!is_numeric($value) || $value < 1 || $value > $index);
        $module = $modules_array['session'][--$value];
    }
    echo "The module '" . $module['title'] . "' has been selected." . PHP_EOL;
    $session_module = $module['name'];
    $session_lib = $module['library'];

    $config = preg_replace('/\$session_module = (.+);/', '\$session_module = "' . $session_module . '";', $config);
    $config = preg_replace('/\$session_lib = (.+);/', '\$session_lib = "' . $session_lib . '";', $config);
} else {
    echo "No session management module found. Make sure a session managmeent module has been set up before using EZrecorder !" . PHP_EOL;
}

file_put_contents(__DIR__ . "/global_config.inc", $config);
