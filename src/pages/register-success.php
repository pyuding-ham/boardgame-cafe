<?php
declare(strict_types = 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$status = $_SESSION['_flash_status'] ?? null;
$nickname = $_SESSION['_flash_nickname'] ?? null;

if ($status) {
    unset($_SESSION['_flash_status']);
    unset($_SESSION['_flash_nickname']);
}

if ($status !== 'register_success') {
    header('Location: ' . DOC_ROOT . 'login');
    exit;
}

$data['nickname']  = $nickname;

echo $twig->render('register-success.html', $data);