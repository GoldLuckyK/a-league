<?php

$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://soccer.sportmonks.com/api/v2.0/rounds/274714?include=fixtures&api_token=xmTD1WQsbqvWntC08MEWwqSfczPJu5g5ceBfUfGjqdozIp1E2ZVZCU6Z6Nvi',
    CURLOPT_RETURNTRANSFER => true
));

$result = curl_exec($curl);
$result = json_decode($result)->data->fixtures->data;



var_dump($result);


