<?php

$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://soccer.sportmonks.com/api/v2.0/teams/season/20243?api_token=tJIld2S4bgphAgu5fOJkB2a4YU8AFX0Ibt0ceB1HrR1tLGF4XNmd9k21vSJS',
    CURLOPT_RETURNTRANSFER => true
));

$result = curl_exec($curl);

$result = json_decode($result)->data;

@file_put_contents('test.json', json_encode($result));

var_dump($result);

