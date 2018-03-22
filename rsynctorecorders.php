<?php
print_r(count($argv));
if(count($argv)!=2){
    echo 'Use : php rsynctorecorders "file_to_rsync" ';
}
else{

    require_once __DIR__."/../ezcast/ezadmin/var/classroom_recorder_ip.inc"; //valid ip file
    print_r($argv);

    for($i=0;$i<count($podcv_ip);$i++){
    //for($i=0;$i<1;$i++){
        exec("sudo -su www-data rsync --timeout 2 -az /usr/local/ezrecorder/$argv[1] ezrecorder@$podcv_ip[$i]:/Library/ezrecorder/$argv[1]", $output, $return);

        if (!$return) {
            echo "\033[01;32m  Rsync OK: $argv[1] $podcv_ip[$i] \033[0m" .PHP_EOL;
        } else {
            echo "\033[01;31m  Rsync KO: $argv[1] $podcv_ip[$i] \033[0m" .PHP_EOL;
        }
    }
}


?>
