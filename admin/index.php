<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();
header('Location: ' . BASE_URL . '/admin/dashboard.php');
exit;
