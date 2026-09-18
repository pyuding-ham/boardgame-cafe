<?php
// 메일문의에서 보낸 상태 저장
$status = $_SESSION['_flash_status'] ?? null;
if ($status) {
    unset($_SESSION['_flash_status']);
}

$user = isset($_SESSION['id']) ? $_SESSION : null;

$data = [
    'status' => $status,
    'user' => $user,
];

echo $twig->render('index.html', $data);
