<?php

require_once(__DIR__ . '/library/common.lib.php');

$neccessery_data = array(
	"root" => "https://soccer.sportmonks.com/api/v2.0",
	"token" => "api_token=tJIld2S4bgphAgu5fOJkB2a4YU8AFX0Ibt0ceB1HrR1tLGF4XNmd9k21vSJS",
	"continent_id" => 4,
	"country_id" => 98,
	"league_id" => 1356,
	"season_id" => 20243,
);

function get_ladder_data()
{
	global $neccessery_data;
	$curl = curl_init();

	curl_setopt_array($curl, array(
		CURLOPT_URL => $neccessery_data['root'] . '/standings' . '/season/' . $neccessery_data['season_id'] . '?' . $neccessery_data['token'],
		CURLOPT_RETURNTRANSFER => true
	));

	$result = json_decode(curl_exec($curl))->data[0];
	return $result;
}

function __process_competition_data($result)
{

	$competition = new \stdClass;

	$competition->league_id = $result->league_id;
	$competition->competition_ref_id = 214;
	$competition->season_code = $result->season_id;
	$competition->round_code = 0;

	$ladders = [];
	foreach ($result->standings->data as $standing) {
		$ladder = new \stdClass;

		$ladder->team_id = 	$standing->team_id;
		$ladder->next_team_id = 0;
		$ladder->ladder_position = 	$standing->position;
		$ladder->stats_played = $standing->overall->games_played;
		$ladder->stats_wins = $standing->overall->won;
		$ladder->stats_drawn = $standing->overall->draw;
		$ladder->stats_lost = $standing->overall->lost;
		$fld_name = "goals_scored";
		$ladder->stats_points_for = $standing->overall->$fld_name;
		$fld_name = "goals_against";
		$ladder->stats_points_against = $standing->overall->$fld_name;
		$ladder->stats_bonus_points = 0;
		$ladder->stats_points = $standing->points;

		$ladders[] = $ladder;
	}

	$competition->ladder = $ladders;

	return $competition;
}

while (true) {
	$result = get_ladder_data();
	$ladder = __process_competition_data($result);

	@file_put_contents('ladder_a-league_2022.json', json_encode($ladder, JSON_PRETTY_PRINT));

	var_dump($ladder);
	exit;

	sendLadderToServer($ladder);

	sleep(3);

	$game_status = @file_get_contents("/var/www/MatesPicks/public/banter/game_status");
	if (!$game_status) {
		sleep(60);
		continue;
	}
}
