<?php

$url = 'https://app.sportdataapi.com/api/v1/soccer/countries?';

$ch = curl_init();

curl_setopt_array($ch, array(
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => array(
        "Content-Type: application/json",
        "apikey: d2d533e0-6ca2-11ed-9153-6f0646f5083f"
    )
));

$content = curl_exec($ch);

curl_close($ch);

print_r($content.'sssssssssssssss');

