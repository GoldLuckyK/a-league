#!/bin/sh

kill $(ps aux | grep '[p]hp' | awk '{print $2}')

cd /var/www/Scrapper

php draw_afl.php > /dev/null 2>&1 &
php draw_a_league.php > /dev/null 2>&1 &
php draw_nrc.php > /dev/null 2>&1 &
php draw_nrl.php > /dev/null 2>&1 &

php ladder_a_league.php > /dev/null 2>&1 &
php ladder_afl.php > /dev/null 2>&1 &
php ladder_nrc.php > /dev/null 2>&1 &
php ladder_nrl.php > /dev/null 2>&1 &

php server_cron.php > /dev/null 2>&1 &

