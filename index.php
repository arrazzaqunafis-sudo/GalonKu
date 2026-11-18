<?php
declare(strict_types=1);

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
header('Location: ' . $basePath . '/GalonKu_Mobile_Starter/pwa/customer/', true, 302);
exit;
