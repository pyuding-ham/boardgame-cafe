<?php
declare(strict_types = 1);

use BoardgameCafe\Controllers\BoardController;

header('Content-Type: application/json');

if (!$currentUserId) {
    echo json_encode([
        'status' => 'error',
        'message' => '로그인이 필요한 서비스입니다.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => '잘못된 요청입니다.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($_FILES['image']['name'])) {
    echo json_encode([
        'status' => 'error',
        'message' => '이미지 파일을 선택해 주세요.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$boardController = new BoardController($cms);
$maxImgSize = 5 * 1024 * 1024;
$imageErrors = $boardController->validateImageUpload($_FILES['image'], $maxImgSize);

if (!empty($imageErrors)) {
    echo json_encode([
        'status' => 'error',
        'message' => $imageErrors[0],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$datePath = date('Y/m/d') . '/';
$uploadDir = APP_ROOT . '/public/uploads/editor/' . $datePath;

if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
    echo json_encode([
        'status' => 'error',
        'message' => '업로드 폴더를 생성할 수 없습니다.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
$newName = 'editor_' . uniqid('', true) . '.' . $ext;
$filePath = $uploadDir . $newName;

if (!move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
    echo json_encode([
        'status' => 'error',
        'message' => '이미지 업로드에 실패했습니다.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'status' => 'success',
    'url' => '/uploads/editor/' . $datePath . $newName,
], JSON_UNESCAPED_UNICODE);
exit;
