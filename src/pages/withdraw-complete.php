<?php
declare(strict_types = 1);

// 로그인 사용자 차단
if ($currentUserId) {
    header('Location: ' . DOC_ROOT . 'login');
    exit;
}

// 회원 탈퇴에서 보낸 상태 저장
$status = $_SESSION['_flash_status'] ?? null;

// 새로 고침 시 메인으로 이동
if ($status === null) {
    header('Location: ' . DOC_ROOT);
    exit;
}

unset($_SESSION['_flash_status']);

echo $twig->render('withdraw-complete.html');
