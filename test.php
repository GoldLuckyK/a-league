<?php

$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://soccer.sportmonks.com/api/v2.0/rounds/274714?include=fixtures&api_token=EfSEM5PefLeTpCbu0xn0UHGFUfuz5Zg3HYhmVl4e4iOtc6CvWAKGRirQUsqW',
    CURLOPT_RETURNTRANSFER => true
));

$result = curl_exec($curl);
$result = json_decode($result)->data->fixtures->data;



var_dump($result);


