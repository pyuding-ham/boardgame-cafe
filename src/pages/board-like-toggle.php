<?php
declare(strict_types = 1);

header('Content-Type: application/json');

// 1. 로그인 여부 검사
if (!$currentUserId) {
    echo json_encode([
        'status' => 'error',
        'message' => '로그인이 필요한 서비스입니다.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 2. 게시글 번호 빈 값 여부 검사
$input = json_decode(file_get_contents('php://input'), true);
$postId = $input['post_id'] ?? null;

if (!$postId) {
    echo json_encode([
        'status' => 'error',
        'message' => '존재하지 않거나 삭제된 게시글입니다.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 3. DB 로직 수행
try {
    // 1) 현재 좋아요 클릭 여부 검사
    $checkSql = "SELECT COUNT(*)
                 FROM post_like
                 WHERE post_id = :post_id
                   AND user_id = :user_id;";

    $stmtCheck = $cms->getDb()->runSql($checkSql, [
        'post_id' => $postId,
        'user_id' => $currentUserId,
    ]);
    
    $isAlreadyLiked = $stmtCheck->fetchColumn() > 0;

    // 2) 좋아요 클릭 시 좋아요 취소
    if ($isAlreadyLiked) {
        $deleteSql = "DELETE FROM post_like
                      WHERE post_id = :post_id
                        AND user_id = :user_id;";

        $cms->getDb()->runSql($deleteSql, [
            'post_id' => $postId,
            'user_id' => $currentUserId,
        ]);

        // 상태 값 변경
        $currentIsLiked = 0;
    }
    // 3) 좋아요 미클릭 시 좋아요 추가
    else {
        $insertSql = "INSERT INTO post_like (post_id, user_id, created_at)
                      VALUES (:post_id, :user_id, NOW());";

        $cms->getDb()->runSql($insertSql, [
            'post_id' => $postId,
            'user_id' => $currentUserId,
        ]);

        // 상태 값 변경
        $currentIsLiked = 1;
    }

    // 4) 최신 좋아요 수 저장
    $countSql = "SELECT COUNT(*)
                 FROM post_like
                 WHERE post_id = :post_id;";

    $stmtCount = $cms->getDb()->runSql($countSql, [
        'post_id' => $postId,
    ]);

    $totalLikeCount = (int)$stmtCount->fetchColumn();

    echo json_encode([
        'status' => 'success',
        'is_liked' => $currentIsLiked,
        'like_count' => $totalLikeCount,
    ]);

} catch (\PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => '존재하지 않거나 삭제된 게시글입니다.',
    ]);
}

exit;
