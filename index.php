<?php

require_once __DIR__ . '/bootstrap.php';

use App\Auth;
use App\Response;

Response::redirect(APP_URL . (Auth::user() ? '/dashboard.php' : '/login.php'));
