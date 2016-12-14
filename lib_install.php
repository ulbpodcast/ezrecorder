<?php

function ssh_ping($username, $ip) {
    $return_val = 0;
    system("ssh -o ConnectTimeout=10 -o BatchMode=yes $username@$ip \"touch /dev/null\" ", $return_val);
    return $return_val == 0;
}

function read_line($prompt = '') {
    echo $prompt . PHP_EOL;
    flush();
    return rtrim(fgets(STDIN), "\n");
}