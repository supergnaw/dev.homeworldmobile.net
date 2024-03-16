<?php

declare(strict_types=1);

require_once(implode(DIRECTORY_SEPARATOR, [$_SERVER['DOCUMENT_ROOT'], '..', 'vendor', 'autoload.php']));

require_once(__DIR__ . '/Parsedown.php');

$credentials = implode(DIRECTORY_SEPARATOR, [$_SERVER['DOCUMENT_ROOT'], '..', '!globals_sensitive.php');
if file_exists($credentials) {
    require_once($credentials);
}

use app\FormSecurity\FormSecurity as FormSecurity;
use app\Nestbox\Nestbox as Nestbox;
use app\Nestbox\Titmouse\Titmouse as Titmouse;
use app\Nestbox\Babbler\Blabbler as Blabbler;

// keep the php session active
if (PHP_SESSION_ACTIVE !== session_status()) {
    session_start();
}

$api = new \app\PlayFab\Playfab(PLAYFAB_APP_ID);
$api->build_playfab_db();
$api->login_with_email(email: PLAYFAB_EMAIL, password: PLAYFAB_PASSWORD);
