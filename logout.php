<?php
require_once __DIR__ . '/assets/php/auth.php';

logout_user();
header('Location: login.php');
exit;
