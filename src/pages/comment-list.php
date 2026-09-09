<?php
declare(strict_types = 1);

header('Content-Type: application/json');

$commentService = $cms->getComment();

$comment = $commentService->getPostComment(
    (int)$postId,
    (int)$currentUserId
);

$commentListHtml = $twig->render('board-comment-list.html', [
    'comment' => $comment,
]);

echo json_encode([
    'commentListHtml' => $commentListHtml,
], JSON_UNESCAPED_UNICODE);

exit;
