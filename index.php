<?php
require __DIR__ . '/loader.php';
require __DIR__ . '/tweaks.php';
//$server = new Wpup_UpdateServer();
$server = new Custom_UpdateServer();
$server->handleRequest();