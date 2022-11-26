<?php

	require_once(__DIR__."/library/common.lib.php");

	$root = "https://www.rugby.com.au/ajax/fixturesandResults?competitionID=";

	function multiRequestForCompetition($__competition = "247") {
		global $root;

		$urls = [];
		$urls[] = $root.$__competition;

		return __multi_process_urls($__competition, $urls);
	}

	function __multi_process_urls($__competition, $__urls) {
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
			$competition_datas[] = __process_competition_data($__competition, curl_multi_getcontent($c));
			curl_multi_remove_handle($mh, $c);
		}
	 
		curl_multi_close($mh);
		return $competition_datas;
	}

	function __process_competition_data($__competition, $data) {
		$json_data = json_decode($data);
		
		$competition = new \stdClass;
		$competition->id = $__competition;
		$rounds = $json_data->rounds;
		$competition->rounds = $rounds;

		return $competition;
	}

while (true) {
	$games = [];

	$competitions = multiRequestForCompetition("247");
	$teams = [];

	foreach ($competitions as $competition) {
		$teamIds = [];
		foreach ($competition->rounds as $round) {
			foreach ($round->fixtures as $game) {
				$game_obj = new \stdClass;

				$game_obj->competition_ref_id = $competition->id;
				$game_obj->season_name = $game->season_id;
				if($round->round_type == 1)
					$game_obj->round_name = "Round ".$round->round_name;
				else
					$game_obj->round_name = $round->round_name;
				$game_obj->round_code = $round->name;

				$game_obj->game_channel = '';
				$game_obj->game_place = $game->venue;
				$game_obj->game_status = (($game->status == "SCHEDULED")?1:(($game->status == "Result")?3:2));
				$game_obj->game_date = date("Y-m-d H:i:s", strtotime($game->datetime));
				$game_obj->homeTeam_key = $game->teams[0]->name;
				$game_obj->awayTeam_key = $game->teams[1]->name;
				$game_obj->homeTeam_score = (isset($game->teams[0]->score)?$game->teams[0]->score:0);
				$game_obj->awayTeam_score = (isset($game->teams[1]->score)?$game->teams[1]->score:0);
				$game_obj->homeTeam_behinds = 0;
				$game_obj->awayTeam_behinds = 0;
				$game_obj->homeTeam_goals = 0;
				$game_obj->awayTeam_goals = 0;
				$game_obj->homeTeam_ladder = 0;
				$game_obj->awayTeam_ladder = 0;
				$game_obj->homeTeam_super_goals = 0;
				$game_obj->awayTeam_super_goals = 0;
				$game_obj->homeTeam_odds = 0;
				$game_obj->awayTeam_odds = 0;

				log_game_status($game_obj->game_date, $game_obj->competition_ref_id, "( NRC ) ".$game->status);

				$games[] = $game_obj;

				if(!in_array($game->teams[0]->name, $teamIds)){
					$teamIds[] = $game->teams[0]->name;
					$team_obj = new \stdClass;
					$team_obj->competition_ref_id = $competition->id;
					$team_obj->team_ref_id = '';
					$team_obj->team_name = $game->teams[0]->name;
					$team_obj->team_code = $game->teams[0]->name;
					$team_obj->team_full_name = $game->teams[0]->name;

					$teams[] = $team_obj;
				}
				
				if(!in_array($game->teams[1]->name, $teamIds)){
					$teamIds[] = $game->teams[1]->name;
					$team_obj = new \stdClass;
					$team_obj->competition_ref_id = $competition->id;
					$team_obj->team_ref_id = '';
					$team_obj->team_name = $game->teams[1]->name;
					$team_obj->team_code = $game->teams[1]->name;
					$team_obj->team_full_name = $game->teams[1]->name;

					$teams[] = $team_obj;
				}
			}
		}
	}

	sendTeamsToServer($teams, 4);

	sendFixtureToServer($games, 4);

	sleep(3);
	
	$game_status = @file_get_contents("/var/www/MatesPicks/public/banter/game_status");
	if(!$game_status) {
		sleep(60);
		continue;
	}
}	
?>