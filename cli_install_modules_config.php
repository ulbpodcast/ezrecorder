<?php

require_once(__DIR__."/lib_install.php");

echo PHP_EOL.
     "*******************************************************************".PHP_EOL;
echo "*        C O N F I G U R A T I O N   O F    M O D U L E S         *".PHP_EOL;
echo "*******************************************************************".PHP_EOL;
echo PHP_EOL."You will be requested to mention the modules you want to enable at the end of this installation script." . PHP_EOL;
$modules = glob('modules/*/info.php');
foreach ($modules as $module) {
    require $module;
    echo "-----------------------------------------------------------------------" . PHP_EOL;
    echo "Name: $module_title" . PHP_EOL;
    echo "Description: $module_description" . PHP_EOL;
    echo "-----------------------------------------------------------------------" . PHP_EOL;

    $value = read_line("Would you like to configure this module ? [Y/n] : ");
    if (strtoupper($value) != 'N') {
        system("$php_cli_cmd $module_path/cli_install.php");
    }
}
echo PHP_EOL;

