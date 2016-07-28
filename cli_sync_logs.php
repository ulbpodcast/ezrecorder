<?php

require_once "global_config.inc";

ini_set("allow_url_fopen", 1);

$get_url = "$ezcast_logs_url?action=last_log_sent&classroom_id=$classroom";
$post_url = "$ezcast_logs_url?action=push_logs"; //followed by json array

$datetime = file_get_contents($get_url);
if($datetime == false) {
    echo "Failed to get content from $get_url" . PHP_EOL;
    return 1;
}

if(strlen($datetime) != 23) {
    echo "Invalid server response:" .PHP_EOL;
    print_r($datetime);
    die();
}

echo "$ezcast_logs_url?action=last_log_sent&classroom_id=$classroom" . PHP_EOL;
echo "Send newer than $datetime";
$events_to_send = $logger->get_all_events_newer_than($datetime, 100);

$events_count = sizeof($events_to_send);

$handle = curl_init($post_url);
if(!$handle) {
    echo "Failed to init curl for $post_url" .PHP_EOL;
    return 2;
}

$post_array = array(
    'log_data' => json_encode($events_to_send),
);

curl_setopt($handle, CURLOPT_POST, 1); //activate POST parameters
curl_setopt($handle, CURLOPT_POSTFIELDS, $post_array);
curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1); //don't send answer to stdout but in returned string
curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10); 
curl_setopt($handle, CURLOPT_TIMEOUT, 5000); //timeout in seconds

$result = curl_exec($handle);
if(!$result !== false) {
    echo "Failed to exec curl for $post_url" . PHP_EOL;
    return 2;
}

if(strpos($result, "SUCCESS") === false) {
    echo "FAILURE" . PHP_EOL;
    echo "Page returned $result" . PHP_EOL;
    echo "What we sent: " . PHP_EOL;
    print_r($post_array);
    echo PHP_EOL;
    return 3;
}

echo "Finished. Sent $events_count log entries" . PHP_EOL;
echo "Server responded: $result";

return 0;