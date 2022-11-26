<?php

	require_once(__DIR__."/library/common.lib.php");

while (true) {
	$root = "https://www.rugbyworldcup.com/matches";
	$data = @file_get_contents($root);
	$html = str_get_html($data);
	//$fixtures = $html->find('.fixtures__match-content');
	$fixtures = $html->find('.fixtures__match-wrapper');

	$games = [];

	foreach ($fixtures as $fixture) {
		$game_month = '';
		$game_month = $fixture->find('.fixtures-date__month', 0);
		if($game_month) $game_month = trim($game_month->innertext);
		else continue;
		if($game_month == "September") $game_month = 9;
		else if($game_month == "October") $game_month = 10;
		else if($game_month == "November") $game_month = 11;
		else $game_month = 12;

		$game_day = '';
		$game_day = $fixture->find('.fixtures-date__day-number', 0);
		if($game_day) $game_day = trim($game_day->innertext);
		else continue;

		$game_time = '';
		$game_time = $fixture->find('.fixtures__time--local-time span.bold', 0);
		if($game_time) $game_time = trim($game_time->innertext);
		else continue;		

		$game_date = date('Y-m-d H:i:s', strtotime("2019-$game_month-$game_day $game_time") - 3600 * 9);

		$game_pool = '';
		$game_pool = $fixture->find('.fixtures__event-phase', 0);
		if($game_pool) $game_pool = trim($game_pool->innertext);
		else continue;

		$game_place = '';
		$game_place = $fixture->find('.fixtures__venue', 0);
		if($game_place) $game_place = trim($game_place->innertext);
		else continue;

		$teams = $fixture->find('.fixtures__flag');

		if(count($teams) == 0) continue;
		$homeTeam_key = substr($teams[0]->class, -3);
		$awayTeam_key = substr($teams[1]->class, -3);

		if(($homeTeam_key == "-sm") || ($awayTeam_key == "-sm")) continue;

		$team_scores = $fixture->find('.fixtures__team-score');
		$homeTeam_score = 0;
		$awayTeam_score = 0;

		if(count($team_scores)) {
			$homeTeam_score = trim($team_scores[0]->innertext);
			$awayTeam_score = trim($team_scores[1]->innertext);
		}

		$game_status = 1;
		if($fixture->find('.fixtures__team--winner', 0)) $game_status = 3;
		else if(strpos($fixture->class, "fixtures__match-wrapper--complete") !== false) $game_status = 3;
		else if(strpos($fixture->class, "fixtures__match-wrapper--cancelled") !== false) $game_status = 4;
		else if(strtotime($game_date) < time()) $game_status = 2;

		$game_obj = new \stdClass;

		$game_obj->competition_ref_id = 999;
		$game_obj->season_name = 2019;
		$game_obj->round_name = $game_pool;
		$game_obj->round_code = $game_pool;

		$game_obj->game_channel = '';
		$game_obj->game_place = $game_place;
		$game_obj->game_status = $game_status;
		$game_obj->game_date = $game_date;
		$game_obj->homeTeam_key = $homeTeam_key;
		$game_obj->awayTeam_key = $awayTeam_key;
		$game_obj->homeTeam_score = $homeTeam_score;
		$game_obj->awayTeam_score = $awayTeam_score;
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

		$games[] = $game_obj;
	}
//print_r($games);
//exit();
	sendFixtureToServer($games, 9);

	sleep(30);
}
?>