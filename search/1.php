<?php
$ch = curl_init();

$vars = json_encode([
        "channel" => 'C02Q8QUU6E4',
        "text" => "Hello",
    ]);
curl_setopt($ch, CURLOPT_URL,"https://slack.com/api/chat.postMessage");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS,$vars);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$headers = [
    "Authorization: Bearer xoxb-2801156916867-2794429722806-fGlkGetJO8ddg0DqcO1dA9jH",
	"Content-type: application/json; charset=utf-8"
];

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$server_output = curl_exec ($ch);
var_dump($server_output);
curl_close ($ch);
?>