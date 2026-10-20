<?php
// PHPUnit includes this bootstrap inside a method scope; preserve config globals.
global $appLocalConfig;
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/autoload.php';
$_SESSION = [];
