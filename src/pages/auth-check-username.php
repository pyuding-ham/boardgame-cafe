<?php
declare(strict_types = 1);

header('Content-Type: application/json');

$username = trim($_GET['username'] ?? '');
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