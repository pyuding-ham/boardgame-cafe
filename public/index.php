<?php
include '../src/bootstrap.php';

$raw_uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$path = mb_strtolower($raw_uri, 'UTF-8');
$path = substr($path, strlen(DOC_ROOT));
$path = trim($path, '/'); 
$parts = explode('/', $path);

if ($parts[0] != 'admin') {
    $page = $parts[0] ? str_replace('.php', '', $parts[0]) : 'index';
    $urlParam = $parts[1] ?? null; 

    if ($page === 'board') {
        $boardId = $urlParam ? (int)$urlParam : null; 
    }
} else {
    $admin_page = isset($parts[1]) ? str_replace('.php', '', $parts[1]) : '';
    $page = 'admin/' . $admin_page;
}

$php_page = APP_ROOT . '/src/pages/' . $page . '.php';

if (!file_exists($php_page)) {
    $php_page = APP_ROOT . '/src/pages/page-not-found.php';
}

include $php_page;