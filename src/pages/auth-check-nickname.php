<?php
declare(strict_types = 1);

header('Content-Type: application/json');

$nickname = trim($_GET['nickname'] ?? '');

// 필수 입력 값 검사
if (empty($nickname)) {
    http_response_code(400);

    echo json_encode([
        'status' => 'empty',
        'message' => '닉네임을 입력해 주세요.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 닉네임 유효성 검사
$length = mb_strlen($nickname);

if ($length < 2 || $length > 10) {
    http_response_code(400);

    echo json_encode([
        'status' => 'invalid',
        'message' => '닉네임은 2~10자 사이여야 합니다.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$sql = "SELECT id FROM user WHERE nickname = :nickname;";
$stmt = $cms->getDb()->runSql($sql, ['nickname' => $nickname]);

if ($stmt && $stmt->fetch()) {
    echo json_encode([
        'status' => 'exists',
        'message' => '이미 사용 중인 닉네임입니다.'
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'status' => 'available',
        'message' => '사용 가능한 닉네임입니다.'
    ], JSON_UNESCAPED_UNICODE);
}

exit;