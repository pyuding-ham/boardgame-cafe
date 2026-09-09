<?php
declare(strict_types = 1);

namespace BoardgameCafe\Controllers;

use BoardgameCafe\Validate\Validate;

class CommentController
{
    private $cms;

    public function __construct($cms) {
        $this->cms = $cms;
    }
    
    /**
     * 댓글 작성
     */
    public function insertComment(string $post_id, string $comment, int $user_id): array
    {
        $comment = trim($comment);
        $errors  = [];
       
        // 3. 내용 필수 입력 값 검사
        if (empty($comment)) {
            $errors['comment'] = '댓글을 입력해 주세요.';
        }

        if (empty($errors['comment']) && !Validate::isText($comment, 1, 3000)) {
            $errors['comment'] = '댓글은 최대 3000자까지 입력할 수 있습니다.';
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'errors'  => $errors,
            ];
        }

        // DB 서비스 호출
        $comment_service = $this->cms->getComment();
        
        $comment_service->insertPostComment(
            $post_id,
            $comment,
            $user_id,
        );

        return [
            'success' => true,
        ];
    }
}
