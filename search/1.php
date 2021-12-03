<?php
$ch = curl_init();

$vars = json_encode([
        "channel" => 'U02KEKWTSF7',
        "text" => "Hello",
    ]);
curl_setopt($ch, CURLOPT_URL,"https://slack.com/api/chat.scheduleMessage");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS,$vars);  //Post Fields
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$headers = [
    "Authorization: Bearer xoxb-1173194449971-2797262894883-3F6ti1BJyjLKuCEHfU68nMnf",
	"Content-type: application/json; charset=utf-8"
];

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$server_output = curl_exec ($ch);
var_dump($server_output);
curl_close ($ch);

?>

xoxb-1173194449971-2797262894883-3F6ti1BJyjLKuCEHfU68nMnf
xapp-1-A02PF5WLAR0-2821001438512-35fbd8967c0be3e28e295826c401cd458b949fef2a08cd6a064ec3ebb4c4c23b