<?php

use JetBrains\PhpStorm\Internal\ReturnTypeContract;

require_once(__DIR__ . "/library/common.lib.php");

$neccessery_data = array(
	"root" => "https://soccer.sportmonks.com/api/v2.0",
	"token" => "api_token=xmTD1WQsbqvWntC08MEWwqSfczPJu5g5ceBfUfGjqdozIp1E2ZVZCU6Z6Nvi",
	"country_id" => 1161,
	"season_id" => 19735,
);

// function multiRequestForCompetition($__season = 2018, $max_round = 27)
// {
// 	global $root;

// 	$urls = [];
// 	for ($__round = 1; $__round <= $max_round; $__round++) {
// 		$urls[] = $root . "s" . $__season . "/r" . $__round . "/fixture";
// 	}

// 	return __multi_process_urls($urls);
// }

// function __multi_process_urls($__urls)
// {
// 	$curly = array();
// 	$mh = curl_multi_init();

// 	foreach ($__urls as $id => $url) {

// 		$curly[$id] = curl_init();

// 		curl_setopt_array($curly[$id], array(
// 			CURLOPT_URL => $url,
// 			CURLOPT_RETURNTRANSFER => true,
// 			CURLOPT_ENCODING => "",
// 			CURLOPT_MAXREDIRS => 10,
// 			CURLOPT_TIMEOUT => 30,
// 			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
// 			CURLOPT_CUSTOMREQUEST => "GET",
// 			CURLOPT_HTTPHEADER => array(
// 				"cache-control: no-cache",
// 				"Accept: application/json",
// 				"user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36"
// 			),
// 		));

// 		curl_multi_add_handle($mh, $curly[$id]);
// 	}

// 	$running = null;
// 	do {
// 		curl_multi_exec($mh, $running);
// 	} while ($running > 0);

// 	$competition_datas = [];
// 	foreach ($curly as $id => $c) {
// 		$competition_datas[] = __process_competition_data(curl_multi_getcontent($c));
// 		curl_multi_remove_handle($mh, $c);
// 	}

// 	curl_multi_close($mh);
// 	return $competition_datas;
// }

// function __get_round_name($round_arr, $round_code)
// {
// 	foreach ($round_arr as $round_obj) {
// 		if ($round_obj->value == $round_code)
// 			return $round_obj->name;
// 	}
// 	return "Round " . $round_code;
// }

// function __process_competition_data($data)
// {
// 	$json_data = json_decode($data);

// 	$competition = new \stdClass;
// 	$competition->id = 214;
// 	$competition->season = $json_data->season->short_name;
// 	$rounds = $json_data->rounds;
// 	$competition->rounds = $rounds;

// 	return $competition;
// }

// function __gen_broadcaster_arr($__arr)
// {
// 	$result = [];
// 	foreach ($__arr as $data) {
// 		$result[] = $data->name;
// 	}
// 	return $result;
// }

// function __get_round_data($__season, $__round)
// {
// 	global $root;

// 	$data = @file_get_contents($root . "s" . $__season . "/r" . $__round . "/fixture");

// 	return __process_competition_data($data);
// }

// while (true) {
// 	$games = [];

// 	$season_year = date("Y");
// 	if(date("n") < 7) $season_year--;

// 	$competitions = multiRequestForCompetition($season_year);

// 	$teams = [];
// 	$teamIds = [];

// 	foreach ($competitions as $competition) {
// 		foreach ($competition->rounds as $round) {
// 			$game_obj = new \stdClass;

// 			$game_obj->competition_ref_id = $competition->id;
// 			$game_obj->season_name = $competition->season;
// 			$game_obj->round_name = $round->round->name;
// 			$game_obj->round_code = $round->round->number;

// 			$game_obj->game_channel = (isset($round->broadcasters)?json_encode(__gen_broadcaster_arr($round->broadcasters)):'');
// 			$game_obj->game_place = (isset($round->venue->city)?$round->venue->city:'').' - '.$round->venue->name;
// 			$game_obj->game_status = (($round->status == "PreMatch")?1:(($round->status == "FullTime")?3:2));
// 			$game_obj->game_date = substr(str_replace("T", " ", $round->start_date), 0, 19);
// 			$game_obj->homeTeam_key = $round->home_team->abbr;
// 			$game_obj->awayTeam_key = $round->away_team->abbr;
// 			$game_obj->homeTeam_score = (isset($round->match_info->home_team->score)?$round->match_info->home_team->score:0);
// 			$game_obj->awayTeam_score = (isset($round->match_info->away_team->score)?$round->match_info->away_team->score:0);
// 			$game_obj->homeTeam_behinds = 0;
// 			$game_obj->awayTeam_behinds = 0;
// 			$game_obj->homeTeam_goals = 0;
// 			$game_obj->awayTeam_goals = 0;
// 			$game_obj->homeTeam_ladder = 0;
// 			$game_obj->awayTeam_ladder = 0;
// 			$game_obj->homeTeam_super_goals = 0;
// 			$game_obj->awayTeam_super_goals = 0;
// 			$game_obj->homeTeam_odds = (isset($round->odds->home_team)?substr($round->odds->home_team, 1):0);
// 			$game_obj->awayTeam_odds = (isset($round->odds->away_team)?substr($round->odds->away_team, 1):0);

// 			log_game_status($game_obj->game_date, $game_obj->competition_ref_id, "( A_LEAGUE ) ".$round->status);

// 			$games[] = $game_obj;

// 			if(!in_array($round->home_team->abbr, $teamIds)){
// 				$teamIds[] = $round->home_team->abbr;
// 				$team_obj = new \stdClass;
// 				$team_obj->competition_ref_id = $competition->id;
// 				$team_obj->team_ref_id = $round->home_team->id;
// 				$team_obj->team_name = $round->home_team->name;
// 				$team_obj->team_code = $round->home_team->abbr;
// 				$team_obj->team_full_name = $round->home_team->nickname;

// 				$teams[] = $team_obj;
// 			} 

// 			if(!in_array($round->away_team->abbr, $teamIds)){
// 				$teamIds[] = $round->away_team->abbr;
// 				$team_obj = new \stdClass;
// 				$team_obj->competition_ref_id = $competition->id;
// 				$team_obj->team_ref_id = $round->away_team->id;
// 				$team_obj->team_name = $round->away_team->name;
// 				$team_obj->team_code = $round->away_team->abbr;
// 				$team_obj->team_full_name = $round->away_team->nickname;

// 				$teams[] = $team_obj;
// 			} 
// 		}
// 	}

// 	sendTeamsToServer($teams, 2);

// 	sendFixtureToServer($games, 2);

// 	sleep(3);

// 	$game_status = @file_get_contents("/var/www/MatesPicks/public/banter/game_status");
// 	if(!$game_status) {
// 		sleep(60);
// 		continue;
// 	}
// }	

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
			CURLOPT_URL => $neccessery_data['root'] . '/rounds/' . $round_id . '?include=fixtures' . '&'. $neccessery_data['token'],
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
	$mathes_per_rounds = __get_matches_per_round($round_ids);

	$teams = [];
	$teamIds = [];

	foreach ($mathes_per_rounds as $matches_of_one_round) {
		foreach ($matches_of_one_round as $match) {
			$game_obj = new \stdClass;

			$game_obj->competition_ref_id = $match->id;
			$game_obj->season_name = $match->season_id;
			$game_obj->round_name = $match->season_id;
			$game_obj->round_code = $match->season_id;

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

			$games[] = $game_obj;

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
	}

	sendTeamsToServer($teams, 2);

	sendFixtureToServer($games, 2);

	exit;

	sleep(3);

	$game_status = @file_get_contents("/var/www/MatesPicks/public/banter/game_status");
	if (!$game_status) {
		sleep(60);
		continue;
	}
}
