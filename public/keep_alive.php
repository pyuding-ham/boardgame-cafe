<?php
require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', (string) SESSION_LIFETIME);
    ini_set('session.cookie_lifetime', (string) SESSION_LIFETIME);
    session_start();
}

// 로그인 세션이 있을 때만 쿠키 수명 늘려줌
if (isset($_SESSION['id']) && !empty($_SESSION['id'])) {
    setcookie(session_name(), session_id(), time() + SESSION_LIFETIME, '/');
}
exit;
