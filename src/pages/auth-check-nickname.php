<?php
declare(strict_types = 1);

header('Content-Type: application/json');

$nickname = trim($_GET['nickname'] ?? '');
$sql = "SELECT id FROM user WHERE nickname = :nickname;";
$stmt = $cms->getDb()->runSql($sql, ['nickname' => $nickname]);

if ($stmt && $stmt->fetch()) {
    echo json_encode([
        'status' => 'exists',
        'message' => '이미 다른 회원이 사용 중인 닉네임입니다.'
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'status' => 'available',
        'message' => '사용 가능한 닉네임입니다.'
    ], JSON_UNESCAPED_UNICODE);
}

exit;