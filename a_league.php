<?php

use JetBrains\PhpStorm\Internal\ReturnTypeContract;

require_once(__DIR__ . "/library/common.lib.php");

$neccessery_data = array(
	"root" => "https://soccer.sportmonks.com/api/v2.0",
	"token" => "api_token=tJIld2S4bgphAgu5fOJkB2a4YU8AFX0Ibt0ceB1HrR1tLGF4XNmd9k21vSJS",
	"country_id" => 1161,
	"season_id" => 19735,
);

function __get_round_ids()
{
	global $neccessery_data;

	$curl = curl_init();

	curl_setopt_array($curl, array(
		CURLOPT_URL => $neccessery_data['root'] . '/rounds/season/' . (int)$neccessery_data['season_id'] . '?' . $neccessery_data['token'],
		CURLOPT_RETURNTRANSFER => true,
	));

	$reasults = json_decode(curl_exec($curl))->data;
	curl_close($curl);

	$round_ids_array = [];

	foreach ($reasults as $reasult) {
		$round_ids_array[] = $reasult->id;
	}

	return $round_ids_array;
}

function __get_matches_per_round($round_ids)
{
	global $neccessery_data;

	$curls = array();
	$mcurl = curl_multi_init();

	foreach ($round_ids as $id => $round_id) {
		$curls[$id] = curl_init();

		curl_setopt_array($curls[$id], array(
			CURLOPT_URL => $neccessery_data['root'] . '/rounds/' . $round_id . '?include=fixtures' . '&' . $neccessery_data['token'],
			CURLOPT_RETURNTRANSFER => true,
		));

		curl_multi_add_handle($mcurl, $curls[$id]);
	}

	$running = null;
	do {
		curl_multi_exec($mcurl, $running);
	} while ($running > 0);

	$matches_per_round = [];
	foreach ($curls as $id => $curl) {
		$matches_per_round[] = __process_match_data(curl_multi_getcontent($curl));
		curl_multi_remove_handle($mcurl, $curl);
	}

	curl_multi_close($mcurl);
	return $matches_per_round;
}

function __process_match_data($data)
{
	$json_data = json_decode($data);
	
	$matches_of_one_round = $json_data->data->fixtures->data;
	
	return $matches_of_one_round;
}

$round_ids = __get_round_ids();

while (true) {
	$games = [];
	$matches_per_round = __get_matches_per_round($round_ids);

	

	$teams = [];
	$teamIds = [];

	$matchDay = 1;

	foreach ($matches_per_round as $matches_of_one_round) {
		$gamesOfOneRound = new \stdClass;
		$matches_per_round = [];
		foreach ($matches_of_one_round as $match) {
			$game_obj = new \stdClass;

			$game_obj->competition_ref_id = $match->id;
			$game_obj->season_name = $match->season_id;
			$game_obj->round_name = $matchDay;
			$game_obj->round_code = '$match->round_id';

			$game_obj->game_channel = '';
			$game_obj->game_status = $match->time->status == "FT" ? 3 : 2;
			$game_obj->game_date = $match->time->starting_at->date;
			$game_obj->homeTeam_key = $match->localteam_id;
			$game_obj->awayTeam_key = $match->visitorteam_id;
			$game_obj->homeTeam_score = $match->scores->localteam_score;
			$game_obj->awayTeam_score = $match->scores->visitorteam_score;
			$game_obj->round_code = $match->season_id;
			$game_obj->homeTeam_behinds = 0;
			$game_obj->awayTeam_behinds = 0;
			$game_obj->homeTeam_goals = 0;
			$game_obj->awayTeam_goals = 0;
			$game_obj->homeTeam_ladder = $match->standings->localteam_position;
			$game_obj->awayTeam_ladder = $match->standings->visitorteam_position;
			$game_obj->homeTeam_super_goals = 0;
			$game_obj->awayTeam_super_goals = 0;
			$game_obj->homeTeam_odds = 0;
			$game_obj->homeTeam_odds = 0;
			$game_obj->awayTeam_odds = 0;

			log_game_status($game_obj->game_date, $game_obj->competition_ref_id, "( A_LEAGUE ) " . $match->time->status);

			$matches_per_round[] = $game_obj;

			if (!in_array($match->localteam_id, $teamIds)) {
				$teamIds[] = $match->localteam_id;
				$team_obj = new \stdClass;
				$team_obj->competition_ref_id = $match->id;
				$team_obj->team_ref_id = $match->localteam_id;
				$team_obj->team_code = $match->localteam_id;

				$teams[] = $team_obj;
			}

			if (!in_array($match->visitorteam_id, $teamIds)) {
				$teamIds[] = $match->visitorteam_id;
				$team_obj = new \stdClass;
				$team_obj->competition_ref_id = $match->id;
				$team_obj->team_ref_id = $match->visitorteam_id;
				$team_obj->team_code = $match->visitorteam_id;

				$teams[] = $team_obj;
			}
		}
		$gamesOfOneRound->Matchday = $matchDay;
		$gamesOfOneRound->MatchData = $matches_per_round;

		$games[] = $gamesOfOneRound;
		$matchDay++;
	}

	@file_put_contents('a-league_matchs_data.json', json_encode($games));

	var_dump($games);
	exit;

	sendTeamsToServer($teams, 2);

	sendFixtureToServer($games, 2);

	sleep(3);

	$game_status = @file_get_contents("/var/www/MatesPicks/public/banter/game_status");
	if (!$game_status) {
		sleep(60);
		continue;
	}
}
