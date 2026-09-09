<?php
declare(strict_types = 1);

use BoardgameCafe\Controllers\CommentController;

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
$postId = $_POST['post_id'] ?? null;

if (!$postId) {
    echo json_encode([
        'status' => 'error',
        'message' => '존재하지 않거나 삭제된 게시글입니다.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $commentController = new CommentController($cms);
    $result = $commentController->insertComment($postId, $_POST['comment'], (int)$currentUserId);

    if ($result['success'] === true) {
        echo json_encode([
            'status' => 'success',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    echo json_encode([
        'status' => 'error',
        'message' => $result['errors']['comment'] ?? '댓글 작성에 실패했습니다.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

exit;
