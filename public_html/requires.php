<?php

declare(strict_types=1);

require_once(implode(DIRECTORY_SEPARATOR, [$_SERVER['DOCUMENT_ROOT'], '..', 'vendor', 'autoload.php']));

// keep the php session active
if (PHP_SESSION_ACTIVE !== session_status()) {
    session_start();
}
