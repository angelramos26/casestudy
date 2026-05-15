<?php
/**
 * pusher.php — Load Pusher credentials from .env (never hardcode keys!)
 *
 * The constants below are consumed by pusher-broadcast.php (server-side)
 * and exposed as PHP variables to the frontend via header.php (client-side).
 *
 * Credentials must live only in the .env file.
 * The .env file is git-ignored; share keys via .env.example only.
 */

$_pusherEnv = parse_ini_file(__DIR__ . '/../.env');

define('PUSHER_APP_ID',      $_pusherEnv['PUSHER_APP_ID']      ?? '');
define('PUSHER_APP_KEY',     $_pusherEnv['PUSHER_APP_KEY']     ?? '');
define('PUSHER_APP_SECRET',  $_pusherEnv['PUSHER_APP_SECRET']  ?? '');
define('PUSHER_APP_CLUSTER', $_pusherEnv['PUSHER_APP_CLUSTER'] ?? 'ap1');

unset($_pusherEnv); // don't leak env array into global scope
