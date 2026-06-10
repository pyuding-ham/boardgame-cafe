<?php
declare(strict_types = 1);
header('Content-Type: application/json');

$email = trim($_GET['email'] ?? '');

if (empty($email)) {
    echo json_encode(['status' => 'empty', 'message' => '이메일을 입력해 주세요.']);
    exit;
}

$sql = "SELECT id FROM user WHERE email = :email;";
$stmt = $cms->getDb()->runSql($sql, ['email' => $email]);

if ($stmt && $stmt->fetch()) {
    echo json_encode(['status' => 'exists', 'message' => '이미 사용 중인 이메일입니다.']);
} else {
    echo json_encode(['status' => 'available', 'message' => '사용 가능한 이메일입니다.']);
}
exit;