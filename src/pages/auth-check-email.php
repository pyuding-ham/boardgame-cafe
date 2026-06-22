<?php
declare(strict_types = 1);

header('Content-Type: application/json');

$email = trim($_GET['email'] ?? '');
$sql = "SELECT id FROM user WHERE email = :email;";
$stmt = $cms->getDb()->runSql($sql, ['email' => $email]);

if ($stmt && $stmt->fetch()) {
    echo json_encode([
        'status' => 'exists',
        'message' => '이미 다른 회원이 사용 중인 이메일입니다.'
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'status' => 'available',
        'message' => '사용 가능한 이메일입니다.'
    ], JSON_UNESCAPED_UNICODE);
}

exit;