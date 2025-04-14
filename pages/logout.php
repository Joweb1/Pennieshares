<?php
if(session_status() === PHP_SESSION_NONE) {
   session_start();
   generateCsrfToken();
}
require_once __DIR__ . '/../src/functions.php';
$_SESSION['csrf_token'] = NULL;
session_destroy();
header("Location: login");
exit;