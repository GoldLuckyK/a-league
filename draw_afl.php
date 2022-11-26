<?php

	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	ini_set('max_execution_time', 3600);
	set_time_limit(0);
	
	require_once(__DIR__."/library/common.lib.php");

	$root = "https://www.afl.com.au/aflrender/get?service=fullFixture&field=json&site=AFL&params=seasonId:CD_S";

	function multiRequestForCompetition($__competition = "014", $__season = 2019, $max_round = 23) {
		global $root;

		$urls = [];
		for($__round = 1; $__round <= $max_round; $__round++) {
			$urls[] = $root.$__season.$__competition.",roundId:CD_R".$__season.$__competition.(sprintf("%02d", $__round)).",competitionType:AFL";
		}

		return __multi_process_urls($__competition, $__season, $urls);
	}

	function __multi_process_urls($__competition, $__season, $__urls) {
		$curly = array();
		$mh = curl_multi_init();

		foreach ($__urls as $id => $url) {
	 
		    $curly[$id] = curl_init();
	 
			curl_setopt_array($curly[$id], array(
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "GET",
				CURLOPT_HTTPHEADER => array(
				  "cache-control: no-cache",
				  "Accept: application/json",
				  "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36"
				),
			));
		 
			curl_multi_add_handle($mh, $curly[$id]);
		}
	 
		$running = null;
		do {
			curl_multi_exec($mh, $running);
		} while($running > 0);

		$competition_datas = [];
		foreach($curly as $id => $c) {
			$competition_datas[] = __process_competition_data($__competition, $__season, curl_multi_getcontent($c));
			curl_multi_remove_handle($mh, $c);
		}
	 
		curl_multi_close($mh);
		return $competition_datas;
	}

	function __process_competition_data($__competition, $__season, $data) {
		$json_data = json_decode($data);
		if(!$json_data) return false;
		if(!isset($json_data->fixtures)) return false;
		
		$competition = new \stdClass;
		$competition->id = $__competition;
		$competition->season = $__season;
		$rounds = $json_data->fixtures;
		$competition->rounds = $rounds;

		return $competition;
	}

	function __get_round_data($__competition, $__season, $__round) {
		global $root;

		$data = @file_get_contents($root.$__season.$__competition.",roundId:CD_R".$__season.$__competition.(sprintf("%02d", $__round)).",competitionType:AFL");
		
		return __process_competition_data($__competition, $__season, $data);
	}

	function __get_last_round($__competition, $__year) {
		$file_name = "afl_".$__competition.".round";
		if(file_exists($file_name)) {
			if(filemtime($file_name)) {
				if(time() < filemtime($file_name) + 86400) {
					return @file_get_contents($file_name);
				}
			}
		}
		$url = "https://www.afl.com.au/aflrender/get?service=ladder&serviceParamNames=seasonId&site=AFL&params=seasonId:CD_S".$__year.$__competition;
		$data = @file_get_contents($url);
		$round_info = json_decode($data);
		if($round_info) {
			@file_put_contents($file_name, $round_info->roundNumber);
			return $round_info->roundNumber;
		}
		return 1;
	}

	function __get_token() {
		$file_name = "afl.token";
		if(file_exists($file_name)) {
			if(filemtime($file_name) + 30 > time()) {
				return @file_get_contents($file_name);
			}
		}

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://www.afl.com.au/api/cfs/afl/WMCTok",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => "",
		  CURLOPT_HTTPHEADER => array(
		    "cache-control: no-cache"
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if($response) {
			$response = json_decode($response);
			$token = $response->token;
			@file_put_contents($file_name, $token);

			return $token;
		}

		return false;
	}

	function __get_live_score($competition, $season, $round, $game_index, $token) {
		$str_url = "https://www.afl.com.au/api/cfs/afl/matchItem/".$game_index;

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => $str_url,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
		    "cache-control: no-cache",
		    "x-media-mis-token: ".$token
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		return $response;
	}

	function __get_score_data($competition, $season, $round, $game_index) 
	{
		$token = __get_token();

		$data = __get_live_score($competition, $season, $round, $game_index, $token);

		return json_decode($data);
	}

while (true) {
	$games = [];

	$competitions[] = __get_round_data("014", date("Y"), __get_last_round("014", date("Y")) - 1);
	$competitions[] = __get_round_data("014", date("Y"), __get_last_round("014", date("Y")));
	$competitions[] = __get_round_data("014", date("Y"), __get_last_round("014", date("Y")) + 1);
	$competitions[] = __get_round_data("264", date("Y"), __get_last_round("264", date("Y")) - 1);
	$competitions[] = __get_round_data("264", date("Y"), __get_last_round("264", date("Y")));
	$competitions[] = __get_round_data("264", date("Y"), __get_last_round("264", date("Y")) + 1);

	$teams = [];
	$check_live = false;
	foreach ($competitions as $competition) {
		$teamIds = [];
		if(!isset($competition->rounds)) continue;
		foreach ($competition->rounds as $key => $round) {
			$game_time = strtotime($round->match->startDateTimes[0]->date.' '.$round->match->startDateTimes[0]->time);

			$game_obj = new \stdClass;

			$game_obj->competition_ref_id = $competition->id;
			$game_obj->season_name = $competition->season;
			$game_obj->round_name = $round->match->roundName;
			$game_obj->round_code = $round->match->roundNumber;

			$game_obj->game_channel = '';
			$game_obj->game_place = $round->match->venueName;

			$game_obj->game_status = (($round->match->status == "SCHEDULED")?1:((($round->match->status == "Full Time") || ($round->match->status == "POSTGAME"))?3:2));
			$game_obj->game_date = date("Y-m-d H:i:s", strtotime($round->match->startDateTimes[0]->date.' '.$round->match->startDateTimes[0]->time));
			$game_obj->homeTeam_key = $round->homeTeam->teamAbbr;
			$game_obj->awayTeam_key = $round->awayTeam->teamAbbr;

			$game_match_id = $round->match->matchId;

			if(($game_time < time()) && ($round->match->status != "Full Time") && ($round->match->status != "POSTGAME")) {
				$check_live = true;
				$live_data = __get_score_data($competition->id, $competition->season, $round->match->roundNumber, $game_match_id);
				$game_obj->homeTeam_score = (isset($live_data->score->homeTeamScore->matchScore->totalScore)?$live_data->score->homeTeamScore->matchScore->totalScore:0);
				$game_obj->awayTeam_score = (isset($live_data->score->awayTeamScore->matchScore->totalScore)?$live_data->score->awayTeamScore->matchScore->totalScore:0);
				$game_obj->homeTeam_behinds = (isset($live_data->score->homeTeamScore->matchScore->behinds)?$live_data->score->homeTeamScore->matchScore->behinds:0);
				$game_obj->awayTeam_behinds = (isset($live_data->score->awayTeamScore->matchScore->behinds)?$live_data->score->awayTeamScore->matchScore->behinds:0);
				$game_obj->homeTeam_goals = (isset($live_data->score->homeTeamScore->matchScore->goals)?$live_data->score->homeTeamScore->matchScore->goals:0);
				$game_obj->awayTeam_goals = (isset($live_data->score->awayTeamScore->matchScore->goals)?$live_data->score->awayTeamScore->matchScore->goals:0);
				$game_obj->homeTeam_ladder = 0;
				$game_obj->awayTeam_ladder = 0;
				$game_obj->homeTeam_super_goals = (isset($live_data->score->homeTeamScore->matchScore->superGoals)?$live_data->score->homeTeamScore->matchScore->superGoals:0);
				$game_obj->awayTeam_super_goals = (isset($live_data->score->awayTeamScore->matchScore->superGoals)?$live_data->score->awayTeamScore->matchScore->superGoals:0);
			} else {
				$game_obj->homeTeam_score = (isset($round->homeTeam->totalScore)?$round->homeTeam->totalScore:0);
				$game_obj->awayTeam_score = (isset($round->awayTeam->totalScore)?$round->awayTeam->totalScore:0);
				$game_obj->homeTeam_behinds = (isset($round->homeTeam->behinds)?$round->homeTeam->behinds:0);
				$game_obj->awayTeam_behinds = (isset($round->awayTeam->behinds)?$round->awayTeam->behinds:0);
				$game_obj->homeTeam_goals = (isset($round->homeTeam->goals)?$round->homeTeam->goals:0);
				$game_obj->awayTeam_goals = (isset($round->awayTeam->goals)?$round->awayTeam->goals:0);
				$game_obj->homeTeam_ladder = (isset($round->homeTeam->ladderPosition)?$round->homeTeam->ladderPosition:0);
				$game_obj->awayTeam_ladder = (isset($round->awayTeam->ladderPosition)?$round->awayTeam->ladderPosition:0);
				$game_obj->homeTeam_super_goals = (isset($round->homeTeam->superGoals)?$round->homeTeam->superGoals:0);
				$game_obj->awayTeam_super_goals = (isset($round->awayTeam->superGoals)?$round->awayTeam->superGoals:0);
			}
			$game_obj->homeTeam_odds = 0;
			$game_obj->awayTeam_odds = 0;

			log_game_status($game_obj->game_date, $game_obj->competition_ref_id, "( AFL ) ".$round->match->status);

			$games[] = $game_obj;

			if(!in_array($round->homeTeam->teamAbbr, $teamIds)){
				$teamIds[] = $round->homeTeam->teamAbbr;
				$team_obj = new \stdClass;
				$team_obj->competition_ref_id = $competition->id;
				$team_obj->team_ref_id = substr($round->homeTeam->teamId, 4);
				$team_obj->team_name = $round->homeTeam->teamNickname;
				$team_obj->team_code = $round->homeTeam->teamAbbr;
				$team_obj->team_full_name = $round->homeTeam->teamName;

				$teams[] = $team_obj;
			}

			if(!in_array($round->awayTeam->teamAbbr, $teamIds)){
				$teamIds[] = $round->awayTeam->teamAbbr;
				$team_obj = new \stdClass;
				$team_obj->competition_ref_id = $competition->id;
				$team_obj->team_ref_id = substr($round->awayTeam->teamId, 4);
				$team_obj->team_name = $round->awayTeam->teamName;
				$team_obj->team_code = $round->awayTeam->teamAbbr;
				$team_obj->team_full_name = $round->awayTeam->teamNickname;

				$teams[] = $team_obj;
			}
		}
	}
	
	if($check_live)
		@file_put_contents("afl_live.status", "1");
	else
		@file_put_contents("afl_live.status", "");
	
	sendTeamsToServer($teams, 3);

	sendFixtureToServer($games, 3);

	sleep(3);
	
	$game_status = @file_get_contents("/var/www/MatesPicks/public/banter/game_status");
	if(!$game_status) {
		sleep(60);
		continue;
	}
}	
?>