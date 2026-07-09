<?php
// 1. 외부 웹 브라우저 접근 철저히 차단
if (php_sapi_name() !== 'cli') {
    header('HTTP/1.1 403 Forbidden');
    die('접근 권한이 없습니다.');
}

// 2. 프로젝트의 공통 파일 호출
require_once __DIR__ . '/../src/bootstrap.php'; 

// 3. $cms 객체로부터 Database.php 인스턴스를 꺼내옵니다.
$pdo = $cms->getDb(); 

try {
    // 4. 6개월 지난 로그인 로그 삭제
    $sql1 = "DELETE FROM user_login_log WHERE login_at < NOW() - INTERVAL 180 DAY";
    $pdo->runSql($sql1);
    
    // 5. 1년 지난 비밀번호 변경 로그 삭제
    $sql2 = "DELETE FROM user_password_change_log WHERE changed_at < NOW() - INTERVAL 365 DAY";
    $pdo->runSql($sql2);

    //6. 1년 지난 비밀번호 변경 로그 삭제
    $sql3 = "DELETE FROM user_profile_change_log WHERE changed_at < NOW() - INTERVAL 365 DAY";
    $pdo->runSql($sql3);
    
    echo "[" . date('Y-m-d H:i:s') . "] 오래된 로그 정리가 완료되었습니다.\n";
} catch (PDOException $e) {
    echo "[" . date('Y-m-d H:i:s') . "] 로그 정리 중 에러 발생: " . $e->getMessage() . "\n";
}