<?php
declare(strict_types = 1);

use BoardgameCafe\Validate\Validate;

header('Content-Type: application/json');

$username = trim($_GET['username'] ?? '');

// 필수 입력 값 검사
if (empty($username)) {
    http_response_code(400);

    echo json_encode([
        'status' => 'empty',
        'message' => '아이디를 입력해 주세요.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 아이디 유효성 검사
if (!Validate::isUsername($username)) {
    http_response_code(400);

    echo json_encode([
        'status' => 'invalid',
        'message' => '아이디는 4~20자의 영문, 숫자, 언더바(_)만 가능합니다.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$sql = "SELECT id FROM user WHERE username = :username;";
$stmt = $cms->getDb()->runSql($sql, ['username' => $username]);

if ($stmt && $stmt->fetch()) {
    echo json_encode([
        'status' => 'exists',
        'message' => '이미 사용 중인 아이디입니다.'
    ]);
} else {
    echo json_encode([
        'status' => 'available',
        'message' => '사용 가능한 아이디입니다.'
    ]);
}
exit;