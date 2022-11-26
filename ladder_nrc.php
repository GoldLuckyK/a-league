<?php

	require_once(__DIR__."/library/common.lib.php");

	$root = "https://www.rugby.com.au/competitions/nrc";

	function __process_competition_data($ladders_data) {
		$competitions = [];
		
		$competition = new \stdClass;
		$competition->league_id = 4;
		$competition->competition_ref_id = 247;
		$competition->season_code = date("Y");
		$competition->round_code = 0;
		
		$ladders = [];

		foreach ($ladders_data as $ladder_obj) {
			$ladder = new \stdClass;

			$ladder->team_id = $ladder_obj->team;
			$ladder->next_team_id = 0;
			$ladder->ladder_position = $ladder_obj->position;
			$ladder->stats_played = $ladder_obj->played;
			$ladder->stats_wins = $ladder_obj->wins;
			$ladder->stats_drawn = $ladder_obj->draws;
			$ladder->stats_lost = $ladder_obj->losses;
			$fld_name = "points-for";
			$ladder->stats_points_for = $ladder_obj->$fld_name;
			$fld_name = "points-against";
			$ladder->stats_points_against = $ladder_obj->$fld_name;
			$fld_name = "bonus-points";
			$ladder->stats_bonus_points = $ladder_obj->$fld_name;
			$ladder->stats_points = $ladder_obj->points;

			$ladders[] = $ladder;
		}

		$competition->ladder = $ladders;
		$competitions[] = $competition;

		return $competitions;
	}

while (true) {

	$data = @file_get_contents($root);

	$table = __get_values($data, 'ladder-all">', '</div>');

	$table_obj = str_get_html($table);

	$ladders = [];

	foreach ($table_obj->find("tbody tr") as $key => $tr_obj) {
		if($key == 0) continue;

		$tds = $tr_obj->find("td");

		$team_obj = new \stdClass();
		$team_obj->position = strip_tags($tds[0]->innertext);
		$team_obj->team = trim(strip_tags($tds[1]->innertext));

		for($i=2; $i<count($tds); $i++){
			$key_name = $tds[$i]->class;
			$key_name = str_replace("cell-", "", $key_name);
			$team_obj->$key_name = strip_tags($tds[$i]->innertext);
		}

		$ladders[] = $team_obj;
	}

	$competitions = __process_competition_data($ladders);

	sendLadderToServer($competitions);

	sleep(3);
	
	$game_status = @file_get_contents("/var/www/MatesPicks/public/banter/game_status");
	if(!$game_status) {
		sleep(60);
		continue;
	}
}	
?>