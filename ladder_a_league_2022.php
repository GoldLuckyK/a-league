<?php

require_once(__DIR__.'/library/common.lib.php');

$neccessery_data = array(
	"root" => "https://soccer.sportmonks.com/api/v2.0",
	"token" => "api_token=tJIld2S4bgphAgu5fOJkB2a4YU8AFX0Ibt0ceB1HrR1tLGF4XNmd9k21vSJS",
	"continent_id" => 4,
	"country_id" => 98,
	"league_id" => 1356,
	"season_id" => 20243,
);

function get_ladder_data() {
	global $neccessery_data;
	$curl = curl_init();

	curl_setopt_array($curl, array(
		CURLOPT_URL => $neccessery_data['root'].'/standings'.'/season/'.$neccessery_data['season_id'].'?'.$neccessery_data['token'],
		CURLOPT_RETURNTRANSFER => true
	));

	$result = json_decode(curl_exec($curl))->data;
	return $result;
}

function __process_ladder_data($result) {
	

	return $ladder;
}

while (true) {
	$result = get_ladder_data();
	$ladder = __process_ladder_data($result);

}