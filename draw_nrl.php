<?php

	require_once(__DIR__."/library/common.lib.php");

	$root = "https://www.nrl.com/draw/data?";

	$competitions = json_decode('[{"name":"Telstra Premiership","value":111,"seasons":1998,"key":"nrl-premiership"},{"name":"Holden Women\'s Premiership","value":161,"seasons":2018,"key":"nrl-womens-premiership"},{"name":"Intrust Super Premiership","value":113,"seasons":2014,"key":"intrust-super-premiership"},{"name":"Intrust Super Cup","value":114,"seasons":2014,"key":"intrust-super-cup"}]');

	// Get Latest Data
	function multiRequest() {
		global $competitions, $root;

		$urls = [];
		foreach ($competitions as $id => $d) {
			$urls[] = $root."competition=".$d->value;
		}

		return __multi_process_urls($urls);
	}

	function multiRequestForCompetition($competition_id = 118, $__season = 2019, $max_round = 25) {
		global $root;

		$urls = [];
		for($__round = 1; $__round <= $max_round; $__round++) {
			$urls[] = $root."competition=".$competition_id."&season=".$__season."&round=".$__round;
		}

		return __multi_process_urls($urls);
	}

	function __multi_process_urls($__urls) {
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
			$competition_datas[] = __process_competition_data(curl_multi_getcontent($c));
			curl_multi_remove_handle($mh, $c);
		}
	 
		curl_multi_close($mh);
		return $competition_datas;
	}

	function __get_round_name($round_arr, $round_code) {
		foreach ($round_arr as $round_obj) {
			if($round_obj->value == $round_code)
				return $round_obj->name;
		}
		return "Round ".$round_code;
	}

	function __process_competition_data($data) {
		$json_data = json_decode($data);
		
		$competition = new \stdClass;
		$competition->id = $json_data->selectedCompetitionId;
		$competition->season = $json_data->selectedSeasonId;
		$competition->round_code = $json_data->selectedRoundId;
		$competition->round_name = __get_round_name($json_data->filterRounds, $json_data->selectedRoundId);
		$draw = $json_data->fixtures;
		$competition->draw = $draw;

		return $competition;
	}

	function __get_competition_data($__id) {
		global $root;

		$data = @file_get_contents($root."competition=".$__id);
		
		return __process_competition_data($data);
	}

	function __get_season_data($__id, $__season) {
		global $root;

		$data = @file_get_contents($root."competition=".$__id."&season=".$__season);
		
		return __process_competition_data($data);
	}

	function __get_round_data($__id, $__season, $__round) {
		global $root;

		$data = @file_get_contents($root."competition=".$__id."&season=".$__season."&round=".$__round);
		
		return __process_competition_data($data);
	}

while (true) {
	$games = [];

	$competitions =  multiRequest();
	//$competitions = multiRequestForCompetition(114, 2019, 23);
	$teams = [];

	$additional_competitons = [];
	foreach ($competitions as $competition) {
		$additional_competiton = __get_round_data($competition->id, $competition->season, $competition->round_code + 1);
		$additional_competitons[] = $additional_competiton;
	}

	foreach ($additional_competitons as $additional_competiton) {
		$competitions[] = $additional_competiton;
	}

	foreach ($competitions as $competition) {
		$teamIds = [];
		foreach ($competition->draw as $match) {
			if(!isset($match->homeTeam->theme)) continue;
			if(!isset($match->awayTeam->theme)) continue;

				$game_obj = new \stdClass;

				$game_obj->competition_ref_id = $competition->id;
				$game_obj->season_name = $competition->season;
				$game_obj->round_name = $competition->round_name;
				$game_obj->round_code = $competition->round_code;
				$game_obj->game_channel = (isset($match->broadcastChannels)?json_encode($match->broadcastChannels):'');
				$game_obj->game_place = $match->venue;
				$game_obj->game_status = (($match->matchMode == "Pre")?1:(($match->matchMode == "Post")?3:2));
				$game_obj->game_date = str_replace("T", " ", str_replace("Z", " ", $match->clock->kickOffTimeLong));
				$game_obj->homeTeam_key = $match->homeTeam->theme->key;
				$game_obj->awayTeam_key = $match->awayTeam->theme->key;
				$game_obj->homeTeam_score = (isset($match->homeTeam->score)?$match->homeTeam->score:0);
				$game_obj->awayTeam_score = (isset($match->awayTeam->score)?$match->awayTeam->score:0);
				$game_obj->homeTeam_behinds = 0;
				$game_obj->awayTeam_behinds = 0;
				$game_obj->homeTeam_goals = 0;
				$game_obj->awayTeam_goals = 0;
				$game_obj->homeTeam_ladder = 0;
				$game_obj->awayTeam_ladder = 0;
				$game_obj->homeTeam_super_goals = 0;
				$game_obj->awayTeam_super_goals = 0;
				$game_obj->homeTeam_odds = (isset($match->homeTeam->odds)?$match->homeTeam->odds:0);
				$game_obj->awayTeam_odds = (isset($match->awayTeam->odds)?$match->awayTeam->odds:0);

				log_game_status($game_obj->game_date, $game_obj->competition_ref_id, "( NRL )".$match->matchMode);

				$games[] = $game_obj;

				if(!in_array($match->homeTeam->theme->key, $teamIds)){
					$teamIds[] = $match->homeTeam->theme->key;
					$team_obj = new \stdClass;
					$team_obj->competition_ref_id = $competition->id;
					$team_obj->team_ref_id = $match->homeTeam->teamId;
					$team_obj->team_name = $match->homeTeam->nickName;
					$team_obj->team_code = $match->homeTeam->theme->key;
					$team_obj->team_full_name = $match->homeTeam->nickName;

					$teams[] = $team_obj;
				}

				if(!in_array($match->awayTeam->theme->key, $teamIds)){
					$teamIds[] = $match->awayTeam->theme->key;
					$team_obj = new \stdClass;
					$team_obj->competition_ref_id = $competition->id;
					$team_obj->team_ref_id = $match->awayTeam->teamId;
					$team_obj->team_name = $match->awayTeam->nickName;
					$team_obj->team_code = $match->awayTeam->theme->key;
					$team_obj->team_full_name = $match->awayTeam->nickName;

					$teams[] = $team_obj;
				}
		}
	}
//	print_r($games);
//	exit();
			
	sendTeamsToServer($teams, 1);

	sendFixtureToServer($games, 1);

	sleep(3);
	
	$game_status = @file_get_contents("/var/www/MatesPicks/public/banter/game_status");
	if(!$game_status) {
		sleep(60);
		continue;
	}
}	
?>