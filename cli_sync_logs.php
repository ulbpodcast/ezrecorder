<?php

require_once "global_config.inc";

Logger::$print_logs = true;

ini_set("allow_url_fopen", 1);

$get_url = "$ezcast_logs_url?action=last_log_sent&source=$classroom";
$post_url = "$ezcast_logs_url?action=push_logs"; //followed by json array

$last_id_sent = file_get_contents($get_url);
if($last_id_sent == false) {
    echo "Failed to get content from $get_url" . PHP_EOL;
    exit(1);
}


$last_id_sent = trim($last_id_sent); //server service does send line returns for some reason

if(!is_numeric($last_id_sent)) {
    echo "Invalid server response:" .PHP_EOL;
    print_r($last_id_sent);
    echo PHP_EOL;
    exit(1);
}

echo "$get_url" . PHP_EOL;
echo "Send newer than $last_id_sent" . PHP_EOL;
$events_to_send = $logger->get_all_events_newer_than($last_id_sent, 1000);

if(count($events_to_send) == 0) {
    echo "Nothing to send" . PHP_EOL;
    exit(0);
}

$events_count = sizeof($events_to_send);

$handle = curl_init($post_url);
if(!$handle) {
    echo "Failed to init curl for $post_url" .PHP_EOL;
    exit(2);
}

$post_array = array(
    'log_data' => json_encode($events_to_send),
);

curl_setopt($handle, CURLOPT_POST, 1); //activate POST parameters
curl_setopt($handle, CURLOPT_POSTFIELDS, $post_array);
curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1); //don't send answer to stdout but in returned string
curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10); 
curl_setopt($handle, CURLOPT_TIMEOUT, 30); //timeout in seconds

$result = curl_exec($handle);
if(!$result !== false) {
    echo "Failed to exec curl for $post_url" . PHP_EOL;
    exit(3);
}

if(strpos($result, "SUCCESS") === false) {
    echo "FAILURE" . PHP_EOL;
    echo "Page returned $result" . PHP_EOL;
    echo "What we sent: " . PHP_EOL;
    print_r($post_array);
    echo PHP_EOL;
    exit(4);
}

echo "Finished. Sent $events_count log entries" . PHP_EOL;
echo "Server responded: $result";

exit(0);