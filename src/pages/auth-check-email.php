<?php
declare(strict_types = 1);

use BoardgameCafe\Validate\Validate;

header('Content-Type: application/json');

$email = trim($_GET['email'] ?? '');

// 필수 입력 값 검사
if (empty($email)) {
    http_response_code(400);

    echo json_encode([
        'status' => 'empty',
        'message' => '이메일을 입력해 주세요.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 이메일 형식 유효성 검사
if (!Validate::isEmail($email)) {
    http_response_code(400);

    echo json_encode([
        'status' => 'invalid',
        'message' => '올바른 이메일 형식이 아닙니다.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$sql = "SELECT id FROM user WHERE email = :email;";
$stmt = $cms->getDb()->runSql($sql, ['email' => $email]);

if ($stmt && $stmt->fetch()) {
    echo json_encode([
        'status' => 'exists',
        'message' => '이미 사용 중인 이메일입니다.'
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'status' => 'available',
        'message' => '사용 가능한 이메일입니다.'
    ], JSON_UNESCAPED_UNICODE);
}

exit;